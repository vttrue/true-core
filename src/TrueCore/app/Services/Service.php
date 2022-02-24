<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 14.04.2019
 * Time: 20:39
 */

namespace TrueCore\App\Services;

use \TrueCore\App\Services\Interfaces\{
    Factory as FactoryInterface,
    Observer as ObserverInterface,
    Repository as RepositoryInterface,
    Service as ServiceInterface
};

use Illuminate\Support\Collection;
use \TrueCore\App\Services\Traits\Exceptions\{
    ModelDeleteException,
    ModelSaveException,
    ModelSortException
};

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

use \ReflectionClass;

/**
 * Class Service
 *
 * @property Dispatcher $dispatcher
 * @property FactoryInterface|null $factory
 * @property RepositoryInterface|null $repository
 * @property ObserverInterface|null $observer
 *
 * @property array $data
 *
 * @property array $serviceEvents
 * @property array $eventList
 *
 * @package TrueCore\App\Services
 */
abstract class Service implements ServiceInterface
{
    protected array $switchableFields = [];

    protected array $serviceEvents = [];

    protected Dispatcher $dispatcher;
    protected ?FactoryInterface $factory;
    protected ?RepositoryInterface $repository;
    protected ?ObserverInterface $observer;

    protected array $data = [];

    protected array $mapDetail = [];

    protected array $eventList = [
        'adding', 'justAdded', 'added', 'justFailedAdding', 'additionFailed',
        'saving', 'saved', 'savingFailed',
        'updating', 'justUpdated', 'updated', 'justFailedUpdating', 'updatingFailed',
        'copying', 'copied', 'copyingFailed',
        'deleting', 'deleted', 'deletionFailed',
        'postProcessing', 'postProcessed'
    ];

    /**
     * @var bool
     */
    protected bool $isPushJob = true;

    /**
     * Service constructor.
     *
     * @param RepositoryInterface|null $repository
     * @param FactoryInterface|null $factory
     * @param ObserverInterface|null $observer
     *
     * @throws \Exception
     */
    public function __construct(RepositoryInterface $repository = null, FactoryInterface $factory = null, ObserverInterface $observer = null)
    {
        $this->dispatcher   = new Dispatcher();
        $this->factory      = $factory;
        $this->observer     = $observer;
        $this->repository   = $repository;

        if($this->factory === null || $this->observer === null || $this->repository === null) {

            try {

                $reflection     = new \ReflectionClass(static::class);
                $constructor    = $reflection->getConstructor();

                if($this->repository === null) {
                    $repositoryConcrete     = $constructor->getParameters()[0]->getType()->getName();
                    $repositoryReflection   = new \ReflectionClass($repositoryConcrete);
                    $repositoryConstructor  = $repositoryReflection->getConstructor();

                    $repositoryDependency   = $repositoryConstructor->getParameters()[0]->getType()->getName();

                    $this->repository       = $repositoryReflection->newInstance(new $repositoryDependency);
                }

                if($this->factory === null) {
                    $factoryConcrete    = $constructor->getParameters()[1]->getType()->getName();
                    $this->factory      = new $factoryConcrete;
                }

                if($this->observer === null) {
                    $observerConcrete    = $constructor->getParameters()[2]->getType()->getName();
                    $this->observer      = new $observerConcrete;
                }

            } catch (\ReflectionException $e) {
                throw new \Exception('Unable to instantiate ' . get_class($this) . ': ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
            }

        }

        $this->addEventObserver($this->observer, $this->data);

        foreach($this->eventList AS $eventName) {
            if(method_exists($this, $eventName)) {
                $this->registerServiceEvent($eventName, $this->{$eventName}());
            }
        }
    }

    /**
     * @return array
     */
    protected function getEventList() : array
    {
        return $this->eventList;
    }

    /**
     * @param ObserverInterface $observer
     * @param array $data
     *
     * @return ServiceInterface
     */
    public function addEventObserver(ObserverInterface $observer, array $data = []) : ServiceInterface
    {
        $container = new \Illuminate\Container\Container;
        $container->bind(get_class($observer), function() use($container, $observer, $data) {
            return new $observer($data);
        });

        $serviceEventDispatcher = new Dispatcher($container);
        $this->dispatcher = $serviceEventDispatcher;

        $observerClassName = get_class($observer);

        $events = $this->getEventList();

        foreach($events AS $event) {
            if (method_exists($observer, $event)) {
                $this->registerServiceEvent($event, $observerClassName . '@' . $event);
            }
        }

        return $this;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository() : RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * @return FactoryInterface
     */
    protected function getFactory() : FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function setData(array $data = [])
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $event
     * @param callable $callback
     *
     * @return void
     */
    protected function registerServiceEvent(string $event, $callback) : void
    {
        if(!array_key_exists($event, $this->serviceEvents)) {
            $this->serviceEvents[$event] = [];
        }

        if($callback instanceof \Closure) {
            $this->serviceEvents[$event][] = $callback;
        } else if(is_string($callback)) {
            $this->serviceEvents[$event][$callback] = $callback;
        }

        $this->dispatcher->listen('service.' . $event . ': ' . static::class, $callback);
    }

    /**
     * @param array $data
     * @return ServiceInterface|null
     * @throws ModelSaveException
     */
    public static function add(array $data)
    {
        try {

            $instance = new static;

            $data = $instance->normalizeData($data);

            $instance->addEventObserver($instance->observer, $data);

            $instance->dispatcher->dispatch('service.saving: ' . static::class, $instance);
            $instance->dispatcher->dispatch('service.adding: ' . static::class, $instance);

            if($instance->getRepository()->add($data, function() use ($instance) {
                $instance->dispatcher->dispatch('service.justAdded: ' . static::class, $instance);
            }, function() use($instance) {
                $instance->dispatcher->dispatch('service.justFailedAdding: ' . static::class, $instance);
            })) {

                $instance->dispatcher->dispatch('service.saved: ' . static::class, $instance);
                $instance->dispatcher->dispatch('service.added: ' . static::class, $instance);

                $instance->postProcessor($data);

                return $instance;

            } else {
                $instance->dispatcher->dispatch('service.savingFailed: ' . static::class, $instance);
                $instance->dispatcher->dispatch('service.additionFailed: ' . static::class, $instance);
            }

            return null;

        } catch (\Throwable $e) {
            throw new ModelSaveException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param array $data
     * @return bool
     * @throws ModelSaveException
     */
    public function edit(array $data) : bool
    {
        try {

            $data = $this->normalizeData($data);

            $this->addEventObserver($this->observer, $data);

            $this->dispatcher->dispatch('service.updating: ' . static::class, $this);
            $this->dispatcher->dispatch('service.saving: ' . static::class, $this);

            $isUpdated = $this->getRepository()->update($data, function() {
                $this->dispatcher->dispatch('service.justUpdated: ' . static::class, $this);
            }, function() {
                $this->dispatcher->dispatch('service.justFailedUpdating: ' . static::class, $this);
            });

            if($isUpdated) {

                if ($this->getRepository()->isSaving() === false) {
                    $this->dispatcher->dispatch('service.updated: ' . static::class, $this);
                    $this->dispatcher->dispatch('service.saved: ' . static::class, $this);

                    $this->postProcessor($data);
                }

            } else {
                $this->dispatcher->dispatch('service.savingFailed: ' . static::class, $this);
                $this->dispatcher->dispatch('service.updatingFailed: ' . static::class, $this);
            }
            //dump($this->dispatcher, $isUpdated);

            if ($isUpdated) {
                $this->mapDetail = [];
            }

            return $isUpdated;

        } catch (\Throwable $e) {
            throw new ModelSaveException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param array|null $relations
     *
     * @return ServiceInterface
     * @throws \Exception
     */
    public function copy(?array $relations = null): ServiceInterface
    {
        return new static($this->getRepository()->copy($relations));
    }

    /**
     * @param bool $soft
     * @return bool
     * @throws ModelDeleteException
     */
    public function delete(bool $soft = false): bool
    {
        try {

            $this->dispatcher->dispatch('service.deleting: ' . static::class, $this);

            $isDeleted = $this->getRepository()->delete();

            if ($isDeleted === true) {
                $this->dispatcher->dispatch('service.deleted: ' . static::class, $this);
            } else {
                $this->dispatcher->dispatch('service.deletionFailed: ' . static::class, $this);
            }

            return $isDeleted;

        } catch (\Throwable $e) {

            $this->dispatcher->dispatch('service.deletionFailed: ' . static::class, $this);

            throw new ModelDeleteException($e->getMessage());
        }
    }

    /**
     * @param string $field
     * @return bool
     * @throws \Exception
     */
    public function switch(string $field): bool
    {
        $this->dispatcher->dispatch('service.updating: ' . static::class, $this);
        $this->dispatcher->dispatch('service.saving: ' . static::class, $this);
        $this->dispatcher->dispatch('service.switching: ' . static::class, $this);

        $isSwitched = $this->getRepository()->switch(Str::snake($field));

        if($isSwitched) {
            $this->dispatcher->dispatch('service.updated: ' . static::class, $this);
            $this->dispatcher->dispatch('service.saved: ' . static::class, $this);
            $this->dispatcher->dispatch('service.switched: ' . static::class, $this);
        } else {
            $this->dispatcher->dispatch('service.updatingFailed: ' . static::class, $this);
            $this->dispatcher->dispatch('service.savingFailed: ' . static::class, $this);
            $this->dispatcher->dispatch('service.switchingFailed: ' . static::class, $this);
        }

        return $isSwitched;
    }

    /**
     * @param array $itemIdList
     * @throws ModelSortException
     */
    public static function sortItems(array $itemIdList = []): void
    {
        try {

            $repository = (new static)->getRepository();

            foreach ($itemIdList as $sortOrder => $id) {

                if (!is_string($id) && !is_numeric($id)) {
                    continue;
                }

                $itemRepository = $repository->getOne(['id' => $id]);

                if($itemRepository !== null) {
                    $itemRepository->update(['sort_order' => $sortOrder]);
                }
            }

        } catch (\Throwable $e) {

            throw new ModelSortException($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $outData = $data;

        return $outData;
    }

    /**
     * @param $data
     */
    protected function postProcessor(array $data): void
    {
        // do whatever it takes to finish with this entity record, no matter what!!!
    }

    /**
     * @param array $options
     * @param array $columns
     * @return ServiceInterface[]
     * @throws \Exception
     */
    public static function getAll(array $options = [], array $columns = ['*'])
    {
        $repoList = (new static)->getRepository()->getAll($options, $columns);

        return array_map(fn (RepositoryInterface $repo) : ServiceInterface => new static($repo), $repoList);
    }

    /**
     * @param array $options
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public static function getAllPaginator(array $options = [], int $perPage = 15)
    {
        $paginator = (new static)->getRepository()->getAllPaginator($options, $perPage);

        return $paginator->setCollection(new Collection(array_map(fn (RepositoryInterface $repository) : ServiceInterface => new static($repository), $paginator->items())));
    }

    /**
     * @param array $options
     * @param int $offset
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public static function getAllDynamicPaginator(array $options = [], $offset = 0, $perPage = 15)
    {
        $paginator = (new static)->getRepository()->getAllDynamicPaginator($options, $offset, $perPage);

        return $paginator->setCollection(new Collection(array_map(fn (RepositoryInterface $repository) : ServiceInterface => new static($repository), $paginator->items())));
    }

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return ServiceInterface|null
     *
     * @throws \Exception
     */
    public static function getOne(array $conditions = [], array $options = [])
    {
        $repo = (new static)->getRepository()->getOne($conditions, $options);

        return (($repo !== null) ? new static($repo) : null);
    }

    /**
     * @param array $conditions
     * @param array $options
     *
     * @return ServiceInterface[]|null
     *
     * @throws \Exception
     */
    public static function getRandom(array $conditions = [], array $options = [])
    {
        $repoList = (new static)->getRepository()->getRandom($conditions, $options);

        return array_map(fn (RepositoryInterface $repo) : ServiceInterface => new static($repo), $repoList);
    }

    /**
     * @param array $conditions
     * @return int
     * @throws \Exception
     */
    public static function count(array $conditions = []): int
    {
        return (new static)->getRepository()->count($conditions);
    }

    /**
     * @param array $items
     * @param array|null $fields
     *
     * @return Structure[]
     *
     * @throws \Exception
     */
    public static function mapList(array $items, ?array $fields = null) : array
    {
        $serviceClass   = static::class;
        $repoClass      = (new ReflectionClass($serviceClass))->getConstructor()->getParameters()[0]->getClass()->getName();

        $result = [];

        foreach ($items as $item) {

            if ($item instanceof $serviceClass) {
                $result[] = $item->mapDetail($fields);
            } elseif ($item instanceof $repoClass) {
                $result[] = (new $serviceClass($item))->mapDetail($fields);
            } else {
                throw new \Exception('Unknown element type. Supported types: ' . $serviceClass . ', ' . $repoClass);
            }

        }

        return $result;
    }

    /**
     * @param array|null $fields
     *
     * @return Structure
     *
     * @throws \Exception
     */
    public function mapDetail(?array $fields = null) : Structure
    {
        $structureClass = get_class($this->getStructureInstance());

        if (is_array($fields) && count($fields) > 0) {
            ksort($fields);
        }

        $mapCacheKey = md5(serialize($fields));

        $this->mapDetail[$mapCacheKey] = ((array_key_exists($mapCacheKey, $this->mapDetail) && $this->mapDetail[$mapCacheKey] instanceof $structureClass) ? $this->mapDetail[$mapCacheKey] : $this->getStructureInstance()->getDetail($fields));

        return $this->mapDetail[$mapCacheKey];
    }

    /**
     *
     */
    public function disablePushJob(): void
    {
        $this->isPushJob = false;
    }

    /**
     *
     */
    public function enablePushJob(): void
    {
        $this->isPushJob = true;
    }

    /**
     * @return bool
     */
    public function isPushJob(): bool
    {
        return $this->isPushJob;
    }

    /**
     * @return Structure
     */
    abstract protected function getStructureInstance() : Structure;
}

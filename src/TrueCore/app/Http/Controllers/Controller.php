<?php

namespace TrueCore\App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use \TrueCore\App\Models\System\Entity as mEntity;

/**
 * Class Controller
 *
 * @property array $data
 * @property array $relations
 *
 * @package TrueCore\App\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected array $data      = [];
    protected array $relations = [];

    /**
     * @param int $code
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    protected function response($code = 200)
    {
        if (isset(request()->debug) || isset(request()->dd)) {
            return view('admin.debug', [
                'data' => json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } else {
            return response($this->data, $code)->withHeaders(['Date' => now()->setTimezone('+00:00')->format('D, d M Y H:i:s \G\M\T')]);
        }
    }

    /**
     * @return array
     */
    protected function getAvailableEntities()
    {
        $entities = [];

        // @TODO: deal with this semi-hardcoded stuff, think of a better approach of restricting API access to certain entities | deprecator @ 2019-12-24
        $entityList = array_values(array_filter(mEntity::where('status', '=', true)->get()->map(function(mEntity $entity) {
            return str_replace(['App\Models\\', 'TrueCore\App\Models\\','TrueCore\App\Services\\', 'App\Services\\'], '', $entity->namespace);
        })->toArray(), function($v) {
            return !in_array($v, [
                'System\\Setting',
                'System\\User',
                'System\\Role',
            ]);
        }));

        foreach($entityList AS $entity) {
            /** @TODO Почему length именно 2??? */
//            $namespace = array_slice(explode('\\', $entity), 0, 2);
            $entitySegments = explode('\\', $entity);
            $namespace = $entitySegments;
//            dump(explode('\\', $entity));
            if(count($namespace) > 1) {
//dump(explode('\\', $entity),$namespace,count($namespace)-1);
                /** @TODO А если кол-во === 3 и больше (вынесено в namespace)? Решить вопрос, пока костыль. Incarnator | 2020-02-06 */
                $entities[$namespace[0]][] = $namespace[count($namespace)-1];
            }
        }

        return $entities;
    }
}

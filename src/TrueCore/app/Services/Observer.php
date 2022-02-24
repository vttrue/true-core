<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 2:51
 */

namespace TrueCore\App\Services;

use \TrueCore\App\Services\Interfaces\{
    Observer as ObserverInterface,
    Service as ServiceInterface,
    Repository as RepositoryInterface
};

/**
 * Class Observer
 *
 * @package TrueCore\App\Services
 */
abstract class Observer implements ObserverInterface
{
    protected Repository $repository;

    /**
     * @return RepositoryInterface
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function adding(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     */
    public function justAdded($service)
    {
        //
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function added(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function additionFailed(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function copying(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function copied(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function copyingFailed(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function updating(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     */
    public function justUpdated($service)
    {
        //
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function updated(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function updatingFailed(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function saving(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function saved(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function savingFailed(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function deleting(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function deleted(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function deletionFailed(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function postProcessing(ServiceInterface $service)
    {
        return true;
    }

    /**
     * @param ServiceInterface $service
     *
     * @return mixed
     */
    public function postProcessed(ServiceInterface $service)
    {
        return true;
    }
}

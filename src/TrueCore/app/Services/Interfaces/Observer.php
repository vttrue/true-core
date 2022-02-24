<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.09.2019
 * Time: 0:48
 */

namespace TrueCore\App\Services\Interfaces;

/**
 * Interface Observer
 *
 * @package TrueCore\App\Services\Interfaces
 */
interface Observer
{
    /**
     * @param Service $service
     */
    public function adding(Service $service);

    /**
     * @param Service $service
     */
    public function justAdded($service);

    /**
     * @param Service $service
     */
    public function added(Service $service);

    /**
     * @param Service $service
     */
    public function additionFailed(Service $service);

    /**
     * @param Service $service
     */
    public function copying(Service $service);

    /**
     * @param Service $service
     */
    public function copied(Service $service);

    /**
     * @param Service $service
     */
    public function copyingFailed(Service $service);

    /**
     * @param Service $service
     */
    public function updating(Service $service);

    /**
     * @param Service $service
     */
    public function justUpdated($service);

    /**
     * @param Service $service
     */
    public function updated(Service $service);

    /**
     * @param Service $service
     */
    public function updatingFailed(Service $service);

    /**
     * @param Service $service
     */
    public function saving(Service $service);

    /**
     * @param Service $service
     */
    public function saved(Service $service);

    /**
     * @param Service $service
     */
    public function savingFailed(Service $service);

    /**
     * @param Service $service
     */
    public function deleting(Service $service);

    /**
     * @param Service $service
     */
    public function deleted(Service $service);

    /**
     * @param Service $service
     */
    public function deletionFailed(Service $service);

    /**
     * @param Service $service
     */
    public function postProcessing(Service $service);

    /**
     * @param Service $service
     */
    public function postProcessed(Service $service);
}
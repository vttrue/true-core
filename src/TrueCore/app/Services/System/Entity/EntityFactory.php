<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 22.10.2019
 * Time: 17:45
 */

namespace TrueCore\App\Services\System\Entity;

use \TrueCore\App\Services\Factory;
use \TrueCore\App\Models\System\Entity as EntityModel;

/**
 * Class UserFactory
 *
 * @package TrueCore\App\Services\System
 */
class EntityFactory extends Factory
{
    /**
     * @return mixed|Entity
     * @throws \Exception
     */
    public function create()
    {
        return new Entity(new EntityRepository(new EntityModel), $this, new EntityObserver());
    }
}

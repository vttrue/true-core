<?php

namespace TrueCore\Tests\Feature\Pub;

/**
 * Interface PubApiTestCaseInterface
 *
 * @package TrueCore\Tests\Feature\Pub
 */
interface PubApiTestCaseInterface
{
    /**
     * @return void
     */
    public function testList();

    /**
     * @return void
     */
    public function testItem();
}

<?php

namespace TrueCore\Tests\Feature\Admin\System;

use TrueCore\App\Models\System\User;
use TrueCore\Tests\{
    TestCase,
    TestResponse
};

class SettingTest extends TestCase
{
    private $withAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->withAuth = $this->actingAs(User::whereRoleId(1)->first(), 'api');
    }

    /**
     * Bundle list test.
     *
     * @return void
     */
    public function testItems()
    {
        $this->markTestSkipped();
        $structure = [];

        $response = $this->withAuth->get(route('admin.system.settings'));

        $response->assertStatus(200);
        (new TestResponse($response->baseResponse))->assertJsonStructure($structure);
    }
}

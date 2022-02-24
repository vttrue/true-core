<?php

namespace TrueCore\Tests\Feature\Admin;

use TrueCore\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLoginSuccess()
    {
        $response = $this->post(route('login'), [
            'email'    => '123@123.com',
            'password' => 'password',
        ]);

        $response
            ->assertJsonStructure(['data' => ['id', 'role', 'name', 'phone', 'email', 'status'], 'meta' => ['token']])
            ->assertStatus(200);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLoginFail()
    {
        $response = $this->post(route('login'), [
            'email'    => '1231@123.com',
            'password' => '123',
        ]);

        $response->assertStatus(401);
    }
}

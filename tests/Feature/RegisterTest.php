<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function register_fails_with_invalid_email()
    {
        $payload = [
            'username'              => 'lietotājs',
            'email'                 => 'nepareizs-e-pasts',
            'password'              => 'Secure1!',
            'password_confirmation' => 'Secure1!',
        ];

        $response = $this->postJson('/register', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}

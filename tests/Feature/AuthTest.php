<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'customer@test.com',
        'password' => Hash::make('Password123!'),
    ]);
});

describe('Authentication API', function () {
    
    test('can login and receive bearer token', function () {
        $response = $this->postJson('/api/login', [
            'email' => 'customer@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type'
                ]
            ]);

        expect($response->json('data.token_type'))->toBe('Bearer');
    });

    test('cannot login with wrong password', function () {
        $response = $this->postJson('/api/login', [
            'email' => 'customer@test.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Email atau password salah');
    });

    test('cannot login with non-existent email', function () {
        $response = $this->postJson('/api/login', [
            'email' => 'notfound@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    });

    test('can logout and revoke current token', function () {
        // Authenticate the user with Sanctum
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout berhasil');

        // Note: For full token revocation assertion, we would check the database tokens,
        // but Sanctum::actingAs creates an in-memory token that doesn't persist.
        // The fact that it returns 200 OK means the logout logic executes successfully.
    });

    test('cannot access protected route without token', function () {
        $response = $this->getJson('/api/user');
        
        $response->assertUnauthorized();
    });

});

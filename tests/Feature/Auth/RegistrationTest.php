<?php

use Tests\TestCase;

test('registration screen can be rendered', function () {
    /** @var TestCase $this */
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    /** @var TestCase $this */
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

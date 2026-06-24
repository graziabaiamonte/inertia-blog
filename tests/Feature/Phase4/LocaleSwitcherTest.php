<?php

use App\Models\User;

test('POST /locale stores locale in session', function () {
    $response = $this->post(route('locale.update'), ['locale' => 'it']);

    $response->assertRedirect();
    $this->assertEquals('it', session('locale'));
});

test('switching back to en updates session', function () {
    session(['locale' => 'it']);

    $response = $this->post(route('locale.update'), ['locale' => 'en']);

    $response->assertRedirect();
    $this->assertEquals('en', session('locale'));
});

test('invalid locale is rejected with validation error', function () {
    $response = $this->post(route('locale.update'), ['locale' => 'de']);

    $response->assertSessionHasErrors(['locale']);
});

test('locale prop in Inertia shared data reflects session locale', function () {
    session(['locale' => 'it']);

    $this->get('/')
        ->assertInertia(fn ($page) => $page->where('locale', 'it'));
});

test('locale route accessible by guests', function () {
    $response = $this->post(route('locale.update'), ['locale' => 'it']);

    $response->assertRedirect();
});

test('locale route accessible by authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('locale.update'), ['locale' => 'it']);

    $response->assertRedirect();
    $this->assertEquals('it', session('locale'));
});

<?php
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

test('login page is accessible', fn() => $this->get('/login')->assertStatus(200));

test('coach can login', function () {
    $coach = User::factory()->coach()->create(['email'=>'coach@test.com','password'=>bcrypt('password')]);
    $this->post('/login',['email'=>'coach@test.com','password'=>'password'])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($coach);
});

test('login fails with wrong password', function () {
    User::factory()->coach()->create(['email'=>'coach@test.com']);
    $this->post('/login',['email'=>'coach@test.com','password'=>'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('unauthenticated user redirected to login', fn() => $this->get('/dashboard')->assertRedirect('/login'));

test('coach cannot access admin reports', function () {
    $this->actingAs(User::factory()->coach()->create())->get('/reports')->assertForbidden();
});

test('admin can access reports', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/reports')->assertOk();
});

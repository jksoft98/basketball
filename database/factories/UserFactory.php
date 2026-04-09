<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory {
    public function definition(): array {
        return ['name'=>fake()->name(),'email'=>fake()->unique()->safeEmail(),'password'=>Hash::make('password'),'role'=>'coach'];
    }
    public function admin(): static { return $this->state(['role'=>'admin']); }
    public function coach(): static { return $this->state(['role'=>'coach']); }
}

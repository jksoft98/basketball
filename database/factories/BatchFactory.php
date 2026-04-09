<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory {
    public function definition(): array {
        return ['coach_id'=>User::factory()->coach(),'name'=>'Under-'.fake()->numberBetween(10,18).' Squad '.fake()->randomLetter(),'description'=>fake()->sentence(),'skill_level'=>fake()->randomElement(['beginner','intermediate','advanced']),'is_active'=>true];
    }
}

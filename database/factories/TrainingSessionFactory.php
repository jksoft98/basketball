<?php
namespace Database\Factories;
use App\Models\Batch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingSessionFactory extends Factory {
    public function definition(): array {
        return ['batch_id'=>Batch::factory(),'created_by'=>User::factory()->coach(),'session_date'=>fake()->dateTimeBetween('-30 days','now'),'session_time'=>'16:00','session_type'=>fake()->randomElement(['training','match','fitness','trial']),'notes'=>fake()->optional()->sentence()];
    }
    public function today(): static { return $this->state(['session_date'=>now()->toDateString()]); }
}

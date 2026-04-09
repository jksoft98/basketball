<?php
namespace Database\Factories;
use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory {
    public function definition(): array {
        return ['batch_id'=>Batch::factory(),'full_name'=>fake()->name(),'age'=>fake()->numberBetween(10,18),'parent_name'=>fake()->name(),'parent_contact'=>fake()->phoneNumber(),'photo_path'=>'students/placeholder.jpg','jersey_number'=>fake()->numberBetween(1,99),'position'=>fake()->randomElement(['guard','forward','centre']),'skill_level'=>'intermediate','injury_status'=>'fit','is_active'=>true];
    }
    public function injured(): static { return $this->state(['injury_status'=>'injured']); }
}

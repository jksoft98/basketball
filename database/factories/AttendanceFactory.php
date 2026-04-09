<?php
namespace Database\Factories;
use App\Models\Student;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory {
    public function definition(): array {
        return ['session_id'=>TrainingSession::factory(),'student_id'=>Student::factory(),'status'=>fake()->randomElement(['present','absent','late']),'marked_at'=>now()];
    }
}

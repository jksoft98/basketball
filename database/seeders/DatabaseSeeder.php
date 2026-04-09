<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name'     => 'Academy Admin',
            'email'    => 'admin@academy.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // Coach
        $coach = User::create([
            'name'     => 'Coach Mike',
            'email'    => 'coach@academy.com',
            'password' => Hash::make('password'),
            'role'     => 'coach',
        ]);

        // Batches
        $batchA = Batch::create([
            'coach_id'    => $coach->id,
            'name'        => 'Under-16 Squad A',
            'description' => 'Main senior development squad',
            'skill_level' => 'intermediate',
        ]);

        $batchB = Batch::create([
            'coach_id'    => $coach->id,
            'name'        => 'Under-14 Squad B',
            'description' => 'Junior development squad',
            'skill_level' => 'beginner',
        ]);

        $batchElite = Batch::create([
            'coach_id'    => $coach->id,
            'name'        => 'Elite Squad',
            'description' => 'Top performers',
            'skill_level' => 'advanced',
        ]);

        // Students for Squad A
        $studentsA = [
            ['James K.', 15, 7, 'guard'],
            ['Liam T.', 16, 11, 'forward'],
            ['Noah A.', 15, 3, 'centre'],
            ['Oliver B.', 14, 5, 'guard'],
            ['Ethan M.', 16, 9, 'forward'],
            ['Aiden R.', 15, 14, 'guard'],
            ['Lucas P.', 16, 2, 'centre'],
            ['Mason S.', 14, 17, 'forward'],
            ['Logan D.', 15, 8, 'guard'],
            ['Elijah W.', 16, 4, 'forward'],
        ];

        foreach ($studentsA as [$name, $age, $jersey, $position]) {
            Student::create([
                'batch_id'      => $batchA->id,
                'full_name'     => $name,
                'age'           => $age,
                'jersey_number' => $jersey,
                'position'      => $position,
                'skill_level'   => 'intermediate',
                'photo_path'    => 'students/placeholder.jpg',
                'parent_name'   => 'Parent of '.$name,
                'parent_contact'=> '+254 700 000 00'.rand(10, 99),
            ]);
        }

        // Students for Squad B
        $studentsB = [
            ['Aaron K.', 13, 6, 'guard'],
            ['Blake T.', 14, 10, 'forward'],
            ['Carter N.', 13, 1, 'centre'],
            ['Dylan O.', 12, 12, 'guard'],
            ['Evan M.', 14, 15, 'forward'],
            ['Felix A.', 13, 18, 'centre'],
        ];

        foreach ($studentsB as [$name, $age, $jersey, $position]) {
            Student::create([
                'batch_id'      => $batchB->id,
                'full_name'     => $name,
                'age'           => $age,
                'jersey_number' => $jersey,
                'position'      => $position,
                'skill_level'   => 'beginner',
                'photo_path'    => 'students/placeholder.jpg',
            ]);
        }

        // Create some sample sessions with attendance
        $session1 = TrainingSession::create([
            'batch_id'     => $batchA->id,
            'created_by'   => $coach->id,
            'session_date' => now()->subDays(7),
            'session_time' => '16:00',
            'session_type' => 'training',
        ]);

        $session2 = TrainingSession::create([
            'batch_id'     => $batchA->id,
            'created_by'   => $coach->id,
            'session_date' => now()->subDays(3),
            'session_time' => '16:00',
            'session_type' => 'match',
        ]);

        // Today's session
        TrainingSession::create([
            'batch_id'     => $batchA->id,
            'created_by'   => $coach->id,
            'session_date' => now(),
            'session_time' => '16:00',
            'session_type' => 'training',
        ]);

        // Mark attendance for past sessions
        $studentsInA = Student::where('batch_id', $batchA->id)->get();

        foreach ($studentsInA as $i => $student) {
            // Session 1 attendance
            Attendance::create([
                'session_id' => $session1->id,
                'student_id' => $student->id,
                'status'     => $i < 8 ? 'present' : 'absent',
                'marked_at'  => now()->subDays(7),
            ]);

            // Session 2 attendance
            Attendance::create([
                'session_id' => $session2->id,
                'student_id' => $student->id,
                'status'     => $i < 2 ? 'absent' : ($i < 4 ? 'late' : 'present'),
                'marked_at'  => now()->subDays(3),
            ]);
        }
    }
}

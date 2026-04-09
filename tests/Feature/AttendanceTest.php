<?php
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->coach   = User::factory()->coach()->create();
    $this->batch   = Batch::factory()->create(['coach_id'=>$this->coach->id]);
    $this->session = TrainingSession::factory()->create(['batch_id'=>$this->batch->id,'created_by'=>$this->coach->id]);
    $this->students = Student::factory()->count(5)->create(['batch_id'=>$this->batch->id]);
});

test('coach can view attendance grid', function () {
    $this->actingAs($this->coach)->get("/sessions/{$this->session->id}/attendance")
        ->assertOk()->assertViewIs('attendance.index');
});

test('attendance grid shows all active students', function () {
    $this->actingAs($this->coach)->get("/sessions/{$this->session->id}/attendance")
        ->assertViewHas('totalStudents', 5);
});

test('coach can save attendance', function () {
    $payload = ['attendance' => $this->students->map(fn($s) => ['id'=>$s->id,'status'=>'present','note'=>''])->toArray()];
    $this->actingAs($this->coach)
        ->postJson("/sessions/{$this->session->id}/attendance", $payload)
        ->assertOk()->assertJsonFragment(['success'=>true]);
    $this->assertDatabaseCount('attendance', 5);
});

test('saving twice updates existing records', function () {
    $student = $this->students->first();
    $url = "/sessions/{$this->session->id}/attendance";
    $this->actingAs($this->coach)->postJson($url, ['attendance'=>[['id'=>$student->id,'status'=>'present','note'=>'']]]);
    $this->actingAs($this->coach)->postJson($url, ['attendance'=>[['id'=>$student->id,'status'=>'absent','note'=>'']]]);
    $this->assertDatabaseCount('attendance', 1);
    $this->assertDatabaseHas('attendance', ['student_id'=>$student->id,'status'=>'absent']);
});

test('mark all present works', function () {
    $this->actingAs($this->coach)
        ->patchJson("/sessions/{$this->session->id}/attendance/mark-all", ['status'=>'present'])
        ->assertOk()->assertJsonFragment(['success'=>true]);
    $this->assertDatabaseCount('attendance', 5);
    Attendance::all()->each(fn($a) => expect($a->status)->toBe('present'));
});

test('invalid status rejected', function () {
    $this->actingAs($this->coach)
        ->postJson("/sessions/{$this->session->id}/attendance", ['attendance'=>[['id'=>$this->students->first()->id,'status'=>'INVALID','note'=>'']]])
        ->assertUnprocessable();
});

test('coach cannot access another coach session', function () {
    $other = User::factory()->coach()->create();
    $otherBatch = Batch::factory()->create(['coach_id'=>$other->id]);
    $otherSession = TrainingSession::factory()->create(['batch_id'=>$otherBatch->id,'created_by'=>$other->id]);
    $this->actingAs($this->coach)->get("/sessions/{$otherSession->id}/attendance")->assertForbidden();
});

test('attendance percentage calculates correctly', function () {
    $student = $this->students->first();
    $statuses = ['present','present','present','late','absent'];
    collect($statuses)->each(function($status) use ($student) {
        $s = TrainingSession::factory()->create(['batch_id'=>$this->batch->id,'created_by'=>$this->coach->id]);
        Attendance::create(['session_id'=>$s->id,'student_id'=>$student->id,'status'=>$status,'marked_at'=>now()]);
    });
    expect($student->fresh()->attendancePercentage())->toBe(80.0);
    expect($student->fresh()->isAtRisk())->toBeFalse();
});

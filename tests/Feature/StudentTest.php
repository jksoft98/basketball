<?php
use App\Models\Batch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->coach = User::factory()->coach()->create();
    $this->batch = Batch::factory()->create(['coach_id'=>$this->coach->id]);
});

test('coach can view student listing', function () {
    Student::factory()->create(['batch_id'=>$this->batch->id]);
    $this->actingAs($this->coach)->get('/students')->assertOk()->assertViewIs('students.index');
});

test('student creation fails without photo', function () {
    $this->actingAs($this->coach)->post('/students', [
        'batch_id'=>$this->batch->id,'full_name'=>'Test Student','skill_level'=>'intermediate','injury_status'=>'fit',
    ])->assertSessionHasErrors('photo');
});

test('student creation fails with non-image', function () {
    $bad = UploadedFile::fake()->create('bad.php', 100, 'text/plain');
    $this->actingAs($this->coach)->post('/students', [
        'batch_id'=>$this->batch->id,'full_name'=>'Test','skill_level'=>'intermediate','injury_status'=>'fit','photo'=>$bad,
    ])->assertStatus(422);
});

test('coach cannot access students from other coach batch', function () {
    $other = User::factory()->coach()->create();
    $otherBatch = Batch::factory()->create(['coach_id'=>$other->id]);
    $student = Student::factory()->create(['batch_id'=>$otherBatch->id]);
    $this->actingAs($this->coach)->get("/students/{$student->id}/edit")->assertForbidden();
});

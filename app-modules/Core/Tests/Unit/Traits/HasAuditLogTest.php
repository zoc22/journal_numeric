<?php

namespace Modules\Core\Tests\Unit\Traits;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasAuditLog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

class AuditTestModel extends Model
{
    use HasAuditLog;
    protected $table = 'audit_test';
    public $timestamps = false;
    protected $fillable = ['name'];
}

class HasAuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('audit_test');
        Schema::create('audit_test', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
    }

    /** @test */
    public function it_logs_created_event()
    {
        Log::shouldReceive('channel')->with('stack')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($message) {
            return str_contains($message, '[AUDIT]') && str_contains($message, 'created') && str_contains($message, 'Test Created');
        });

        $model = new AuditTestModel();
        $model->name = 'Test Created';
        $model->save();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_logs_updated_event()
    {
        $model = new AuditTestModel();
        $model->name = 'Initial';
        $model->save();

        Log::shouldReceive('channel')->with('stack')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($message) {
            return str_contains($message, '[AUDIT]') && str_contains($message, 'updated') && str_contains($message, 'Updated');
        });

        $model->name = 'Updated';
        $model->save();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_logs_deleted_event()
    {
        $model = new AuditTestModel();
        $model->name = 'To Delete';
        $model->save();

        Log::shouldReceive('channel')->with('stack')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($message) {
            return str_contains($message, '[AUDIT]') && str_contains($message, 'deleted') && str_contains($message, 'To Delete');
        });

        $model->delete();

        $this->assertTrue(true);
    }
}

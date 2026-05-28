<?php

namespace Modules\Core\Tests\Unit\Traits;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UuidTestModel extends Model
{
    use HasUuid;
    protected $table = 'uuid_test';
    public $timestamps = false;
}

class HasUuidTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('uuid_test');
        // Crée une table temporaire pour le test (SQLite en mémoire)
        Schema::create('uuid_test', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
        });
    }

    /** @test */
    public function it_generates_uuid_on_creating()
    {
        $model = new UuidTestModel();
        $model->save();  // déclenche l'événement creating → l'UUID est généré
        $this->assertNotEmpty($model->getKey());
        $this->assertEquals(36, strlen($model->getKey()));
    }

    /** @test */
    public function it_does_not_override_existing_id()
    {
        $model = new UuidTestModel();
        $customUuid = '550e8400-e29b-41d4-a716-446655440000';
        $model->id = $customUuid;
        $model->save();
        $this->assertEquals($customUuid, $model->getKey());
    }
}

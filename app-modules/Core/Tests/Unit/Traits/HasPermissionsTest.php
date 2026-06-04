<?php

namespace Modules\Core\Tests\Unit\Traits;

use Tests\TestCase;
use Modules\Core\Traits\HasPermissions;
use Illuminate\Support\Facades\Gate;

class PermissionsTestModel
{
    use HasPermissions;
    public $role = 'guest';

    // Mock getAttributes for the trait
    public function getAttributes() {
        return ['role' => $this->role];
    }

    public function can($permission) { return $permission === 'allowed'; }
    public function save() { return true; }
}

class HasPermissionsTest extends TestCase
{
    /** @test */
    public function it_checks_permissions_via_gate()
    {
        $model = new PermissionsTestModel();

        Gate::shouldReceive('forUser')
            ->with($model)
            ->andReturnSelf();

        Gate::shouldReceive('allows')
            ->with('edit-posts')
            ->andReturn(true);

        $this->assertTrue($model->hasPermissionViaGate('edit-posts'));
    }

    /** @test */
    public function it_falls_back_to_can_method()
    {
        $model = new PermissionsTestModel();

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('allows')->andReturn(false);

        $this->assertTrue($model->hasPermissionViaGate('allowed'));
        $this->assertFalse($model->hasPermissionViaGate('denied'));
    }

    /** @test */
    public function it_checks_roles_via_attribute()
    {
        $model = new PermissionsTestModel();
        $model->role = 'admin';

        $this->assertTrue($model->hasRole('admin'));
        $this->assertFalse($model->hasRole('user'));
    }

    /** @test */
    public function it_assigns_roles()
    {
        $model = new PermissionsTestModel();
        $model->assignRole('editor');

        $this->assertEquals('editor', $model->role);
    }
}

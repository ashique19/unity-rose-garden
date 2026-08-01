<?php

namespace Tests\Feature;

use App\Models\Flat;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminFlatsAndUsersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_update_flat_contact_info(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();
        $flat = Flat::query()->where('name', '2A')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.flats.update', $flat), [
                'name' => '2A',
                'contact_name' => 'Karim Resident',
                'phone' => '01711112222',
                'status' => 'online',
            ])
            ->assertRedirect(route('admin.flats.index'));

        $flat->refresh();
        $this->assertSame('Karim Resident', $flat->contact_name);
        $this->assertSame('01711112222', $flat->phone);
        $this->assertSame('online', $flat->status);
    }

    #[Test]
    public function admin_can_add_and_remove_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Secretary One',
                'phone' => '01799998888',
                'password' => 'secret',
                'roles' => ['secretary'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('phone', '01799998888')->firstOrFail();
        $this->assertTrue($user->hasRole('secretary'));
        $this->assertFalse($user->isAdmin());

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse(User::query()->where('phone', '01799998888')->exists());
    }

    #[Test]
    public function cannot_delete_own_account_or_last_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertTrue(User::query()->whereKey($admin->id)->exists());
    }

    #[Test]
    public function admin_pages_require_admin_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $secretary = User::query()->create([
            'name' => 'Sec',
            'phone' => '01755554444',
            'password' => 'secret',
        ]);
        $secretary->roles()->attach(Role::query()->where('name', 'secretary')->firstOrFail());

        $this->actingAs($secretary)
            ->get(route('admin.flats.index'))
            ->assertForbidden();

        $this->actingAs($secretary)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_assign_member_role(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('phone', '01785636359')->firstOrFail();

        $this->assertTrue(Role::query()->where('name', 'member')->exists());

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Flat Member',
                'phone' => '01788887777',
                'password' => 'secret',
                'roles' => ['member'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $member = User::query()->where('phone', '01788887777')->firstOrFail();
        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());

        $this->actingAs($member)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}

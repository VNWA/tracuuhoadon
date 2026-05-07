<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_own_bills(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('staff');

        $staffA = User::factory()->create();
        $staffA->assignRole('staff');

        $staffB = User::factory()->create();
        $staffB->assignRole('staff');

        $ownBill = Bill::factory()->for($staffA)->create(['bill_private_key' => 'OWNKEY']);
        $otherBill = Bill::factory()->for($staffB)->create(['bill_private_key' => 'OTHERKEY']);

        $response = $this->actingAs($staffA)->get(route('admin.bills.index'));

        $response->assertOk();
        $response->assertSee($ownBill->bill_private_key);
        $response->assertDontSee($otherBill->bill_private_key);
    }

    public function test_staff_cannot_delete_bill_of_other_staff(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('staff');

        $staffA = User::factory()->create();
        $staffA->assignRole('staff');

        $staffB = User::factory()->create();
        $staffB->assignRole('staff');

        $otherBill = Bill::factory()->for($staffB)->create();

        $response = $this->actingAs($staffA)->delete(route('admin.bills.destroy', $otherBill));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_any_bill(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('staff');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $bill = Bill::factory()->for($staff)->create();

        $response = $this->actingAs($admin)->delete(route('admin.bills.destroy', $bill));

        $response->assertRedirect(route('admin.bills.index'));
        $this->assertModelMissing($bill);
    }
}

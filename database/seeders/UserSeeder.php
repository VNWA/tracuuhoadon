<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // create roles and assign existing permissions
        $role1 = Role::findOrCreate('admin');
        Role::findOrCreate('staff');

        // create admin user
        $user = User::factory()->create([
            'name' => 'Super',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        $user->ulid = Str::ulid()->toBase32();
        $user->email_verified_at = now();
        $user->save(['timestamps' => false]);

        $user->assignRole($role1);
    }
}

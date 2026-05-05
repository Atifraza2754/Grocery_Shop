<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Create the four primary roles, and a super-admin account so
     * we can immediately log in to the Filament panel.
     */
    public function run(): void
    {
        // Make sure permission cache is cleared
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['Admin', 'Staff', 'Ambassador', 'Vendor'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Default super-admin (change password after first login)
        $admin = User::updateOrCreate(
            ['email' => 'admin@groceryshop.test'],
            [
                'name' => 'Super Admin',
                'phone' => '03000000000',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Admin']);
    }
}

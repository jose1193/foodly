<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Ramsey\Uuid\Uuid;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
   public function run(): void
{
    $permissions = [
        Permission::create(['name' => 'Super Admin', 'guard_name' => 'sanctum']),
        Permission::create(['name' => 'Manager', 'guard_name' => 'sanctum']),
        Permission::create(['name' => 'Admin', 'guard_name' => 'sanctum']),
        Permission::create(['name' => 'Employee', 'guard_name' => 'sanctum']),
        Permission::create(['name' => 'Customer', 'guard_name' => 'sanctum']),
    ];

    // SUPER ADMIN USER
    $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'sanctum']);
    $superRole->syncPermissions($permissions);

    $superUser = User::factory()->create([
        'name' => 'Super Admin',
        'username' => 'superadmin369',
        'email' => 'admin369@admin369.com',
        'uuid' => 'ID-' . Uuid::uuid4()->toString(25),
        'phone' => '00000',
        'password' => bcrypt('superadmin369')
    ]);
    $superUser->assignRole($superRole);
    // END SUPER ADMIN USER

    // MANAGER USER
    $managerRole = Role::create(['name' => 'Manager', 'guard_name' => 'sanctum']);
    $managerRole->syncPermissions([$permissions[1], $permissions[2], $permissions[3], $permissions[4]]); // Manager tiene todos los permisos

    $managerUser = User::factory()->create([
        'name' => 'Manager',
        'username' => 'manager369',
         'uuid' => 'ID-' . Uuid::uuid4()->toString(25),
        'email' => 'manager@manager.com',
        'phone' => '00000',
        'password' => bcrypt('manager369')
    ]);
    $managerUser->assignRole($managerRole);
    // END MANAGER USER

    // ADMIN USER
    $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'sanctum']);
    $adminRole->syncPermissions([$permissions[2], $permissions[3], $permissions[4]]); // Admin tiene permisos de Customer y Employee

    $adminUser = User::factory()->create([
        'name' => 'Admin',
        'username' => 'admin369',
        'email' => 'admin@admin.com',
        'uuid' => 'ID-' . Uuid::uuid4()->toString(),
        'phone' => '00000',
        'password' => bcrypt('admin369')
    ]);
    $adminUser->assignRole($adminRole);
    // END ADMIN USER

    // EMPLOYEE USER
    $employeeRole = Role::create(['name' => 'Employee', 'guard_name' => 'sanctum']);
    $employeeRole->syncPermissions([$permissions[3],$permissions[4]]);

    $employeeUser = User::factory()->create([
        'name' => 'Employee',
        'username' => 'employee369',
        'email' => 'employee@employee.com',
        'uuid' => 'ID-' . Uuid::uuid4()->toString(),
        'phone' => '00000',
        'password' => bcrypt('employee369')
    ]);
    $employeeUser->assignRole($employeeRole);
    // END EMPLOYEE USER

    // CUSTOMER USER
    $customerRole = Role::create(['name' => 'Customer', 'guard_name' => 'sanctum']);
    $customerRole->syncPermissions([$permissions[4]]);

    $customerUser = User::factory()->create([
        'name' => 'Customer',
        'username' => 'customer369',
        'email' => 'customer@customer.com',
        'uuid' => 'ID-' . Uuid::uuid4()->toString(),
        'phone' => '00000',
        'password' => bcrypt('customer369')
    ]);
    $customerUser->assignRole($customerRole);
    // END CUSTOMER USER


    
}


}
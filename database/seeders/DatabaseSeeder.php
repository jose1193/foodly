<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Category;
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

    // CATEGORIAS
    $categories = [
            ['category_name' => 'Cocina Internacional', 'category_description' => 'Descripción de Cocina Internacional', 'url_icon' => 'url_icon_cocina_internacional.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Comida Rápida', 'category_description' => 'Descripción de Comida Rápida', 'url_icon' => 'url_icon_comida_rapida.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Pizzerías', 'category_description' => 'Descripción de Pizzerías', 'url_icon' => 'url_icon_pizzerias.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cocina Japonesa', 'category_description' => 'Descripción de Cocina Japonesa', 'url_icon' => 'url_icon_cocina_japonesa.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Carnes y Parrillas', 'category_description' => 'Descripción de Carnes y Parrillas', 'url_icon' => 'url_icon_carnes_parrillas.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Fusión', 'category_description' => 'Descripción de Fusión', 'url_icon' => 'url_icon_fusion.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cocina Vegetariana', 'category_description' => 'Descripción de Cocina Vegetariana', 'url_icon' => 'url_icon_cocina_vegetariana.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cocina Mexicana', 'category_description' => 'Descripción de Cocina Mexicana', 'url_icon' => 'url_icon_cocina_mexicana.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cocina Koreana', 'category_description' => 'Descripción de Cocina Koreana', 'url_icon' => 'url_icon_cocina_koreana.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cocina Portuguesa', 'category_description' => 'Descripción de Cocina Portuguesa', 'url_icon' => 'url_icon_cocina_portuguesa.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Pastelería y Postres', 'category_description' => 'Descripción de Pastelería y Postres', 'url_icon' => 'url_icon_pasteleria_postres.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Pubs y Vinotecas', 'category_description' => 'Descripción de Pubs y Vinotecas', 'url_icon' => 'url_icon_pubs_vinotecas.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Cafés y Desayunos', 'category_description' => 'Descripción de Cafés y Desayunos', 'url_icon' => 'url_icon_cafes_desayunos.png', 'bgcolor' => '#ffffff', 'user_id' => 1],
            ['category_name' => 'Mercados y Tiendas', 'category_description' => 'Descripción de Mercados y Tiendas', 'url_icon' => 'url_icon_mercados_tiendas.png', 'bgcolor' => '#ffffff', 'user_id' => 1]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
        // END CATEGORIAS


        
}


}
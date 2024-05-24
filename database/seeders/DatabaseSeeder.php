<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Service;
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
        'uuid' => Uuid::uuid4()->toString(25),
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
         'uuid' => Uuid::uuid4()->toString(25),
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
        'uuid' => Uuid::uuid4()->toString(),
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
        'uuid' => Uuid::uuid4()->toString(),
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
        'uuid' => Uuid::uuid4()->toString(),
        'phone' => '00000',
        'password' => bcrypt('customer369')
    ]);
    $customerUser->assignRole($customerRole);
    // END CUSTOMER USER

    // CATEGORIAS
    $categories = [
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Internacional', 'category_description' => 'Descripción de Cocina Internacional', 'category_image_path' => 'storage/app/public/categories_images/3c5b3260-0cf1-436f-9ad1-227435a0bc61.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Comida Rápida', 'category_description' => 'Descripción de Comida Rápida', 'category_image_path' => 'storage/app/public/categories_images/da811fc1-163f-493d-958d-b9320fc0881a.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pizzerías', 'category_description' => 'Descripción de Pizzerías', 'category_image_path' => 'storage/app/public/categories_images/e4888d0f-8b4c-45f5-82dc-b03adc133d14.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Japonesa', 'category_description' => 'Descripción de Cocina Japonesa', 'category_image_path' => 'storage/app/public/categories_images/bfb867ce-b300-4f2e-8f4a-28ec31d7cf2a.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Carnes y Parrillas', 'category_description' => 'Descripción de Carnes y Parrillas', 'category_image_path' => 'storage/app/public/categories_images/2d7e4a44-bc47-4fd3-907c-e185c12f1a94.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Fusión', 'category_description' => 'Descripción de Fusión', 'category_image_path' => 'storage/app/public/categories_images/4ba100b2-5a7b-4783-a053-82d86360e5c1.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Vegetariana', 'category_description' => 'Descripción de Cocina Vegetariana', 'category_image_path' => 'storage/app/public/categories_images/efe164c5-b2f9-4688-afe0-2e47c9fb1a84.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Mexicana', 'category_description' => 'Descripción de Cocina Mexicana', 'category_image_path' => 'storage/app/public/categories_images/119da1c4-886c-4d43-a763-8366aa61828b.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Koreana', 'category_description' => 'Descripción de Cocina Koreana', 'category_image_path' => 'storage/app/public/categories_images/e24fa9c3-6f01-4bab-9898-c8bc3fa86a8b.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Portuguesa', 'category_description' => 'Descripción de Cocina Portuguesa', 'category_image_path' => 'storage/app/public/categories_images/70d14b78-0272-4b47-8004-a1de2917826b.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pastelería y Postres', 'category_description' => 'Descripción de Pastelería y Postres', 'category_image_path' => 'storage/app/public/categories_images/4b2090b7-3584-44b7-b6c6-fe08704d2481.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pubs y Vinerías', 'category_description' => 'Descripción de Pubs y Vinerías', 'category_image_path' => 'storage/app/public/categories_images/9d225207-3e4a-4f9b-94a9-2e2246e3302a.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cafés y Desayunos', 'category_description' => 'Descripción de Cafés y Desayunos', 'category_image_path' => 'storage/app/public/categories_images/5b117488-072b-4640-a3b1-e50d41a4c194.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Mercados y Tiendas', 'category_description' => 'Descripción de Mercados y Tiendas', 'category_image_path' => 'storage/app/public/categories_images/e196cdbd-87b5-4417-8320-5b4e1d49d706.png', 'user_id' => 1],
    ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Escuelas de Cocina', 'category_description' => 'Descripción de Escuelas de Cocina', 'category_image_path' => 'storage/app/public/categories_images/b5f64d98-3a58-4709-924a-91bbebbaf9e4.png', 'user_id' => 1]
];

        foreach ($categories as $category) {
            Category::create($category);
        }
        // END CATEGORIAS

        // CATEGORIAS
   $services = [
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Wifi', 'service_description' => 'Descripción de Wifi', 'service_image_path' => 'storage/app/public/services_images/wifi.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Multilenguage', 'service_description' => 'Descripción de Multilenguage', 'service_image_path' => 'storage/app/public/services_images/multilenguage.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Kid Chairs', 'service_description' => 'Descripción de Kid Chairs', 'service_image_path' => 'storage/app/public/services_images/kid_chairs.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Baby Changing St..', 'service_description' => 'Descripción de Baby Changing St..', 'service_image_path' => 'storage/app/public/services_images/baby_changing_st.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Kids Play Area', 'service_description' => 'Descripción de Kids Play Area', 'service_image_path' => 'storage/app/public/services_images/kids_play_area.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Accesible', 'service_description' => 'Descripción de Accesible', 'service_image_path' => 'storage/app/public/services_images/accesible.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'PMR', 'service_description' => 'Descripción de PMR', 'service_image_path' => 'storage/app/public/services_images/pmr.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Delivery', 'service_description' => 'Descripción de Delivery', 'service_image_path' => 'storage/app/public/services_images/delivery.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Take Away', 'service_description' => 'Descripción de Take Away', 'service_image_path' => 'storage/app/public/services_images/take_away.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Smoking Area', 'service_description' => 'Descripción de Smoking Area', 'service_image_path' => 'storage/app/public/services_images/smoking_area.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Happy Hours', 'service_description' => 'Descripción de Happy Hours', 'service_image_path' => 'storage/app/public/services_images/happy_hours.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Happy Birthday', 'service_description' => 'Descripción de Happy Birthday', 'service_image_path' => 'storage/app/public/services_images/happy_birthday.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Parking', 'service_description' => 'Descripción de Parking', 'service_image_path' => 'storage/app/public/services_images/parking.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Pet Friendly', 'service_description' => 'Descripción de Pet Friendly', 'service_image_path' => 'storage/app/public/services_images/pet_friendly.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Catering', 'service_description' => 'Descripción de Catering', 'service_image_path' => 'storage/app/public/services_images/catering.png', 'user_id' => 1],
        ];


        foreach ($services as $service) {
            Service::create($service);
        }
        // END CATEGORIAS

        
}


}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        $deliveryRole = Role::create(['name' => 'delivery']);
        // Define Product Permissions
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);
        Permission::create(['name' => 'view products']);


        //Define Categories Permissions


        Permission::create(['name' => 'create categories']);
        Permission::create(['name' => 'edit categories']);
        Permission::create(['name' => 'delete categories']);
        Permission::create(['name' => 'view categories']);

        //Define Orders Permissions
        Permission::create(['name' => 'create orders']);
        Permission::create(['name' => 'edit orders']);
        Permission::create(['name' => 'delete orders']);
        Permission::create(['name' => 'view orders']);

        //Define Users Permissions
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);
        Permission::create(['name' => 'view users']);

        //Define delivery Permissions

        Permission::create(['name' => 'update delivery']);
        Permission::create(['name' => 'view delivery']);

        // Assign adminRole all permissions
        $adminRole->givePermissionTo(Permission::all());
        // Assign customerRole specific permissions
        $customerRole->givePermissionTo(['create orders', 'view products', 'view categories', 'view orders']);
        // Assign deliveryRole specific permissions
        $deliveryRole->givePermissionTo(['update delivery', 'view delivery', 'view orders']);

}
}
//command for run roleand permission seeder

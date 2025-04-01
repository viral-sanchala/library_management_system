<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'Create Role', 'slug' => 'create-role'],
            ['name' => 'Update Role', 'slug' => 'edit-role'],
            ['name' => 'View Role', 'slug' => 'view-role'],
            ['name' => 'Delete Role', 'slug' => 'delete-role'],
            ['name' => 'Create Book', 'slug' => 'create-book'],
            ['name' => 'Edit Book', 'slug' => 'edit-book'],
            ['name' => 'Delete Book', 'slug' => 'delete-book'],
            ['name' => 'View Book', 'slug' => 'view-book'],
            ['name' => 'Borrow Book', 'slug' => 'borrow-book'],
            ['name' => 'Return Book', 'slug' => 'return-book']
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $admin_role = Role::where('slug', 'admin')->first();
        $user_role = Role::where('slug', 'user')->first();

        if ($admin_role) {
            $not_eligible_rule = ['borrow-book', 'return-book'];
            $permission_ids = Permission::whereNotIn('slug', $not_eligible_rule)->pluck('id')->toArray();
            foreach ($permission_ids as $id) {
                RolePermission::create([
                    'id' => Str::uuid(),
                    'role_id' => $admin_role->id,
                    'permission_id' => $id
                ]);
            }
        }

        if ($user_role) {
            $user_permissions = [
                "view-book",
                "borrow-book",
                "return-book"
            ];

            $permission_ids = Permission::whereIn('slug', $user_permissions)->pluck('id')->toArray();

            foreach ($permission_ids as $id) {
                RolePermission::create([
                    'id' => Str::uuid(),
                    'role_id' => $user_role->id,
                    'permission_id' => $id
                ]);
            }
        }
    }
}

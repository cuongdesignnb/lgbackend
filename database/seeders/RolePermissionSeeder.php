<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Define permissions by module ────────────────────────────────
        $modules = [
            'products'      => ['Sản phẩm', ['view', 'create', 'edit', 'delete', 'import', 'export']],
            'categories'    => ['Danh mục', ['view', 'create', 'edit', 'delete']],
            'brands'        => ['Thương hiệu', ['view', 'create', 'edit', 'delete']],
            'filters'       => ['Bộ lọc', ['view', 'create', 'edit', 'delete']],
            'orders'        => ['Đơn hàng', ['view', 'edit', 'delete']],
            'coupons'       => ['Mã giảm giá', ['view', 'create', 'edit', 'delete']],
            'posts'         => ['Bài viết', ['view', 'create', 'edit', 'delete']],
            'post_categories' => ['DM Bài viết', ['view', 'create', 'edit', 'delete']],
            'pages'         => ['Trang tĩnh', ['view', 'create', 'edit', 'delete']],
            'banners'       => ['Banner', ['view', 'create', 'edit', 'delete']],
            'videos'        => ['Video', ['view', 'create', 'edit', 'delete']],
            'catalogues'    => ['Catalogue', ['view', 'create', 'edit', 'delete']],
            'media'         => ['Thư viện Media', ['view', 'upload', 'delete']],
            'menus'         => ['Menu', ['view', 'create', 'edit', 'delete']],
            'customers'     => ['Khách hàng', ['view']],
            'reviews'       => ['Đánh giá', ['view', 'edit', 'delete']],
            'settings'      => ['Cài đặt', ['view', 'edit']],
            'ai_articles'   => ['AI Bài viết', ['view', 'create', 'delete']],
            'users'         => ['Quản lý User', ['view', 'create', 'edit', 'delete']],
            'roles'         => ['Phân quyền', ['view', 'create', 'edit', 'delete']],
        ];

        $allPermissions = [];
        foreach ($modules as $module => [$label, $actions]) {
            foreach ($actions as $action) {
                $permName = "{$module}.{$action}";
                $allPermissions[] = $permName;
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }
        }

        // ─── Define roles ────────────────────────────────────────────────
        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($allPermissions);

        // Admin — all except user/role management
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = array_filter($allPermissions, fn($p) => !str_starts_with($p, 'users.') && !str_starts_with($p, 'roles.'));
        $admin->syncPermissions($adminPermissions);

        // Content Editor — content modules only
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $editorModules = ['posts', 'post_categories', 'pages', 'banners', 'videos', 'catalogues', 'media', 'ai_articles'];
        $editorPermissions = array_filter($allPermissions, function ($p) use ($editorModules) {
            $module = explode('.', $p)[0];
            return in_array($module, $editorModules);
        });
        $editor->syncPermissions($editorPermissions);

        // Sales Staff — orders, coupons, customers, reviews
        $sales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $salesModules = ['orders', 'coupons', 'customers', 'reviews', 'products'];
        $salesPermissions = array_filter($allPermissions, function ($p) use ($salesModules) {
            $module = explode('.', $p)[0];
            return in_array($module, $salesModules);
        });
        $sales->syncPermissions($salesPermissions);

        // ─── Assign super_admin role to first admin user ─────────────────
        $adminUser = User::where('role', 'admin')->first();
        if ($adminUser && !$adminUser->hasRole('super_admin')) {
            $adminUser->assignRole('super_admin');
        }

        $this->command->info('✅ Roles & Permissions seeded successfully.');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['super_admin', count($allPermissions)],
                ['admin', count(array_values($adminPermissions))],
                ['editor', count(array_values($editorPermissions))],
                ['sales', count(array_values($salesPermissions))],
            ]
        );
    }
}

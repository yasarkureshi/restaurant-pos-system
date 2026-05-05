<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Organization;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Models\TableSection;
use App\Models\TaxCategory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name' => 'Restaurant Group',
            'slug' => 'restaurant-group',
            'email' => 'admin@restaurantpos.com',
            'phone' => '9999999999',
            'subscription_plan' => 'premium',
            'is_active' => true,
        ]);

        $restaurant = Restaurant::create([
            'organization_id' => $org->id,
            'name' => 'Restaurant - Main Branch',
            'code' => 'PPM',
            'slug' => 'restaurant-main',
            'email' => 'main@restaurantpos.com',
            'phone' => '9876543210',
            'address' => '123 MG Road, Koramangala',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560034',
            'gst_number' => '29AABCT1332L1ZN',
            'fssai_license' => '11225003004071',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'operation_start_time' => '09:00:00',
            'operation_end_time' => '23:00:00',
            'is_active' => true,
            'settings' => ['service_charge_percentage' => 5, 'print_kot' => true, 'print_bill' => true],
        ]);

        // Permissions
        $permList = [
            ['dashboard', 'View Dashboard', 'dashboard.view'],
            ['orders', 'View Orders', 'orders.view'],
            ['orders', 'Create Orders', 'orders.create'],
            ['orders', 'Edit Orders', 'orders.edit'],
            ['orders', 'Delete Orders', 'orders.delete'],
            ['orders', 'Cancel Orders', 'orders.cancel'],
            ['orders', 'Apply Discounts', 'orders.discount'],
            ['payments', 'Process Payments', 'payments.process'],
            ['payments', 'View Payments', 'payments.view'],
            ['payments', 'Refund Payments', 'payments.refund'],
            ['menu', 'View Menu', 'menu.view'],
            ['menu', 'Create Products', 'menu.create'],
            ['menu', 'Edit Products', 'menu.edit'],
            ['menu', 'Delete Products', 'menu.delete'],
            ['menu', 'Manage Categories', 'menu.categories'],
            ['tables', 'Manage Tables', 'tables.manage'],
            ['tables', 'View Tables', 'tables.view'],
            ['inventory', 'View Inventory', 'inventory.view'],
            ['inventory', 'Manage Inventory', 'inventory.manage'],
            ['inventory', 'Purchase Orders', 'inventory.purchase'],
            ['staff', 'View Staff', 'staff.view'],
            ['staff', 'Manage Staff', 'staff.manage'],
            ['reports', 'View Reports', 'reports.view'],
            ['reports', 'Export Reports', 'reports.export'],
            ['reports', 'Financial Reports', 'reports.financial'],
            ['settings', 'View Settings', 'settings.view'],
            ['settings', 'Manage Settings', 'settings.manage'],
            ['settings', 'Manage Taxes', 'settings.taxes'],
            ['customers', 'View Customers', 'customers.view'],
            ['customers', 'Manage Customers', 'customers.manage'],
            ['kitchen', 'View KDS', 'kitchen.view'],
            ['kitchen', 'Manage KDS', 'kitchen.manage'],
            ['kitchen', 'Update Order Status', 'kitchen.update_status'],
        ];

        $permissionIds = [];
        foreach ($permList as [$module, $name, $slug]) {
            $p = Permission::create(['module' => $module, 'name' => $name, 'slug' => $slug]);
            $permissionIds[$slug] = $p->id;
        }

        $adminRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Restaurant Admin', 'slug' => 'admin', 'is_system' => true]);
        $managerRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);
        $cashierRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Cashier', 'slug' => 'cashier', 'is_system' => true]);
        $waiterRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Waiter', 'slug' => 'waiter', 'is_system' => true]);
        $kitchenRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Kitchen Staff', 'slug' => 'kitchen_staff', 'is_system' => true]);

        $adminRole->permissions()->sync(array_values($permissionIds));

        $cashierPerms = ['dashboard.view', 'orders.view', 'orders.create', 'orders.edit', 'orders.discount',
            'payments.process', 'payments.view', 'menu.view', 'tables.view', 'customers.view', 'customers.manage'];
        $cashierRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($cashierPerms))));

        $waiterPerms = ['dashboard.view', 'orders.view', 'orders.create', 'orders.edit', 'menu.view', 'tables.view', 'customers.view'];
        $waiterRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($waiterPerms))));

        $kitchenPerms = ['kitchen.view', 'kitchen.manage', 'kitchen.update_status', 'orders.view'];
        $kitchenRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($kitchenPerms))));

        $managerRole->permissions()->sync(array_values($permissionIds));

        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $adminRole->id, 'name' => 'Admin User', 'email' => 'admin@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543210', 'employee_code' => 'EMP001', 'pin_code' => '1234', 'date_of_joining' => now()->toDateString(), 'is_active' => true]);
        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $cashierRole->id, 'name' => 'Cashier User', 'email' => 'cashier@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543211', 'employee_code' => 'EMP002', 'pin_code' => '5678', 'date_of_joining' => now()->toDateString(), 'is_active' => true]);
        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $kitchenRole->id, 'name' => 'Kitchen Staff', 'email' => 'kitchen@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543212', 'employee_code' => 'EMP003', 'pin_code' => '9012', 'is_active' => true]);

        $gst5 = TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 5%', 'tax_percentage' => 5, 'cgst_percentage' => 2.5, 'sgst_percentage' => 2.5, 'is_active' => true]);
        TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 12%', 'tax_percentage' => 12, 'cgst_percentage' => 6, 'sgst_percentage' => 6, 'is_active' => true]);
        TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 18%', 'tax_percentage' => 18, 'cgst_percentage' => 9, 'sgst_percentage' => 9, 'is_active' => true]);

        $starters = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Starters', 'slug' => 'starters', 'sort_order' => 1, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);
        $mains = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Main Course', 'slug' => 'main-course', 'sort_order' => 2, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);
        $breads = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Breads', 'slug' => 'breads', 'sort_order' => 3, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);
        $rice = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Rice & Biryani', 'slug' => 'rice-biryani', 'sort_order' => 4, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);
        $drinks = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Beverages', 'slug' => 'beverages', 'sort_order' => 5, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);
        $desserts = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Desserts', 'slug' => 'desserts', 'sort_order' => 6, 'is_active' => true, 'display_in_pos' => true, 'display_in_kds' => true]);

        $prods = [
            ['Paneer Tikka', 'paneer-tikka', $starters->id, 280, 120, true, 15, true],
            ['Chicken 65', 'chicken-65', $starters->id, 320, 150, false, 15, true],
            ['Veg Manchurian', 'veg-manchurian', $starters->id, 180, 80, true, 10, false],
            ['Fish Fry', 'fish-fry', $starters->id, 380, 180, false, 20, false],
            ['Butter Paneer Masala', 'butter-paneer-masala', $mains->id, 320, 140, true, 20, true],
            ['Chicken Curry', 'chicken-curry', $mains->id, 360, 180, false, 25, true],
            ['Dal Tadka', 'dal-tadka', $mains->id, 180, 60, true, 15, false],
            ['Palak Paneer', 'palak-paneer', $mains->id, 300, 130, true, 18, false],
            ['Mutton Rogan Josh', 'mutton-rogan-josh', $mains->id, 480, 250, false, 35, false],
            ['Butter Naan', 'butter-naan', $breads->id, 60, 20, true, 8, false],
            ['Garlic Naan', 'garlic-naan', $breads->id, 70, 25, true, 8, false],
            ['Tandoori Roti', 'tandoori-roti', $breads->id, 40, 12, true, 6, false],
            ['Veg Dum Biryani', 'veg-dum-biryani', $rice->id, 280, 120, true, 30, false],
            ['Chicken Biryani', 'chicken-biryani', $rice->id, 380, 180, false, 30, true],
            ['Mutton Biryani', 'mutton-biryani', $rice->id, 480, 240, false, 40, false],
            ['Jeera Rice', 'jeera-rice', $rice->id, 120, 40, true, 12, false],
            ['Masala Chai', 'masala-chai', $drinks->id, 40, 15, true, 5, false],
            ['Cold Coffee', 'cold-coffee', $drinks->id, 120, 40, true, 5, false],
            ['Mango Lassi', 'mango-lassi', $drinks->id, 100, 35, true, 5, false],
            ['Fresh Lime Soda', 'fresh-lime-soda', $drinks->id, 80, 25, true, 3, false],
            ['Gulab Jamun', 'gulab-jamun', $desserts->id, 100, 40, true, 5, false],
            ['Kulfi', 'kulfi', $desserts->id, 120, 45, true, 2, false],
            ['Ice Cream', 'ice-cream', $desserts->id, 80, 30, true, 2, false],
        ];

        foreach ($prods as [$name, $slug, $catId, $price, $cost, $isVeg, $prepTime, $isFeatured]) {
            Product::create([
                'restaurant_id' => $restaurant->id,
                'category_id' => $catId,
                'tax_category_id' => $gst5->id,
                'name' => $name,
                'slug' => $slug,
                'price' => $price,
                'cost_price' => $cost,
                'is_veg' => $isVeg,
                'is_available' => true,
                'is_active' => true,
                'is_featured' => $isFeatured,
                'preparation_time_minutes' => $prepTime,
                'unit_type' => 'plate',
            ]);
        }

        $indoor = TableSection::create(['restaurant_id' => $restaurant->id, 'name' => 'Indoor', 'sort_order' => 1, 'is_active' => true]);
        $outdoor = TableSection::create(['restaurant_id' => $restaurant->id, 'name' => 'Outdoor', 'sort_order' => 2, 'is_active' => true]);
        $private = TableSection::create(['restaurant_id' => $restaurant->id, 'name' => 'Private Dining', 'sort_order' => 3, 'is_active' => true]);

        $tables = [
            [$indoor->id, 'T1', '1', 2, 'couple'], [$indoor->id, 'T2', '2', 4, 'regular'],
            [$indoor->id, 'T3', '3', 4, 'regular'], [$indoor->id, 'T4', '4', 6, 'family'],
            [$indoor->id, 'T5', '5', 6, 'family'], [$indoor->id, 'T6', '6', 8, 'party'],
            [$outdoor->id, 'O1', '7', 4, 'outdoor'], [$outdoor->id, 'O2', '8', 4, 'outdoor'],
            [$outdoor->id, 'O3', '9', 6, 'outdoor'],
            [$private->id, 'P1', '10', 10, 'private'], [$private->id, 'P2', '11', 12, 'private'],
        ];

        foreach ($tables as [$sectionId, $name, $num, $cap, $type]) {
            Table::create(['restaurant_id' => $restaurant->id, 'section_id' => $sectionId, 'name' => $name, 'table_number' => $num, 'capacity' => $cap, 'table_type' => $type, 'status' => 'available', 'is_active' => true]);
        }

        $this->command->info('✅ Database seeded! Login: admin@restaurantpos.com / password123');
    }
}

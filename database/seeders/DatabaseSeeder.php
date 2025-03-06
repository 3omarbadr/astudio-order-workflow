<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Astudio User',
            'email' => 'test@astudio.com',
        ]);

        $order = Order::factory()->create();

        $orderWithItems = Order::factory()
            ->approved()
            ->withItems(5)
            ->create();

        $pendingOrder = Order::factory()
            ->pendingApproval()
            ->requiresApproval()
            ->create();

        $item = OrderItem::factory()
            ->product('Widget XYZ', 19.99)
            ->create();

        $expensiveItems = OrderItem::factory()
            ->highValue()
            ->count(10)
            ->create();
    }
}

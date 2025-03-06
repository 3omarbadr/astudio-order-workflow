<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'order_number' => 'ORD-' . Str::upper(Str::random(8)),
            'total' => $this->faker->randomFloat(2, 100, 2000),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_by' => User::factory(),
            'approved_by' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the order is pending approval.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function pendingApproval()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'approved_by' => null,
            ];
        });
    }

    /**
     * Indicate that the order is approved.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * Indicate that the order is rejected.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'approved_by' => User::factory(),
            ];
        });
    }

    /**
     * Configure the model factory to create an order with the specified number of items.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withItems(int $count = 3)
    {
        return $this->hasItems(
            \App\Models\OrderItem::factory()
                ->count($count)
                ->state(function (array $attributes, Order $order) {
                    return ['order_id' => $order->id];
                })
        );
    }

    /**
     * Configure the model factory to create an order that requires approval.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function requiresApproval()
    {
        return $this->state(function (array $attributes) {
            return [
                'total' => $this->faker->randomFloat(2, 1000, 5000),
            ];
        });
    }
}

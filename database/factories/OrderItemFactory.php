<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 10, 500);
        $subtotal = $quantity * $price;

        return [
            'order_id' => Order::factory(),
            'name' => $this->faker->words(3, true),
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => function (array $attributes) {
                return $this->faker->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Configure the model factory to create a high-value item.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function highValue()
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(1, 5);
            $price = $this->faker->randomFloat(2, 500, 2000);
            $subtotal = $quantity * $price;

            return [
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Configure the model factory to create a bulk order item.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function bulkOrder()
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(20, 100);
            $price = $this->faker->randomFloat(2, 5, 50);
            $subtotal = $quantity * $price;

            return [
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Configure the model factory to create a specific product.
     *
     * @param string $name
     * @param float $price
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function product(string $name, float $price)
    {
        return $this->state(function (array $attributes) use ($name, $price) {
            $quantity = $this->faker->numberBetween(1, 10);
            $subtotal = $quantity * $price;

            return [
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Configure the model factory to create a correctly calculated subtotal.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withCalculatedSubtotal()
    {
        return $this->state(function (array $attributes) {
            return [
                'subtotal' => $attributes['quantity'] * $attributes['price'],
            ];
        });
    }
}

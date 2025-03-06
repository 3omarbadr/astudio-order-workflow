<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderHistory;
use App\OrderStatus;
use App\Services\OrderService;
use App\Services\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;
    protected $orderNumberGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock the order number generator
        $this->orderNumberGenerator = Mockery::mock(OrderNumberGenerator::class);
        $this->orderNumberGenerator->shouldReceive('generate')->andReturn('ORD-202403-12345678');

        $this->orderService = new OrderService($this->orderNumberGenerator);

        // Mock authentication
        $this->actingAs(\App\Models\User::factory()->create());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_creates_an_order_with_items()
    {
        // Arrange
        $items = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'price' => 100.00
            ],
            [
                'name' => 'Product 2',
                'quantity' => 1,
                'price' => 50.00
            ]
        ];

        // Act
        $order = $this->orderService->createOrder($items);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('ORD-202403-12345678', $order->order_number);
        $this->assertEquals(OrderStatus::PENDING->value, $order->status);
        $this->assertEquals(Auth::id(), $order->created_by);
        $this->assertEquals(250.00, $order->total);

        // Assert items were created
        $this->assertCount(2, $order->items);
        $this->assertEquals('Product 1', $order->items[0]->name);
        $this->assertEquals(2, $order->items[0]->quantity);
        $this->assertEquals(100.00, $order->items[0]->price);
        $this->assertEquals(200.00, $order->items[0]->subtotal);

        // Assert history was recorded
        $history = OrderHistory::where('order_id', $order->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(OrderStatus::PENDING->value, $history->new_status);
        $this->assertEquals('Order created', $history->note);
    }

    #[Test]
    public function it_throws_validation_exception_when_creating_order_with_no_items()
    {
        // Arrange
        $items = [];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->orderService->createOrder($items);
    }

    #[Test]
    public function it_updates_an_order()
    {
        // Arrange
        $initialItems = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'price' => 100.00
            ]
        ];

        $order = $this->orderService->createOrder($initialItems);

        $updatedItems = [
            [
                'name' => 'Product 2',
                'quantity' => 3,
                'price' => 75.00
            ]
        ];

        // Act
        $updatedOrder = $this->orderService->updateOrder($order, $updatedItems);

        // Assert
        $this->assertEquals(225.00, $updatedOrder->total);
        $this->assertCount(1, $updatedOrder->items);
        $this->assertEquals('Product 2', $updatedOrder->items[0]->name);

        // Assert history was recorded
        $history = OrderHistory::where('order_id', $order->id)
            ->where('note', 'Order updated')
            ->first();
        $this->assertNotNull($history);
    }

    #[Test]
    public function it_submits_order_for_approval()
    {
        // Arrange
        $items = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'price' => 100.00
            ]
        ];

        $order = $this->orderService->createOrder($items);

        // Act
        $submittedOrder = $this->orderService->submitForApproval($order);

        // Assert
        $this->assertEquals(OrderStatus::APPROVED->value, $submittedOrder->status);

        // Assert history was recorded
        $history = OrderHistory::where('order_id', $order->id)
            ->whereIn('note', ['Order submitted for approval', 'Order auto-approved'])
            ->first();
        $this->assertNotNull($history);
    }

    #[Test]
    public function it_throws_validation_exception_when_submitting_non_pending_order()
    {
        // Arrange
        $items = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'price' => 100.00
            ]
        ];

        $order = $this->orderService->createOrder($items);
        $order->status = OrderStatus::APPROVED->value;
        $order->save();

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->orderService->submitForApproval($order);
    }

    #[Test]
    public function it_retrieves_order_by_order_number()
    {
        // Arrange
        $items = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'price' => 100.00
            ]
        ];

        $order = $this->orderService->createOrder($items);

        // Act
        $retrievedOrder = $this->orderService->getByOrderNumber($order->order_number);

        // Assert
        $this->assertInstanceOf(Order::class, $retrievedOrder);
        $this->assertEquals($order->id, $retrievedOrder->id);
    }
}

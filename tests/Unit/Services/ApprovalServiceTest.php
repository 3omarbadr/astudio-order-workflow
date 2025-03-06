<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderHistory;
use App\OrderStatus;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $approvalService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approvalService = new ApprovalService();

        // Mock authentication
        $this->actingAs(\App\Models\User::factory()->create());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_approves_a_pending_order()
    {
        // Arrange
        $order = Order::factory()->create(['status' => OrderStatus::PENDING->value]);

        // Mock the isPendingApproval method on the real instance
        $mock = Mockery::mock($order);
        $mock->shouldReceive('isPendingApproval')->andReturn(true);

        // Act
        $approvedOrder = $this->approvalService->approveOrder($mock);

        // Assert
        $this->assertEquals(OrderStatus::APPROVED->value, $approvedOrder->status);
        $this->assertEquals(Auth::id(), $approvedOrder->approved_by);

        // Assert history was recorded
        $history = OrderHistory::where('order_id', $order->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(OrderStatus::PENDING->value, $history->status);
        $this->assertEquals(OrderStatus::APPROVED->value, $history->new_status);
        $this->assertEquals('Order approved', $history->note);
    }

    #[Test]
    public function it_throws_validation_exception_when_approving_non_pending_order()
    {
        // Arrange
        $order = Order::factory()->create(['status' => OrderStatus::APPROVED->value]);

        // Mock the isPendingApproval method on the real instance
        $mock = Mockery::mock($order);
        $mock->shouldReceive('isPendingApproval')->andReturn(false);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->approvalService->approveOrder($mock);
    }

    #[Test]
    public function it_rejects_a_pending_order()
    {
        // Arrange
        $order = Order::factory()->create(['status' => OrderStatus::PENDING->value]);

        // Mock the isPendingApproval method on the real instance
        $mock = Mockery::mock($order);
        $mock->shouldReceive('isPendingApproval')->andReturn(true);

        // Act
        $rejectedOrder = $this->approvalService->rejectOrder($mock, 'Budget exceeded');

        // Assert
        $this->assertEquals(OrderStatus::REJECTED->value, $rejectedOrder->status);

        // Assert history was recorded
        $history = OrderHistory::where('order_id', $order->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(OrderStatus::PENDING->value, $history->status);
        $this->assertEquals(OrderStatus::REJECTED->value, $history->new_status);
        $this->assertEquals('Budget exceeded', $history->note);
    }

    #[Test]
    public function it_throws_validation_exception_when_rejecting_non_pending_order()
    {
        // Arrange
        $order = Order::factory()->create(['status' => OrderStatus::APPROVED->value]);

        // Mock the isPendingApproval method on the real instance
        $mock = Mockery::mock($order);
        $mock->shouldReceive('isPendingApproval')->andReturn(false);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->approvalService->rejectOrder($mock);
    }

    #[Test]
    public function it_rejects_order_with_default_reason_when_no_reason_provided()
    {
        // Arrange
        $order = Order::factory()->create(['status' => OrderStatus::PENDING->value]);

        // Mock the isPendingApproval method on the real instance
        $mock = Mockery::mock($order);
        $mock->shouldReceive('isPendingApproval')->andReturn(true);

        // Act
        $rejectedOrder = $this->approvalService->rejectOrder($mock);

        // Assert
        $this->assertEquals(OrderStatus::REJECTED->value, $rejectedOrder->status);

        // Assert history was recorded with default reason
        $history = OrderHistory::where('order_id', $order->id)->first();
        $this->assertEquals('Order rejected', $history->note);
    }
}

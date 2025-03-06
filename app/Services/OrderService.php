<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderHistory;
use App\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderService
{
    protected $orderNumberGenerator;

    public function __construct(OrderNumberGenerator $orderNumberGenerator)
    {
        $this->orderNumberGenerator = $orderNumberGenerator;
    }

    /**
     * Create a new order with items
     *
     * @param array $items
     * @return Order
     * @throws ValidationException
     */
    public function createOrder(array $items): Order
    {
        $this->validateItems($items);

        return DB::transaction(function () use ($items) {
            try {
                // Create the order
                $order = new Order();
                $order->order_number = $this->orderNumberGenerator->generate();
                $order->status = OrderStatus::PENDING->value;
                $order->created_by = Auth::id();
                $order->save();

                $previousStatus = $order->status;
                $order->total = $this->addOrderItems($order, $items);
                $order->save();

                $this->recordHistory($order, $previousStatus, OrderStatus::PENDING->value, 'Order created');

                return $order;
            } catch (\Exception $e) {
                Log::error('Failed to create order: ' . $e->getMessage(), [
                    'exception' => $e,
                    'items' => $items
                ]);
                throw $e;
            }
        }, 5); // Retry up to 5 times on deadlock
    }

    /**
     * Update an existing order
     *
     * @param Order $order
     * @param array $items
     * @return Order
     * @throws ValidationException
     */
    public function updateOrder(Order $order, array $items): Order
    {
        $this->validateItems($items);
        $this->ensureOrderCanBeModified($order);

        return DB::transaction(function () use ($order, $items) {
            try {
                // Use lockForUpdate to prevent concurrent modifications
                $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

                $lockedOrder->items()->delete();
                $lockedOrder->total = $this->addOrderItems($lockedOrder, $items);
                $lockedOrder->save();

                $this->recordHistory($lockedOrder, $lockedOrder->status, $lockedOrder->status, 'Order updated');

                return $lockedOrder->fresh(['items']);
            } catch (\Exception $e) {
                Log::error('Failed to update order: ' . $e->getMessage(), [
                    'exception' => $e,
                    'order_id' => $order->id,
                    'items' => $items
                ]);
                throw $e;
            }
        }, 5);
    }

    /**
     * Submit order for approval
     *
     * @param Order $order
     * @return Order
     * @throws ValidationException
     */
    public function submitForApproval(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            try {
                // Use lockForUpdate to prevent concurrent status changes
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

                // Ensure the order is in PENDING status
                if ($lockedOrder->status !== OrderStatus::PENDING->value) {
                    throw ValidationException::withMessages([
                        'status' => ['Only pending orders can be submitted for approval.'],
                    ]);
                }

                // Ensure the order has at least one item
                if ($lockedOrder->items()->count() === 0) {
                    throw ValidationException::withMessages([
                        'items' => ['Order must have at least one item before submission.'],
                    ]);
                }

                $previousStatus = $lockedOrder->status;

                if ($lockedOrder->requiresApproval()) {
                    $lockedOrder->status = OrderStatus::APPROVED->value;
                    $statusMessage = 'Order submitted for approval';
                } else {
                    $lockedOrder->status = OrderStatus::APPROVED->value;
                    $statusMessage = 'Order auto-approved';
                }

                $lockedOrder->save();

                $this->recordHistory($lockedOrder, $previousStatus, $lockedOrder->status, $statusMessage);

                return $lockedOrder->fresh();
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::error('Failed to submit order for approval: ' . $e->getMessage(), [
                    'exception' => $e,
                    'order_id' => $order->id
                ]);
                throw new \RuntimeException('An unexpected error occurred while submitting the order.');
            }
        }, 5);
    }

    /**
     * Get order by order number with optimistic locking
     *
     * @param string $orderNumber
     * @return Order|null
     */
    public function getByOrderNumber(string $orderNumber): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->with('items')
            ->first();
    }

    /**
     * Add items to an order and calculate the total
     *
     * @param Order $order
     * @param array $items
     * @return float
     */
    private function addOrderItems(Order $order, array $items): float
    {
        $totalAmount = 0;

        foreach ($items as $itemData) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->name = $itemData['name'];
            $orderItem->quantity = $itemData['quantity'];
            $orderItem->price = $itemData['price'];
            $orderItem->subtotal = $orderItem->calculateSubtotal();
            $orderItem->save();

            $totalAmount += $orderItem->subtotal;
        }

        return $totalAmount;
    }

    /**
     * Record order history
     *
     * @param Order $order
     * @param string|null $previousStatus
     * @param string $newStatus
     * @param string|null $note
     * @return void
     */
    private function recordHistory(Order $order, ?string $previousStatus, string $newStatus, string $note = null): void
    {
        OrderHistory::create([
            'order_id' => $order->id,
            'status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(),
            'note' => $note,
        ]);
    }

    /**
     * Validate order items
     *
     * @param array $items
     * @throws ValidationException
     */
    private function validateItems(array $items): void
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => ['Order must have at least one item'],
            ]);
        }
    }

    /**
     * Ensure order can be modified
     *
     * @param Order $order
     * @throws ValidationException
     */
    private function ensureOrderCanBeModified(Order $order): void
    {
        if (!$order->canBeModified()) {
            throw ValidationException::withMessages([
                'order' => ['Approved orders cannot be modified'],
            ]);
        }
    }
}

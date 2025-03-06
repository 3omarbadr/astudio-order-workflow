<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderHistory;
use App\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    /**
     * Approve an order
     */
    public function approveOrder(Order $order)
    {
        if (!$order->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => ['Only pending orders can be approved.'],
            ]);
        }

        $order->status = OrderStatus::APPROVED->value;
        $order->approved_by = Auth::id();
        $order->save();

        OrderHistory::create([
            'order_id' => $order->id,
            'status' => OrderStatus::PENDING->value,
            'new_status' => OrderStatus::APPROVED->value,
            'note' => 'Order approved',
            'changed_by' => Auth::id(),
        ]);

        return $order;
    }

    public function rejectOrder(Order $order, ?string $reason = null)
    {
        if (!$order->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => ['Only pending orders can be rejected.'],
            ]);
        }

        $order->status = OrderStatus::REJECTED->value;
        $order->save();

        OrderHistory::create([
            'order_id' => $order->id,
            'status' => OrderStatus::PENDING->value,
            'new_status' => OrderStatus::REJECTED->value,
            'note' => $reason ?? 'Order rejected',
            'changed_by' => Auth::id(),
        ]);

        return $order;
    }

    /**
     * Record order history
     */
    private function recordHistory(Order $order, string $previousStatus, string $newStatus, string $note = null): void
    {
        OrderHistory::create([
            'order_id' => $order->id,
            'status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(),
            'note' => $note,
        ]);
    }
}

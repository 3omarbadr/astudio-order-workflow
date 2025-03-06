<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    protected $orderService;
    protected $approvalService;

    public function __construct(
        OrderService $orderService,
        ApprovalService $approvalService
    ) {
        $this->orderService = $orderService;
        $this->approvalService = $approvalService;
    }

    /**
     * Approve an order
     */
    public function approve(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        try {
            $order = $this->approvalService->approveOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Order approved successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject an order
     */
    public function reject(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        try {
            $order = $this->approvalService->rejectOrder($order, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully',
                'data' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

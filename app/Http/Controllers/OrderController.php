<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * Get all orders
     */
    public function index(): JsonResponse
    {
        $orders = Order::with('items')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Create a new order
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated()['items']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order->load('items')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific order
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order->load(['items', 'history'])),
        ]);
    }

    /**
     * Submit order for approval
     */
    public function submit(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        try {
            $order = $this->orderService->submitForApproval($order);

            return response()->json([
                'success' => true,
                'message' => 'Order submitted for approval',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order history
     */
    public function history(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->getByOrderNumber($orderNumber);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order->history()->orderBy('created_at', 'desc')->get(),
        ]);
    }
}

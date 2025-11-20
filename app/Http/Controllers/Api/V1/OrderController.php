<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private OrderRepository $orderRepository
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'order_number']);
        
        // Customers can only see their own orders
        if (auth()->user()->isCustomer()) {
            $filters['customer_id'] = auth()->id();
        }

        $orders = $this->orderRepository->paginate(
            $request->get('per_page', 15),
            $filters
        );

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Customers can only view their own orders
        if (auth()->user()->isCustomer() && $order->customer_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $order]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'sometimes|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_attributes' => 'sometimes|array',
            'tax' => 'sometimes|numeric|min:0',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'shipping_address' => 'sometimes|string',
            'billing_address' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_phone' => 'sometimes|string',
            'notes' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $customerId = auth()->user()->isCustomer() ? auth()->id() : $request->customer_id;
            $order = $this->orderService->createOrder($request->all(), $customerId);

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function confirm($id)
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order cannot be confirmed'], 400);
        }

        try {
            $this->orderService->confirmOrder($order);

            return response()->json([
                'message' => 'Order confirmed successfully',
                'data' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request, $id)
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Customers can only cancel their own orders
        if (auth()->user()->isCustomer() && $order->customer_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->orderService->cancelOrder($order, $request->reason);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->orderService->updateOrderStatus(
            $order,
            $request->status,
            auth()->id(),
            $request->notes
        );

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order->fresh(),
        ]);
    }
}

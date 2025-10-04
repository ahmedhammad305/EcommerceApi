<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Enum\OrderStatus;
use Illuminate\Http\Request;
use App\Models\OrderMangement;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
//
class OrderMangementController extends Controller
{
    public function index(Request $request)
    {
        // validate request
        $request->validate([
            'status' => 'in:' . implode(',', OrderStatus::values()),
            'from_date' => 'date',
            'to_date' => 'date',
        ]);

        $query = Order::query()->with(['user','items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ],200);

    }
        public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'payment']);
        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }

    public function updateStatus(Request $request, $id)

    {

        // validate request
        $request->validate([
            'status' => 'required|in:' . implode(',', OrderStatus::values()),
        ]);

        $order = Order::with('user')->find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        //check if order can transition to the new status
        $newStatus = OrderStatus::from($request->status);
        if (!$order->status->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Order status cannot transition from {$order->status->value} to {$newStatus->value}"
            ], 400);
        }
        $order->transitionTo($newStatus, auth()->user());
        OrderStatusUpdated::dispatch($order);
        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order
        ], 200);
}
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Enum\OrderStatus;
use App\Enum\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
        public function checkout(Request $request){
        // Validate the request data
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_zipcode' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'payment_method' => 'nullable|in:credit_card,paypal', // if null default to 'cod'
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        // dd($request->user());
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        $subtotal = 0;
        $items = []; // order items array
        foreach ($cartItems as $item) {
            $product = $item->product;

            if (!$product->is_active) {
                return response()
                    ->json(['message' => "Product '{$product->name}' is no longer available"], 400);
            }

            if ($product->stock < $item->quantity) {
                return response()
                    ->json(['message' => "not enogh stock for product '{$product->name}'"], 400);
            }

            $itemSubTotal = round($product->price * $item->quantity, 2);
            $subtotal += $itemSubTotal;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                // 'product_sku' => $product->sku,
                'quantity' => $item->quantity,
                'price' => $product->price,
                'subtotal' => $itemSubTotal,
            ];
        }

        $tax = round($subtotal * 0.08, 2); // assuming 8% tax
        $shippingCost = 5.00; // flat rate shipping cost
        $total = round($subtotal + $tax + $shippingCost, 2);

        // Create the order with database transaction
        DB::beginTransaction();
        try {
            $order = new Order([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'shipping_name' => $request->shipping_name,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zipcode' => $request->shipping_zipcode,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => PaymentStatus::PENDING,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'notes' => $request->notes,
            ]);
            $user->orders()->save($order);
            foreach ($items as $item) {
                $order->items()->create($item);
                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']); // decrement stock
            }
            // Clear the user's cart
            Cart::where('user_id', $user->id)->each(function ($cartItem) {
                $cartItem->delete();
            });
            DB::commit();

            return response()
                ->json([
                    'message' => 'Order placed successfully',
                    'order' => $order->load('items'),
                    'status' => true,
                ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
        return response()->json(['message' => 'Order placed successfully'], 201);
    }

    public function orderHistory()
    {
        $orders = auth()->user()->orders()->with('items')->get();
        if ($orders->isEmpty()) {

            return response()->json(['message' => 'No orders found'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'orders' => $orders
        ], 200);
    }

    public function orderDetails($orderid) {

        $order = auth()->user()->orders()->where('id',$orderid)->with('items')->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }


        return response()->json([
            'success' => true ,
            'order' => $order,

        ],200);
    }
}

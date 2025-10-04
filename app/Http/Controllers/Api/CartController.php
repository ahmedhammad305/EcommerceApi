<?php

namespace App\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $carts = auth()->user()->carts()->with('product')->get();

       // If no carts found, return a message
        if ($carts->isEmpty()) {
            return response()->json(['message' => 'No items in cart'], 404);
        }
        // $total = $carts->sum(function ($cart) {
        //     return $cart->product->price * $cart->quantity;
        // });


        return response()->json([
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'carts' => $carts,
            'total' => $carts->sum(fn($cart) => $cart->product->price * $cart->quantity)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        // Check if the product is already in the cart
        //get authenticated user carts
        $user = auth()->user();
        $existingCart = $user->carts()->where('product_id', $data['product_id'])->first();
        if ($existingCart) {
            // If it exists, update the quantity
            $existingCart->quantity += $data['quantity'];
            $existingCart->save();
            return response()->json([
                'success' => true,
                'message' => 'Product quantity updated in cart successfully',
                'cart' => $existingCart
            ], 200);
        }

        $cart = $user->carts()->create([
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart' => $cart
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        $cart = auth()->user()->carts()->find($id);
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);

    }
        $cart->update([
            'quantity' => $data['quantity'],
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'cart' => $cart
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $cart = auth()->user()->carts()->find($id);
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
    }
        $cart->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully'
        ], 200);
    }
    public function undo()
    {
        // delet all items in cart
        $cart = auth()->user()->carts();
        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
    }
        $cart->delete();
        return response()->json([
            'success' => true,
            'message' => 'All Cart items deleted successfully'
        ], 200);
    }


}

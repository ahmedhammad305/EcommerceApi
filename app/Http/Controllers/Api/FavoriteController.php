<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
         $user = $request->user();
        $favorites = $user->favorites()->with('product','user')->get();
        if ($favorites->isEmpty()) {
            return response()->json(['message' => 'No favorite products found'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Favorite products retrieved successfully',
            'favorites' => $favorites
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
          $data = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = $request->user();
        $existingFavorite = $user->favorites()->where('product_id', $data['product_id'])->first();
        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already in favorites'
            ], 400);
        }

        $favorite = $user->favorites()->create([
            'product_id' => $data['product_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to favorites successfully',
            'favorite' => $favorite
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request , string $id)
    {
        $user = $request->user();
        $favorite = $user->favorites()->where('product_id', $id)->first();
        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found in favorites'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from favorites successfully'
        ], 200);
    }
}

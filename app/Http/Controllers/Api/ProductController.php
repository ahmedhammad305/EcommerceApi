<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $products = Product::onlyTrashed()->get();
        $products = Product::with('category')->paginate(10);
        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'products' => $products
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        //validate request
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'is_active' => $data['is_active'] ?? true,
            'category_id' => $data['category_id'],
            'image' => $data['image'] ?? null,
        ]);

        return response()->json([

            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::find($id)->with('category')->first();
        if (!$product) {
            return response()->json(['message' => 'Prdoduct not found'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'product' => $product
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
  {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        //validate request
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'stock' => 'sometimes|required|integer',
            'is_active' => 'sometimes|boolean',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            //delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }



        $product->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product
        ], 201);
    }

    public function Products_by_Category($Category_id)
    {
        $product = Product::where('category_id', $Category_id)->with('category')->get();
        if ($product->isEmpty()) {
            return response()->json(['message' => 'No products found for this category'], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'products' => $product
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
            'product' => $product
        ], 200);
    }
    // undo delete
    public function restore($id)
    {
        $product = Product::withTrashed()->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        if ($product->trashed()) {
            $product->restore();
            return response()->json([
                'success' => true,
                'message' => 'Product restored successfully',
                'product' => $product
            ], 200);
        } else {
            return response()->json(['message' => 'Product is not deleted'], 400);
        }
    }
    public function forceDelete($id)
    {
        $product = Product::withTrashed()->find($id);
        if(!$product){
            return response()->json(['message' =>'Product not found'],404);
        }
        if($product->trashed()){
            $product->forceDelete();
            return response()->json([
                'success' => true,
                'message' => 'Product deleted permanently',
            ],200);

    } else {
            return response()->json(['message' => 'Product is not deleted'], 400);
        }
}

        public function filter(Request $request)
{
    $products = Product::query()
        ->when($request->price_min, fn($query) =>
            $query->where('price', '>=', $request->price_min)
        )
        ->when($request->price_max, fn($query) =>
            $query->where('price', '<=', $request->price_max)
        )
        ->when($request->q, function($query) use ($request){
            $query->where(fn($query) =>
                $query->where('name', 'like', "%{$request->q}%")
                      ->orWhere('description', 'like', "%{$request->q}%")
            );
        })
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Products retrieved successfully',
        'data'    => $products
    ], 200);
}

}

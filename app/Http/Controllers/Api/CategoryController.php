<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $category = Category::all();
        if ($category->isEmpty()) {
            return response()->json([
                'success' => false,
                'Message' => 'No Category Found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'Message' => 'Category Retrieved successfully',
            'category' => $category,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //request validation
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
        ]);
        $category = Category::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => $data['is_active'],
        ]);
        return response()->json([
            'success' => true,
            'Message' => 'Category Created successfully',
            'category' => $category,
        ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // find category by id
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'Message' => 'Category Not Found',
            ], 404);
    }
        return response()->json([
            'success' => true,
            'Message' => 'Category Retrieved successfully',
            'category' => $category,
        ], 200);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $category = Category::find($id);
        if (!$category) {
            return response()->json([
                'success' => false,
                'Message' => 'Category Not Found',
            ], 404);
        }
        //request validation
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($data);
        return response()->json([
            'success' => true,
            'Message' => 'Category Updated successfully',
            'category' => $category,
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        if(!$category){
            return response()->json([
                'success' => false,
                'Message' => 'Category Not Found',
            ], 404);

        }
        $category->delete();
        return response()->json([
            'success' => true,
            'Message' => 'Category Deleted successfully',
        ], 200);

}
      public function restore($id)
    {
        $product = Category::withTrashed()->find($id);
        if (!$product) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        if ($product->trashed()) {
            $product->restore();
            return response()->json([
                'success' => true,
                'message' => 'Category restored successfully',
                'product' => $product
            ], 200);
        } else {
            return response()->json(['message' => 'Category is not deleted'], 400);
        }
    }
    public function forceDelete($id)
    {
        $product = Category::withTrashed()->find($id);
        if(!$product){
            return response()->json(['message' =>'Category not found'],404);
        }
        if($product->trashed()){
            $product->forceDelete();
            return response()->json([
                'success' => true,
                'message' => 'Category deleted permanently',
            ],200);

    } else {
            return response()->json(['message' => 'Category is not deleted'], 400);
        }
}
}

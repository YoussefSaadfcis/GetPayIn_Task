<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show($id)
    {
        //cached product will be deleted if observer detect any update on that product
        $product = Cache::remember('products/' . $id, 60 * 1, function () use ($id) {
            return Product::find($id);
        });
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        return response()->json($product,200);
    }
}

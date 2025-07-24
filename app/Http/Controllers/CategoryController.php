<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * @group Categories
 * 
 * API endpoints for managing product categories
 */
class CategoryController extends Controller
{
    /**
     * Get all categories
     * 
     * Retrieve a list of all available product categories.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Categories retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Coffee Beans",
     *       "description": "Premium coffee beans from around the world",
     *       "created_at": "2024-01-15T10:00:00.000000Z",
     *       "updated_at": "2024-01-15T10:00:00.000000Z",
     *       "products_count": 5
     *     },
     *     {
     *       "id": 2,
     *       "name": "Ready-to-Drink",
     *       "description": "Cold brew, iced coffee, and ready-to-drink beverages",
     *       "created_at": "2024-01-15T10:00:00.000000Z",
     *       "updated_at": "2024-01-15T10:00:00.000000Z",
     *       "products_count": 4
     *     }
     *   ]
     * }
     */
    public function index()
    {
        $categories = Category::withCount('products')->get();
        
        return Response::success($categories, 'Categories retrieved successfully');
    }

    /**
     * Get single category
     * 
     * Retrieve details of a specific category with its products.
     * 
     * @urlParam id integer required The ID of the category. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Category retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Coffee Beans",
     *     "description": "Premium coffee beans from around the world",
     *     "created_at": "2024-01-15T10:00:00.000000Z",
     *     "updated_at": "2024-01-15T10:00:00.000000Z",
     *     "products": [
     *       {
     *         "id": 1,
     *         "name": "Ethiopian Light Roast",
     *         "description": "Bright and fruity single-origin beans.",
     *         "image_url": "Ethiopian Light Roast.jpg",
     *         "category_id": 1,
     *         "created_at": "2024-01-15T10:00:00.000000Z",
     *         "updated_at": "2024-01-15T10:00:00.000000Z",
     *         "image_full_url": "http://localhost/storage/products/Ethiopian Light Roast.jpg"
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Category not found"
     * }
     */
    public function show($id)
    {
        $category = Category::with('products')->find($id);
        
        if (!$category) {
            return Response::notFound('Category not found');
        }
        
        return Response::success($category, 'Category retrieved successfully');
    }
}

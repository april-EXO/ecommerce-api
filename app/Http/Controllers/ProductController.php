<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

/**
 * @group Products
 * 
 * API endpoints for managing products
 */
class ProductController extends Controller
{
    /**
     * Get products list
     * 
     * Retrieve a paginated list of products with advanced filtering and sorting options.
     * 
     * @queryParam country string Optional country code (MY or SG) to filter products by pricing availability. Example: MY
     * @queryParam per_page integer Number of products per page. Default: 15. Example: 10
     * @queryParam category_id integer Optional category ID to filter products by category. Example: 1
     * @queryParam price_from decimal Optional minimum price filter (in selected country currency). Example: 10.00
     * @queryParam price_to decimal Optional maximum price filter (in selected country currency). Example: 50.00
     * @queryParam search string Optional search term to filter products by name or description. Example: Ethiopian
     * @queryParam sort_by string Optional sorting field. Options: name, price. Default: name. Example: price
     * @queryParam sort_order string Optional sorting order. Options: asc, desc. Default: asc. Example: desc
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Products retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Ethiopian Light Roast",
     *       "description": "Bright and fruity single-origin beans.",
     *       "image_url": "ethiopian-light.jpg",
     *       "category_id": 1,
     *       "created_at": "2024-01-15T10:00:00.000000Z",
     *       "updated_at": "2024-01-15T10:00:00.000000Z",
     *       "image_full_url": "http://localhost/storage/products/ethiopian-light.jpg",
     *       "category": {
     *         "id": 1,
     *         "name": "Coffee Beans",
     *         "description": "Premium coffee beans from around the world"
     *       },
     *       "prices": [
     *         {
     *           "id": 1,
     *           "product_id": 1,
     *           "country_code": "MY",
     *           "price": "35.00",
     *           "country": {
     *             "code": "MY",
     *             "name": "Malaysia",
     *             "currency_code": "MYR"
     *           }
     *         }
     *       ]
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 1,
     *     "per_page": 15,
     *     "total": 3
     *   },
     *   "meta": {
     *     "filtered_country": "MY",
     *     "country_source": "session",
     *     "filters": {
     *       "category_id": 1,
     *       "price_from": 10.00,
     *       "price_to": 50.00,
     *       "search": "Ethiopian"
     *     },
     *     "sorting": {
     *       "sort_by": "price",
     *       "sort_order": "asc"
     *     }
     *   }
     * }
     */
    public function index(Request $request)
    {
        try {
            // 直接从请求获取国家代码，前端必须提供
            $selectedCountry = $request->get('country', 'MY');
            
            // 验证国家代码
            if (!in_array($selectedCountry, ['MY', 'SG'])) {
                $selectedCountry = 'MY';
            }
            
            $query = Product::with('category');
            
            // Apply filters
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->get('search') . '%');
            }
            
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }
            
            // Price range filter (requires country-specific prices)
            if ($request->filled('price_from') || $request->filled('price_to')) {
                $query->whereHas('prices', function ($q) use ($request, $selectedCountry) {
                    $q->where('country_code', $selectedCountry);
                    
                    if ($request->filled('price_from')) {
                        $q->where('price', '>=', $request->get('price_from'));
                    }
                    
                    if ($request->filled('price_to')) {
                        $q->where('price', '<=', $request->get('price_to'));
                    }
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if ($sortBy === 'name') {
                $query->orderBy('name', $sortOrder);
            } elseif ($sortBy === 'price') {
                // Price sorting using subquery for the selected country
                $query->orderBy(
                    DB::table('product_prices')
                        ->select('price')
                        ->whereColumn('product_prices.product_id', 'products.id')
                        ->where('product_prices.country_code', $selectedCountry)
                        ->limit(1),
                    $sortOrder
                );
            }
            
            // Load prices relationship for the selected country
            $query->with(['prices' => function ($q) use ($selectedCountry) {
                $q->where('country_code', $selectedCountry)->with('country');
            }]);
            
            $products = $query->paginate(12);
            
            return Response::paginated($products, 'Products retrieved successfully', [
                'selected_country' => $selectedCountry,
                'filters' => [
                    'search' => $request->get('search'),
                    'category_id' => $request->get('category_id'),
                    'price_from' => $request->get('price_from'),
                    'price_to' => $request->get('price_to'),
                ],
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ]
            ]);
            
        } catch (\Exception $e) {
            return Response::serverError('Failed to retrieve products: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific product
     * @group Products
     * @urlParam id integer required The product ID
     * @queryParam country string The country code (MY, SG). Example: MY
     */
    public function show($id, Request $request)
    {
        try {
            // 直接从请求获取国家代码
            $selectedCountry = $request->get('country', 'MY');
            
            // 验证国家代码
            if (!in_array($selectedCountry, ['MY', 'SG'])) {
                $selectedCountry = 'MY';
            }
            
            $product = Product::with('category')
                ->with(['prices' => function ($q) use ($selectedCountry) {
                    $q->where('country_code', $selectedCountry)->with('country');
                }])
                ->find($id);
                
            if (!$product) {
                return Response::notFound('Product not found');
            }
            
            return Response::success($product, 'Product retrieved successfully', [
                'selected_country' => $selectedCountry
            ]);
            
        } catch (\Exception $e) {
            return Response::serverError('Failed to retrieve product: ' . $e->getMessage());
        }
    }

    /**
     * Get products by country
     * 
     * Retrieve products available in a specific country.
     * 
     * @urlParam countryCode string required The country code (MY or SG). Example: SG
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Products for SG retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Ethiopian Light Roast",
     *       "description": "Bright and fruity single-origin beans.",
     *       "image_url": "ethiopian-light.jpg",
     *       "category_id": 1,
     *       "created_at": "2024-01-15T10:00:00.000000Z",
     *       "updated_at": "2024-01-15T10:00:00.000000Z",
     *       "image_full_url": "http://localhost/storage/products/ethiopian-light.jpg",
     *       "category": {
     *         "id": 1,
     *         "name": "Coffee Beans",
     *         "description": "Premium coffee beans from around the world"
     *       },
     *       "prices": [
     *         {
     *           "id": 2,
     *           "product_id": 1,
     *           "country_code": "SG",
     *           "price": "15.00",
     *           "country": {
     *             "code": "SG",
     *             "name": "Singapore",
     *             "currency_code": "SGD"
     *           }
     *         }
     *       ]
     *     }
     *   ]
     * }
     */
    public function getProductsByCountry($countryCode)
    {
        if (!in_array(strtoupper($countryCode), ['MY', 'SG'])) {
            return Response::error('Invalid country code. Only MY and SG are supported.', null, 400);
        }

        $countryCode = strtoupper($countryCode);

        $products = Product::whereHas('prices', function($query) use ($countryCode) {
            $query->where('country_code', $countryCode);
        })
        ->with(['prices' => function($query) use ($countryCode) {
            $query->where('country_code', $countryCode)->with('country');
        }, 'category'])
        ->get();

        return Response::success($products, "Products for {$countryCode} retrieved successfully");
    }
}

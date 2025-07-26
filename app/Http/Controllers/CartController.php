<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @group Shopping Cart
 * 
 * API endpoints for managing shopping cart operations
 */
class CartController extends Controller
{
    /**
     * Get cart contents
     * 
     * Retrieve the current user's shopping cart with all items and pricing for the specified country.
     * 
     * @authenticated
     * 
     * @queryParam country string The country code for pricing (MY or SG). Default: MY. Example: SG
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Cart retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "country_code": "MY",
     *     "total_quantity": 3,
     *     "total_price": 105.00,
     *     "cart_items": [
     *       {
     *         "id": 1,
     *         "quantity": 2,
     *         "total_price": 70.00,
     *         "formatted_total_price": "RM 70.00",
     *         "product": {
     *           "id": 1,
     *           "name": "Ethiopian Light Roast",
     *           "description": "Bright and fruity single-origin beans.",
     *           "image_full_url": "http://localhost/storage/products/ethiopian-light.jpg",
     *           "category": {
     *             "id": 1,
     *             "name": "Coffee Beans"
     *           },
     *           "current_price": {
     *             "price": "35.00",
     *             "currency_code": "MYR"
     *           }
     *         }
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Login required"
     * }
     */
    public function index(Request $request)
    {
        try {
            $countryCode = $request->get('country', 'MY');
            
            if (!in_array($countryCode, ['MY', 'SG'])) {
                $countryCode = 'MY';
            }

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $cart = Cart::getOrCreateCart($user->id);
            $cart->load(['cartItemsWithDetails']);

            // Calculate totals for current country
            $totalQuantity = $cart->cartItems->sum('quantity');
            $totalPrice = $cart->cartItems->sum(function ($item) use ($countryCode) {
                return $item->getTotalPriceForCountry($countryCode);
            });

            return response()->success([
                'id' => $cart->id,
                'country_code' => $countryCode,
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'cart_items' => $cart->cartItemsWithDetails->map(function ($item) use ($countryCode) {
                    $currentPrice = $item->getPriceForCountry($countryCode);
                    return [
                        'id' => $item->id,
                        'quantity' => $item->quantity,
                        'total_price' => $item->getTotalPriceForCountry($countryCode),
                        'formatted_total_price' => $item->getFormattedTotalPriceForCountry($countryCode),
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'description' => $item->product->description,
                            'image_full_url' => $item->product->image_full_url,
                            'category' => $item->product->category,
                            'current_price' => $currentPrice
                        ]
                    ];
                })
            ], 'Cart retrieved successfully');

        } catch (\Exception $e) {
            return response()->serverError('Failed to retrieve cart: ' . $e->getMessage());
        }
    }

    /**
     * Add item to cart
     * 
     * Add a product to the shopping cart or update quantity if item already exists.
     * 
     * @authenticated
     * 
     * @bodyParam product_id integer required The ID of the product to add. Example: 1
     * @bodyParam quantity integer The quantity to add (1-99). Default: 1. Example: 2
     * @bodyParam country string The country code for pricing validation (MY or SG). Default: MY. Example: SG
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Item added to cart successfully",
     *   "data": {
     *     "cart_item": {
     *       "id": 1,
     *       "quantity": 2,
     *       "product_id": 1
     *     },
     *     "cart_summary": {
     *       "total_quantity": 3,
     *       "total_price": 105.00
     *     }
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Product not available in SG"
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "product_id": ["The selected product id is invalid."]
     *   }
     * }
     */
    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'integer|min:1|max:99',
                'country' => 'string|in:MY,SG'
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $productId = $request->product_id;
            $quantity = $request->get('quantity', 1);
            $countryCode = $request->get('country', 'MY');

            // Check if product has price for the country
            $product = Product::with(['prices' => function ($query) use ($countryCode) {
                $query->where('country_code', $countryCode);
            }])->find($productId);

            if (!$product || $product->prices->isEmpty()) {
                return response()->json(['success' => false, 'message' => "Product not available in {$countryCode}"], 400);
            }

            $cart = Cart::getOrCreateCart($user->id);
            $cartItem = CartItem::addOrUpdate($cart->id, $productId, $quantity);
            
            // Calculate totals for current country
            $cart->load(['cartItems']);
            $totalQuantity = $cart->cartItems->sum('quantity');
            $totalPrice = $cart->cartItems->sum(function ($item) use ($countryCode) {
                return $item->getTotalPriceForCountry($countryCode);
            });

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'product_id' => $cartItem->product_id
                    ],
                    'cart_summary' => [
                        'total_quantity' => $totalQuantity,
                        'total_price' => $totalPrice
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add item to cart: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update cart item quantity
     * 
     * Update the quantity of a specific cart item.
     * 
     * @authenticated
     * 
     * @urlParam itemId integer required The ID of the cart item to update. Example: 1
     * @bodyParam quantity integer required The new quantity (1-99). Example: 3
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Cart item updated successfully",
     *   "data": {
     *     "cart_item": {
     *       "id": 1,
     *       "quantity": 3,
     *       "total_price": 105.00
     *     },
     *     "cart_summary": {
     *       "total_quantity": 5,
     *       "total_price": 175.00
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Cart item not found"
     * }
     */
    public function updateItem(Request $request, $itemId)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1|max:99'
            ]);

            $cartItem = $this->findCartItem($request, $itemId);
            if (!$cartItem) {
                return response()->notFound('Cart item not found');
            }

            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            // Refresh cart totals
            $cartItem->cart->refresh();

            return response()->success([
                'cart_item' => [
                    'id' => $cartItem->id,
                    'quantity' => $cartItem->quantity,
                    'total_price' => $cartItem->total_price
                ],
                'cart_summary' => [
                    'total_quantity' => $cartItem->cart->total_quantity,
                    'total_price' => $cartItem->cart->total_price
                ]
            ], 'Cart item updated successfully');

        } catch (\Exception $e) {
            return response()->serverError('Failed to update cart item: ' . $e->getMessage());
        }
    }

    /**
     * Remove item from cart
     * 
     * Remove a specific item from the shopping cart.
     * 
     * @authenticated
     * 
     * @urlParam itemId integer required The ID of the cart item to remove. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Item removed from cart successfully",
     *   "data": {
     *     "cart_summary": {
     *       "total_quantity": 2,
     *       "total_price": 70.00
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Cart item not found"
     * }
     */
    public function removeItem(Request $request, $itemId)
    {
        try {
            $cartItem = $this->findCartItem($request, $itemId);
            if (!$cartItem) {
                return response()->notFound('Cart item not found');
            }

            $cart = $cartItem->cart;
            $cartItem->delete();

            // Refresh cart totals
            $cart->refresh();

            return response()->success([
                'cart_summary' => [
                    'total_quantity' => $cart->total_quantity,
                    'total_price' => $cart->total_price
                ]
            ], 'Item removed from cart successfully');

        } catch (\Exception $e) {
            return response()->serverError('Failed to remove cart item: ' . $e->getMessage());
        }
    }

    /**
     * Clear cart
     * 
     * Remove all items from the shopping cart.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Cart cleared successfully",
     *   "data": {
     *     "cart_summary": {
     *       "total_quantity": 0,
     *       "total_price": 0
     *     }
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Cart not found"
     * }
     */
    public function clear(Request $request)
    {
        try {
            $cart = $this->getCurrentCart($request);
            if (!$cart) {
                return response()->notFound('Cart not found');
            }

            $cart->cartItems()->delete();

            return response()->success([
                'cart_summary' => [
                    'total_quantity' => 0,
                    'total_price' => 0
                ]
            ], 'Cart cleared successfully');

        } catch (\Exception $e) {
            return response()->serverError('Failed to clear cart: ' . $e->getMessage());
        }
    }

    /**
     * Get cart count (for header display)
     * 
     * Get the total number of items in the user's cart for display purposes.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Cart count retrieved successfully",
     *   "data": {
     *     "count": 3
     *   }
     * }
     */
    public function count(Request $request)
    {
        try {
            $cart = $this->getCurrentCart($request);
            $count = $cart ? $cart->total_quantity : 0;

            return response()->success([
                'count' => $count
            ], 'Cart count retrieved successfully');

        } catch (\Exception $e) {
            return response()->serverError('Failed to get cart count: ' . $e->getMessage());
        }
    }



    /**
     * Get current cart for authenticated user
     */
    private function getCurrentCart(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }
        
        return Cart::where('user_id', $user->id)->first();
    }

    /**
     * Find cart item that belongs to current user
     */
    private function findCartItem(Request $request, $itemId)
    {
        $cart = $this->getCurrentCart($request);
        if (!$cart) return null;

        return $cart->cartItems()->find($itemId);
    }
}

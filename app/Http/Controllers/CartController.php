<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get cart contents
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

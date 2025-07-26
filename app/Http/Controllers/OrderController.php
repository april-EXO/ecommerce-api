<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\User;

class OrderController extends Controller
{
    /**
     * Get user's orders list
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $perPage = $request->get('per_page', 10);
            $status = $request->get('status');
            $includeDeleted = $request->get('include_deleted', false);

            $query = Order::where('user_id', $user->id)
                ->with(['orderItemsWithDetails', 'country'])
                ->orderBy('created_at', 'desc');

            // Include soft deleted orders if requested
            if ($includeDeleted) {
                $query->withTrashed();
            }

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->paginate($perPage);

            $orders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => Order::getStatuses()[$order->status] ?? $order->status,
                    'status_display' => $order->status_display,
                    'total_price' => $order->total_price,
                    'formatted_total_price' => $order->formatted_total_price,
                    'total_quantity' => $order->total_quantity,
                    'country_code' => $order->country_code,
                    'country_name' => $order->country->name ?? null,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'deleted_at' => $order->deleted_at,
                    'can_be_cancelled' => $order->canBeCancelled(),
                    'can_be_deleted' => !$order->trashed() && in_array($order->status, ['cancelled', 'refunded']),
                    'is_deleted' => $order->isDeleted(),
                    'order_items_count' => $order->orderItems->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve orders: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get specific order details
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->with(['orderItemsWithDetails.product.category', 'country'])
                ->withTrashed()
                ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order retrieved successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => Order::getStatuses()[$order->status] ?? $order->status,
                    'status_display' => $order->status_display,
                    'total_price' => $order->total_price,
                    'formatted_total_price' => $order->formatted_total_price,
                    'total_quantity' => $order->total_quantity,
                    'country_code' => $order->country_code,
                    'country_name' => $order->country->name ?? null,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'deleted_at' => $order->deleted_at,
                    'can_be_cancelled' => $order->canBeCancelled(),
                    'can_be_deleted' => !$order->trashed() && in_array($order->status, ['cancelled', 'refunded']),
                    'is_completed' => $order->isCompleted(),
                    'is_cancelled' => $order->isCancelled(),
                    'is_deleted' => $order->isDeleted(),
                    'order_items' => $order->orderItemsWithDetails->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'unit_price' => $item->unit_price,
                            'formatted_unit_price' => $item->formatted_unit_price,
                            'quantity' => $item->quantity,
                            'subtotal' => $item->subtotal,
                            'formatted_subtotal' => $item->formatted_subtotal,
                            'currency_code' => $item->currency_code,
                            'product' => $item->product ? [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'image_full_url' => $item->product->image_full_url,
                                'category' => $item->product->category
                            ] : null
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retrieve order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create order from cart (checkout)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'country' => 'required|string|in:MY,SG',
                'shipping_address' => 'required|array',
                'shipping_address.name' => 'required|string|max:255',
                'shipping_address.phone' => 'required|string|max:20',
                'shipping_address.address' => 'required|string|max:1000',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $countryCode = $request->country;

            // Get user's cart
            $cart = Cart::where('user_id', $user->id)
                ->with(['cartItems.product'])
                ->first();

            if (!$cart || $cart->cartItems->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Cart is empty'], 400);
            }

            // Validate all products have prices for the selected country
            foreach ($cart->cartItems as $cartItem) {
                $price = $cartItem->getPriceForCountry($countryCode);
                if (!$price) {
                    return response()->json([
                        'success' => false, 
                        'message' => "Product '{$cartItem->product->name}' is not available in {$countryCode}"
                    ], 400);
                }
            }

            // Create order from cart
            $shippingAddress = $request->shipping_address;
            $order = Order::createFromCart($cart, $countryCode, $shippingAddress);

            // Clear the cart after successful order creation
            $cart->cartItems()->delete();

            // Load order with details for response
            $order->load(['orderItemsWithDetails.product', 'country']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => Order::getStatuses()[$order->status] ?? $order->status,
                    'total_price' => $order->total_price,
                    'formatted_total_price' => $order->formatted_total_price,
                    'total_quantity' => $order->total_quantity,
                    'country_code' => $order->country_code,
                    'created_at' => $order->created_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            if (!$order->canBeCancelled()) {
                return response()->json(['success' => false, 'message' => 'Order cannot be cancelled'], 400);
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => Order::getStatuses()[$order->status]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get order statuses (for dropdowns, etc.)
     */
    public function statuses()
    {
        return response()->json([
            'success' => true,
            'message' => 'Order statuses retrieved successfully',
            'data' => Order::getStatuses()
        ]);
    }

    /**
     * Soft delete an order
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Login required'], 401);
            }

            $order = Order::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            // Only allow deletion of cancelled or refunded orders
            if (!in_array($order->status, ['cancelled', 'refunded'])) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Only cancelled or refunded orders can be deleted'
                ], 422);
            }

            if ($order->trashed()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Order is already deleted'
                ], 422);
            }

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'deleted_at' => $order->deleted_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\User;

/**
 * @group Orders
 * 
 * API endpoints for managing customer orders
 */
class OrderController extends Controller
{
    /**
     * Get user's orders list
     * 
     * Retrieve a paginated list of orders for the authenticated user with filtering options.
     * 
     * @authenticated
     * 
     * @queryParam per_page integer Number of orders per page. Default: 10. Example: 15
     * @queryParam status string Filter orders by status. Options: pending, processing, shipped, delivered, cancelled, refunded. Example: pending
     * @queryParam include_deleted boolean Include soft-deleted orders. Default: false. Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Orders retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "order_number": "ORD-20240115-0001",
     *       "status": "pending",
     *       "status_label": "Pending",
     *       "status_display": "Pending",
     *       "total_price": 105.00,
     *       "formatted_total_price": "RM 105.00",
     *       "total_quantity": 3,
     *       "country_code": "MY",
     *       "country_name": "Malaysia",
     *       "created_at": "2024-01-15T10:00:00.000000Z",
     *       "updated_at": "2024-01-15T10:00:00.000000Z",
     *       "deleted_at": null,
     *       "can_be_cancelled": true,
     *       "can_be_deleted": false,
     *       "is_deleted": false,
     *       "order_items_count": 2
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 1,
     *     "per_page": 10,
     *     "total": 1,
     *     "from": 1,
     *     "to": 1
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
     * 
     * Retrieve detailed information about a specific order including all order items.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the order. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Order retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "order_number": "ORD-20240115-0001",
     *     "status": "pending",
     *     "status_label": "Pending",
     *     "status_display": "Pending",
     *     "total_price": 105.00,
     *     "formatted_total_price": "RM 105.00",
     *     "total_quantity": 3,
     *     "country_code": "MY",
     *     "country_name": "Malaysia",
     *     "created_at": "2024-01-15T10:00:00.000000Z",
     *     "updated_at": "2024-01-15T10:00:00.000000Z",
     *     "deleted_at": null,
     *     "can_be_cancelled": true,
     *     "can_be_deleted": false,
     *     "is_completed": false,
     *     "is_cancelled": false,
     *     "is_deleted": false,
     *     "order_items": [
     *       {
     *         "id": 1,
     *         "product_id": 1,
     *         "product_name": "Ethiopian Light Roast",
     *         "unit_price": 35.00,
     *         "formatted_unit_price": "RM 35.00",
     *         "quantity": 2,
     *         "subtotal": 70.00,
     *         "formatted_subtotal": "RM 70.00",
     *         "currency_code": "MYR",
     *         "product": {
     *           "id": 1,
     *           "name": "Ethiopian Light Roast",
     *           "image_full_url": "http://localhost/storage/products/ethiopian-light.jpg",
     *           "category": {
     *             "id": 1,
     *             "name": "Coffee Beans"
     *           }
     *         }
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Order not found"
     * }
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
     * 
     * Create a new order from the user's current shopping cart.
     * 
     * @authenticated
     * 
     * @bodyParam country string required The country code for pricing (MY or SG). Example: MY
     * @bodyParam shipping_address object required The shipping address details.
     * @bodyParam shipping_address.name string required Recipient's name. Example: John Doe
     * @bodyParam shipping_address.phone string required Recipient's phone number. Example: +60123456789
     * @bodyParam shipping_address.address string required Full shipping address. Example: 123 Main Street, Kuala Lumpur 50000, Malaysia
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Order created successfully",
     *   "data": {
     *     "id": 1,
     *     "order_number": "ORD-20240115-0001",
     *     "status": "pending",
     *     "status_label": "Pending",
     *     "total_price": 105.00,
     *     "formatted_total_price": "RM 105.00",
     *     "total_quantity": 3,
     *     "country_code": "MY",
     *     "created_at": "2024-01-15T10:00:00.000000Z"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Cart is empty"
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "country": ["The country field is required."]
     *   }
     * }
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
     * 
     * Cancel a pending or processing order.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the order to cancel. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Order cancelled successfully",
     *   "data": {
     *     "id": 1,
     *     "order_number": "ORD-20240115-0001",
     *     "status": "cancelled",
     *     "status_label": "Cancelled"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Order cannot be cancelled"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Order not found"
     * }
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
     * 
     * Retrieve all available order statuses for filtering and display purposes.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Order statuses retrieved successfully",
     *   "data": {
     *     "pending": "Pending",
     *     "processing": "Processing",
     *     "shipped": "Shipped",
     *     "delivered": "Delivered",
     *     "cancelled": "Cancelled",
     *     "refunded": "Refunded"
     *   }
     * }
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
     * 
     * Delete an order (only cancelled or refunded orders can be deleted).
     * 
     * @authenticated
     * 
     * @urlParam id integer required The ID of the order to delete. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Order deleted successfully",
     *   "data": {
     *     "id": 1,
     *     "order_number": "ORD-20240115-0001",
     *     "deleted_at": "2024-01-15T11:00:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Only cancelled or refunded orders can be deleted"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Order not found"
     * }
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

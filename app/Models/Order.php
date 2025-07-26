<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'country_code',
        'total_price',
        'status',
        'order_number',
        'shipping_address'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_price' => 'decimal:2',
        'shipping_address' => 'array',
    ];

    // Order status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    /**
     * Get the user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country for this order
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Get the order items for this order
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the order items with product details
     */
    public function orderItemsWithDetails()
    {
        return $this->orderItems()->with(['product']);
    }

    /**
     * Get total quantity of items in order
     */
    public function getTotalQuantityAttribute()
    {
        return $this->orderItems->sum('quantity');
    }

    /**
     * Get formatted total price with currency
     */
    public function getFormattedTotalPriceAttribute()
    {
        $currency = $this->country_code === 'MY' ? 'RM' : 'S$';
        return "{$currency} " . number_format($this->total_price, 2);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Create order from cart
     */
    public static function createFromCart(Cart $cart, $countryCode, $shippingAddress = null)
    {
        // Calculate total price for the country
        $totalPrice = $cart->cartItems->sum(function ($item) use ($countryCode) {
            return $item->getTotalPriceForCountry($countryCode);
        });

        // Create order
        $order = self::create([
            'user_id' => $cart->user_id,
            'country_code' => $countryCode,
            'total_price' => $totalPrice,
            'status' => self::STATUS_PENDING,
            'order_number' => self::generateOrderNumber(),
            'shipping_address' => $shippingAddress
        ]);

        // Create order items from cart items
        foreach ($cart->cartItems as $cartItem) {
            $price = $cartItem->getPriceForCountry($countryCode);
            if ($price) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'currency_code' => $price->country->currency_code,
                    'unit_price' => $price->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $price->price * $cartItem->quantity
                ]);
            }
        }

        return $order;
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled()
    {
        return !$this->trashed() && in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED
        ]);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled()
    {
        return in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED
        ]);
    }

    /**
     * Check if order is soft deleted
     */
    public function isDeleted()
    {
        return $this->trashed();
    }

    /**
     * Get status display including soft delete status
     */
    public function getStatusDisplayAttribute()
    {
        if ($this->trashed()) {
            return 'Deleted';
        }
        
        return $this->status_label;
    }
}

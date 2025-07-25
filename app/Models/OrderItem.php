<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'currency_code',
        'unit_price',
        'quantity',
        'subtotal'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the order that owns the order item
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this order item
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted unit price with currency
     */
    public function getFormattedUnitPriceAttribute()
    {
        return $this->currency_code . ' ' . number_format($this->unit_price, 2);
    }

    /**
     * Get formatted subtotal with currency
     */
    public function getFormattedSubtotalAttribute()
    {
        return $this->currency_code . ' ' . number_format($this->subtotal, 2);
    }

    /**
     * Calculate subtotal (unit_price * quantity)
     */
    public function calculateSubtotal()
    {
        return $this->unit_price * $this->quantity;
    }

    /**
     * Update subtotal based on current unit_price and quantity
     */
    public function updateSubtotal()
    {
        $this->subtotal = $this->calculateSubtotal();
        return $this;
    }
}

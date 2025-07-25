<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * Get the user that owns the cart
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get cart items with products and prices
     */
    public function cartItemsWithDetails()
    {
        return $this->cartItems()
            ->with(['product.category', 'product.prices']);
    }

    /**
     * Get total quantity of items in cart
     */
    public function getTotalQuantityAttribute()
    {
        return $this->cartItems->sum('quantity');
    }

    /**
     * Get total price of cart
     */
    public function getTotalPriceAttribute()
    {
        return $this->cartItems->sum(function ($item) {
            $price = $item->product->prices->where('country_code', $this->country_code)->first();
            return $price ? $price->price * $item->quantity : 0;
        });
    }

    /**
     * Get or create cart for user
     */
    public static function getOrCreateCart($userId)
    {
        $cart = self::where('user_id', $userId)->first();

        if (!$cart) {
            $cart = self::create([
                'user_id' => $userId,
            ]);
        }

        return $cart;
    }

   
}

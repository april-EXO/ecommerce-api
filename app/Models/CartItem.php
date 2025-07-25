<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity'
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    /**
     * Get the cart that owns the cart item
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the price for this cart item based on country (will be determined by frontend)
     */
    public function getPriceForCountry($countryCode)
    {
        return $this->product->prices()->where('country_code', $countryCode)->first();
    }

    /**
     * Get total price for this cart item for specific country
     */
    public function getTotalPriceForCountry($countryCode)
    {
        $price = $this->getPriceForCountry($countryCode);
        return $price ? $price->price * $this->quantity : 0;
    }

    /**
     * Get formatted total price with currency for specific country
     */
    public function getFormattedTotalPriceForCountry($countryCode)
    {
        $totalPrice = $this->getTotalPriceForCountry($countryCode);
        $currency = $countryCode === 'MY' ? 'RM' : 'S$';
        return "{$currency} " . number_format($totalPrice, 2);
    }

    /**
     * Add or update cart item
     */
    public static function addOrUpdate($cartId, $productId, $quantity)
    {
        $existingItem = self::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            $existingItem->quantity += $quantity;
            $existingItem->save();
            return $existingItem;
        } else {
            return self::create([
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
    }
}

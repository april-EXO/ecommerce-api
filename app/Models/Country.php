<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'currency_code'
    ];

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class, 'country_code', 'code');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_prices', 'country_code', 'product_id', 'code', 'id')
                    ->withPivot('price');
    }
}

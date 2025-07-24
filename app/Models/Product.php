<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'image_url', 'category_id'
    ];

    protected $appends = ['image_full_url'];

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'product_prices', 'product_id', 'country_code', 'id', 'code')
                    ->withPivot('price')
                    ->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageFullUrlAttribute()
    {
        if (!$this->image_url) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }

        // If it starts with storage/, return the full URL
        if (str_starts_with($this->image_url, 'storage/')) {
            return url($this->image_url);
        }

        // If it's just the filename or relative path, build the full URL
        return url('storage/products/' . ltrim($this->image_url, '/'));
    }
}

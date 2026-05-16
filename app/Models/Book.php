<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
        use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'price',
        'stock_quantity',
        'description',
        'cover_image',
    ];

    protected $appends = ['reviewCount', 'averageRating'];

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

  
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

   
    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function userReview()
    {
        return $this->hasOne(Review::class)->where('user_id', auth()->id());
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}

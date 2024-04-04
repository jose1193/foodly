<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

     protected $fillable = [
        'business_uuid',
        'business_logo',
        'business_name',
        'business_email',
        'business_phone',
        'business_address',
        'business_zipcode',
        'business_city',
        'business_country',
        'business_website',
        'business_latitude',
        'business_longitude',
        'category_id',
        'user_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coverImages()
    {
        return $this->hasMany(BusinessCoverImage::class, 'business_id');
    }
}

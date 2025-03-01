<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class,'creator_id');
    }
    public function  comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function  likes()
    {
        return $this->hasMany(Like::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class,'blog_categories','blog_id','category_id');
    }
}

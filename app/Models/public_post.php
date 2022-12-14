<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   public_post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'body',
        'image',
    ];
    public function public_comments()
    {
        return $this->hasMany(public_comment::class);
    }
}

<?php

namespace App\Models;
use App\Models\user;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class volunteer_campaign_request extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(user::class,'user_id');
    }
    public function volunteer_campaign()
    {
        return $this->hasOne(volunteer_campaign::class);
    }
    public function photo()
    {
        return $this->belongsTo(Photo::class,'photo_id');
    }
    public function location()
    {
        return $this->belongsTo(location::class,'location_id');
    }
}
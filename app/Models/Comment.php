<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function replies()
    {
    	return $this->hasMany(Reply::class);
    }
}

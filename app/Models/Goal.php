<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
    	'title', 'current_weight', 'user_id',
    	'target_weight', 'calorie_balance', 'deficit_weight',
    	'length', 'weekly_deficit', 'weekly_calorie_def', 'status'
    ];

    public function getLengthAttribute($val)
    {
        if ($val > 1) {
            return $val.' weeks';
        }
        
    	return $val.' week';
    }

    public  function setCurrentWeightAttribute($val)
    {
    	return $this->attributes['current_weight'] = (int) $val;
    }

    public  function setTargetWeightAttribute($val)
    {
    	return $this->attributes['target_weight'] = (int) $val;
    }

    public  function setWeeklyDeficitAttribute($val)
    {
        return $this->attributes['weekly_deficit'] = (float) $val;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

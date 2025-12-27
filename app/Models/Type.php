<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $fillable = ['name'];

    public function teacherLoads()
    {
        return $this->hasMany(TeacherLoad::class);
    }
    public function getTotalHoursAttribute()
    {
        return $this->teacherLoads->sum('hours_allocated');
    }
    public function getTotalHoursPerWeekAttribute()
    {
        return $this->teacherLoads->sum('hours_per_week');
    }
}

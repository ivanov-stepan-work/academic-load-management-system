<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyYear extends Model
{
    protected $fillable = ['start_year', 'end_year'];

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

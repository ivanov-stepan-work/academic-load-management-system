<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLoad extends Model
{
    protected $fillable = ['teacher_id', 'hours_allocated','study_year_id','half_year', 'department_load_id', 'type_id', 'subtype_id', ];

    public function teacher()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function subtype()
    {
        return $this->belongsTo(Subtype::class);
    }
    public function departmentLoad()
    {
        return $this->belongsTo(DepartmentLoad::class);
    }
    public function getWeeksCountAttribute()
    {
        return $this->half_year == 1 ? 17 : 23;
    }

    public function getHoursPerWeekAttribute()
    {
        $hoursPerWeek = $this->hours_allocated / $this->weeks_count;
        return round($hoursPerWeek, 1);
    }
}

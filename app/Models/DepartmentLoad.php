<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentLoad extends Model
{
    protected $fillable = ['group_id', 'subject_id', 'semester', 'hours_allocated', 'department_id','user_id'];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teacherLoads()
    {
        return $this->hasMany(TeacherLoad::class);
    }

    public function getHoursAllocatedToTeachersAttribute()
    {
        return $this->teacherLoads()->sum('hours_allocated');
    }

    public function getDifferenceBetweenDepartmentAndTeachersHoursAttribute()
    {
        $hoursAllocatedToTeachers = $this->getHoursAllocatedToTeachersAttribute();
        $difference = $this->hours_allocated - $hoursAllocatedToTeachers;
        return $difference;
    }
}

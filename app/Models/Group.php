<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'student_count', 'specialty_id'];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function departmentLoads()
    {
        return $this->hasMany(DepartmentLoad::class);
    }
}

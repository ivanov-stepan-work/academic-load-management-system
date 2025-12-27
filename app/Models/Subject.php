<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name'];

    public function departmentLoads()
    {
        return $this->hasMany(DepartmentLoad::class);
    }
}

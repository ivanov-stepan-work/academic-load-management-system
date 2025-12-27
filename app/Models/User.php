<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    protected $fillable = [
        'login',
        'password',
        'firstname',
        'lastname',
        'middlename',
        'email',
    ];

    public function department()
    {
        return $this->hasOne(Department::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function teacherStatuses()
    {
        return $this->belongsToMany(TeacherStatus::class, 'user_teacher_statuses');
    }
    public function teacherLoads()
    {
        return $this->belongsToMany(TeacherStatus::class, 'teacher_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function department_load()
    {
        return $this->hasMany(DepartmentLoad::class);
    }
    public function totalHours($userId = null)
    {
        $query = $this->department_load();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->sum('hours_allocated');
    }
}

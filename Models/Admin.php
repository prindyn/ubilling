<?php

namespace App\Models;

use App\Services\FileModel;
use App\Models\Passive\TaskmanEmployee;
use Illuminate\Database\Eloquent\Builder;

class Admin extends FileModel
{
    protected $path = 'content/users';

    public function employee()
    {
        return $this->hasOne(Employee::class, 'admlogin', 'username');
    }

    public function tasks()
    {
        return $this->hasManyThrough(Taskman::class, TaskmanEmployee::class, 'employee', 'id', 'id', 'task');
    }

    public function scopeTaskmanExtra(Builder $builder)
    {
        return array_values(
            $builder->with('employee')
                ->get(['id', 'username', 'employee'])
                // ->filter(function ($item) {
                //     return $item->employee == null || $item->employee->active;
                // })
                ->toArray()
        );
    }
}

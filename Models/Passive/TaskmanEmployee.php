<?php

namespace App\Models\Passive;

use App\Models\Employee;
use App\Models\Taskman;

class TaskmanEmployee extends BaseModel
{
    protected $table = 'taskmanemployee';

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Taskman::class, 'task', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee', 'id');
    }
}

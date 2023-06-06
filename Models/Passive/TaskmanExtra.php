<?php

namespace App\Models\Passive;

use App\Models\Taskman;

class TaskmanExtra extends BaseModel
{
    protected $table = 'taskman_extra';

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Taskman::class, 'task_id', 'id');
    }
}

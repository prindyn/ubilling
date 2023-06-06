<?php

namespace App\Models\Passive;

use App\Models\Taskman;

class TaskmanLog extends BaseModel
{
    protected $table = 'taskmanlogs';

    protected $guarded = [];

    public function task()
    {
        return $this->belongsTo(Taskman::class, 'taskid', 'id');
    }
}

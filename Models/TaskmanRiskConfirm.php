<?php

namespace App\Models;

use App\Models\Passive\BaseModel;

class TaskmanRiskConfirm extends BaseModel
{
    protected $table = 'taskman_risks_confirms';

    protected $guarded = ['gst_nick'];

    public function task()
    {
        return $this->belongsTo(Taskman::class, 'task', 'id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin', 'username');
    }

    public static function confirmed($task, $admin, $date = null)
    {
        $date = $date ? $date : curdate();
        $confirm = TaskmanRiskConfirm::where('task', $task)
            ->where('admin', $admin)
            ->where('created_at', 'like', "$date%")->first();

        return $confirm ? !empty($confirm->status) : false;
    }
}

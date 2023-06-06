<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Models\Passive\BaseModel;
use App\Models\Passive\TaskmanEmployee;
use App\Models\Passive\TaskmanExtra;
use App\Models\Passive\TaskmanLog;
use Illuminate\Database\Eloquent\Builder;

class Taskman extends BaseModel
{
    use Filterable;

    const DONE = 1;
    const UNDONE = 0;
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const IMAGES_SCOPE = 'TASKMAN';
    const STATUSES = [
        0 => 'undone',
        1 => 'done',
        2 => 'backlog',
        3 => 'qa',
    ];

    protected $table = 'taskman';

    protected $guarded = [
        'task', 'gst_nick', 'workers', 'extra'
    ];

    protected $dateAttrs = ['date', 'startdate', 'starttime', 'enddate'];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'id', 'employee');
    }

    public function employees()
    {
        return $this->hasMany(TaskmanEmployee::class, 'task', 'id');
    }

    public function extra()
    {
        return $this->hasMany(TaskmanExtra::class, 'task_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(TaskmanLog::class, 'taskid', 'id');
    }

    public function statuses()
    {
        return self::STATUSES;
    }

    public function workers()
    {
        $workers = [];

        if (isset($this->employees)) {
            $this->employees->map(function ($worker) use (&$workers) {
                $workers[] = $worker->employee;
            });
            unset($this->employees);
        }
        $this->workers = $workers;

        return $this;
    }

    public function images($images)
    {
        $taskImages = [];
        $images = is_array($images) ? collect($images) : $images;

        if ($images->all()) {
            $taskImages = $images->where('item', $this->id)->values();
        }
        $this->images = $taskImages;

        return $this;
    }

    public function clearEmptyDates()
    {
        foreach ($this->dateAttrs as $attr) {
            if ($this->$attr) {
                if ($this->$attr == '0000-00-00' || $this->$attr == '00:00:00') {
                    $this->$attr = null;
                }
            }
        }

        return $this;
    }

    public function scopeStatus(Builder $builder, $arguments)
    {
        if (!empty($arguments['id'])) {
            $task = $builder->where('id', $arguments['id'])->first();

            if ($task) {
                $update = [
                    'status' => isset($arguments['status']) ? (int) $arguments['status'] : $task->status,
                ];

                if (array_key_exists('startdate', $arguments)) {
                    $update['startdate'] = !empty($arguments['startdate']) ? $arguments['startdate'] : null;
                }

                if (array_key_exists('starttime', $arguments)) {
                    $update['starttime'] = !empty($arguments['starttime']) ? $arguments['starttime'] : null;
                }

                return $task->update($update);
            }
        }

        return false;
    }

    public function saveWorkers($workers, $id = null)
    {
        $workers = collect($workers);
        $id = $id ? $id : $this->id;

        $this->deleteWorkers($id);

        foreach ($workers as $worker) {
            TaskmanEmployee::create([
                'task' => $id, 'employee' => $worker
            ]);
        }

        return $this;
    }

    public function deleteWorkers($id = null)
    {
        $id = $id ? $id : $this->id;
        TaskmanEmployee::where('task', $id)->delete();

        return $this;
    }

    public function saveExtra($extras, $id = null)
    {
        $extras = collect($extras);
        $id = $id ? $id : $this->id;

        $this->deleteExtra($id);

        foreach ($extras as $extra) {
            TaskmanExtra::create(
                array_merge(['task_id' => $id], $extra)
            );
        }

        return $this;
    }

    public function deleteExtra($id = null)
    {
        $id = $id ? $id : $this->id;
        TaskmanExtra::where('task_id', $id)->delete();

        return $this;
    }

    public function saveLogs($logs = [], $id = null, $event = 'create')
    {
        $logs = $logs ? $logs : $this->toArray();

        if (!empty($logs)) {
            $id = $id ? $id : $this->id;
            try {
                TaskmanLog::create([
                    'taskid' => $id,
                    'date' => curdatetime(),
                    'admin' => whoami(),
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'event' => $event,
                    'logs' => serialize($logs),
                ]);
            } catch (\Exception $e) {
                //
            }
        }

        return $this;
    }

    public function deleteLogs($id = null)
    {
        $id = $id ? $id : $this->id;
        TaskmanLog::where('taskid', $id)->delete();

        return $this;
    }

    public static function titleFromUserData(User $user)
    {
        return $user->contract->contract . ' - ' . $user->addressExtended->address_exten;
    }

    public static function isEmployeeOwner($employee, $task)
    {
        if (!$task->employees->isEmpty()) {
            return !$task->employees->where('employee', $employee)->isEmpty();
        } else {
            return $task->employee == $employee;
        }
        
        return false;
    }
}

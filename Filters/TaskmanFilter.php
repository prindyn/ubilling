<?php

namespace App\Filters;

use App\Models\Taskman;
use Illuminate\Support\Carbon;
use App\Models\Passive\TaskmanEmployee;

class TaskmanFilter extends QueryFilter
{
    protected $staticFilters = [
        'endDateLessDays' => 14
    ];

    /**
     * @param string $search
     */
    public function search(string $search)
    {
        $this->builder->where(function ($query) use ($search) {
            $query->orWhere('title', 'like', "%$search%")
                ->orWhere('address', 'like', "%$search%")
                ->orWhere('login', 'like', "%$search%")
                ->orWhere('noi', 'like', "%$search%")
                ->orWhere('jobnote', 'like', "%$search%")
                ->orWhere('donenote', 'like', "%$search%")
                ->orWhere('admin', 'like', "%$search%");
        });
    }

    /**
     * @param int $status
     */
    // public function status(string $status)
    // {
    //     if (method_exists($this, $status)) {
    //         $this->$status();
    //     }
    // }

    public function backlog()
    {
        $this->builder->orWhere(function ($query) {
            $query->orWhere('startdate', "")
                ->orWhere('startdate', null);
        });
    }

    public function done()
    {
        $this->builder->orWhere('status', 1);
    }

    public function undone()
    {
        $this->builder->orWhere('status', 0);
    }

    /**
     * @param int $type
     */
    public function jobType(int $type)
    {
        $this->builder->where('jobtype', $type);
    }

    /**
     * @param int $id
     */
    public function project(int $id)
    {
        $this->builder->where('project_id', $id);
    }

    /**
     * @param mixed $ids
     */
    public function workers($ids)
    {
        if (is_serialized($ids)) {
            $ids = unserialize($ids);
        } elseif (is_json($ids)) {
            $ids = json_decode($ids);
        } elseif (!is_array($ids)) {
            $ids = array_map('trim', explode(',', $ids));
        }
        if (!is_array($ids)) $ids = [$ids];

        $this->builder->whereIn('id', function ($query) use ($ids) {
            $query->select('task')
                ->from(with(new TaskmanEmployee)->getTable())
                ->whereIn('employee', $ids);
        });
    }

    /**
     * @param mixed $ids
     */
    public function jobtypes($ids)
    {
        if (is_serialized($ids)) {
            $ids = unserialize($ids);
        } elseif (is_json($ids)) {
            $ids = json_decode($ids);
        } elseif (!is_array($ids)) {
            $ids = array_map('trim', explode(',', $ids));
        }
        if (!is_array($ids)) $ids = [$ids];

        $this->builder->whereIn('jobtype', $ids);
    }

    /**
     * @param string $startDate
     */
    public function startDate(string $startDate)
    {
        $this->builder
            ->where('startdate', '>', Carbon::parse($startDate)->format(Taskman::DATE_FORMAT));
    }

    /**
     * @param string $endDate
     */
    public function endDate(string $endDate)
    {
        $this->builder
            ->where('startdate', '<=', Carbon::parse($endDate)->addDay()->format(Taskman::DATE_FORMAT));
    }

    /**
     * @param string $days
     */
    public function endDateLessDays(int $days)
    {
        if (empty($this->request('search'))) {
            $this->builder->where(function ($query) use ($days) {
                $query->orWhere('enddate', '=', '')
                    ->orWhere('enddate', '=', null)
                    ->orWhere('enddate', '=', '0000-00-00')
                    ->orWhere('enddate', '>', Carbon::now()->subDays($days)->format(Taskman::DATE_FORMAT));
            });
        }
    }
}

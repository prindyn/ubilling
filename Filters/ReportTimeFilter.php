<?php

namespace App\Filters;

use App\Models\Taskman;
use Illuminate\Support\Carbon;

class ReportTimeFilter extends QueryFilter
{
    public $staticFilters = [
        // 'onlyForDays' => 5,
    ];

    /**
     * @param string $worker
     */
    public function worker($worker)
    {
        $this->builder->where('worker', ucfirst(strtolower($worker)));
    }

    /**
     * @param string $startDate
     */
    public function startDate(string $startDate)
    {
        $this->builder
            ->where('report_date', '>', Carbon::parse($startDate)->format(Taskman::DATE_FORMAT));
    }

    /**
     * @param string $endDate
     */
    public function endDate(string $endDate)
    {
        $this->builder
            ->where('report_date', '<=', Carbon::parse($endDate)->addDay()->format(Taskman::DATE_FORMAT));
    }

    /**
     * @param string $days
     */
    public function onlyForDays(int $days)
    {
        $this->builder->where(function ($query) use ($days) {
            $query->orWhere('report_date', '>', Carbon::now()->subDays($days)->format(Taskman::DATE_FORMAT));
        });
    }
}

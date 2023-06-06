<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Models\Passive\BaseModel;

class ReportTime extends BaseModel
{
    use Filterable;

    const IMAGES_SCOPE = 'REPORT_TIME';
    const STATUSES = [
        0 => 'In progress',
        1 => 'Done',
    ];

    protected $table = 'report_time';

    protected $guarded = [
        'task', 'gst_nick'
    ];

    public function extra($serialized = false)
    {
        if ($this->extra) {
            if (is_serialized($this->extra) || $serialized) {
                $this->extra = unserialize($this->extra);
            } else {
                $this->extra = serialize($this->extra);
            }
        }
        return $this;
    }

    public function materials($serialized = false)
    {
        if ($this->materials) {
            if (is_serialized($this->materials) || $serialized) {
                $this->materials = unserialize($this->materials);
            } else {
                $this->materials = serialize($this->materials);
            }
        }
        return $this;
    }

    public function images($images)
    {
        $reportImages = [];
        $images = is_array($images) ? collect($images) : $images;

        if ($images->all()) {
            $reportImages = $images->where('item', $this->id)->values();
        }
        $this->images = $reportImages;

        return $this;
    }

    public static function isEmployeeOwner($employee, $report)
    {
        return strtolower($report->worker) == strtolower($employee);
    }

    public function statuses()
    {
        return self::STATUSES;
    }
}

<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Models\Passive\BaseModel;
use Illuminate\Support\Carbon;

class AreaStatus extends BaseModel
{
    use Filterable;

    protected $table = 'area_statuses';

    protected $guarded = ['gst_nick', 'modal-2'];

    const STATUS_UP = 1;
    const STATUS_DOWN = 0;
    const END_DATE_HOURS_OFFSET = 2;
    const END_DATE_MINUTES_OFFSET = 120;

    const CITIES = [
        ['id' => 1, 'name' => 'Clydach'],
        ['id' => 2, 'name' => 'Crickhowell'],
        ['id' => 3, 'name' => 'Coedypaen'],
        ['id' => 4, 'name' => 'Cwmdu'],
        ['id' => 5, 'name' => 'Gilwern'],
        ['id' => 6, 'name' => 'Glangrwyney'],
        ['id' => 7, 'name' => 'Govilon'],
        ['id' => 8, 'name' => 'Llandeilo`r Fan'],
        ['id' => 9, 'name' => 'Llangasty'],
        ['id' => 10, 'name' => 'Llangorse'],
        ['id' => 11, 'name' => 'Llangynidr'],
        ['id' => 12, 'name' => 'Maesygwartha'],
        ['id' => 13, 'name' => 'Talyllyn'],
        ['id' => 14, 'name' => 'Tretower'],
        ['id' => 15, 'name' => 'Pennorth'],
        ['id' => 16, 'name' => 'Pentrebach'],
    ];

    public function status()
    {
        $now = Carbon::now();
        $startDate = new Carbon($this->start_date);
        $endDate = new Carbon($this->end_date);

        if ($endDate->diffInMinutes($now, false) >= self::END_DATE_MINUTES_OFFSET) {
            $this->status = self::STATUS_UP;
        } elseif ($startDate->diffInMinutes($now, false) > 0) {
            $this->status = self::STATUS_DOWN;
        } else {
            $this->status = self::STATUS_UP;
        }
        return $this;
    }

    public function cityName()
    {
        $city = null;
        if ($this->city) {
            $city = array_filter(self::CITIES, function ($city) {
                return $city['id'] == $this->city;
            });
        }
        $this->cityName = $city ? current($city)['name'] : $city;
        return $this;
    }

    public function messagesConverted()
    {
        $this->messages = self::convertMessagse($this->messages);
        return $this;
    }

    public static function extras($items)
    {
        $result = [];
        $instance = new static;
        $items = explode(',', $items);

        if ($items) {
            foreach ($items as $item) {
                if (method_exists($instance, $item)) {
                    $result[$item] = $instance->$item();
                }
            }
        }
        return collect($result);
    }

    public static function convertMessagse($messages, $type = 'array')
    {
        switch ($type) {
            case 'json':
                $messages = base64_encode(serialize(json_decode(str_replace("\\", "", $messages), true)));
                break;
            case 'string':
                $messages = base64_encode(serialize(array_map('trim', explode(',', $messages))));
                break;
            case 'array':
                $messages = unserialize(base64_decode($messages));
                break;
        }
        return $messages;
    }

    private function cities()
    {
        return collect(self::CITIES);
    }
}

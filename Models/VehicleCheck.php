<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use App\Models\Passive\ReportTimeConfig;

class VehicleCheck extends BaseModel
{
    protected $table = 'vehicle_checks';

    protected $guarded = ['gst_nick'];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin', 'username');
    }

    public function checks($serialized = false)
    {
        if ($this->checks) {
            if (is_serialized($this->checks) || $serialized) {
                $this->checks = unserialize($this->checks);
            } else {
                $this->checks = serialize($this->checks);
            }
        }
        return $this;
    }

    public static function checked($admin, $date = null)
    {
        $existed = [];
        $date = $date ? $date : curdate();
        $checks = self::where('created_at', 'like', "$date%")
            ->where('admin', $admin)->get();

        if ($checks->isEmpty()) return false;

        $requiredChecks = ReportTimeConfig::all()->get('required_checks')->toArray();
        foreach ($checks->first()->checks()->checks as $check) {
            if ($check['status']) $existed[$check['id']] = $check;
        }
        foreach ($requiredChecks as $check) {
            if (!isset($existed[$check])) return false;
        }

        return true;
    }

    public static function getChecksByAdmin($admin, $defaultIfEmpty = true)
    {
        $date = curdate();
        $dayChecks = self::where('created_at', 'like', "$date%")
            ->where('admin', $admin)->get();

        if (!$defaultIfEmpty) return $dayChecks;

        $statChecks = ReportTimeConfig::all()->get('vehicle_checks')->toArray();

        if (!$dayChecks->isEmpty()) {
            $checks = $dayChecks->transform(function ($item) use ($statChecks) {
                $item->checks();
                $curChecks = $statChecks;

                if ($item->checks) {
                    $itemChecks = collect($item->checks);
                    foreach ($curChecks as $key => $curCheck) {
                        $filtered = $itemChecks->filter(function ($item) use ($curCheck) {
                            return $item['id'] == $curCheck['id'];
                        });
                        if ($filtered) $curChecks[$key] = $filtered->first();
                    }
                }
                return $curChecks;
            });
        } else {
            $checks = [$statChecks];
        }

        return $checks;
    }

    public static function updateAdminVehicleChecks($admin, $checks, $multiple = false)
    {
        $date = curdatetime();
        $statChecks = ReportTimeConfig::all()->get('vehicle_checks')->toArray();
        $dayChecks = self::getChecksByAdmin($admin, false);

        if (!$dayChecks->isEmpty()) {
            $dayCheck = $dayChecks->first()->checks();
            $statChecks = $dayCheck->checks;
        }

        if ($multiple) {
            $checks = collect($checks);
            foreach ($statChecks as $key => $statCheck) {
                $filtered = $checks->filter(function ($item) use ($statCheck) {
                    return $item['id'] == $statCheck['id'];
                });
                if ($filtered) {
                    $statChecks[$key] = array_merge($statChecks[$key], $filtered->first());
                }
            }
        } else {
            if (!self::find($checks['id'])) return false;
            foreach ($statChecks as $key => $statCheck) {
                if ($checks['id'] == $statCheck['id']) {
                    $statChecks[$key] = array_merge($statChecks[$key], $checks);
                    break;
                }
            }
        }

        if (empty($dayCheck)) {
            $dayCheck = VehicleCheck::create([
                'checks' => serialize($statChecks),
                'admin' => $admin,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        } else {
            $dayCheck->update([
                'checks' => serialize($statChecks),
                'updated_at' => $date,
            ]);
        }

        return $dayCheck;
    }

    public static function find($id)
    {
        $checks = ReportTimeConfig::all()->get('vehicle_checks')->toArray();
        return array_filter($checks, function ($v, $k) use ($id) {
            return $v['id'] == $id;
        }, ARRAY_FILTER_USE_BOTH);
    }
}

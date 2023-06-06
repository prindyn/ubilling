<?php

namespace App\Models;

use App\Models\Passive\BaseModel;
use App\Models\Passive\ReportTimeConfig;

class TaskmanRisk extends BaseModel
{
    protected $table = 'taskman_risks';

    protected $guarded = ['gst_nick'];

    public function task()
    {
        return $this->belongsTo(Taskman::class, 'task', 'id');
    }

    public function riskAssessments($serialized = false)
    {
        if ($this->risks) {
            if (is_serialized($this->risks) || $serialized) {
                $this->risks = unserialize($this->risks);
            } else {
                $this->risks = serialize($this->risks);
            }
        }
        return $this;
    }

    public static function checked($task)
    {
        $existed = [];
        $risks = self::where('task', $task)->get();

        if ($risks->isEmpty()) return false;

        $requiredRisks = ReportTimeConfig::all()->get('required_task_risks')->toArray();
        foreach ($risks->first()->riskAssessments()->risks as $risk) {
            if ($risk['status']) $existed[$risk['id']] = $risk;
        }
        foreach ($requiredRisks as $risk) {
            if (!isset($existed[$risk])) return false;
        }

        return true;
    }

    public static function getRiskAssessmentsByTask($task, $defaultIfEmpty = true)
    {
        $taskRisks = self::where('task', $task)->get();

        if (!$defaultIfEmpty) return $taskRisks;

        $statRisks = ReportTimeConfig::all()->get('task_risks')->toArray();

        if (!$taskRisks->isEmpty()) {
            $risks = $taskRisks->transform(function ($item) use ($statRisks) {
                $item->riskAssessments();
                $curRisks = $statRisks;

                if ($item->risks) {
                    $itemRisks = collect($item->risks);
                    foreach ($curRisks as $key => $curRisk) {
                        $filtered = $itemRisks->filter(function ($item) use ($curRisk) {
                            return $item['id'] == $curRisk['id'];
                        });
                        if ($filtered) $curRisks[$key] = $filtered->first();
                    }
                }
                return $curRisks;
            });
        } else {
            $risks = [$statRisks];
        }

        return $risks;
    }

    public static function updateTaskRiskAssessments($task, $risks, $multiple = false, $admin = 'guest')
    {
        $date = curdatetime();
        $statRisks = ReportTimeConfig::all()->get('task_risks')->toArray();
        $taskRisks = self::getRiskAssessmentsByTask($task, false);

        if (!$taskRisks->isEmpty()) {
            $taskRisk = $taskRisks->first()->riskAssessments();
            $statRisks = $taskRisk->risks;
        }

        if ($multiple) {
            $risks = collect($risks);
            foreach ($statRisks as $key => $statRisk) {
                $filtered = $risks->filter(function ($item) use ($statRisk) {
                    return $item['id'] == $statRisk['id'];
                });
                if ($filtered) {
                    $statRisks[$key] = array_merge($statRisks[$key], $filtered->first());
                }
            }
        } else {
            if (!self::find($risks['id'])) return false;
            foreach ($statRisks as $key => $statRisk) {
                if ($risks['id'] == $statRisk['id']) {
                    $statRisks[$key] = array_merge($statRisks[$key], $risks);
                    break;
                }
            }
        }

        if (empty($taskRisk)) {
            $taskRisk = self::create([
                'risks' => serialize($statRisks),
                'task' => $task,
                'admin' => $admin,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        } else {
            $taskRisk->update([
                'risks' => serialize($statRisks),
                'updated_at' => $date,
            ]);
        }

        return $taskRisk;
    }

    public static function find($id)
    {
        $risks = ReportTimeConfig::all()->get('task_risks')->toArray();
        return array_filter($risks, function ($v, $k) use ($id) {
            return $v['id'] == $id;
        }, ARRAY_FILTER_USE_BOTH);
    }
}

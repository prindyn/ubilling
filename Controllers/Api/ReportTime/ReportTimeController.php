<?php

namespace App\Controllers\Api\ReportTime;

use App\Models\Taskman;
use App\Models\ReportTime;
use App\Traits\PhotoStorage;
use App\Models\PhotoStorage as ImagesStorage;
use App\Filters\ReportTimeFilter;
use App\Controllers\AbstractController;
use App\Models\DcmsProject;
use App\Models\Passive\ReportTimeConfig;
use App\Models\TaskmanRisk;
use App\Models\TaskmanRiskConfirm;
use App\Models\VehicleCheck;
use App\Repository\ReportTimeSystem;

class ReportTimeController extends AbstractController
{
    use PhotoStorage;

    protected $auth;

    public function create()
    {
        $vehicleChecked = VehicleCheck::checked(
            $this->auth->user->get('username')
        );

        if (!$vehicleChecked) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Vehicle checks is not completed.'
            ], 400));
        }

        $validator = $this->validator($this->post(), [
            'start_time' => 'string|required|date_format:H:i:s',
            'end_time' => 'string|required|date_format:H:i:s',
            'comment' => 'string|nullable',
            'status' => 'integer|nullable|max:1'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $task = Taskman::with('employees')
            ->find($this->validated('task'));

        if (!$task || !Taskman::isEmployeeOwner(
            $this->auth->user->get('id'),
            $task
        )) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'The selected task is invalid.',
            ], 400));
        }

        $report = ReportTime::create([
            'task_id' => $this->validated('task'),
            'start_time' => $this->validated('start_time'),
            'end_time' => $this->validated('end_time'),
            'project' => DcmsProject::getNameById($task->project_id),
            'activity' => $task->jobtype,
            'report_desc' => $this->validated('comment'),
            'worker' => ucfirst($this->auth->user->get('username')),
            'reporter_id' => $this->auth->user->get('id'),
            'report_date' => curdate(),
            'timestamp' => curdatetime(),
        ]);

        if ($this->validated('status')) $task->update(['status' => 1]);

        $this->send(
            $this->response()->json($report->toArray())
        );
    }

    public function update()
    {
        $vehicleChecked = VehicleCheck::checked(
            $this->auth->user->get('username')
        );

        if (!$vehicleChecked) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Vehicle checks is not completed.'
            ], 400));
        }

        $validator = $this->validator(array_merge($this->post(), ['id' => $this->get('id')]), [
            'start_time' => 'string|nullable|date_format:H:i:s',
            'end_time' => 'string|nullable|date_format:H:i:s',
            'comment' => 'string|nullable',
            'status' => 'integer|nullable|max:1'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $report = ReportTime::find($this->validated('id'));

        if (!$report || !ReportTime::isEmployeeOwner(
            $this->auth->user->get('username'),
            $report
        )) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'The selected report is invalid.',
            ], 400));
        }

        $updates = [
            'start_time' => $this->validated('start_time') ? $this->validated('start_time') : $report->start_time,
            'end_time' => $this->validated('end_time') ? $this->validated('end_time') : $report->end_time,
            'report_desc' => $this->validated('comment') ? $this->validated('comment') : $report->report_desc,
        ];

        if (!$report->update($updates)) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Failed to update report.',
            ], 400));
        }

        if ($this->validated('status')) {
            $task = Taskman::find($report->task_id);
            if ($task) $task->update(['status' => 1]);
        }

        $this->send(
            $this->response()->json($report->toArray())
        );
    }

    public function delete()
    {
        $report = ReportTime::find($this->get('id'));

        if (!$report || !ReportTime::isEmployeeOwner(
            $this->auth->user->get('username'),
            $report
        )) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'The selected report is invalid.',
            ], 400));
        }

        if (!$report->delete()) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Failed to delete report.',
            ], 400));
        }

        $this->send(
            $this->response()->json([
                'status' => true,
                'id' => $report->id,
            ])
        );
    }

    public function tasks()
    {
        $tasks = Taskman::with('employees')
            ->where('status', 0)
            ->get()->filter(function ($item) {
                $employee = $this->auth->user->get('id');
                return Taskman::isEmployeeOwner($employee, $item);
            })->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'date' => $item->date,
                    'jobtype' => $item->jobtype,
                    'status' => $item->status,
                ];
            });

        $this->send(
            $this->response()->json($tasks->values()->toArray())
        );
    }

    public function reports()
    {
        $filter = new ReportTimeFilter;
        $filter->staticFilters['worker'] = $this->auth->user->get('username');
        $reports = ReportTime::filter($filter)
            ->get()
            ->transform(function ($item) {
                // return $item->materials()->extra();
                return [
                    'id' => $item->id,
                    'report_date' => $item->report_date,
                    'activity' => $item->activity,
                    'report_desc' => $item->report_desc,
                ];
            });

        $this->send(
            $this->response()->json($reports->toArray())
        );
    }

    public function task()
    {
        $validator = $this->validator($this->get(), [
            'id' => 'integer|required|exists:taskman,id',
            'images' => 'string|nullable'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $task = Taskman::with('employees')
            ->find($this->validated('id'));

        if (!$task) {
            $this->send($this->response()->json([]));
        }

        if (!Taskman::isEmployeeOwner($this->auth->user->get('id'), $task)) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'The task is forbidden.'
            ], 403));
        }

        if ($this->validated('images')) {
            $storageProxy = $this->photoStorageProxy(
                Taskman::IMAGES_SCOPE,
                $this->validated('id')
            );
            $images = $this->photoStorage()
                ->returnItems()
                ->all($storageProxy->storage());
            $task->images($images);
            if ($task->images) {
                $task->images = $task->images->transform(function ($item) {
                    return [
                        'id' => $item['id'],
                        'filename' => $item['filename'],
                        'shortname' => $item['shortname'],
                    ];
                });
            }
        }

        $task = $task->only(['id', 'title', 'date', 'jobnote', 'images']);

        $this->send(
            $this->response()->json($task)
        );
    }

    public function report()
    {
        $validator = $this->validator($this->get(), [
            'id' => 'integer|required|exists:report_time,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $report = ReportTime::find($this->validated('id'));

        if (!$report) {
            $this->send($this->response()->json([]));
        }

        if (!ReportTime::isEmployeeOwner($this->auth->user->get('username'), $report)) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'The report is forbidden.'
            ], 403));
        }

        $report->materials()->extra();

        if ($this->validated('images')) {
            $storageProxy = $this->photoStorageProxy(
                ReportTime::IMAGES_SCOPE,
                $this->validated('id')
            );
            $images = $this->photoStorage()
                ->returnItems()
                ->all($storageProxy->storage());
            $report->images($images);
            if ($report->images) {
                $report->images = $report->images->transform(function ($item) {
                    return [
                        'id' => $item['id'],
                        'filename' => $item['filename'],
                        'shortname' => $item['shortname'],
                    ];
                });
            }
        }

        $report = $report->only([
            'id', 'start_time', 'end_time', 'report_desc', 'images'
        ]);

        $this->send(
            $this->response()->json($report)
        );
    }

    public function images($scope)
    {
        $validator = $this->validator($this->get(), [
            'id' => 'integer|required|exists:' . strtolower($scope) . ',id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $storageProxy = $this->photoStorageProxy(
            $scope,
            $this->validated('id')
        );

        $this->photoStorage()->all($storageProxy->storage(), [
            'scope' => $scope,
            'item' => $this->validated('id')
        ]);
    }

    public function saveImages($scope)
    {
        $validator = $this->validator($this->get(), [
            'id' => 'integer|required|exists:' . strtolower($scope) . ',id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $query = [
            'scope' => $scope,
            'item' => $this->validated('id')
        ];

        $storageProxy = $this->photoStorageProxy($query['scope'], $query['item']);

        return $this->photoStorage()
            ->update($storageProxy->storage(), $query);
    }

    public function deleteImages($scope)
    {
        $errors = collect();
        $validator = $this->validator($this->get(), [
            'id' => 'string|required',
            'report' => 'string|required|exists:' . strtolower($scope) . ',id',
        ]);

        if (count($validator->errors()) > 0) {
            $errors->push($validator->errors()->first());
        }

        if ($errors->isEmpty()) {
            $ids = explode(',', $this->validated('id'));
            $errors = ImagesStorage::validateItems($ids);
        }

        if ($errors->count() > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $errors->first()
            ], 400));
        }

        $query = [
            'id' => $ids,
            'scope' => $scope,
            'item' => $this->validated('report'),
        ];
        $storageProxy = $this->photoStorageProxy($query['scope'], $query['item']);

        return $this->photoStorage()
            ->deleteMultiple($storageProxy->storage(), $query);
    }

    public function auth($auth = null)
    {
        if (!$auth && $this->auth) return $this->auth;

        $this->auth = $auth ? $auth : (new ReportTimeAuthController())->userAuth();

        return $this->auth;
    }

    public function isVehicleChecked()
    {
        $validator = $this->validator($this->get(), [
            'date' => 'string|nullable|date_format:Y-m-d'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $checked = VehicleCheck::checked(
            $this->auth->user->get('username'),
            $this->validated('date')
        );

        return $this->send(
            $this->response()->json([
                'status' => true,
                'checked' => $checked
            ])
        );
    }

    public function vehicleChecks()
    {
        $checks = VehicleCheck::getChecksByAdmin(
            $this->auth->user->get('username')
        );

        return $this->send(
            $this->response()->json($checks)
        );
    }

    public function updateVehicleChecks()
    {
        $validator = $this->validator(array_merge($this->post(), $this->get()), [
            'id' => 'integer|required',
            'desc' => 'string|nullable',
            'status' => 'integer|required|max:1',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ], 400));
        }

        $checks = [
            'id' => (int) $this->validated('id'),
            'desc' => $this->validated('desc', ''),
            'status' => (int) $this->validated('status', 0),
        ];

        $dayCheck = VehicleCheck::updateAdminVehicleChecks(
            $this->auth->user->get('username'),
            $checks
        );

        if (!$dayCheck) {
            $this->send($this->response()->json([
                'error' => 'Selected id is invalid'
            ], 400));
        }

        return $this->send(
            $this->response()->json($checks)
        );
    }

    public function isTaskRiskAssessmentsChecked()
    {
        $validator = $this->validator($this->get(), [
            'task' => 'integer|required|exists:taskman,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $checked = TaskmanRisk::checked($this->validated('task'));

        return $this->send(
            $this->response()->json([
                'status' => $checked,
            ])
        );
    }

    public function isTaskRiskAssessmentsConfirmed()
    {
        $date = curdate();
        $validator = $this->validator($this->get(), [
            'task' => 'integer|required|exists:taskman,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $confirmed = TaskmanRiskConfirm::confirmed(
            $this->validated('task'),
            $this->auth->user->get('username')
        );

        return $this->send(
            $this->response()->json([
                'status' => $confirmed,
            ])
        );
    }

    public function taskRiskAssessments()
    {
        $validator = $this->validator($this->get(), [
            'task' => 'integer|required|exists:taskman,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $risks = TaskmanRisk::getRiskAssessmentsByTask($this->validated('task'));

        return $this->send(
            $this->response()->json($risks)
        );
    }

    public function confirmTaskRiskAssessments()
    {
        $date = curdate();
        $validator = $this->validator(array_merge($this->post(), $this->get()), [
            'task' => 'integer|required|exists:taskman,id',
            'status' => 'integer|nullable|max:1',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ], 400));
        }

        $confirm = TaskmanRiskConfirm::where('task', $this->validated('task'))
            ->where('admin', $this->auth->user->get('username'))
            ->where('created_at', 'like', "$date%")->first();
        $date = curdatetime();

        if ($confirm) {
            if ($confirm->status == $this->validated('status', 1)) {
                $message = 'You have already ' . ($confirm->status ? 'confirmed' : 'unconfirmed') . ' daily task risk assessments.';
                $this->send($this->response()->json([
                    'error' => $message
                ], 400));
            } else {
                $confirm->update([
                    'status' => $this->validated('status', 1),
                    'updated_at' => $date,
                ]);
            }
        } else {
            $confirm = TaskmanRiskConfirm::create([
                'task' => $this->validated('task'),
                'admin' => $this->auth->user->get('username'),
                'status' => $this->validated('status', 1),
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $message = 'Daily task risk assessments successfully ' . ($confirm->status ? 'confirmed' : 'unconfirmed') . '.';

        return $this->send(
            $this->response()->json([
                'status' => true,
                'message' => $message
            ])
        );
    }

    public function updateTaskRiskAssessments()
    {
        $validator = $this->validator(array_merge($this->post(), $this->get()), [
            'id' => 'integer|required',
            'task' => 'integer|required|exists:taskman,id',
            'desc' => 'string|nullable',
            'status' => 'integer|required|max:2',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ], 400));
        }

        $risks = [
            'id' => (int) $this->validated('id'),
            'desc' => $this->validated('desc', ''),
            'status' => (int) $this->validated('status', 0),
        ];

        $taskRisks = TaskmanRisk::updateTaskRiskAssessments(
            $this->validated('task'),
            $risks,
            false,
            $this->auth->user->get('username'),
        );

        if (!$taskRisks) {
            $this->send($this->response()->json([
                'error' => 'Selected id is invalid'
            ], 400));
        }

        return $this->send(
            $this->response()->json($risks)
        );
    }

    public function systemData()
    {
        $validator = $this->validator($this->get(), [
            'only' => 'string|nullable'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ], 400));
        }

        $extras = ReportTimeSystem::only($this->validated('only'))->get();

        return $this->send(
            $this->response()->json($extras->toArray())
        );
    }
}

<?php

namespace App\Services;

use App\Models\Taskman;
use App\Controllers\AbstractController;
use App\Models\Admin;
use App\Models\DcmsProject;
use App\Models\JobType;

class TaskService extends AbstractController
{
    protected $response;

    public function __construct()
    {
        $this->response = collect([
            'result' => true,
            'errors' => collect(),
        ]);
    }

    public function createTasksFromCSV()
    {
        $countAdded = 0;
        $file = $this->files('csv_tasks');
        $rows = $this->readCsvFile($file);
        $date = date('Y-m-d H:i:s');

        foreach ($rows as $lineNum => $data) {
            try {
                $employee = $this->getAdminByIdentifier($data[5]);
                $jobtype = JobType::query()->where('id', (int)$data[4])->orWhere('jobname', $data[4])->first();
                $projectid = DcmsProject::query()->where('id', (int)$data[8])->orWhere('name', $data[8])->first();

                if ($lineNum > 0) {
                    if ($jobtype == null) {
                        $this->response->get('errors')->add("Job type not found for line {$lineNum}. It should be a strict job type name or id.");
                    }

                    if ($employee == null) {
                        $this->response->get('errors')->add("Eployee not found for line {$lineNum}. It should be a strict employee name or id.");
                    }

                    if ($projectid == null) {
                        $this->response->get('errors')->add("Project not found for line {$lineNum}. It should be a strict project name or id.");
                    }
                }

                if ($jobtype == null || $employee == null || $projectid == null) continue;

                $task = new Taskman();
                $task->date = $date;
                $task->title = $data[0];
                $task->object_id = $data[1];
                $task->startdate = $data[2];
                $task->starttime = $data[3];
                $task->jobtype = $jobtype->id;
                $task->employee = $employee->id;
                $task->login = $data[6];
                $task->noi = $data[7];
                $task->project_id = $projectid->id;
                $task->geo = $data[9];
                $task->jobnote = $data[10];
                if ($this->taskNotExists($task, ['*'], ['date', 'jobnote', 'starttime'])) {
                    $countAdded++;
                    $task->save();
                } else {
                    $this->response->get('errors')->add("Task already exist for line {$lineNum}.");
                }
            } catch (\Exception $e) {
                $this->response->get('errors')->add($e->getMessage());
                continue;
            }
        }
        $this->response->put('result', $countAdded);
        return $this->response;
    }

    private function getAdminByIdentifier($identifier)
    {
        $employeesAll = Admin::all();
        $employeesByName = $employeesAll->where('username', $identifier);
        if ($employeesByName->count() == 0) {
            $employeesByName = $employeesAll->where('username', strtolower($identifier));
        }

        if ($employeesByName->count() == 0) {
            $employeesByName = $employeesAll->where('nickname', $identifier);
        }
        $employeesById = $employeesAll->where('id', (int)$identifier);

        return $employeesByName->count() > 0 ? $employeesByName->first() : $employeesById->first();
    }

    private function readCsvFile($file)
    {
        $data = array();

        if (($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }

    private function taskNotExists($task, $checks = ['*'], $exclude = [])
    {
        $check = clone $task;
        $query = Taskman::query();

        foreach ($check->getAttributes() as $key => $value) {
            if ($checks == ['*'] || in_array($key, $checks)) {
                if (empty($exclude) || !in_array($key, $exclude)) {
                    $query->where($key, $value);
                }
            }
        }

        return !$query->exists();
    }
}

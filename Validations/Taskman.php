<?php

namespace App\Validations;

use App\Models\Admin;

class Taskman extends Validation
{
    protected function createRules()
    {
        return array_merge([], $this->defaultRules());
    }

    protected function updateRules()
    {
        return array_merge($this->defaultRules(), [
            'task' => 'integer|required|exists:taskman,id',
            'employee' => 'integer|nullable',
            'donenote' => 'string|nullable',
            'enddate' => 'date_format:Y-m-d|nullable',
            'status' => 'integer|nullable|max:3',
            'noi' => 'string|nullable',
            'project_id' => 'integer|nullable',
        ]);
    }

    protected function workers()
    {
        if (empty($this->data['workers']) || !Admin::all()->whereIn('id', $this->data['workers'])->all()) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Workers list is invalid')
            ]));
        }
    }

    protected function defaultRules()
    {
        return [
            'title' => 'string|nullable',
            'address' => 'string|nullable',
            'login' => 'string|nullable|exists:users,login',
            'noi' => 'string|required',
            'jobtype' => 'required|integer|exists:jobtypes,id',
            'jobnote' => 'nullable',
            'phone' => 'numeric|nullable',
            'employee' => 'integer|nullable',
            'startdate' => 'date_format:Y-m-d|nullable',
            'starttime' => 'date_format:H:i:s|nullable',
            'workers' => 'required|array',
            'smsdata' => 'string|nullable',
            'project_id' => 'required|integer|exists:dcms_fundings,id',
            'extra' => "array|nullable",
        ];
    }
}

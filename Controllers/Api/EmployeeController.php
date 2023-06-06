<?php

namespace App\Controllers\Api;

use App\Controllers\AbstractController;
use App\Models\JobType;

class EmployeeController extends AbstractController
{
    public function getAssignedMaterialTypes()
    {
        $validator = $this->validator($this->get(), [
            'id' => 'required|integer|exists:jobtypes,id',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $jobType = JobType::find($this->validated('id'));

        return $this->send(
            $this->response()->json($jobType->materialTypes()->pluck('materialtype_id')->toArray())
        );
    }

    public function attachMaterialTypes()
    {
        $validator = $this->validator($this->post(), [
            'jobtype-id' => 'required|integer|exists:jobtypes,id',
            'jobtype-materials' => 'array|nullable',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $jobType = JobType::find($this->validated('jobtype-id'));
        $materialTypes = $this->validated('jobtype-materials') ? $this->validated('jobtype-materials') : [];
        $jobType->materialTypes()->sync($materialTypes);

        return $this->send(
            $this->response()->json('Success to attach material types to job type.')
        );
    }
}
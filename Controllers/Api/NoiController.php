<?php

namespace App\Controllers\Api;

use App\Controllers\AbstractController;
use App\Models\Noi;
use App\Models\NoiConfig;

class NoiController extends AbstractController
{
    public function updateConfigs()
    {
        $validator = $this->validator(
            $this->post(),
            [
                'id' => 'integer|nullable',
                'noi_id' => 'integer|required|exists:noi,id',
                'cp_ref' => 'string|required|max:255',
                'start_date' => 'string|required',
                'duration' => 'string|required',
                'expiry_date' => 'string|nullable',
                'ext_count' => 'integer|nullable',
                'poles_num' => 'integer|nullable',
                'ducts_num' => 'integer|nullable',
                'ducts_total' => 'integer|nullable',
                'oh_lead' => 'integer|nullable',
                'ug_lead' => 'integer|nullable',
                'leads_total' => 'integer|nullable',
                'area_name' => 'integer|required',
                'region' => 'string|nullable',
                'status' => 'integer|required',
                'expired_flag' => 'present',
            ]
        );

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]));
        }

        $startSeconds = Noi::convertDuration($this->validated('start_date'));
        $endSeconds = Noi::convertDuration($this->validated('duration'));

        if ($startSeconds - $endSeconds > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Start date can not be less then end date')
            ]));
        }

        $configs = [
            'duration' => $endSeconds - $startSeconds,
            'expired_flag' => !empty($this->validated('expired_flag')) ? 1 : 0,
        ];

        foreach ($this->validated() as $key => $value) {
            if (!isset($configs[$key]) && !empty($value)) {
                $configs[$key] = $value;
            }
        }

        if ($this->validated('status') == Noi::SUBMITTED) {
            $configs['submitted_date'] = curdatetime();
        }

        if ($this->validated('id')) {
            $noiConfig = NoiConfig::find($this->validated('id'));
            $configs['updated_at'] = curdatetime();
            if ($noiConfig->submitted_date != '0000-00-00 00:00:00') {
                $configs['submitted_date'] = $noiConfig->submitted_date;
            }

            if (!$noiConfig->update($configs)) {
                $this->send($this->response()->json([
                    'status' => false,
                    'error' => __('Failed to update status'),
                ]));
            }
        } else {
            $configs['created_at'] = $configs['updated_at'] = curdatetime();
            $noiConfig = NoiConfig::create($configs);
        }

        $this->send($this->response()->json([
            'status' => true,
            'task' => $noiConfig,
        ]));
    }
}

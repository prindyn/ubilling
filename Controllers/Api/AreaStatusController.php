<?php

namespace App\Controllers\Api;

use App\Models\AreaStatus;
use App\Filters\AreaStatusFilter;
use App\Controllers\AbstractController;

class AreaStatusController extends AbstractController
{
    public function all($remoteApi = false)
    {
        $statuses = AreaStatus::filter(new AreaStatusFilter)
            ->get()
            ->transform(function ($item) use ($remoteApi) {
                $item->cityName()
                    ->messagesConverted()
                    ->status();
                if ($remoteApi) {
                    $item = [
                        'id' => $item->id,
                        'status' => $item->status,
                        'cityName' => $item->cityName,
                        'updated' => $item->updated_date,
                    ];
                } else {
                    $item = array_values($item->toArray());
                }
                return $item;
            });
        $response = !$remoteApi ? ['aaData' => $statuses] : $statuses;
        return $this->send(
            $this->response()->json($response)
        );
    }

    public function show()
    {
        $validator = $this->validator($this->get(), [
            'show' => 'required|integer|exists:area_statuses,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $status = AreaStatus::where('id', $this->validated('show'))
            ->first()
            ->messagesConverted()
            ->status();

        return $this->send(
            $this->response()->json($status->toArray())
        );
    }

    public function create()
    {
        $validator = $this->validator($this->post(), [
            'city' => 'integer|required',
            'start_date' => 'string|nullable',
            'end_date' => 'string|nullable',
            'messages' => 'string|nullable',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]));
        }

        $this->setValidated([
            'admin' => whoami(),
            'updated_date' => curdatetime(),
            'messages' => AreaStatus::convertMessagse($this->validated('messages'), 'json')
        ]);

        $status = AreaStatus::create($this->validated());

        if (!$status) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to create status'),
            ]));
        }

        $this->send($this->response()->json($status));
    }

    public function update()
    {
        $validator = $this->validator(
            array_merge(['id' => $this->get('update')], $this->post()),
            [
                'city' => 'integer|required',
                'start_date' => 'string|nullable',
                'end_date' => 'string|nullable',
                'messages' => 'string|nullable',
            ]
        );

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]));
        }

        $this->setValidated([
            'admin' => whoami(),
            'updated_date' => curdatetime(),
            'messages' => AreaStatus::convertMessagse($this->validated('messages'), 'json')
        ]);

        $status = AreaStatus::find($this->validated('id'));

        if (!$status->update($this->validated())) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to update status'),
            ]));
        }

        $this->send($this->response()->json($status));
    }

    public function delete()
    {
        $validator = $this->validator($this->get(), [
            'delete' => 'required|integer|exists:area_statuses,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $status = AreaStatus::find($this->validated('delete'));

        if (!$status->delete()) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to delete status'),
            ]));
        }

        $this->send($this->response()->json($status));
    }

    public function extras()
    {
        $validator = $this->validator($this->get(), [
            'only' => 'string|nullable'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $extras = AreaStatus::extras($this->validated('only'));

        return $this->send(
            $this->response()->json($extras->toArray())
        );
    }
}

<?php

namespace App\Controllers\Api\ReportTime;

use App\Models\Employee;
use App\Controllers\Api\ApiAuthController;

class ReportTimeAuthController extends ApiAuthController
{
    protected $tokenName = 'report_time_key';

    public function login()
    {
        $validator = $this->validator($this->post(), [
            'username' => 'string|required',
            'password' => 'string|required',
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        $auth = $this->userAuth([
            'username' => $this->validated('username'),
            'password' => $this->validated('password'),
        ], 'basic');

        if (!$auth->user) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Login attempt failed.'
            ], 401));
        }

        if (!$this->get('refresh')) {
            $auth->checkAuthToken(null, true, true);
        }
        $token = $auth->refreshApiToken();

        if (!$token) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Login attempt failed.'
            ], 401));
        }

        $this->send($this->response()->json([
            'status' => true,
            'api_token' => $token,
        ]));
    }

    public function logout()
    {
        $auth = $this->userAuth();

        if (!$auth->checkAuth('user')) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => 'Authentication failed.'
            ], 401));
        }

        $this->refreshApiToken(true);

        $this->send($this->response()->json([
            'status' => true,
        ]));
    }

    public function asEmployee()
    {
        if ($this->checkAuth()) {
            $employee = Employee::where('admlogin', $this->user->get('username'))->first();
            $this->user->put('employee', $employee);
        }

        return $this;
    }
}

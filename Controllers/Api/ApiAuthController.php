<?php

namespace App\Controllers\Api;

use App\Controllers\AbstractController;
use App\Traits\Authenticable;

abstract class ApiAuthController extends AbstractController
{
    use Authenticable;

    const TOKEN_LENGTH = 80;
    const TOKEN_NAME = 'api_key';
    const AUTH_PATH = DATA_PATH . 'api_users/';
    const USERS_PATH = DATA_PATH . 'users/';
    const HASH_ALGO = 'sha256';
    const RULES = [
        'token' => [
            'token' => 'required',
        ],
        'basic' => [
            'username' => 'string|required',
            'password' => 'string|required',
        ],
    ];

    /**
     * User data from `content/auth_users`
     */
    public $auth;

    /**
     * User data from `content/users`
     */
    public $user;

    /**
     * User data from `content/auth_users`
     */
    private $auths;

    /**
     * All users data from `content/users`
     */
    private $users;

    protected $tokenName;

    public function __construct()
    {
        $this->loadUsers();
        $this->loadAuthUsers();
        $this->setTokenName();
    }

    public function checkAuthToken($token = null, $checkExists = false, $return = false)
    {
        $returnToken = '';
        $errors = collect();

        if ($this->checkAuth()) {
            $apiKey = $this->auth->get($this->tokenName);

            if ($checkExists) {
                if ($token && $token == $apiKey) {
                    $errors->push('Api token already exists');
                } elseif ($apiKey) {
                    if ($return) {
                        $username = $this->user->get('username');
                        $password = $this->user->get('password');
                        $returnToken = $this->tokenHash("$username:$password", $apiKey);
                    } else {
                        $errors->push('Api token already exists');
                    }
                }
            } elseif (!$apiKey) {
                $errors->push('Invalid api token');
            } elseif ($token && $token != $apiKey) {
                if ($checkExists) $errors->push('Invalid api token');
            }
        } else {
            $errors->push('Username not found');
        }

        if ($returnToken) {
            $this->send($this->response()->json([
                'status' => true,
                'api_token' => $returnToken
            ]));
        } elseif ($errors->all()) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $errors->first()
            ], 400));
        }

        return true;
    }

    public function checkAuth($only = null)
    {
        if ($only && property_exists($this, $only)) {
            return !empty($this->$only);
        }
        return !empty($this->user) && !empty($this->auth);
    }

    public function userAuth($params = [], $type = 'token', $return = false)
    {
        $user = null;

        if (!$params && $type == 'token') {
            $params = ['token' => $this->getBearerToken()];
        }

        $validator = $this->validator($params, self::RULES[$type]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ], 400));
        }

        if ($type == 'token') {
            $this->userIdentifyToken($this->validated('token'));
            $user = $this->auth;
        } elseif ($type == 'basic') {
            $this->userIdentifyBasic($this->validated('username'), $this->validated('password'));
            $user = $this->user;
        }

        return $return ? $user : $this;
    }

    protected function refreshApiToken($delete = false)
    {
        $token = !$delete ? zb_rand_string(self::TOKEN_LENGTH) : null;

        if ($this->checkAuth()) {
            $apiKey = $this->auth->get($this->tokenName);
            if (!$token && $apiKey) {
                $this->auth->offsetUnset($this->tokenName);
            } elseif ($token) {
                $this->auth->put($this->tokenName, $token);
            }

            try {
                $this->commitChanges();
                if ($token) {
                    $username = $this->user->get('username');
                    $password = $this->user->get('password');
                    return $this->tokenHash("$username:$password", $token);
                }
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    protected function loadUsers($return = false)
    {
        $this->users = collect();
        $users = rcms_scandir(self::USERS_PATH);

        if ($users) {
            foreach ($users as $id => $username) {
                $data = ['id' => $id];
                $user = file_get_contents(self::USERS_PATH . "$username");
                $data = collect($data + (!empty($user) ? unserialize($user) : []));
                $this->users->put($username, $data);
            }
        }

        return $return ? $this->users : $this;
    }

    protected function loadAuthUsers($return = false)
    {
        $this->auths = collect();
        $auths = rcms_scandir(self::AUTH_PATH);

        if ($auths) {
            foreach ($auths as $username) {
                if (!$this->users->get($username)) {
                    continue;
                }
                $data = file_get_contents(self::AUTH_PATH . "$username");
                $data = collect(!empty($data) ? unserialize($data) : []);
                $this->auths->put($username, $data);
            }
        }

        return $return ? $this->auths : $this;
    }

    protected function commitChanges($username = null)
    {
        $updates = [];

        if (!$username && $this->checkAuth()) {
            $updates[$this->user->get('username')] = $this->auth->toArray();
        } else {
            foreach ($this->auths as $user => $auth) {
                if ($username && $user != $username) continue;
                $updates[$user] = $auth->toArray();
            }
        }

        if (!empty($updates)) {
            foreach ($updates as $username => $data) {
                file_put_contents(self::AUTH_PATH . "$username", serialize($data));
            }
        }
    }

    private function userIdentifyToken($token)
    {
        if (empty($token)) return null;

        foreach ($this->users as $user) {
            $username = $user->get('username');
            if ($auth = $this->auths->get($username)) {
                $data = "$username:{$user->get('password')}";
                $hash = $this->tokenHash($data, $auth->get($this->tokenName));
                if ($token === $hash) {
                    $this->auth = $auth;
                    $this->user = $user;
                    $this->setUserRights();
                }
            }
        }

        return $this->auth;
    }

    private function userIdentifyBasic($username, $password)
    {
        if (empty($username)) return null;

        foreach ($this->users as $user) {
            if ($user->get('username') != $username) continue;
            if ($user->get('password') != md5($password)) continue;
            $this->user = $user;
        }

        if ($this->user) {
            $this->setUserRights();
            $this->auth = $this->auths->get($username);

            if (!$this->auth) {
                $this->auth = collect();
                file_put_contents(self::AUTH_PATH . $username, serialize($this->auth->toArray()));
            }
        }

        return $this->user;
    }

    private function setUserRights()
    {
        global $system;
        $system->initialiseAccess(
            $this->user->get('admin'),
            (int) @$this->user->get('accesslevel')
        );
        $system->user = $this->user;
    }

    private function setTokenName()
    {
        if (!$this->tokenName) {
            $this->tokenName = self::TOKEN_NAME;
        }
    }

    private function tokenHash($data, $key)
    {
        return hash(self::HASH_ALGO, "$data:$key");
    }
}

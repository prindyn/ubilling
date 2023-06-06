<?php

namespace App\Controllers;

use App\Traits\Responsible;
use App\Traits\Validatable;

abstract class AbstractController
{
    use Responsible, Validatable;

    protected function get($key = null, $default = null)
    {
        $data = $this->request()->query->all();

        if (!is_null($key)) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        return $data;
    }

    protected function post($key = null, $default = null)
    {
        $content = $this->request()->getContent();
        if (!empty($content)) {
            $content = json_decode($content, true);
        }
        $data = array_merge(
            $this->request()->request->all(),
            !empty($content) ? $content : []
        );

        if (!is_null($key)) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        return $data;
    }

    protected function files($key = null, $default = null)
    {
        $data = $this->request()->files->all();

        if (!is_null($key)) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        return $data;
    }

    protected function processScope($scope, $model, $params = [])
    {
        if ($this->get('scope') == $scope) {
            $method = 'scope' . ucfirst($this->get('scope'));

            try {
                return call_user_func_array([$model, $method], [$model::query(), $params]);
            } catch (\Exception $e) {
                return $this->send(
                    $this->response()->json([
                        'status' => false,
                        'error' => __('Request scope failed')
                    ])
                );
            }
        }

        return false;
    }
}

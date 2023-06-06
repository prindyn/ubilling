<?php

namespace App\Controllers\Api;

use App\Models\User;
use App\Models\Taskman;
use App\Repository\TaskmanExtra;
use App\Controllers\AbstractController;
use App\Filters\TaskmanFilter;
use App\Traits\PhotoStorage;
use App\Validations\Taskman as TaskmanValidation;

class TaskmanController extends AbstractController
{
    use PhotoStorage;

    public function all()
    {
        $storageProxy = $this->photoStorageProxy(Taskman::IMAGES_SCOPE, 0);
        $images = $this->photoStorage()
            ->returnItems()
            ->all($storageProxy->storage(), [
                'scope' => Taskman::IMAGES_SCOPE,
                'item' => 0
            ]);
        $tasks = Taskman::filter(new TaskmanFilter)
            ->with('employees')
            ->get()
            ->transform(function ($item) use ($images) {
                return $item->images($images)->workers();
            });

        return $this->send(
            $this->response()->json($tasks->toArray())
        );
    }

    public function show()
    {
        $validator = $this->validator($this->get(), [
            'show' => 'required|integer|exists:taskman,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $task = Taskman::where('id', $this->validated('show'))
            ->with(['extra', 'logs'])
            ->first()
            ->workers();

        return $this->send(
            $this->response()->json($task->toArray())
        );
    }

    public function create()
    {
        $validator = $this->validation(
            new TaskmanValidation($this->post()),
            ['create', 'workers']
        );

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]));
        }

        if ($this->validated('login')) {
            $this->identifyUser($this->validated('login'));
        }

        $this->setValidated([
            'admin' => whoami(),
            'title' => $this->makeTitle(),
            'date' => curdatetime(),
            'phone' => $this->makePhone(),
            'employee' => current($this->validated('workers')),
        ]);

        $task = Taskman::create($this->validated());

        if (!$task) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to create task'),
            ]));
        }

        $task
            ->saveExtra($this->validated('extra', []))
            ->saveWorkers($this->validated('workers', []))
            ->saveLogs($this->validated('logs', []), null, 'create');

        $this->send($this->response()->json([
            'status' => true,
            'task' => $task
        ]));
    }

    public function update()
    {
        $processScope = $this->processScope(
            'status',
            Taskman::class,
            array_merge($this->get(), $this->post(), ['id' => $this->get('update')])
        );

        if ($processScope) {
            return $this->send(
                $this->response()->json([
                    'status' => true,
                    'message' => __('Request scope action success')
                ])
            );
        }

        $validator = $this->validation(
            new TaskmanValidation(
                array_merge(['task' => $this->get('update')], $this->post())
            ),
            ['update', 'workers']
        );

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]));
        }

        if ($this->validated('login')) {
            $this->identifyUser($this->validated('login'));
        }

        $update = array_merge($this->validated(), [
            'change_admin' => whoami(),
            'title' => $this->makeTitle(),
            'phone' => $this->makePhone(),
            'employee' => current($this->validated('workers')),
        ]);

        $task = Taskman::find($this->validated('task'));

        if (!$task->update($update)) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to update task'),
            ]));
        }

        $task
            ->saveExtra($this->validated('extra', []))
            ->saveWorkers($this->validated('workers', []))
            ->saveLogs($this->validated('logs', []), null, 'modify');

        $this->send($this->response()->json([
            'status' => true,
            'task' => $this->validated(),
        ]));
    }

    public function delete()
    {
        $validator = $this->validator($this->get(), [
            'delete' => 'required|integer|exists:taskman,id'
        ]);

        if (count($validator->errors()) > 0) {
            $this->send($this->response()->json([
                'error' => $validator->errors()->first()
            ]));
        }

        $task = Taskman::find($this->validated('delete'));

        if (!$task->delete()) {
            $this->send($this->response()->json([
                'status' => false,
                'error' => __('Failed to delete task'),
            ]));
        }

        $task
            ->deleteExtra()
            ->deleteWorkers()
            ->deleteLogs();

        $this->send($this->response()->json([
            'status' => true,
            'task' => $task,
        ]));
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

        $extras = TaskmanExtra::only($this->validated('only'))->get();

        return $this->send(
            $this->response()->json($extras->toArray())
        );
    }

    public function photos()
    {
        $query = [
            'item' => $this->get('task', 0),
            'scope' => strtoupper($this->get('scope', Taskman::IMAGES_SCOPE)),
        ];
        $storageProxy = $this->photoStorageProxy($query['scope'], $query['item']);
        $photos = $this->photoStorage()
            ->all($storageProxy->storage(), $query);

        return $this->send(
            $this->response()->json($photos->toArray())
        );
    }

    public function updatePhotos()
    {
        $query = array_merge($this->get(), [
            'scope' => strtoupper($this->get('scope', Taskman::IMAGES_SCOPE)),
        ]);

        $storageProxy = $this->photoStorageProxy(
            $query['scope'],
            isset($query['item']) ? $query['item'] : null
        );

        return $this->photoStorage()
            ->update($storageProxy->storage(), $query);
    }

    public function deletePhotos()
    {
        $query = array_merge($this->get(), [
            'id' => $this->get('file'),
            'scope' => strtoupper($this->get('scope', Taskman::IMAGES_SCOPE)),
        ]);

        $storageProxy = $this->photoStorageProxy(
            $query['scope'],
            isset($query['item']) ? $query['item'] : null
        );

        return $this->photoStorage()
            ->delete($storageProxy->storage(), $query);
    }

    protected function identifyUser($login)
    {
        $this->user = User::with([
            'phone', 'contract', 'addressExtended',
        ])->where('login', $login)->first();
    }

    protected function makeTitle()
    {
        if (!$this->validated('title') && !empty($this->user)) {
            return Taskman::titleFromUserData($this->user);
        }

        return $this->validated('title', '');
    }

    protected function makePhone()
    {
        if (!$this->validated('phone') && !empty($this->user)) {
            return $this->user->phone->mobile
                ? $this->user->phone->mobile
                : $this->user->phone->phone;
        }

        return $this->validated('phone', '');
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FileModel extends Model
{
    protected $path;

    protected $table = '';

    protected $expression = '';

    /**
     * Get all of the models from the database.
     *
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = ['*'])
    {
        return static::query()->get();
    }

    /**
     * Get the path associated with the model.
     *
     * @return string
     */
    public function getPath()
    {
        if ($this->path[-1] != '/') {
            $this->path .= '/';
        }
        return $this->path;
    }

    /**
     * Get the expression associated with the model.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new FileBuilder($query);
    }
}

class FileBuilder extends Builder
{
    protected $columns;

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded, which will solve the
        // n+1 query issue for the developers to avoid running a lot of queries.
        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        return $this->model->hydrate(
            collect($this->onceWithColumns(Arr::wrap($columns), function () {
                return $this->getScanDirData();
            }))->all()
        )->all();
    }

    private function getScanDirData()
    {
        $path = RCMS_ROOT_PATH . $this->model->getPath();
        $data = rcms_scandir($path, $this->model->getExpression(), 'file');

        if ($data && $this->model->getTable()) {
            $data = array_filter($data, function ($v, $k) {
                return $v == $this->model->getTable();
            }, ARRAY_FILTER_USE_BOTH);

            if ($data) {
                $content = file_get_contents($path . current($data));

                switch (true) {
                    case is_serialized($content):
                        $content = unserialize($content);
                        break;
                    case is_json($content):
                        $content = json_decode($content, true);
                        break;
                    default:
                        $content = include $path . current($data);
                        break;
                }
                $data = $content;
            }
        } else {
            foreach ($data as $id => &$item) {
                $content = file_get_contents("$path/$item");

                switch (true) {
                    case is_serialized($content):
                        $content = unserialize($content);
                        break;
                    case is_json($content):
                        $content = json_decode($content, true);
                        break;
                    default:
                        $content = include $path . current($data);
                        break;
                }

                if (!$this->model->getKeyName()) {
                    $item = $content;
                } else {
                    $item = array_merge([$this->model->getKeyName() => $id], $content);
                }

                if ($this->columns != ['*']) {
                    $item = array_intersect_key($item, array_combine(
                        $this->columns,
                        array_pad([], count($this->columns), true)
                    ));
                }
            }
        }

        return collect($data);
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return mixed
     */
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }
        $result = $callback();

        $this->columns = $original;

        return $result;
    }
}

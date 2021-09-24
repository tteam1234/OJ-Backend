<?php
namespace NEUQOJ\Repository\Eloquent;

use Carbon\Carbon;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use NEUQOJ\Repository\Contracts\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    /** @var Model $model */
    protected $model;

    function __construct(Container $app)
    {
        $this->model = $app->make($this->model());
    }

    abstract function model();

    function all(array $columns = ['*'])
    {
        return $this->model->get($columns);
    }

    function get(int $id, array $columns = ['*'], string $primary = 'id',string $orderBy=null,string $order=null)
    {
        if ($orderBy!=null) {
            return $this->model
                ->where($primary,$id)
                ->orderBy($orderBy,$order)
                ->get($columns);
        }

        return $this->model
            ->where($primary, $id)
            ->get($columns);
    }

    function getBy(string $param, string $value, array $columns = ['*'])
    {
        return $this->model
            ->where($param, $value)
            ->get($columns);
    }

    function getByMult(array $params, array $columns = ['*'])
    {
        return $this->model
            ->where($params)
            ->get($columns);
    }

    //在多个候选列表中的匹配
    function getIn($param, array $data, array $columns = ['*'])
    {
        return $this->model
            ->whereIn($param, $data)
            ->get($columns);
    }

    function insert(array $data)
    {
        if ($this->model->timestamps) {
            $current = new Carbon();

            if (!is_array(reset($data))) {
                $data = array_merge($data,
                    [
                        'created_at' => $current,
                        'updated_at' => $current,
                    ]);
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = array_merge($value,
                        [
                            'created_at' => $current,
                            'updated_at' => $current,
                        ]);
                }
            }

        }
        return $this->model
            ->insert($data);
    }

    function update(array $data, int $id, string $attribute = "id")
    {
        if ($this->model->timestamps) {
            $current = new Carbon();

            if (!is_array(reset($data))) {
                $data = array_merge($data,
                    [
                        'updated_at' => $current,
                    ]);
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = array_merge($value,
                        [
                            'updated_at' => $current,
                        ]);
                }
            }

        }
        return $this->model
            ->where($attribute, '=', $id)
            ->update($data);
    }


    /**
     * 多条件限定查找
     */
    function updateWhere(array $condition, array $data)
    {
        if ($this->model->timestamps) {
            $current = new Carbon();

            if (!is_array(reset($data))) {
                $data = array_merge($data,
                    [
                        'updated_at' => $current,
                    ]);
            } else {
                foreach ($data as $key => $value) {
                    $data[$key] = array_merge($value,
                        [
                            'updated_at' => $current,
                        ]);
                }
            }

        }
        return $this->model
            ->where($condition)
            ->update($data);
    }


    function delete(int $id): bool
    {
        //WARNING:DO NOT USE THIS FUNCTION
        return $this->model
            ->destory($id);
    }

    function deleteWhere(array $param = [])
    {
        return $this->model
            ->where($param)->delete();
    }

    function paginate(int $page = 1, int $size = 15, array $param = [], array $columns = ['*'])
    {
        if (!empty($param))
            return $this->model
                ->where($param)
                ->skip($size * --$page)
                ->take($size)
                ->get($columns);
        else
            return $this->model
                ->skip($size * --$page)
                ->take($size)
                ->get($columns);
    }

    function getWhereCount(array $param = []):int
    {
        if (!empty($param)) {
            return $this->model->where($param)->count();
        }
        else
            return $this->model->count();
    }

    function whereExist(Closure $func)
    {
        return $this->model->whereExists($func)->get();
    }

    private function freshTimestamp(): Carbon
    {
        return new Carbon;
    }
}
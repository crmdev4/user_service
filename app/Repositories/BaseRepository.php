<?php

namespace App\Repositories;

use App\Filters\BaseFilter;
use App\Helpers\Datatable;
use App\Helpers\Pagination;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BaseRepository
{
    protected $model;

    protected $view = null;

    public function baseQuery()
    {
        return is_null($this->view) ? $this->model->query() : $this->view->query();
    }

    public function getDatatables($filterClass = null, $query = null)
    {
        try {
            $qry = BaseFilter::dtApply(is_null($query) ? $this->baseQuery() : $query, $filterClass);
            $baseModel = is_null($query) ? $this->baseQuery()->getModel() : $query->getModel();
            $baseTable = (new $baseModel())->getTable();

            return $qry->addIndexColumn()
                ->orderColumn('DT_RowIndex', function ($query, $order) use ($baseTable) {
                    $query->orderBy($baseTable . '.id', $order);
                })
                ->make(true);
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function setBaseData($query, $request, $filter)
    {
        $request = Datatable::handle($request);
        $query = BaseFilter::apply($query, $request, $filter);
        $datas = Pagination::paginate($query, $request);
        if (array_key_exists('dt', $request) && $request['dt'] === 'true') {
            return DataTables::collection($datas['data'])
                ->filter(function () {
                })
                ->setTotalRecords($datas['meta']['total'])
                ->setFilteredRecords($datas['meta']['total'])
                ->skipPaging()
                ->make(true);
        }

        return $datas;
    }

    public function all($request, $filter)
    {
        $query = $this->model;
        $datas = $this->setBaseData($query, $request, $filter);

        return $datas;
    }

    public function allInactive($request, $filter)
    {
        $query = $this->model->onlyTrashed();
        $datas = $this->setBaseData($query, $request, $filter);

        return $datas;
    }

    public function select(string ...$selects): BaseRepository
    {
        $this->model = $this->model->select(...$selects);

        return $this;
    }

    /**
     * Get All Data with/without condition
     *
     * @param array
     */
    public function get(array $cond = []): Collection
    {
        $mdl = $this->model;
        if (!empty($cond)) {
            $mdl = $this->wheremapper($this->model, $cond);
        }

        return $mdl->get();
    }

    /**
     * Get All Data with select, condition and with call function
     *
     * @param array
     */
    public function getWithSelect(array $select, array $with, array $cond = []): Collection
    {
        $this->model = $this->wheremapper($this->model->select($select)->with($with), $cond);

        return $this->model->get();
    }

    /**
     * Get All Data with select, condition, order by and with call function
     *
     * @param array
     */
    public function getWithSelectOrderBy(array $select, array $with, string $order = '', string $by = 'ASC', array $cond = []): Collection
    {
        $this->model = $this->wheremapper($this->model->select($select)->with($with)->orderBy($order, $by), $cond);

        return $this->model->get();
    }

    public function count(array $cond = []): int
    {
        $this->model = $this->model->selectRaw('COUNT(*) AS count');
        if (count($cond) > 0) {
            $this->model = $this->wheremapper($this->model, $cond);
        }

        return $this->model->first()->count;
    }

    /**
     * Get All Data with IN condition
     *
     * @param string
     * @param array
     */
    public function getWhereIn(string $field, array $cond = []): Collection
    {
        if (!empty($cond)) {
            $this->model = $this->model->whereIn($field, $cond);
        }

        return $this->model->get();
    }

    /**
     * Get All Data with NOT IN condition
     *
     * @param string
     * @param array
     */
    public function getWhereNotIn(string $field, array $cond = []): Collection
    {
        if (!empty($cond)) {
            $this->model = $this->model->whereNotIn($field, $cond);
        }

        return $this->model->get();
    }

    /**
     * Get sorted Data
     *
     * @param string
     * @param string
     * @param array|string
     */
    public function getOrderBy(string $ref, string $order = 'ASC', array $cond = []): Collection
    {
        if (!empty($cond)) {
            $this->model = $this->wheremapper($this->model, $cond);
        }

        $this->model->orderBy($ref, $order);

        return $this->model->get();
    }

    /**
     * Get 1 Data by Order
     *
     * @param string
     * @param string
     * @param int
     * @param array
     * @return object
     */
    public function getOrderByLimit(string $ref, string $order = 'ASC', int $limit = 1, array $cond = [])
    {
        if (!empty($cond)) {
            $this->model = $this->wheremapper($this->model, $cond);
        }

        $this->model->orderBy($ref, $order);
        $this->model->limit($limit);

        return $this->model->get();
    }

    /**
     * Get a data by id.
     *
     * @param string
     * @return object
     */
    public function find(string $id): object|null
    {
        return $this->model->find($id);
    }

    /**
     * Get a data by field.
     *
     * @param string
     * @param string
     * @return object
     */
    public function findByField(string $field, string $value): object|null
    {
        return $this->model->where($field, $value)->first();
    }

    /**
     * Get a data by set of condition.
     *
     * @param array
     * @return object
     */
    public function findWhere(array $cond): object|null
    {
        return $this->wheremapper($this->model, $cond)->first();
    }

    /**
     * Get a data by set of condition.
     *
     * @param string
     * @param array
     * @return object
     */
    public function findWhereIn(string $field, array $values): object|null
    {
        return $this->model->whereIn($field, $values)->first();
    }

    /**
     * Get a data by set of condition.
     *
     * @param string
     * @param array
     * @return object
     */
    public function findWhereNotIn(string $field, array $values): object|null
    {
        return $this->model->whereNotIn($field, $values)->first();
    }

    /**
     * Create new data.
     *
     * @param array
     * @return object
     */
    public function create(array $attributes): object|null
    {
        return $this->model->create($attributes);
    }

    /**
     * Create batch data.
     *
     * @param array
     * @return object
     */
    public function createMany(array $attributes): object|null
    {
        return $this->model->createMany($attributes);
    }

    /**
     * Create data from builder.
     *
     * @param Builder
     * @param array
     * @return object
     */
    public function createRaw($relation, array $attributes): object|null
    {
        return $relation->create($attributes);
    }

    /**
     * Create batch data from builder.
     *
     * @param string
     * @param array
     * @return object
     */
    public function createManyRaw($relation, array $attributes): object|null
    {
        return $relation->createMany($attributes);
    }

    /**
     * Update data by id.
     *
     * @param array|int
     * @param string
     * @return object
     */
    public function update(array $attributes, $id)
    {
        $object = $this->model->findOrFail($id);
        $object->fill($attributes);
        $object->save();

        return $object->fresh();
    }

    /**
     * Update or create data.
     *
     * @param array
     * @param array
     * @return object
     */
    public function updateOrCreate(array $init, array $attributes)
    {
        $object = $this->model->updateOrCreate($init, $attributes);

        return $object->fresh();
    }

    /**
     * Update data by id.
     *
     * @param array|int
     * @param string
     * @return object
     */
    public function updateWhere(array $attributes, array $cond): object|null
    {
        $object = $this->wheremapper($this->model, $cond)->first();
        $object->fill($attributes);
        $object->save();

        return $object->fresh();
    }

    /**
     * Delete data by id.
     *
     * @param string|int
     * @return object
     */
    public function delete($id): object|null
    {
        $object = $this->model->findOrFail($id);

        return $object->delete();
    }

    /**
     * Delete data by condition.
     *
     * @param string|int
     * @return object
     */
    public function deleteWhere(array $cond)
    {
        $this->model = $this->wheremapper($this->model, $cond);

        return $this->model->delete();
    }

    /**
     * Destroy data by id.
     *
     * @param string|int
     * @return object
     */
    public function destroy($id)
    {
        return $this->model->withTrashed()->find($id)->destroy();
    }

    /**
     * Destroy data by id.
     *
     * @param string|int
     * @return object
     */
    public function restore($id)
    {
        return $this->model->onlyTrashed()->find($id)->restore();
    }

    /**
     * Set base model with additional data.
     *
     * @param array
     */
    public function with(array $with = []): BaseRepository
    {
        $this->model = $this->model->with($with);

        return $this;
    }

    /**
     * Get Raw Builder
     *
     * @return builder
     */
    public function raw()
    {
        return $this->model;
    }

    /**
     * Get model with trashed data.
     *
     * @return object
     */
    public function withTrashed(): BaseRepository
    {
        $this->model = $this->model->withTrashed();

        return $this;
    }

    /**
     * Get model with trashed data only.
     *
     * @return object
     */
    public function onlyTrashed(): BaseRepository
    {
        $this->model = $this->model->onlyTrashed();

        return $this;
    }

    /**
     * Get model without scopes
     *
     * @param array|null
     */
    public function removeScopes($class = null): BaseRepository
    {
        if (is_null($class)) {
            $this->model = $this->model->withoutGlobalScopes();
        } else {
            $this->model = $this->model->withoutGlobalScope($class);
        }

        return $this;
    }

    private function wheremapper($builder, array $cond)
    {
        foreach ($cond as $key => $value) {
            if ($value instanceof \Closure) {
                $builder = $builder->where(function ($qry) use ($value) {
                    $value($qry);
                });

                continue;
            }

            if (is_numeric($key)) {
                $builder = $builder->whereRaw($value);

                continue;
            }

            $column = explode(' ', $key);
            if (count($column) > 1) {
                if ($this->containOperator(['IN', 'NOT IN'], $key)) {
                    if (strpos('NOT IN', $key)) {
                        $builder = $builder->whereNotIn($column[0], $value);
                    } else {
                        $builder = $builder->whereIn($column[0], $value);
                    }

                    continue;
                }

                $operator = str_replace($column[0] . ' ', '', $key);
                $builder = $builder->where($column[0], $operator, $value);
            } else {
                $builder = $builder->where($key, $value);
            }
        }

        return $builder;
    }

    private function containOperator($words, $sentence): bool
    {
        foreach ($words as $word) {
            if (Str::contains($sentence, $word)) {
                return true;
            }
        }

        return false;
    }
}

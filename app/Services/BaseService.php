<?php

namespace App\Services;

use App\Exceptions\ActionException;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class BaseService
{
    use ApiResponseTrait;

    protected $repo;

    protected $object;

    protected $filterClass;

    protected $indexWith = [];

    protected $detailWith = [];

    protected $uploadFolder;

    protected $connection;

    public function __construct()
    {
        $this->connection = config('database.default');
    }

    public function getDatatable()
    {
        try {
            return $this->repo->getDatatables($this->filterClass);
        } catch (\Throwable $th) {
            logError('Get datatables in ' . get_called_class(), $th);
            return DataTables::of([])->make(true);
        }
    }

    public function all(array $request = null)
    {
        try {
            $datas = $this->repo->with($this->indexWith)->all($request);
            $success = $datas;

            return $this->successResponse($success, __('content.message.default.success'));
        } catch (\Throwable $th) {
            logError('Get All Data in ' . get_called_class(), $th);

            return $this->failedResponse([], __('message.server_error'), $th->getCode());
        }
    }

    public function getData($id)
    {
        $data = $this->repo->with($this->detailWith)->find($id);
        $success['data'] = $data;

        return $this->successResponse($success, __('content.message.default.success'));
    }

    public function create(array $data)
    {
        try {
            $execute = DB::transaction(function () use ($data) {
                $created = $this->repo->create($data);

                return $created->refresh();
            });

            $success['data'] = $execute;

            return $this->successResponse($success, __('content.message.create.success'), 201);
        } catch (Exception $exc) {
            logError('Creating data from ' . get_class($this), $exc);

            return $this->failedResponse(null, __('content.message.create.failed'), 400);
        }
    }

    public function update(array $data, $id)
    {
        try {
            $execute = DB::transaction(function () use ($data, $id) {
                $updated = $this->repo->update($data, $id);

                return $updated;
            });

            $success['data'] = $execute;

            return $this->successResponse($success, __('content.message.update.success'));
        } catch (Exception $exc) {
            logError('Updating data from ' . get_class($this), $exc);

            return $this->failedResponse(null, __('content.message.create.failed'), 400);
        }
    }

    public function delete($id)
    {
        try {
            $execute = DB::transaction(function () use ($id) {
                return $this->repo->delete($id);
            });

            $success['data'] = $execute;

            return $this->successResponse($success, __('content.message.delete.success'));
        } catch (Exception $exc) {
            logError('Deleting data from ' . get_class($this), $exc);

            return $this->failedResponse(null, __('content.message.delete.failed'), 400);
        }
    }

    public function restore($id)
    {
        try {
            $execute = DB::transaction(function () use ($id) {
                return $this->repo->withTrashed()->find($id)->restore();
            });

            $success['data'] = $execute;

            return $this->successResponse($success, __('content.message.update.success'));
        } catch (Exception $e) {
            logError('Restoring data from ' . get_class($this), $e);

            return $this->failedResponse(null, __('content.message.create.failed'), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $execute = DB::transaction(function () use ($id) {
                return $this->repo->withTrashed()->find($id)->destroy();
            });

            $success['data'] = $execute;

            return $this->successResponse($success, __('content.message.delete.success'));
        } catch (Exception $e) {
            logError('Detroy data from ' . get_class($this), $e);

            return $this->failedResponse(null, __('content.message.delete.failed'), 400);
        }
    }

    public function uploadFile(array $data, $key, $rename = false, $filename = '')
    {
        if (isset($data[$key])) {
            $file = $data[$key];
            $filepath = 'public/' . $this->uploadFolder;
            $extension = $file->extension();

            $uploadedFile = Storage::put($filepath, $file);

            if ($rename && $filename !== '') {
                $newFilename = $filepath . '/' . $filename . '.' . $extension;
                if (Storage::exists($newFilename)) {
                    Storage::delete($newFilename);
                }
                Storage::move($uploadedFile, $newFilename);
            } else {
                $newFilename = $uploadedFile;
            }

            $data[$key] = $newFilename;
        }

        return $data;
    }

    /**
     * A Method which help you handle the error without catching every error
     *
     * @param  callable  $process your default logic
     * @param  array|string  $conn database connection
     */
    public function wrapTransaction(callable $process, array|string $conn = null): mixed
    {
        $conn = $conn ?? $this->connection;

        $this->beginTransaction($conn);
        try {
            $response = $process();
            $this->commit($conn);

            return $response;
        } catch (ActionException $e) {
            $this->rollBack($conn);

            return $this->failedResponse(null, $e->getMessage(), $e->getCode());
        } catch (\Throwable $th) {
            logError(get_called_class(), $th);
            $this->rollBack($conn);

            return $this->failedResponse(null, 'Error encountered', 500);
        }
    }

    public function beginTransaction(string|array $connection = null): void
    {
        if ($connection !== null && is_array($connection)) {
            foreach ($connection as $conn) {
                DB::connection($conn)->beginTransaction();
            }
        } elseif (is_array($this->connection)) {
            foreach ($this->connection as $conn) {
                DB::connection($conn)->beginTransaction();
            }
        } else {
            DB::connection($connection ?? $this->connection)->beginTransaction();
        }
    }

    public function commit(string|array $connection = null): void
    {
        if ($connection !== null && is_array($connection)) {
            foreach ($connection as $conn) {
                DB::connection($conn)->commit();
            }
        } elseif (is_array($this->connection)) {
            foreach ($this->connection as $conn) {
                DB::connection($conn)->commit();
            }
        } else {
            DB::connection($connection ?? $this->connection)->commit();
        }
    }

    public function rollBack(string|array $connection = null): void
    {
        if ($connection !== null && is_array($connection)) {
            foreach ($connection as $conn) {
                DB::connection($conn)->rollBack();
            }
        } elseif (is_array($this->connection)) {
            foreach ($this->connection as $conn) {
                DB::connection($conn)->rollBack();
            }
        } else {
            DB::connection($connection ?? $this->connection)->rollBack();
        }
    }
}

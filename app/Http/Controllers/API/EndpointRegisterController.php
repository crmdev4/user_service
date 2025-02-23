<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api as Controller;
use App\Models\EndpointRegister;
use App\Models\EndpointRelation;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;

class EndpointRegisterController extends Controller
{
    function __construct()
    {
    }

    private function addServicesToUri($val)
    {
        $segments = explode('/', $val->path);

        $index = array_search($val->version, $segments);
        if ($index !== false) {
            array_splice($segments, $index + 1, 0, $val->service_name);
        }

        $newUri = implode('/', $segments);
        $newUris = url('/') . '/' . $newUri;
        return $newUris;
    }

    public function index(Request $request)
    {


        $draw = $request->input('draw');
        $offset = $request->input('start');
        if ($offset == '') {
            $offset = 0;
        }
        ;
        $limit = $request->input('length');
        if ($limit == '') {
            $limit = 25;
        }
        ;
        $search = $request->input('search')['value'];
        if ($search == '') {
            $search = '';
        }
        ;
        $order = $request->input('order')[0]['column'];
        $sort = $request->input('order')[0]['dir'];
        if ($sort == '') {
            $sort = 'ASC';
        }
        ;
        $columns = $request->input('columns')[$order]['data'];
        if ($columns == '') {
            $columns = 'created_at';
        }
        ;

        $data = EndpointRegister::query();
        $data = $data->orderBy($columns, $sort);
        $data = $data->where('path', 'like', '%' . $search . '%');
        $count = $data->count();
        $data = $data->offset($offset);
        $data = $data->limit($limit);

        $data = $data->get();
        $datas = [];

        foreach ($data as $key => $val) {
            $datas[$key] = $val;
            $datas[$key]->masking_url = $this->addServicesToUri($val);
            $datas[$key]->count_relation = EndpointRelation::where('endpoint_register_id', $val->id)->count();
        }

        $result['draw'] = $draw;
        $result['recordsTotal'] = $count;
        $result['recordsFiltered'] = $count;
        $result['data'] = $datas;

        return $this->sendResponseOk($result);
    }
    public function getRelationById($id, Request $request)
    {
        $draw = $request->input('draw');
        $offset = $request->input('start');
        if ($offset == '') {
            $offset = 0;
        }
        ;
        $limit = $request->input('length');
        if ($limit == '') {
            $limit = 25;
        }
        ;
        $search = $request->input('search')['value'];
        if ($search == '') {
            $search = '';
        }
        ;
        $order = $request->input('order')[0]['column'];
        $sort = $request->input('order')[0]['dir'];
        if ($sort == '') {
            $sort = 'ASC';
        }
        ;
        $columns = $request->input('columns')[$order]['data'];
        if ($columns == '') {
            $columns = 'created_at';
        }
        ;

        $data = EndpointRelation::query();
        $data = $data->select('endpoint_relation.id', 'endpoint_relation.relation_references_name', 'endpoint_relation.status', 'endpoint_register.service_name', 'endpoint_relation.created_at', 'endpoint_relation.updated_at');
        $data = $data->where('endpoint_register_id', $id);
        $data = $data->join('endpoint_register', 'endpoint_register.id', '=', 'endpoint_relation.relation_endpoint_register_id');
        $data = $data->orderBy($columns, $sort);
        $count = $data->count();
        $data = $data->offset($offset);
        $data = $data->limit($limit);

        $data = $data->get();

        $result['draw'] = $draw;
        $result['recordsTotal'] = $count;
        $result['recordsFiltered'] = $count;
        $result['data'] = $data;

        return $this->sendResponseOk($result);
    }

    public function deleteRelationById($rel_id, $id)
    {
        if (empty($rel_id) || empty($id)) {
            return $this->sendResponseError('Invalid Request', null);
        }

        $relation = EndpointRelation::where(['endpoint_register_id' => $rel_id, 'id' => $id])->first();
        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }
        $relation->delete();
        return response()->json(['message' => 'Relation deleted successfully']);
    }

    public function list()
    {

        $result = EndpointRegister::where('method', 'GET')->get();

        return $this->sendResponseOk($result);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_name' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());
        }
        $data = [
            "service_name" => $request->service_name,
            "base_uri" => $request->base_uri,
            "version" => 'v' . $request->version,
            "base_uri" => $request->base_uri,
            "method" => $request->method,
            "path" => $request->path,
            "api_key" => $request->api_key,
            "status" => $request->status,
            "reference" => $request->reference,
        ];

        EndpointRegister::updateorCreate($data);

        return $this->sendResponseCreate(null);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());
        }
        $data = [
            "service_name" => $request->service_name,
            "base_uri" => $request->base_uri,
            "version" => 'v' . $request->version,
            "base_uri" => $request->base_uri,
            "method" => $request->method,
            "path" => $request->path,
            "api_key" => $request->api_key,
            "status" => $request->status,
            "reference" => $request->reference,
        ];

        $query = EndpointRegister::find($request->id);
        $query = $query->update($data);

        return $this->sendResponseCreate(null);
    }

    public function createRelation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint_register_id' => 'required',
            'relation_endpoint_register_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());
        }

        $data = [
            "endpoint_register_id" => $request->endpoint_register_id,
            "relation_endpoint_register_id" => $request->relation_endpoint_register_id,
            "relation_references_name" => $request->relation_references_name,
            "status" => $request->status,
        ];

        EndpointRelation::updateorCreate(
            [
                "endpoint_register_id" => $request->endpoint_register_id,
                "relation_endpoint_register_id" => $request->relation_endpoint_register_id,
                "relation_references_name" => $request->relation_references_name,
            ],
            $data
        );

        return $this->sendResponseCreate(null);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',

        ]);
        if ($validator->fails()) {
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());
        }

        $query = EndpointRegister::find($request->id);
        $relation = EndpointRelation::where('endpoint_register_id', $query->id)->delete();
        $query->delete();

        return $this->sendResponseOK('Deleted');
    }
}

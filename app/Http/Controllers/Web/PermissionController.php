<?php

namespace App\Http\Controllers\Api\Dashboard;
use App\Http\Controllers\Api as Controller;
//use \Spatie\Permission\Models\Permission;
//use \Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
	function __construct()
    {
		//$this->middleware('permission:Melihat Daftar Role', ['only' => ['index']]);
		//$this->middleware('permission:Melihat Informasi Role', ['only' => ['view']]);
		//$this->middleware('permission:Menambah Informasi Role', ['only' => ['create']]);
		//$this->middleware('permission:Merubah Informasi Role', ['only' => ['update']]);
		//$this->middleware('permission:Menghapus Informasi Role', ['only' => ['delete']]);
	}
	
    public function index(Request $request) {

		 
		$draw 		= $request->input('draw');
		$offset		= $request->input('start'); if ($offset == ''){$offset = 0; };
		$limit		= $request->input('length'); if ($limit == ''){$limit = 25; };
		$search		= $request->input('search')['value']; if ($search == ''){$search = ''; };		
		$order		= $request->input('order')[0]['column']; 
		$sort 		= $request->input('order')[0]['dir']; if ($sort == ''){$sort = 'ASC'; };
		$columns	= $request->input('columns')[$order]['data'];  if ($columns == ''){$columns = 'name'; };

		$data 	= Permission::orderBy($columns, $sort)
					->where('name', 'like', '%'.$search.'%')
					->offset($offset)
					->limit($limit)
					->get();
					
		$total  = Permission::count();
		
		$result['draw']           = $draw ;
		$result['recordsTotal']   = count($data);
		$result['recordsFiltered']= $total;
		$result['data'] = $data;
		
		return  $this->sendResponseOk($result);
		
    }
	
	public function list() {

		$result['permission'] = Permission::all();
		
		return $this->sendResponseOk($result);
		
    }
	
	public function create(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required',
		]);
		
		if($validator->fails()){
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());       
		}
		$permissions = [
			"name"			=> $request->name,
			"guard_name"	=> "web",
		];
		
		$Permission = Permission::create($permissions);
		
		return $this->sendResponseCreate($Permission);
	}

	public function delete(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
            
		]);
		if($validator->fails()){
            return $this->sendResponseError(json_encode($validator->errors()), $validator->errors());       
		}

		$permission = Permission::find($request->id);
		 
		
		return $this->sendResponseError('NON AUTHORITY');
	}
	

}

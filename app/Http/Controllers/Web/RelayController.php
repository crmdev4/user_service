<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller as Controller;
use App\Models\EndpointRegister;

class RelayController extends Controller
{
    function __construct() {}

    public function index()
    {

        $data['title']   = 'Dashboard';
        $data['page_title']   = 'Relay List';


        return view('web.relay.index', compact('data'));
    }

    public function getRelation($id)
    {
        $data['title'] = 'Dashboard';
        $data['id'] = $id;
        $endpoint = EndpointRegister::find($id);
        $data['page_title'] = 'Relay Relation List ' . ucfirst($endpoint->service_name);

        return view('web.relay.relation', compact('data'));
    }

    public function auth()
    {

        $data['title']   = 'Dashboard';
        $data['page_title']   = 'Auth List';


        return view('web.auth.index', compact('data'));
    }
}

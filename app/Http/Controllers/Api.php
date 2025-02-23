<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Api extends Controller
{

    public function sendResponseCustom($msg, $result)
    {
        $response = [
            'success' => true,
            'message' => $msg,
        ];
        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, 200);
    }

    public function sendResponseOk($result)
    {
        $response = [
            'success' => true,
            'message' => 'Your request has been found',
        ];
        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, 200);
    }

    public function sendResponseCreate($result)
    {
        $response = [
            'success' => true,
            'message' => 'Your request has been saved',
        ];
        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, 201);
    }

    public function sendResponseUpdate($result)
    {
        $response = [
            'success' => true,
            'message' => 'Your request has been updated',
        ];
        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, 201);
    }

    public function sendResponseDelete($result)
    {
        $response = [
            'success' => true,
            'message' => 'Your request has been deleted',
        ];
        if (!empty($result)) {
            $response['data'] = $result;
        }

        return response()->json($response, 200);
    }

    public function sendResponseError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

}

<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Passport\Client;

class ValidateClientController extends Controller
{
    public function validateClient(Request $request)
    {
        \Log::info("Request data : ");
        \Log::info($request);
        // dd($request);
        $token = Client::where('id', $request->client_id)
            ->where('secret', $request->client_secret)
            ->first();
        \Log::info($token);

        if (!$token) {
            return response()->json(['message' => 'Invalid client credentials'], 401);
        }

        // Optional: Check token expiration logic if required.
        return response()->json(['message' => 'Valid client'], 200);
    }
}

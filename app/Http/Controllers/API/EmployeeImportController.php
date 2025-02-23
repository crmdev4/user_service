<?php

namespace App\Http\Controllers\API;

use App\Models\UserAccount;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Imports\EmployeesImport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class EmployeeImportController extends Controller
{
    public function importEmployees(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
        $user = Auth::user();
        $account = UserAccount::select('secondary_id')->where('user_id', $user->id)->first();
        $importKey = Str::uuid();
        Excel::import(new EmployeesImport($account, $importKey), $request->file('file'));
        return response()->json([
            'success' => true,
            'message' => 'Employee import initiated, data is being processed in the background.',
            'importKey' => $importKey,
        ]);
    }

    public function getImportProgress($importKey)
    {
        // $progress = Redis::get("import_progress_{$importKey}");
        $progress = Cache::get("import_employee_progress_{$importKey}", 0);
        return response()->json([
            'progress' => $progress ?? 0,
        ]);
    }
}

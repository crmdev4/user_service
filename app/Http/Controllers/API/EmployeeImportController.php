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
        
        // Use chunk reading to avoid memory issues
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

    public function clearImportCache(Request $request)
    {
        $request->validate([
            'import_key' => 'nullable|string',
        ]);

        if ($request->has('import_key') && $request->import_key) {
            // Clear specific import key cache
            $importKey = $request->import_key;
            $progressKey = "import_employee_progress_{$importKey}";
            $totalBatchesKey = "import_employee_total_batches_{$importKey}";
            
            Cache::forget($progressKey);
            Cache::forget($totalBatchesKey);
            
            return response()->json([
                'success' => true,
                'message' => "Cache cleared for import key: {$importKey}",
            ]);
        } else {
            // Clear all import caches (use with caution)
            $driver = config('cache.default');
            
            if ($driver === 'redis') {
                try {
                    $redis = Cache::getStore()->getRedis();
                    $keys = $redis->keys('*import_employee*');
                    
                    $cleared = 0;
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            // Remove prefix if exists
                            $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                            Cache::forget($cleanKey);
                            $cleared++;
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => "Cleared {$cleared} import cache entries",
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to clear cache: ' . $e->getMessage(),
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot clear all caches for non-Redis drivers. Please specify import_key.',
                ], 400);
            }
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\UserAccount;

class ValidateClientHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->header('X-Forwarded-Host');

        if (!$host) {
            return response()->json(['error' => 'Host not provided.'], 404);
        }

        // Define allowed hosts
        $grantHost = config('host.grant_host');
        /* $grantHost = [
            'http://rentfms.test',
            'http://127.0.0.1:8000',
            'http://127.0.0.1',
        ]; */

        // Check if host is allowed without transformations
        if (in_array($host, $grantHost)) {
            $isAllowed = true;
            $isActivated = true;
        } else {
            // Use cache for unauthorized hosts
            $cacheKey = "host_check_{$host}";

            $hostInfo = Cache::remember($cacheKey, now()->addDays(7), function () use ($host) {
                $userAccount = UserAccount::where('is_subdomain', 1)->where('host', $host);

                return [
                    'is_allowed' => $userAccount->exists(),
                    'is_activated' => $userAccount->where('is_activated', 1)->exists(),
                ];
            });

            $isAllowed = $hostInfo['is_allowed'];
            $isActivated = $hostInfo['is_activated'];

            if ($isActivated === false) {
                Cache::forget($cacheKey);
            }

            if (!$isAllowed) {
                Cache::forget($cacheKey);
                return response()->json(['error' => 'Host unauthorized.'], 401);
            }
        }

        // Attach host information to the request attributes
        $request->attributes->set('host', $host);
        $request->attributes->set('is_allowed', $isAllowed);
        $request->attributes->set('is_activated', $isActivated);

        return $next($request);
    }

}


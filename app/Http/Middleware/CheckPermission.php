<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * 
     * Checks if the user has the required privilege for a page/action.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $pageSlug  The page slug (e.g., 'vendors', 'contracts')
     * @param  string  $action  The action (read, create, update, delete)
     */
    public function handle(Request $request, Closure $next, string $pageSlug, string $action): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->hasPrivilege($pageSlug, $action)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            return redirect()
                ->back()
                ->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

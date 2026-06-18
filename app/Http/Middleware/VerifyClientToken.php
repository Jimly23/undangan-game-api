<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyClientToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('X-Client-Token');
        
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Token tidak ditemukan.'], 401);
        }

        $slug = $request->route('slug');
        $undangan = null;

        if ($slug) {
            $undangan = \App\Models\Undangan::where('slug', $slug)->first();
        } else {
            $undanganParam = $request->route('undangan');
            if ($undanganParam instanceof \App\Models\Undangan) {
                $undangan = $undanganParam;
            } elseif (is_numeric($undanganParam)) {
                $undangan = \App\Models\Undangan::find($undanganParam);
            } else {
                $undangan = \App\Models\Undangan::where('slug', $undanganParam)->first();
            }
        }

        if (!$undangan || $undangan->client_token !== $token) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid.'], 403);
        }

        return $next($request);
    }
}

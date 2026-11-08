<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSafety
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            if (!$request->headers->has('Origin') || !hash_equals((string) config('app.url'), (string) $request->header('Origin'))) {
                return response()->json(['error' => 'same-origin request required'], 403);
            }
            if (!str_starts_with(strtolower((string) $request->header('Content-Type')), 'application/json')) {
                return response()->json(['error' => 'application/json required'], 415);
            }
            $raw = $request->getContent();
            if (strlen($raw) > 65536 || !is_array(json_decode($raw, true)) || json_last_error() !== JSON_ERROR_NONE || !str_starts_with(ltrim($raw), '{')) {
                return response()->json(['error' => 'invalid JSON request'], 400);
            }
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'no-store');
        return $response;
    }
}

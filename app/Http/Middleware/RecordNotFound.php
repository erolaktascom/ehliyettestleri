<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\SeoRedirectRules;
use Symfony\Component\HttpFoundation\Response;
class RecordNotFound {
    public function handle(Request $request, Closure $next) { return $next($request); }
    public function terminate(Request $request, Response $response): void {
        if ($response->getStatusCode() !== 404 || !in_array($request->method(), ['GET','HEAD'])
            || SeoRedirectRules::protectedPath($request->getPathInfo())) return;
        // Query strings can contain credentials or personal data: only store the path.
        $path = mb_substr($request->getPathInfo(), 0, 255);
        try {
            DB::table('seo_not_found_logs')->upsert([['url'=>$path,'request_count'=>1,
                'first_seen_at'=>now(),'last_seen_at'=>now(),'created_at'=>now(),'updated_at'=>now()]],
                ['url'], ['request_count'=>DB::raw('request_count + 1'), 'last_seen_at'=>now(), 'updated_at'=>now()]);
        } catch (\Throwable $e) {
            // Logging must not turn a missing page into a server error.
            report($e);
        }
    }
}

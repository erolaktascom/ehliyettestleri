<?php

namespace App\Http\Middleware;

use App\Support\SeoRedirectRules;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplySeoRedirects
{
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), ['GET', 'HEAD']) || SeoRedirectRules::protectedPath($request->getPathInfo())) {
            return $next($request);
        }
        $match = SeoRedirectRules::resolve($request->getRequestUri(), SeoRedirectRules::activeRules());
        if (!$match) return $next($request);
        [$rule, $target] = $match;
        DB::table('seo_redirects')->where('id', $rule->id)->update([
            'usage_count' => DB::raw('COALESCE(usage_count, 0) + 1'),
            'last_used_at' => now(),
        ]);
        if ((string) $rule->type === '410') {
            return response()->view('errors.410', [], 410)
                ->header('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate')
                ->header('X-Robots-Tag', 'noindex')->prepare($request);
        }
        // Rules remain editable/deletable: never let a cached 301 bypass the database.
        return redirect()->to($target, (int) $rule->type)
            ->header('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate');
    }
}

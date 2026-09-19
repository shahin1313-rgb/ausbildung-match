<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Add browser security controls to every application response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('security-headers.enabled', true)) {
            return $response;
        }

        $headers = config('security-headers.headers', []);

        foreach ($headers as $name => $value) {
            if (is_string($value) && $value !== '') {
                $response->headers->set($name, $value);
            }
        }

        $policy = $this->contentSecurityPolicy($request);

        if ($policy !== '') {
            $header = config('security-headers.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($header, $policy);
        }

        if ($this->shouldSendHsts($request)) {
            $response->headers->set(
                'Strict-Transport-Security',
                config('security-headers.hsts.value', 'max-age=31536000; includeSubDomains')
            );
        } else {
            $response->headers->remove('Strict-Transport-Security');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        $directives = config('security-headers.csp.directives', []);

        if ($request->is('admin/*', 'admin', 'livewire/*', 'filament/*')) {
            $directives = array_replace(
                $directives,
                config('security-headers.csp.admin_directives', [])
            );
        }

        if (app()->isLocal() && ($devServer = config('frontend.dev_server_url'))) {
            $origin = rtrim((string) $devServer, '/');
            $directives['script-src'][] = $origin;
            $directives['connect-src'][] = $origin;
            $directives['connect-src'][] = preg_replace('/^http/', 'ws', $origin);
        }

        return collect($directives)
            ->filter(fn ($sources) => is_array($sources) && $sources !== [])
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }

    private function shouldSendHsts(Request $request): bool
    {
        return (bool) config('security-headers.hsts.enabled', false)
            && $request->isSecure();
    }
}

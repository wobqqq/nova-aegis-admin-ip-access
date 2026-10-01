<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AccessListStore;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;

final readonly class RestrictNovaAccess
{
    /**
     * Marks a request already checked: the middleware sits in several of Nova's groups.
     */
    private const CHECKED = 'aegis.admin-ip-access.checked';

    public function __construct(private AccessListStore $store, private ViewFactory $views)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->getBoolean(self::CHECKED)) {
            return $next($request);
        }

        $request->attributes->set(self::CHECKED, true);
        $list = $this->store->get();
        $ip = $request->ip();

        return $list->allows($ip) ? $next($request) : $this->deny($request, $list, $ip);
    }

    private function deny(Request $request, AccessList $list, ?string $ip): Response
    {
        $message = (string)__('aegis-admin-ip-access::admin-ip-access.denied.message');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message], 403);
        }

        $data = ['ip' => is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null];

        /** @var view-string $fallback */
        $fallback = AdminIpAccessModule::DEFAULT_VIEW;

        try {
            /** @var view-string $view */
            $view = $this->views->exists($list->view) ? $list->view : $fallback;
            $html = $this->views->make($view, $data)->render();
        } catch (Throwable $e) {
            report($e);
            $html = $this->views->make($fallback, $data)->render();
        }

        return new HttpResponse($html, 403, ['Cache-Control' => 'no-store, private']);
    }
}

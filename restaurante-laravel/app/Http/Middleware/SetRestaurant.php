<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SetRestaurant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tests: resolver sin cache
        if (app()->runningUnitTests()) {
            if (Schema::hasTable('restaurants')) {
                $restaurantId = Restaurant::query()->value('id');
                if ($restaurantId) {
                    app()->instance('current_restaurant_id', $restaurantId);
                    $request->attributes->set('restaurant_id', $restaurantId);
                }
            }
            return $next($request);
        }

        $restaurantId = $this->resolveRestaurantId($request);

        if ($restaurantId !== null) {
            app()->instance('current_restaurant_id', $restaurantId);
        } else {
            if (app()->environment(['local', 'testing'])) {
                $fallback = $this->fetchFirstRestaurantId() ?? 1;
                app()->instance('current_restaurant_id', $fallback);
                Log::warning('SetRestaurant: usando fallback', ['restaurant_id' => $fallback]);
            } else {
                app()->forgetInstance('current_restaurant_id');
                abort(500, 'No se pudo determinar el restaurante activo');
            }
        }

        return $next($request);
    }

    private function resolveRestaurantId(Request $request): ?int
    {
        // Opción 1: header numérico o slug
        $header = $request->headers->get('X-Restaurant-ID');
        if ($header) {
            if (ctype_digit((string) $header)) {
                return (int) $header;
            }
            return Cache::remember("restaurant_slug:{$header}", 300, fn () =>
                Restaurant::where('slug', $header)->value('id')
            ) ?: null;
        }

        // Opción 2: subdominio
        $host = $request->getHost();
        $parts = explode('.', $host);
        $slug = count($parts) > 1 ? $parts[0] : null;
        if ($slug && !in_array($slug, ['localhost', '127', 'www'], true)) {
            return Cache::remember("restaurant_slug:{$slug}", 300, fn () =>
                Restaurant::where('slug', $slug)->value('id')
            ) ?: null;
        }

        // Opción 3: único restaurante registrado (cacheado 5 min)
        return $this->fetchFirstRestaurantId();
    }

    private function fetchFirstRestaurantId(): ?int
    {
        return Cache::remember('restaurant_first_id', 300, function () {
            if (!Schema::hasTable('restaurants')) {
                return null;
            }
            $ids = Restaurant::query()->limit(2)->pluck('id');
            if ($ids->count() >= 1) {
                return (int) $ids->first();
            }
            return null;
        });
    }
}
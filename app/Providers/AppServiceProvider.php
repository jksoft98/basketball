<?php

namespace App\Providers;

use App\Services\ImageService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImageService::class);
        $this->app->singleton(\App\Services\SessionGeneratorService::class);
    }

    public function boot(): void
    {
        // Fix for older MySQL versions (key length limit)
        Schema::defaultStringLength(191);

        Model::preventLazyLoading(app()->isProduction());

        RateLimiter::for('attendance-save', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('photo-upload', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}

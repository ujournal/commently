<?php

namespace Commently\CustomFrontend;

use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Tonysm\TurboLaravel\Broadcasters\Broadcaster;
use Tonysm\TurboLaravel\Broadcasters\LaravelBroadcaster;
use Tonysm\TurboLaravel\Http\MultiplePendingTurboStreamResponse;
use Tonysm\TurboLaravel\Http\PendingTurboStreamResponse;
use Tonysm\TurboLaravel\Turbo;
use Tonysm\TurboLaravel\Views\Components as ViewComponents;

/**
 * Flarum-compatible Turbo Laravel provider.
 * Registers config, bindings, views, Blade components, and macros only.
 * Skips: publications, artisan commands, routes, middleware, and TestResponse
 * (they rely on Laravel Application / HTTP Kernel not present in Flarum).
 */
class TurboServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom($this->vendorPath('resources/views'), 'turbo-laravel');
        $this->configureComponents();
        $this->configureMacros();
        $this->configureRequestAndResponseMacros();
    }

    public function register(): void
    {
        // Flarum does not call Facade::setFacadeApplication(); set it so Blade/Response/Turbo facades resolve.
        if (Facade::getFacadeApplication() === null) {
            Facade::setFacadeApplication($this->app);
        }

        $this->mergeConfigFrom($this->vendorPath('config/turbo-laravel.php'), 'turbo-laravel');

        $this->app->scoped(Turbo::class);
        $this->app->bind(Broadcaster::class, LaravelBroadcaster::class);
    }

    private function vendorPath(string $path): string
    {
        $base = $this->app->bound('path.base')
            ? $this->app->make('path.base')
            : dirname(__DIR__, 3); // extension root is 2 up, project root 3 up from src/

        return rtrim($base, '/') . '/vendor/hotwired-laravel/turbo-laravel/' . ltrim($path, '/');
    }

    private function configureComponents(): void
    {
        $this->loadViewComponentsAs('turbo', [
            ViewComponents\Frame::class,
            ViewComponents\Stream::class,
            ViewComponents\StreamFrom::class,
        ]);
    }

    private function configureMacros(): void
    {
        $app = $this->app;

        Blade::if('turbonative', function () use ($app) {
            return $app->make(Turbo::class)->isTurboNativeVisit();
        });

        Blade::if('unlessturbonative', function () use ($app) {
            return ! $app->make(Turbo::class)->isTurboNativeVisit();
        });

        Blade::directive('domid', function ($expression) {
            return "<?php echo e(\\Tonysm\\TurboLaravel\\dom_id($expression)); ?>";
        });

        Blade::directive('domclass', function ($expression) {
            return "<?php echo e(\\Tonysm\\TurboLaravel\\dom_class($expression)); ?>";
        });

        Blade::directive('channel', function ($expression) {
            return "<?php echo {$expression}->broadcastChannel(); ?>";
        });
    }

    private function configureRequestAndResponseMacros(): void
    {
        $app = $this->app;

        // Only register Response macros if Laravel's ResponseFactory is bound (Flarum does not bind it).
        if ($app->bound(ResponseFactoryContract::class)) {
            ResponseFacade::macro('turboStream', function ($model = null, string $action = null): MultiplePendingTurboStreamResponse|PendingTurboStreamResponse {
                return turbo_stream($model, $action);
            });

            ResponseFacade::macro('turboStreamView', function ($view, array $data = []): Response|ResponseFactoryContract {
                return turbo_stream_view($view, $data);
            });
        }

        // Only register Request macros if Laravel's Http Request is available (Flarum uses PSR-7, not Illuminate\Http\Request).
        if (class_exists(\Illuminate\Http\Request::class)) {
            Request::macro('wantsTurboStream', function (): bool {
                return Str::contains($this->header('Accept'), Turbo::TURBO_STREAM_FORMAT);
            });

            Request::macro('wasFromTurboNative', function () use ($app): bool {
                return $app->make(Turbo::class)->isTurboNativeVisit();
            });
        }
    }
}

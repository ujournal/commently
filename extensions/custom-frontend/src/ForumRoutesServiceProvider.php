<?php

namespace Commently\CustomFrontend;

use Commently\CustomFrontend\Controller\DiscussionController;
use Commently\CustomFrontend\Controller\PostController;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Registers all custom-frontend forum routes: "/", "/discussions/{id}", "/posts/{id}", POST "/discussions" (create), POST "/discussions/{id}/posts" (reply).
 * The "/" route replaces Flarum's default so we register in afterResolving to avoid duplicate route errors.
 */
class ForumRoutesServiceProvider extends BaseServiceProvider
{
    public const EXTENSION_ID = 'commently-custom-frontend';

    public function boot(): void
    {
        $this->app->make('view')->composer('custom-frontend::layout', function ($view): void {
            $extensions = $this->app->make(ExtensionManager::class);
            $extension = $extensions->getExtension(self::EXTENSION_ID);
            if (!$extension) {
                $view->with('customFrontendAsset', fn (string $path): string => '');
                return;
            }
            $disk = $this->app->make('filesystem')->disk('flarum-assets');
            $view->with('customFrontendAsset', function (string $path) use ($disk, $extension): string {
                return $disk->url('extensions/'.$extension->getId().'/'.ltrim($path, '/'));
            });
        });
    }

    public function register(): void
    {
        $this->app->afterResolving('flarum.forum.routes', function (RouteCollection $routes, Container $container) {
            $routes->removeRoute('default');

            /** @var RouteHandlerFactory $factory */
            $factory = $container->make(RouteHandlerFactory::class);

            $toAction = function (string $controllerClass, string $action) use ($container): callable {
                return function (Request $request, array $routeParams) use ($container, $controllerClass, $action) {
                    $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));
                    $controller = $container->make($controllerClass);

                    return $controller->{$action}($request);
                };
            };

            $routes->get('/', 'custom-frontend.index', $toAction(DiscussionController::class, 'index'));
            $routes->post('/discussions/tag-subscriptions', 'custom-frontend.discussions.tag-subscriptions', $toAction(DiscussionController::class, 'updateTagSubscriptions'));
            $routes->get('/discussions/create', 'custom-frontend.discussions.create.page', $toAction(DiscussionController::class, 'create'));
            $routes->get('/discussions/{id}', 'custom-frontend.discussion', $toAction(DiscussionController::class, 'show'));
            $routes->get('/posts/{id}', 'custom-frontend.post', $toAction(PostController::class, 'show'));
            $routes->post('/discussions', 'custom-frontend.discussions.create', $toAction(DiscussionController::class, 'store'));
            $routes->post('/discussions/{id}/posts', 'custom-frontend.posts.create', $toAction(PostController::class, 'store'));
        });
    }
}

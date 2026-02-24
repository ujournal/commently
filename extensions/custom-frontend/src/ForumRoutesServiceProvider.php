<?php

namespace Commently\CustomFrontend;

use Commently\CustomFrontend\Controller\DiscussionController;
use Commently\CustomFrontend\Controller\PostController;
use Commently\CustomFrontend\Controller\TagController;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\UrlGenerator;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

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
                $view->with('tagsFrameUrl', '');
                $view->with('primaryTags', []);
                return;
            }
            $disk = $this->app->make('filesystem')->disk('flarum-assets');
            $view->with('customFrontendAsset', function (string $path) use ($disk, $extension): string {
                return $disk->url('extensions/'.$extension->getId().'/'.ltrim($path, '/'));
            });
            $url = $this->app->make(UrlGenerator::class);
            $view->with('tagsFrameUrl', $url->to('forum')->route('custom-frontend.tags.index'));
            $view->with('url', $url);
            $view->with('translator', $this->app->make(TranslatorInterface::class));
            try {
                $request = $this->app->make(Request::class);
                $tagController = $this->app->make(TagController::class);
                $view->with('primaryTags', $tagController->getPrimaryTagsWithBadges($request));
            } catch (\Throwable $e) {
                $view->with('primaryTags', []);
            }
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
            $routes->get('/tags', 'custom-frontend.tags.index', $toAction(TagController::class, 'index'));
            $routes->post('/discussions/tag-subscriptions', 'custom-frontend.discussions.tag-subscriptions', $toAction(DiscussionController::class, 'updateTagSubscriptions'));
            $routes->get('/discussions/create', 'custom-frontend.discussions.create.page', $toAction(DiscussionController::class, 'create'));
            $routes->get('/discussions/{id}', 'custom-frontend.discussion', $toAction(DiscussionController::class, 'show'));
            $routes->get('/posts/{id}', 'custom-frontend.post', $toAction(PostController::class, 'show'));
            $routes->post('/discussions', 'custom-frontend.discussions.create', $toAction(DiscussionController::class, 'store'));
            $routes->post('/discussions/{id}/posts', 'custom-frontend.posts.create', $toAction(PostController::class, 'store'));
        });
    }
}

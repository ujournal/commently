<?php

namespace Commently\CustomFrontend;

use Commently\CustomFrontend\Controller\DiscussionController;
use Commently\CustomFrontend\Controller\PostController;
use Commently\CustomFrontend\Controller\TagController;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Registers all custom-frontend forum routes: "/", "/t/{slug}" (tag filter), "/discussions/{id}", "/posts/{id}", POST "/discussions" (create), POST "/discussions/{id}/posts" (reply).
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
            $request = $this->app->bound(Request::class) ? $this->app->make(Request::class) : null;
            try {
                if ($request !== null) {
                    $tagController = $this->app->make(TagController::class);
                    $view->with('primaryTags', $tagController->getPrimaryTagsWithBadges($request));
                } else {
                    $view->with('primaryTags', []);
                }
            } catch (\Throwable $e) {
                $view->with('primaryTags', []);
            }
        });

    }

    public function register(): void
    {
        $this->app->afterResolving('flarum.forum.routes', function (RouteCollection $routes, Container $container) {
            $routes->removeRoute('default');
            $routes->removeRoute('tag');  // Flarum's /t/{slug}; we replace it with our handler but keep name 'tag' for Mentions/URL generation

            /** @var RouteHandlerFactory $factory */
            $factory = $container->make(RouteHandlerFactory::class);

            $toAction = function (string $controllerClass, string $action) use ($container): callable {
                return function (Request $request, array $routeParams) use ($container, $controllerClass, $action) {
                    $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));
                    $request = self::ensureActorOnRequest($request);
                    $container->instance(Request::class, $request);
                    $actor = RequestUtil::getActor($request);
                    $url = $container->make(UrlGenerator::class);
                    $profileUrl = null;
                    $actorAvatarUrl = null;
                    if (!$actor->isGuest() && $actor->id) {
                        $forumBase = rtrim($url->to('forum')->route('custom-frontend.index'), '/');
                        $username = $actor->getAttribute('username') ?? $actor->username ?? null;
                        $slug = $username !== null && $username !== '' ? $username : (string) $actor->id;
                        $profileUrl = $forumBase . '/u/' . rawurlencode($slug);
                        $avatarPath = $actor->getAttribute('avatar_url') ?? $actor->avatar_url ?? null;
                        if ($avatarPath !== null && $avatarPath !== '') {
                            $actorAvatarUrl = str_starts_with($avatarPath, 'http') ? $avatarPath : $forumBase . '/' . ltrim($avatarPath, '/');
                        }
                    }
                    $container->make('view')->share('actor', $actor);
                    $container->make('view')->share('profileUrl', $profileUrl);
                    $container->make('view')->share('actorAvatarUrl', $actorAvatarUrl);
                    $controller = $container->make($controllerClass);

                    return $controller->{$action}($request);
                };
            };

            $routes->get('/', 'custom-frontend.index', $toAction(DiscussionController::class, 'index'));
            $routes->get('/t/{slug}', 'tag', $toAction(DiscussionController::class, 'index'));
            $routes->get('/tags', 'custom-frontend.tags.index', $toAction(TagController::class, 'index'));
            $routes->post('/discussions/tag-subscriptions', 'custom-frontend.discussions.tag-subscriptions', $toAction(DiscussionController::class, 'updateTagSubscriptions'));
            $routes->get('/discussions/create', 'custom-frontend.discussions.create.page', $toAction(DiscussionController::class, 'create'));
            $routes->get('/discussions/{id}', 'custom-frontend.discussion', $toAction(DiscussionController::class, 'show'));
            $routes->get('/posts/{id}', 'custom-frontend.post', $toAction(PostController::class, 'show'));
            $routes->post('/discussions', 'custom-frontend.discussions.create', $toAction(DiscussionController::class, 'store'));
            $routes->post('/discussions/{id}/posts', 'custom-frontend.posts.create', $toAction(PostController::class, 'store'));
        });
    }

    /**
     * Ensure the request has an actor set from the session.
     * Forum HTML requests may not run API session middleware, so actorReference is missing and getActor() fails.
     * We replicate AuthenticateWithSession: resolve user from session access_token and set actor on the request.
     */
    private static function ensureActorOnRequest(Request $request): Request
    {
        $session = $request->getAttribute('session');
        if (!$session || !$session->has('access_token')) {
            return $request;
        }
        try {
            $token = AccessToken::findValid($session->get('access_token'));
            if ($token && $token->user) {
                $request = RequestUtil::withActor($request, $token->user);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $request;
    }
}

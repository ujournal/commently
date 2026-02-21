<?php

namespace Commently\TagSubscriptions\Api\Controller;

use Commently\TagSubscriptions\TagSubscriptionRepository;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListTagSubscriptionsController implements RequestHandlerInterface
{
    public function __construct(
        protected TagSubscriptionRepository $subscriptions
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if ($actor->isGuest()) {
            return new JsonResponse(['slugs' => []], 200);
        }

        $slugs = $this->subscriptions->subscribedTagSlugsForUser($actor);

        return new JsonResponse(['slugs' => $slugs]);
    }
}

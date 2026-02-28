<?php

namespace Commently\TagSubscriptions\Api\Controller;

use Commently\TagSubscriptions\TagSubscriptionRepository;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateTagSubscriptionsController implements RequestHandlerInterface
{
    public function __construct(
        protected TagSubscriptionRepository $subscriptions
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if ($actor->isGuest()) {
            return new JsonResponse(['error' => 'Must be logged in'], 401);
        }

        // Prefer parsedBody so internal Api\Client withBody(['slugs' => [...]]) works
        // (getBody() would read php://input from the outer POST request and break parsing)
        $body = $request->getParsedBody();
        if (is_array($body) && array_key_exists('slugs', $body)) {
            $slugs = $body['slugs'];
        } else {
            $raw = (string) $request->getBody();
            $body = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
            $slugs = $body['slugs'] ?? [];
        }

        if (! is_array($slugs)) {
            return new JsonResponse(['error' => 'slugs must be an array'], 422);
        }

        $this->subscriptions->setSubscribedTagSlugsForUser($actor, $slugs);

        return new JsonResponse(['slugs' => $this->subscriptions->subscribedTagSlugsForUser($actor)]);
    }
}

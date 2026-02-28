<?php

namespace Commently\CustomFrontend\Request;

use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates form data for creating a post (reply) in a discussion.
 */
class CreatePostRequest
{
    public static function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * Validate the request and return validated attributes.
     *
     * @return array{content: string}
     * @throws ValidationException
     */
    public static function validate(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody() ?? [];
        /** @var Factory $factory */
        $factory = resolve(Factory::class);
        $validator = $factory->make($data, self::rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'content' => trim((string) $data['content']),
        ];
    }
}

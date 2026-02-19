<?php

namespace Commently\CustomFrontend\Request;

use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates form data for creating a discussion.
 * Title is optional; content (first post) is required.
 */
class CreateDiscussionRequest
{
    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:80'],
            'content' => ['required', 'string', 'min:1'],
        ];
    }

    /**
     * Validate the request and return validated attributes.
     *
     * @return array{title: string|null, content: string}
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
            'title' => isset($data['title']) && trim((string) $data['title']) !== ''
                ? trim((string) $data['title'])
                : null,
            'content' => trim((string) $data['content']),
        ];
    }
}

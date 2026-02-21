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
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'min:1'],
        ];
    }

    /**
     * Validate the request and return validated attributes.
     *
     * @return array{title: string|null, content: string, tag_ids: list<int>}
     * @throws ValidationException
     */
    public static function validate(ServerRequestInterface $request): array
    {
        $data = $request->getParsedBody() ?? [];
        $tagIds = $data['tag_ids'] ?? [];
        if (! is_array($tagIds)) {
            $tagIds = [];
        }
        $data['tag_ids'] = array_values(array_filter(array_map('intval', $tagIds), fn ($id) => $id > 0));

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
            'tag_ids' => $data['tag_ids'],
        ];
    }
}

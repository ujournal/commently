<?php

namespace Tests\Integration\SortByLikes;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;

class ListDiscussionsSortTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        $tmp = getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR');
        if (! $tmp) {
            $tmp = dirname(__DIR__, 3) . '/vendor/flarum/testing/src/integration/tmp';
        }
        if (! is_file($tmp . '/config.php')) {
            $this->markTestSkipped(
                'Integration tests require running "composer test:setup" first (with DB env vars). '
                . 'See .github/workflows/backend.yml for required env.'
            );
        }

        parent::setUp();

        $this->extension('flarum-likes', 'ujournal-sort-by-likes');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                [
                    'id' => 1,
                    'title' => 'Discussion with likes',
                    'slug' => '1-discussion-with-likes',
                    'comment_count' => 1,
                    'participant_count' => 1,
                    'post_number_index' => 1,
                    'created_at' => Carbon::now(),
                    'user_id' => 1,
                    'first_post_id' => 1,
                    'last_posted_at' => Carbon::now(),
                    'last_post_number' => 1,
                    'last_post_id' => 1,
                    'like_count' => 5,
                ],
            ],
            'posts' => [
                [
                    'id' => 1,
                    'number' => 1,
                    'discussion_id' => 1,
                    'created_at' => Carbon::now(),
                    'user_id' => 1,
                    'type' => 'comment',
                    'content' => '<t><p>First post</p></t>',
                ],
            ],
        ]);
    }

    /** @test */
    public function list_discussions_includes_like_count_attribute(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);

        $discussion = $body['data'][0];
        $this->assertArrayHasKey('attributes', $discussion);
        $this->assertArrayHasKey('likeCount', $discussion['attributes']);
        $this->assertSame(5, $discussion['attributes']['likeCount']);
    }

    /** @test */
    public function list_discussions_accepts_sort_by_like_count(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['sort' => 'likeCount'])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function list_discussions_accepts_sort_by_hot(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['sort' => 'hot'])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}

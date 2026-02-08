<?php

namespace Tests\Unit\SortByLikes;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Mockery;
use PHPUnit\Framework\TestCase;
use UJournal\SortByLikes\Access\AllowGlobalLikePostsPolicy;

class AllowGlobalLikePostsPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_like_posts_when_user_has_global_permission(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('hasPermission')->with('discussion.likePosts')->andReturn(true);

        $discussion = Mockery::mock(Discussion::class);

        $policy = new AllowGlobalLikePostsPolicy();
        $result = $policy->likePosts($actor, $discussion);

        $this->assertSame(AbstractPolicy::FORCE_ALLOW, $result);
    }

    /** @test */
    public function it_does_not_allow_when_user_lacks_permission(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('hasPermission')->with('discussion.likePosts')->andReturn(false);

        $discussion = Mockery::mock(Discussion::class);

        $policy = new AllowGlobalLikePostsPolicy();
        $result = $policy->likePosts($actor, $discussion);

        $this->assertNull($result);
    }
}

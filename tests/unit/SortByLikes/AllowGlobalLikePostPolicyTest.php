<?php

namespace Tests\Unit\SortByLikes;

use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Mockery;
use PHPUnit\Framework\TestCase;
use UJournal\SortByLikes\Access\AllowGlobalLikePostPolicy;

class AllowGlobalLikePostPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_denies_guest(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isGuest')->andReturn(true);

        $post = Mockery::mock(Post::class);

        $policy = new AllowGlobalLikePostPolicy($settings);
        $this->assertNull($policy->like($actor, $post));
    }

    /** @test */
    public function it_allows_when_user_has_global_permission_and_not_own_post(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('flarum-likes.like_own_post', true)->andReturn('1');

        $actor = Mockery::mock(User::class)->makePartial();
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $actor->shouldReceive('hasPermission')->with('discussion.likePosts')->andReturn(true);
        $actor->shouldNotReceive('groups');

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('setAttribute')->andReturnSelf();
        $post->shouldReceive('getAttribute')->with('user_id')->andReturn(2);

        $policy = new AllowGlobalLikePostPolicy($settings);
        $result = $policy->like($actor, $post);

        $this->assertSame(AbstractPolicy::FORCE_ALLOW, $result);
    }

    /** @test */
    public function it_denies_liking_own_post_when_setting_disallows(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('flarum-likes.like_own_post', true)->andReturn('0');

        $actor = Mockery::mock(User::class)->makePartial();
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $actor->shouldReceive('hasPermission')->with('discussion.likePosts')->andReturn(true);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('setAttribute')->andReturnSelf();
        $post->shouldReceive('getAttribute')->with('user_id')->andReturn(1);

        $policy = new AllowGlobalLikePostPolicy($settings);
        $this->assertNull($policy->like($actor, $post));
    }

    /** @test */
    public function it_allows_liking_own_post_when_setting_allows(): void
    {
        $settings = Mockery::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('flarum-likes.like_own_post', true)->andReturn('1');

        $actor = Mockery::mock(User::class)->makePartial();
        $actor->shouldReceive('isGuest')->andReturn(false);
        $actor->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $actor->shouldReceive('hasPermission')->with('discussion.likePosts')->andReturn(true);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('setAttribute')->andReturnSelf();
        $post->shouldReceive('getAttribute')->with('user_id')->andReturn(1);

        $policy = new AllowGlobalLikePostPolicy($settings);
        $result = $policy->like($actor, $post);

        $this->assertSame(AbstractPolicy::FORCE_ALLOW, $result);
    }
}

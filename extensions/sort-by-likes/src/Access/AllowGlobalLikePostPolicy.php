<?php

namespace UJournal\SortByLikes\Access;

use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/**
 * Allow liking any post when the user has global discussion.likePosts,
 * so per-tag permissions are not required. Still respects "like own post" setting.
 * Uses group_permission so token users without is_email_confirmed still get like if their group has it.
 */
class AllowGlobalLikePostPolicy extends AbstractPolicy
{
    /** @var SettingsRepositoryInterface */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function like(User $actor, Post $post)
    {
        if ($actor->isGuest()) {
            return null;
        }
        if (! $this->userHasGlobalLikePermission($actor)) {
            return null;
        }
        if ($actor->id === $post->user_id && ! (bool) $this->settings->get('flarum-likes.like_own_post', true)) {
            return null;
        }

        return $this->forceAllow();
    }

    private function userHasGlobalLikePermission(User $actor): bool
    {
        if ($actor->hasPermission('discussion.likePosts')) {
            return true;
        }
        // Fallback: user may have permission via group but hasPermission is false (e.g. unconfirmed email)
        $groupIds = $actor->groups()->pluck('id')->all();
        if (empty($groupIds)) {
            $groupIds = [Group::MEMBER_ID];
        } else {
            $groupIds[] = Group::MEMBER_ID;
        }

        return Permission::where('permission', 'discussion.likePosts')
            ->whereIn('group_id', array_unique($groupIds))
            ->exists();
    }
}

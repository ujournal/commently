<?php

namespace App\Access;

use Flarum\Post\Post;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/**
 * Force-allow 'like' when user has global discussion.likePosts (or is in a group that has it).
 * No constructor deps so the Gate can always instantiate it.
 */
class AllowLikePolicy extends AbstractPolicy
{
    public function like(User $actor, Post $post)
    {
        if ($actor->isGuest()) {
            return null;
        }
        if ($actor->hasPermission('discussion.likePosts')) {
            return $this->forceAllow();
        }
        $groupIds = $actor->groups()->pluck('id')->all();
        $groupIds = array_unique(array_merge($groupIds, [\Flarum\Group\Group::MEMBER_ID]));
        $has = \Flarum\Group\Permission::where('permission', 'discussion.likePosts')
            ->whereIn('group_id', $groupIds)
            ->exists();
        if ($has) {
            return $this->forceAllow();
        }
        return null;
    }
}

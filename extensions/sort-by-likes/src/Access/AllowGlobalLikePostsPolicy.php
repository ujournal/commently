<?php

namespace Commently\SortByLikes\Access;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/**
 * Allow likePosts on any discussion when the user has the global discussion.likePosts
 * permission, so per-tag permissions (tagX.discussion.likePosts) are not required.
 */
class AllowGlobalLikePostsPolicy extends AbstractPolicy
{
    public function likePosts(User $actor, Discussion $discussion)
    {
        if ($actor->hasPermission('discussion.likePosts')) {
            return $this->forceAllow();
        }
    }
}

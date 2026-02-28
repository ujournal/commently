<?php

namespace Commently\TagSubscriptions;

use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Support\Facades\DB;

class TagSubscriptionRepository
{
    /**
     * Get tag slugs the user is subscribed to (for feed filtering).
     *
     * @return string[]
     */
    public function subscribedTagSlugsForUser(User $user): array
    {
        $tagIds = DB::table('user_tag_subscription')
            ->where('user_id', $user->id)
            ->pluck('tag_id');

        if ($tagIds->isEmpty()) {
            return [];
        }

        return Tag::query()
            ->whereIn('id', $tagIds)
            ->whereVisibleTo($user)
            ->pluck('slug')
            ->values()
            ->all();
    }

    /**
     * Set the list of tag slugs the user is subscribed to (replaces existing).
     *
     * @param  string[]  $slugs
     */
    public function setSubscribedTagSlugsForUser(User $user, array $slugs): void
    {
        $slugs = array_values(array_unique(array_filter($slugs, 'is_string')));

        $tagIds = collect();
        if ($slugs !== []) {
            $tagIds = Tag::query()
                ->whereIn('slug', $slugs)
                ->whereVisibleTo($user)
                ->pluck('id');
        }

        DB::transaction(function () use ($user, $tagIds) {
            DB::table('user_tag_subscription')
                ->where('user_id', $user->id)
                ->delete();

            foreach ($tagIds as $tagId) {
                DB::table('user_tag_subscription')->insert([
                    'user_id' => $user->id,
                    'tag_id' => $tagId,
                ]);
            }
        });
    }
}

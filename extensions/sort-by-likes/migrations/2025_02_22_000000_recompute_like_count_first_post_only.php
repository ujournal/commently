<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema): void {
        $columns = $schema->getColumnListing('discussions');
        if (! in_array('like_count', $columns)) {
            return;
        }

        // Recompute discussion like_count from first-post likes only (post number = 1)
        $schema->getConnection()->statement(
            'UPDATE discussions SET like_count = (
                SELECT COALESCE(COUNT(*), 0) FROM post_likes
                INNER JOIN posts ON posts.id = post_likes.post_id
                WHERE posts.discussion_id = discussions.id AND posts.number = 1
            )'
        );
    },
    'down' => function (): void {
        // Irreversible: we cannot restore the previous "all posts" count without stored data
    },
];

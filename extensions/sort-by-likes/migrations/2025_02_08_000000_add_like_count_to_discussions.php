<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->unsignedInteger('like_count')->default(0)->after('comment_count');
        });

        // Backfill: set like_count from current post_likes per discussion
        $connection = $schema->getConnection();
        $connection->statement(
            'UPDATE discussions SET like_count = (
                SELECT COALESCE(COUNT(*), 0) FROM post_likes
                INNER JOIN posts ON posts.id = post_likes.post_id
                WHERE posts.discussion_id = discussions.id
            )'
        );
    },
    'down' => function (Builder $schema) {
        $schema->table('discussions', function (Blueprint $table) {
            $table->dropColumn('like_count');
        });
    },
];

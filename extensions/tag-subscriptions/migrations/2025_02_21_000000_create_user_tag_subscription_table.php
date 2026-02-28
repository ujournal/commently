<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('user_tag_subscription', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('tag_id');
            $table->primary(['user_id', 'tag_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('user_tag_subscription');
    },
];

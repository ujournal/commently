<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $columns = $schema->getColumnListing('fof_upload_files');
        if (in_array('width', $columns) && in_array('height', $columns)) {
            return;
        }
        $schema->table('fof_upload_files', function (Blueprint $table) use ($columns) {
            if (! in_array('width', $columns)) {
                $table->unsignedInteger('width')->nullable()->after('size');
            }
            if (! in_array('height', $columns)) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
        });
    },
    'down' => function (Builder $schema) {
        $columns = $schema->getColumnListing('fof_upload_files');
        $drop = array_filter(['width', 'height'], function ($col) use ($columns) {
            return in_array($col, $columns);
        });
        if (count($drop) > 0) {
            $schema->table('fof_upload_files', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    },
];

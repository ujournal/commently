<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->getConnection()->table('fof_upload_files')
            ->where('upload_method', 'awss3')
            ->update(['upload_method' => 'aws-s3']);
    },
    'down' => function (Builder $schema) {
        $schema->getConnection()->table('fof_upload_files')
            ->where('upload_method', 'aws-s3')
            ->update(['upload_method' => 'awss3']);
    },
];

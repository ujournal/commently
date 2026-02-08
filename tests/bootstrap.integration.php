<?php

require dirname(__DIR__) . '/vendor/autoload.php';

if (! getenv('FLARUM_TEST_TMP_DIR_LOCAL') && ! getenv('FLARUM_TEST_TMP_DIR')) {
    putenv('FLARUM_TEST_TMP_DIR_LOCAL=' . dirname(__DIR__) . '/tests/integration/tmp');
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

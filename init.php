<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/lib.php';

$config = include __DIR__.'/config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

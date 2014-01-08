<?php

require 'vendor/autoload.php';
require 'lib.php';

require 'SvnGetDiffJob.php';
require 'SvnGetBlameJob.php';
require 'SvnUpdateLogJob.php';

$config = include 'config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

include 'vendor/chrisboulton/php-resque/resque.php';

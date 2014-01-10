<?php

include 'init.php';

require 'SvnGetDiffJob.php';
require 'SvnGetBlameJob.php';
require 'SvnUpdateLogJob.php';

include 'vendor/chrisboulton/php-resque/resque.php';

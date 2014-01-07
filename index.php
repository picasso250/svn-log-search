<?php

require 'vendor/autoload.php';
require 'lib.php';
require 'SvnUpdateLogJob.php';
$config = include 'config.php';

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$root_url = $config['repos'][$repo];

$fpath = get_log_file_name($root_url);
if (!file_exists($fpath)) {
    $args = array(
            'url' => $root_url,
            );
    $token = Resque::enqueue('default', 'SvnUpdateLogJob', $args);
    die('we are updating, please come back later');
}

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$keywords = array_filter(explode(' ', trim($keyword)), 'trim');
$logs = search($keywords, $root_url);

include 'index.phtml';

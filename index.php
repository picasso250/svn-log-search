<?php

require 'vender/autoload.php';
require 'lib.php';
$config = include 'config.php';

$root_url = $config[0];

$fpath = get_log_file_name($root_url);
if (!file_exists($fpath)) {

    // Required if redis is located elsewhere
    Resque::setBackend('localhost:6379');

    $args = array(
            'url' => $root_url,
            );
    Resque::enqueue('default', 'SvnUpdateLogJob', $args);

    die('we are updating, please come back later');

}

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$keywords = array_filter(explode(' ', trim($keyword)), 'trim');
$logs = search($keywords, $root_url);

include 'index.phtml';

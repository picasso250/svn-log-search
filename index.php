<?php

require 'vendor/autoload.php';
require 'lib.php';
require 'SvnUpdateLogJob.php';
$config = include 'config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$root_url = $config['repos'][$repo];

$args = array(
        'url' => $root_url,
        );
$token = Resque::enqueue('log', 'SvnUpdateLogJob', $args);

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$keywords = array_filter(explode(' ', trim($keyword)), 'trim');
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
if ($is_ajax) {
    $limit = 10;
} else {
    $limit = 100;
}
$logs = search_db($keywords, $root_url, $limit);

$data = array(
    'root_url' => $root_url,
    'logs' => $logs,
    'keyword' => $keyword,
    'keywords' => $keywords,
    'title' => 'SVN Log Search',
);
if ($is_ajax) {
    render('template/logs.phtml', $data);
    exit();
}
render('index.phtml', $data, 'layout.phtml');

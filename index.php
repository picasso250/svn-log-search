<?php

include 'init.php';

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
list($logs, $count) = search_db($keywords, $root_url, $limit);

$data = array(
    'root_url' => $root_url,
    'logs' => $logs,
    'count' => $count,
    'keyword' => $keyword,
    'keywords' => $keywords,
    'title' => 'SVN Log Search',
);
if ($is_ajax) {
    render('logs.phtml', $data);
    exit();
}
render('index.phtml', $data, 'layout.phtml');

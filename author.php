<?php

require 'vendor/autoload.php';
require 'lib.php';
$config = include 'config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

if (!isset($_GET['repo']) || !isset($_GET['author'])) {
    die('no repo or no author given');
}

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$author = $_GET['author'];

$args = array(
        'url' => $repo,
        );
$token = Resque::enqueue('log', 'SvnUpdateLogJob', $args);

$count = get_commit_count_by_author($repo, $author);
$file_count = get_file_commit_count_by_author($repo, $author);
$logs = get_commit_by_author($repo, $author);

$data = array(
    'count' => $count,
    'file_count' => $file_count,
    'logs' => $logs,
    'title' => "$author",
);
render('author.phtml', $data, 'layout.phtml');

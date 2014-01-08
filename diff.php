<?php

require 'vendor/autoload.php';
require 'lib.php';
require 'SvnGetDiffJob.php';
$config = include 'config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

if (!isset($_GET['repo']) || !isset($_GET['file']) || !isset($_GET['revision'])) {
    die('no repo or no file or no revision given');
}

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$file = $_GET['file'];
$revision = $_GET['revision'];

$diff = get_diff_from_db($repo, $file, $revision);
if (empty($diff)) {
    $args = array(
            'url' => $repo,
            'file' => $file,
            'revision' => $revision,
            );
    $token = Resque::enqueue('diff', 'SvnGetDiffJob', $args);
    die('we are get diff, please come back later');
}

render('diff.phtml', array(
    'diff' => $diff,
    'title' => 'SVN Diff',
), 'layout.phtml');


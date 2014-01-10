<?php

require 'vendor/autoload.php';
require 'lib.php';
$config = include 'config.php';

ORM::configure($config['dsn']);
ORM::configure($config['db_config']);

if (!isset($_GET['repo']) || !isset($_GET['revision'])) {
    die('no repo or no revision given');
}

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$revision = $_GET['revision'];

$args = array(
        'url' => $repo,
        );
$token = Resque::enqueue('log', 'SvnUpdateLogJob', $args);

$rev = get_rev($repo, $revision);

$data = array(
    'log' => $rev,
    'title' => "Revision $revision",
);
render('revision.phtml', $data, 'layout.phtml');

<?php

include 'init.php';

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
    'repo' => $repo,
);
render('revision.phtml', $data, 'layout.phtml');

<?php

include 'init.php';

if (!isset($_GET['repo']) || !isset($_GET['file']) || !isset($_GET['revision'])) {
    die('no repo or no file or no revision given');
}

$repo = isset($_GET['repo']) ? $_GET['repo'] : 0;
$file = $_GET['file'];
$revision = $_GET['revision'];

$blame = get_blame_from_db($repo, $file, $revision);
if (empty($blame)) {
    $args = array(
            'url' => $repo,
            'file' => $file,
            'revision' => $revision,
            );
    $token = Resque::enqueue('blame', 'SvnGetBlameJob', $args);
    die('we are get blame, please come back later');
}

render('blame.phtml', array(
    'blame' => $blame,
    'title' => 'SVN Blame '.$file,
), 'layout.phtml');


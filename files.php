<?php

include 'init.php';

$repo = isset($_GET['repo']) ? $_GET['repo'] : '';
$rev_id = isset($_GET['rev_id']) ? $_GET['rev_id'] : '';

$files = get_files_by_rev($rev_id);
$data = array(
    'files' => $files,
    'repo' => $repo,
    'log' => ORM::forTable('rev')->findOne($rev_id),
);
render('files.phtml', $data);

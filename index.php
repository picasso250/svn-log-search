<?php

require 'lib.php';

$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$root_url = isset($_GET['root_url']) ? $_GET['root_url'] : '';
if ($root_url) {
    $keywords = array_filter(explode(' ', trim($keyword)), 'trim');
    $logs = search($keywords, $root_url);
}

include 'index.phtml';

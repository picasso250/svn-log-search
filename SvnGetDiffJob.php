<?php

class SvnGetDiffJob
{
    public function perform()
    {
        $root_url = $this->args['url'];
        $file_path = $this->args['file'];
        $revision = $this->args['revision'];
        echo "get diff for $root_url $file_path @ $revision"," ...\n";
        get_diff($root_url, $file_path, $revision);
        echo "ok\n";
    }
}

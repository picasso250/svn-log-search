<?php

class SvnGetBlameJob
{
    public function perform()
    {
        $root_url = $this->args['url'];
        $file_path = $this->args['file'];
        $revision = $this->args['revision'];
        echo "get blame for $root_url $file_path @ $revision"," ...\n";
        get_blame($root_url, $file_path, $revision);
        echo "ok\n";
    }
}

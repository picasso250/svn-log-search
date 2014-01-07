<?php

class SvnUpdateLogJob
{
    public function perform()
    {
        echo $this->args['url'],"\n";
        update_cache($this->args['url']);
    }
}

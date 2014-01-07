<?php

class SvnUpdateLogJob
{
    public function perform()
    {
        echo "update log of ",$this->args['url']," ... ";
        update_cache($this->args['url']);
        echo "ok\n";
    }
}

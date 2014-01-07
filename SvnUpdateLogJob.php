<?php

class SvnUpdateLogJob
{
    public function perform()
    {
        echo "update log of ",$this->args['url']," ...\n";
        update_svn_log_db($this->args['url']);
        echo "ok\n";
    }
}

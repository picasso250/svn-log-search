<?php

class SvnUpdateLogJob
{
    public function perform()
    {
        echo "update log of ",$this->args['url']," ...\n";
        init_svn_log_db($this->args['url']);
        echo "ok\n";
    }
}

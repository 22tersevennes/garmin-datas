<?php

namespace MyGarmin;

class Log 
{

    public function trace($str) 
    {
        fwrite(STDOUT, $str);
    }
    
    public function error($str) 
    {
        fwrite(STDERR, $str);
        self::trace($str);
    }

}
<?php
class Logger
{
    public static function Log($msg,$newline=true)
        {
            list($usec, $sec) = explode(" ", microtime());
            list($sec,$usec) = explode(".",$usec);        
            echo(date("Y/m/d H:i:s").".".$usec." ".$msg.($newline ? "\n" : ""));
        }
}
?>
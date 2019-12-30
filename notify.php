<?php

use Mosquitto\Client;

$pid = pcntl_fork();
if ($pid == -1) 
    {
        die('could not fork');
    } 
elseif ($pid)
    {
//        pcntl_wait($status); //Protect against Zombie children
    }
else
    {

require(__DIR__.'/vendor/autoload.php');

Supla\Project\Init::Config(__DIR__);

try {
        ob_start();    

        $dump=new dump(array(
            'type' => 'usocket',
            'file'=>SYS_DIR.'/'.ConfigHelper::getConfig('notify.file'), 'minread'=>0,
            'rtype'=>PHP_NORMAL_READ
        ));

        $c = new Mosquitto\Client("PHP");

        $messages=array();
        $c->onMessage(function($msg) use($c, &$messages) {
            $messages[]=$msg;
            $c->exitLoop();
        });
        
        $MQTT=new SUPLA\Notify\MQTT($c);
        $MQTT->connect();
        $cfgMgr=new SUPLA\Notify\Config;
        $cfgMgr->check($MQTT);
        while (!$exit)
            {
                $d=$dump->getFromSocket();
                if($d)
                {
                    if($d[strlen($d)-1]!="\n")
                        continue;
                    $e=explode("\n",$d);
                    foreach($e as $k=>$v)
                        {
                            $dump->resetBuf();
                            $arr=unserialize(trim($v));
                            if($arr['type']=='STATIC')
                                {
                                    $msg=$arr['params'];
                                    $class="".__NAMESPACE__."\\".$arr['params']['class'];
                                    $method=$arr['params']['method'];
                                    if(method_exists($class,$method))
                                        $class::$method($arr['data']);
                                }
                            if($arr['type']=='CALL')
                                {
                                    $msg=$arr['params'];
                                    $class=$arr['params']['class'];
                                    $method=$arr['params']['method'];
                                    if(method_exists($$class,$method))
                                        $$class->$method($arr['data']);
                                }
                            if($arr['type']=='SYS')
                                {
                                    $msg=$arr['params'];
                                    if($arr['exec']=='RELOAD')
                                        $cfgMgr->check($MQTT);
                                    if($arr['exec']=='CLOUD_RELOAD')
                                        Supla\Project\Cloud::getConfig(true);
                                }
                        }
                }

                $c->loop(100);
                if (count($messages)>0)
                    {
                        foreach($messages as $k=>$v)
                            {
                                unset($messages[$k]);
                                $MQTT->onMessage($v);
                            }
                    }
                $MQTT->Execute();
                usleep(10000);

                $debugData.=ob_get_contents();
                if(strlen($debugData)>256 && $argv[1])
                    {
                        file_put_contents($argv[1],$debugData,FILE_APPEND|LOCK_EX);
                        $debugData='';
                    }
                ob_clean();
            }
    }
catch (exception $e)
    {
    }

    }
?>

<?php
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

        $CONFIG=Supla\Project\Init::Config(__DIR__);

        $WSSocketServer=new WebSocketServer($CONFIG);

        $dump=new dump(array(
            'type' => 'usocket',
            'file'=>SYS_DIR.'/'.$CONFIG['socket']['file'], 'minread'=>0,
            'rtype'=>PHP_NORMAL_READ
        ));

        echo("Output buffer write to: ".$argv[1]."\n");
        ob_start();    
        while(1)
            {
                $WSSocketServer->readSocket();
                $d=$dump->getFromSocket();
                if($d)
                {
                    if($d[strlen($d)-1]!="\n")
                        continue;
                    $e=explode("\n",$d);
                    foreach($e as $k=>$v)
                        {
                            $dump->resetBuf();
                            try
                                {
                                }
                            catch (exception $e)
                                {
                                    ErrorLogger::write(__FILE__,__LINE__,$e->getMessage());
                                    continue;
                                }
                            $arr=unserialize(trim($v));
                            if($arr['type']=='WS')
                                {
                                    $msg=$arr['params'];
                                    $WSSocketServer->response($arr);
                                }
                            if($arr['type']=='STATIC')
                                {
                                    $msg=$arr['params'];
                                    $class="".__NAMESPACE__."\\".$arr['params']['class'];
                                    $method=$arr['params']['method'];
                                    if(method_exists($class,$arr['params']['method']))
                                        $class::$method($arr['data']);
                                }
                            if($arr['type']=='SYS')
                                {
                                    if($arr['exec']=='CLOUD_RELOAD')
                                        Supla\Project\Cloud::getConfig(true);
                                }
                        }
                }
                else
                    usleep(1000);

		//dummy ping every client
		if(date('is') == "0000")
			$WSSocketServer->Ping();

                $debugData.=ob_get_contents();
                if(strlen($debugData)>256 && $argv[1])
                    {
                        file_put_contents($argv[1],$debugData,FILE_APPEND|LOCK_EX);
                        $debugData='';
                    }
                ob_clean();
            }
        $WSSocketServer->closeSocket();
        $dump->close();
        ob_end_clean();
    }


?>

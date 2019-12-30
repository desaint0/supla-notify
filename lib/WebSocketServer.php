<?php
class WebSocketServer
{
    function __construct($CONFIG)
        {
            $this->debugLevel=6;
            $this->CONFIG=$CONFIG['socket'];
            $context = stream_context_create();
            stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'local_cert', $this->CONFIG['local_cert'] ? $this->CONFIG['local_cert'] : '/etc/apache2/apache2.pem');

            $this->socket = stream_socket_server(
                'ssl://'.$this->CONFIG['host'].":".$this->CONFIG['port'],
                $errno,
                $errstr,
                STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
                $context
            );
            if(!$this->socket)
		    echo ("$errstr ($errno)\n");

            stream_socket_enable_crypto($this->socket, false);
            $this->clients = array($this->socket);
            $this->_clients = array();
            $this->_max=0;
            $this->_buf='';
        }
    function response($resp)
        {
            foreach($this->_clients as $k=>$v)
                {
                    if(!$v['authenticated'])
                        continue;
                    if(!$v['queue'])
                        continue;
                    $resp['params']['script']="";
                    foreach($v['queue'] as $qk=>$qv)
                        {
                            if($qk!=$resp['params']['name'])
                                continue;
                            $classN=$resp['params']['class'] ? $resp['params']['class'] : $qv['class'];
                            $methodN=$resp['params']['method'] ? $resp['params']['method'] : $qv['method'];
                            if($classN && class_exists($classN))
                                {
                                    $class=new $classN;
                                    if($methodN && method_exists($class,$methodN))
                                        {                                    
                                            if($this->debugLevel>4)
                                                echo("Calling ".$classN."->".$methodN." for ".$qk."/".$k."\n");
                                            $r=$class->$methodN($qv['data'],$resp);
                                            if(!$r['script'])
                                                continue;
                                            $resp['params']['script'].=$r['script'];
                                        }
                                }
                        }
                    if($this->debugLevel>4)
                        echo("Response: ".json_encode($resp['params'])."\n");
                    if($resp['params']['script'])
                        $this->sendMsgToClient($k,$resp['params']);
                }
        }
    function readSocket()
        {
            $changed = $this->clients;
            @stream_select($changed, $null, $null, 0, 100);
            if (in_array($this->socket, $changed))
                {
                    $socket_new=stream_socket_accept($this->socket,0,$remoteIP);
                    if(!$socket_new)
                        return;
                    stream_socket_enable_crypto($socket_new, true, STREAM_CRYPTO_METHOD_TLSv1_2_SERVER);

                    $newkey=1;
                    if($this->debugLevel>4)
                        echo("Accepting new Con, searching for key ($newkey)... ".$this->_max."\n");
                    for($i=1;$i<=$this->_max;$i++)
                        {
                            if(!$this->clients[$i])
                                {
                                    $newkey=$i;
                                    break;
                                }
                            $newkey=$i+1;
                        }
                    if($this->debugLevel>4)
                        echo("Found new id: ".$newkey." of ".count($this->clients)."\n");
                    $this->_max=$this->_max>$newkey ? $this->_max : $newkey;
                    $this->clients[$newkey] = $socket_new; //add socket to client array		
                    $header="";
                    $to_read=1;
                    $time=microtime(true);
                    stream_set_blocking($socket_new,false);
                    while(!feof($socket_new))
                        {
                            $read = fread($socket_new, $to_read); //read data sent by the socket
                            $header.=$read;
                            $stream_meta_data = stream_get_meta_data($socket_new);
                        if($header)
                            $to_read=$stream_meta_data['unread_bytes'];
                            if($to_read <= 0)
                                break;
                            if($time+5<microtime(true))
                                {
                                    $header="";
                                    break;
                                }
                        }
                    stream_set_blocking($socket_new,true);
                    if($header)
                        {
                            $this->perform_handshaking($header, $socket_new, $this->CONFIG['host'], $this->CONFIG['port']); //perform websocket handshake
                        }

                    $found_socket = array_search($this->socket, $changed);
                    unset($changed[$found_socket]);
                }
	
	//loop through all connected sockets
            foreach ($changed as $changed_socket)
                {
                    $buf="";
                    $to_read=1;
                    $time=microtime(true);
                    stream_set_blocking($changed_socket,false);
                    while(!feof($changed_socket))
                        {
                            $read=fread($changed_socket, $to_read);
                            $stream_meta_data = stream_get_meta_data($changed_socket);
                            $buf.=$read;
                            if($buf)
                                $to_read=$stream_meta_data['unread_bytes'];
                            if($to_read <= 0)
                                break;
                            if($time+5<microtime(true))
                                {
                                    $buf="";
                                    break;
                                }
                        }
                    stream_set_blocking($changed_socket,true);
                    $buf=$this->_clients[$found_socket]['buf'].$buf;
                    $this->_clients[$found_socket]['buf']='';
                    if($buf)
                        {
                            $found_socket = array_search($changed_socket, $this->clients);
                            $received_text = $this->unmask($buf); //unmask data
                            $msg = json_decode($received_text,true); //json decode
                            if(!$msg)
                                $this->_clients[$found_socket]['buf']=$buf;
                            if($msg['cmd']=='init')
                                {
                                    $this->_clients[$found_socket]['authenticated']=false;
                                    if(is_array($msg['var']))
                                        foreach($msg['var'] as $k=>$v)
                                            $this->_clients[$found_socket]['params'][$v['var']]=$v['value'];
                                    else
                                        $this->_clients[$found_socket]['params'][$msg['var']]=$msg['value'];
                                }
                            if($msg['cmd']=='addqueue')
                                {
                                    if($this->debugLevel>4)
                                        echo("Adding ".$msg['cmd']." (".$msg['name'].") to queue (".$found_socket.") for '".$msg['authID']."'...\n");
                                    $this->_clients[$found_socket]['queue'][$msg['name']]=$msg['value'];
                                }
                            if($msg['cmd']=='delqueue')
                                {
                                    if($this->debugLevel>4)
                                        echo("Removing ".$msg['cmd']." queue (".$found_socket.") for '".$msg['authID']."'...\n");
                                    unset($this->_clients[$found_socket]['queue'][$msg['name']]);
                                }
                            if($msg['cmd']=='cmd')
                                {
                                    if($this->_clients[$found_socket]['authenticated'])
                                       {
                                            $classN=$msg['class'];
                                            $methodN=$msg['method'];
                                    
                                            if($classN && class_exists($classN))
                                                {
                                                    $class=new $classN;
                                                    if($methodN && method_exists($class,$methodN))
                                                        {                                    
                                                            if($this->debugLevel>4)
                                                                echo("Executing ".$msg['cmd']." ".$classN."->".$methodN." for '".$msg['authID']."'\n");
                                                            $r=$class->$methodN($msg['data'],$resp);
                                                            if($r)
                                                                $this->sendMsgToClient($found_socket,$r);
                                                        }
                                                } 
                                        }
                                }
                                
                            if($this->_clients[$found_socket]['params']['_login'] && $this->_clients[$found_socket]['params']['_pass'] && !$this->_clients[$found_socket]['authenticated'])
                                {
                                    if($this->_clients[$found_socket]['params']['_login']==$this->CONFIG['login'] && $this->_clients[$found_socket]['params']['_pass']==crypt($this->CONFIG['pass'],'$1$'.$this->_clients[$found_socket]['params']['_salt'].'$'))
                                        {
                                            if($this->debugLevel>4)
                                                echo("Accepting new client: ".$found_socket."\n");
                                            $this->_clients[$found_socket]['authenticated']=true;
                                            $this->sendMsgToClient($found_socket,array(
                                                'type'=> 'auth',
                                                'value'=> 'OK'
                                            ));
                                        }
                                    else
                                        {
                                            $this->sendMsgToClient($found_socket,array(
                                                'type'=>'auth',
                                                'value'=> 'failed'
                                            ));
                            		    $this->closeSocket($this->clients[$found_socket]);
                                            unset($this->clients[$found_socket]);
                                            unset($this->_clients[$found_socket]);                                        
                                        }
                                }
                        }

                    if ($buf == "")
                        { 
                            $found_socket = array_search($changed_socket, $this->clients);
                            $this->closeSocket($this->clients[$found_socket]);
                            unset($this->clients[$found_socket]);
                            unset($this->_clients[$found_socket]);
                            if($this->debugLevel>4)
                                echo("Unlink socket id: ".$found_socket."\n");

                            
                        }
                }
        }
    
// close the listening socket
    function closeSocket($socket)
        {
            stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        }

    function sendMsgToClient($k,$m)
        {
            $msg=$this->mask(json_encode($m));
            stream_set_blocking($this->clients[$k],false);
            if(!fwrite($this->clients[$k],$msg,strlen($msg)))
                {        
                    $this->closeSocket($this->clients[$k]);
                    unset($this->clients[$k]);
                    unset($this->_clients[$k]);
                }
	    else
            	stream_set_blocking($this->clients[$k],true);
        }
    function send_message($msg)
        {
            foreach($this->clients as $changed_socket)
                {
                    stream_set_blocking($this->socket,false);
                    fwrite($changed_socket,$msg,strlen($msg));
                    stream_set_blocking($this->socket,true);
                }
            return true;
        }


//Unmask incoming framed message
    function unmask($text)
        {
            $length = ord($text[1]) & 127;
            if($length == 126)
                {
                    $masks = substr($text, 4, 4);
                    $data = substr($text, 8);
                }
            elseif($length == 127)
                {
                    $masks = substr($text, 10, 4);
                    $data = substr($text, 14);
                }
            else
                {
                    $masks = substr($text, 2, 4);
                    $data = substr($text, 6);
                }
            $text = "";
            for ($i = 0; $i < strlen($data); ++$i)
                {
                    $text .= $data[$i] ^ $masks[$i%4];
                }
            return $text;
        }

//Encode message for transfer to client.
    function mask($text)
        {
            $b1 = 0x80 | (0x1 & 0x0f);
            $length = strlen($text);
	
            if($length <= 125)
                $header = pack('CC', $b1, $length);
            elseif($length > 125 && $length < 65536)
                $header = pack('CCn', $b1, 126, $length);
            elseif($length >= 65536)
                $header = pack('CCNN', $b1, 127, $length);
            return $header.$text;
        }

//handshake new client.
    function perform_handshaking($receved_header,$client_conn, $host, $port)
        {
            $headers = array();
            $lines = preg_split("/\r\n/", $receved_header);
            foreach($lines as $line)
                {
                    $line = chop($line);
                    if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
                        {
                            $headers[$matches[1]] = $matches[2];
                        }
                }
            $secKey = $headers['Sec-WebSocket-Key'];
            $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
//hand shaking header
            $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: ".$host."\r\n" .
            "WebSocket-Location: wss://$host:$port\r\n".
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
            stream_set_blocking($client_conn,false);
            fwrite($client_conn,$upgrade,strlen($upgrade));            
            stream_set_blocking($client_conn,true);
        }
/*
    Call unix socket (from api).
*/
    public static function CallWS($v,$socket_name="socket")
        {
            if(!ConfigHelper::getConfig('socket.enabled'))
                return false;

            $count=20;
            $hash=md5(json_encode($v).time());

            $socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            if(!$socket)
                {
                    error_log('Unix socket create fail');
                    return false;
                }
            socket_set_nonblock($socket);
            $connect=false;
            while($count>0 && !$connect)
                { 
                    if(!socket_connect($socket, SYS_DIR.'/'.ConfigHelper::getConfig($socket_name.'.file')))
                        {
                            error_log($hash.' => Fail to connect to unix socket '.ConfigHelper::getConfig($socket_name.'.file'). " ".socket_strerror(socket_last_error()) ." count: ".$count .", ".json_encode($v));
                            $count--;
                            if(!$count)
                                {
                                    error_log($hash.' => Exiting => Fail to connect to unix socket '.ConfigHelper::getConfig($socket_name.'.file'). " ".socket_strerror(socket_last_error()) ." count: ".$count);
                                    return false;
                                }
                            usleep(rand(50,100)*1000);
                        }
                    else
                        $connect=true;
                }
            $data=serialize($v)."\n";
            socket_write($socket, $data, strlen($data));
            socket_close($socket);
            return true;
        }        

	function Ping()
	{
		//some dummy message to every client to time-out (SendQ'ed) zombie connections
		$m = 'PING PONG PUNK';
		foreach($this->_clients as $k=>$v)
		{
			if($v['authenticated']) {
				$this->sendMsgToClient($k,$m);
				if($this->debugLevel>4)
					echo (date('c')."  ping id $k \n");
			}
		}
		usleep (500000);
	}
}

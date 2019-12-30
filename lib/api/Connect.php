<?php
namespace Supla\API;

class Connect
{
    private static $_host='';
    private static $_port=null;
    private static $_token='';
    private static $_handler=null;

    private static $_proto="ssl://";
    private static $_obj=null;
        
    public function __construct($host,$port,$token)
        {
            self::$_host=$host;
            self::$_port=$port;
            self::$_token=$token;
            self::$_obj=$this;
        }
    public function connect()
        {
            if(self::$_handler!==null)
                return self::$_handler;
            
	    $options=array(
               'ssl'=> array(
            	    'verify_peer' => false,
		    'verify_peer_name' => false,
		    'cafile' => '/etc/ssl/certs/cacert.pem',
        	)
    	    );
            $socket_context = stream_context_create($options);
             //Suppress errors; connection failures are handled at a higher level
            self::$_handler = stream_socket_client(
               self::$_proto.self::$_host . ":" . self::$_port,
               $errno,
               $errstr,
               10,
               STREAM_CLIENT_CONNECT,
               $socket_context
           );
                                                                                                                                                               
#            self::$_handler = fsockopen(self::$_proto.self::$_host, self::$_port, $errno, $errstr, 10);
            return self::$_handler;
        }
    public function disconnect()
        {
            fclose(self::$_handler);
            self::$_handler=null;
        }
    public static function getConnection()
        {
            self::$_obj->connect();
            return self::$_obj;
        }
    public function send($method='GET',$uri='/',$data=null,$params=array())
        {
            $data_to_send=array_key_exists('Content-Type',$params) ? ($params['Content-Type']=='application/json' ? json_encode($data) : $data) : $data;
            $headers[]=$method.' '.$uri.' HTTP/1.1';
            $headers[]="Host: ".self::$_host;
            $headers[]="Authorization: Bearer ".self::$_token;
            $headers[]="Content-Type: ".(array_key_exists('Content-Type',$params) ? $params['Content-Type'] : "application/x-www-form-urlencoded");
            $headers[]="Content-length: ".strlen($data_to_send);
            $headers[]="Connection: Close";
            
            $out=implode("\r\n",$headers)."\r\n\r\n";
            if($data_to_send)
                $out.=$data_to_send;

            fwrite(self::$_handler, $out);
        }
    private function decodeResp($l)
        {
            $headers=array();
            $l=explode("\r\n",$l);
            foreach($l as $k=>$v)
                if($v=="")
                    break;
                else
                    {
                        list($t,$w)=explode(":",$v);
                        $headers[$t]=trim($w);
                        unset($l[$k]);
                    }
            $data=implode("\n",$l);
            if(isset($headers['Transfer-Encoding']))
                if($headers['Transfer-Encoding']=='chunked')
                    $data=$this->_http_chunked_decode($data);

            $raw=$data;
            if($headers)
                if($headers['Content-Type']=='application/json')
                    $data=json_decode($data,true);
            return array('headers' => $headers, 'data'=>$data, 'raw'=>$raw);
        }
    public function read()
        {
            $l='';
            while (!feof(self::$_handler))
                {
                    $l.=fgets(self::$_handler, 1024);
                }
            return $this->decodeResp($l);
        }
    private function _is_hex($hex) 
        {
            $hex = strtolower(trim(ltrim($hex,"0")));
            if (empty($hex)) { $hex = 0; };
            $dec = hexdec($hex);
            return ($hex == dechex($dec));
        }
    private function _http_chunked_decode($chunk) 
        {
            $pos = 0;
            $len = strlen($chunk);
            $dechunk = null;

            while(($pos < $len) && ($chunkLenHex = substr($chunk,$pos, ($newlineAt = strpos($chunk,"\n",$pos+1))-$pos)))
                {
                    if (! $this->_is_hex($chunkLenHex)) 
                        {
                            trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
                            return $chunk;
                        }
                    $pos = $newlineAt + 1;
                                                                                                                    
                    $chunkLen = hexdec(rtrim($chunkLenHex,"\r\n"));
                    $dechunk .= substr($chunk, $pos, $chunkLen);
                    $pos = strpos($chunk, "\n", $pos + $chunkLen) + 1;
                }
            return $dechunk;
        }
} 
?>
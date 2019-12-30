<?php
class dump
{
    private $_prev="";
    private $_curr="";
    private $_buf="";
    function __construct($cfg)
        {
            $this->first=true;
            $this->_pcapHeader="";
            $this->type=$cfg['type'] ? $cfg['type'] : 'stdin';
            $this->minread=$cfg['minread'];
            $this->rtype=$cfg['rtype'];
            
            if(!is_array($cfg) || $cfg['type']=='stdin')
                {
                    $this->_handle=fopen("php://stdin","r");
                }
            if($cfg['type']=='usocket')
                {
                    echo("Create socket for ".$cfg['file']."...\n");
		    if(file_exists($cfg['file']))
                    	unlink($cfg['file']);
                    $this->handle = socket_create(AF_UNIX, SOCK_STREAM, 0);
                    socket_bind($this->handle, $cfg['file']);
                    socket_listen($this->handle);
                    socket_set_nonblock($this->handle);                
                }
        }
    function getFromSocket()
        {
            if(strlen($this->_prev)<=$this->minread)
                {
                    if($this->type=='stdin')
                        $this->_buf=fread($this->_handle,8192);
                    if($this->type=='usocket')
                        {
                            if($s=@socket_accept($this->handle))
                                {
                                    socket_set_nonblock($s);                
                                    $this->_buf=socket_read($s,8192,$this->rtype ? $this->rtype : PHP_BINARY_READ);
                                    socket_close($s);
                                }
                        }
                }
            $this->_curr=$this->_curr.$this->_buf;
            $this->_curr=$this->_prev.$this->_curr;
            $this->_buf="";
            $this->_prev="";
            return $this->_curr; 
        }
    function resetBuf()
        {
            $this->_curr="";
        }
    function changePrev($buf)
        {
            $this->_prev=$buf;
        }
    function close()
        {
            fclose($this->_handle);
        }
}

?>

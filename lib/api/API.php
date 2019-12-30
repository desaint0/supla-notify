<?php
namespace Supla\API;

class API
{
    private static $api=null;
    
    function __construct()
        {
            if(self::$api!==null)
                return;
            self::$api=$this;
        }

    public static function getAPI()
        {
            return self::$api;
        }    

    private function _send($t,$m,$id=null)
        {
            $this->server=Connect::getConnection();
            $this->server->send($t,'/api/'.$m.($id ? '/'.$id : ''));
            $r=$this->server->read();
            $this->server->disconnect();
            return $r;
        }
    function getChannels($id=null)
        {
            $r=$this->_send('GET','channels',$id);
            if($id)
                return $r;
            foreach($r['data'] as $k=>$v)
                $to_return[$v['id']]=$v;
            $r['data']=$to_return;
            return $r;
        }
    function getChannelsGroups($id=null)
        {
            $r=$this->_send('GET','channel-groups',$id);
            if($id)
                return $r;
            foreach($r['data'] as $k=>$v)
                $to_return[$v['id']]=$v;
            $r['data']=$to_return;
            return $r;
        }
    function getIOdevices($id=null)
        {
            $r=$this->_send('GET','iodevices',$id);
            if($id)
                return $r;
            foreach($r['data']['iodevices'] as $k=>$v)
                $to_return[$v['id']]=$v;
            $r['data']=$to_return;
            return $r;
        }
    function getLocations($id=null)
        {
            $r=$this->_send('GET','locations',$id);
            if($id)
                return $r;
            foreach($r['data']['locations'] as $k=>$v)
                $to_return[$v['id']]=$v;
            $r['data']=$to_return;
            return $r;
        }
}
?>
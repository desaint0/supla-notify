<?php
class SuplaChannelsWS
    {
/*
$d = data array from JS
$r = data array from calling PHP script (API)
*/
        private static $channels;
          
        function changeStatus($d,$r)
            {
                $DB=\LMSDB::getInstance();
                $id=explode("/",$r['data']['channel']);
                $id=$id[count($id)-1];
                $data=$DB->GetRow("SELECT * from cloud where type_mqtt='subscribe' and cloud='supla' and type_id=?",array($id));
                $data['payload']=json_decode($data['payload']);
                foreach($d['obj'] as $k=>$v)
                    $resp['script'].="
                        ".$v.".UpdateState('".json_encode($data)."','".$r['data']['value']."');
                    ";
                return $resp;
            }
        public static function UpdateChannel($r)
            {
                self::$channels[$r['channel']]=$r['value'];
                $arr=array(
                    'type'=> 'WS',
                    'params'=>array(
                    'name' => 'SuplaChannels',
                        'class' => 'SuplaChannelsWS',
                        'method' => 'changeStatus',
                    ),
                    'data'=>array(
                        'channel' => $r['channel'],
                        'value' => $r['value'], 
                    ),
                );
                WebSocketServer::CallWS($arr);
            }
        function initialStatus($d,$r)
            {
                foreach(self::$channels as $k=>$v)
                    {
                        $s=array('data'=> 
                            array(
                                'channel' => $k,
                                'value' =>$v, 
                            ) 
                        );
                        $ch=$this->changeStatus($d,$s);
                        $resp['script'].=$ch['script'];
                    }
                return $resp;
            }
    }
?>
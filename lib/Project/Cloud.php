<?php
namespace Supla\Project;

class Cloud
{
    private static $api;
    private static $mqttData;
    private static $config;
    
    private static $mqttPrefix='supla/channels/';
    
    public function ShowCloud($name)
        {
            $objResponse=new \XajaxResponse;

            $result=\Supla\Project\Init::getTemplate()->fetch('Cloud_config.html');
            $script=\LMSWindowManager::getJS($result);            
            $objResponse->assign($name,'innerHTML',$result);
            $objResponse->script($script);
            return $objResponse;
        }
    public function CloudGet()
        {
            $objResponse=new \XajaxResponse;

            $server=new \Supla\API\Connect(\ConfigHelper::getConfig('supla.host'),\ConfigHelper::getConfig('supla.port'),\ConfigHelper::getConfig('supla.authkey'));
            $server->connect();

            try
                {
                    self::$api=new \Supla\API\API();
                    $ch=self::$api->getChannels();
                    $dev=self::$api->getIOdevices();

                    if(!$ch || !$dev)
                        throw new \exception('Brak danych z Cloud Supla');

                    self::prepareData($dev['data'],$ch['data']);
                    self::saveConfig();
                    $objResponse->assign('CloudRet','innerHTML','<div class="alert alert-success">Zapisano konfigurację</div>');
                }
            catch(\exception $e)
                {
                    $objResponse->assign('CloudRet','innerHTML','<div class="alert alert-danger">'.$e->getMessage().'</div>');
                }
            return $objResponse;
        }
    
    private static function saveConfig()
        {
            $DB=\LMSDB::getInstance();
            foreach(self::$mqttData as $type=>$data)
                foreach($data as $k=>$v)
                    {
                        if($DB->GetOne("SELECT from cloud where type_mqtt=? and cloud='supla' and type_id=?",array($type,$k)))
                            $DB->Execute("UPDATE cloud set description=?, devid=?, devname=?, cmd=?, type=?, payload=? where id=?",array(
                                $v['description'],
                                $v['devid'],
                                $v['devname'],
                                $v['cmd'],
                                $v['type'],
                                json_encode($v['payload']),
                                $k
                            ));
                        else
                            $DB->Execute("INSERT INTO cloud (type_mqtt, cloud, type_id, description, devid, devname, cmd, type, payload) values(?, ?, ?, ?, ?, ?, ?, ?, ?)",array(
                                $type,
                                'supla',
                                $k,
                                $v['description'],
                                $v['devid'],
                                $v['devname'],
                                $v['cmd'],
                                $v['type'],
                                json_encode($v['payload']),
                            ));
                    }
            $arr=array(
                'type' => 'SYS',
                'exec' => 'CLOUD_RELOAD',
            );
            \WebSocketServer::CallWS($arr,'notify');
            \WebSocketServer::CallWS($arr);
        }
    public static function getConfig($setNull=false)
        {
            if($setNull)
                self::$config=NULL;
            if(self::$config)
                return self::$config;
            $DB=\LMSDB::getInstance();
            $e['publish']=$DB->GetAll("SELECT * from cloud where type_mqtt='publish'");
            $e['subscribe']=$DB->GetAll("SELECT * from cloud where type_mqtt='subscribe'");
            foreach($e as $type=>$data)
                foreach($data as $k=>$v)
                    $e[$type][$k]['payload']=json_decode($v['payload'],true);            
            self::$config=json_encode($e);
            return self::$config;
        }
    public static function prepareData($dev,$ch)
        {
            foreach($ch as $k=>$v)
                {
                    $channels[$v['id']]=$v;
                }

            foreach($dev as $k=>$v)
                {
                    foreach($v['channels'] as $ck=>$cv)
                        {
                            $channel=$channels[$cv['id']];
                            if(!$channel['hidden'])
                                self::addChannel($channel,$v);                                    
                        }
                }
        }
    private static function publish($arr)
        {
            $id=$arr['channel']['id'];
            self::$mqttData['publish'][$id]['cmd']=self::$mqttPrefix."command/".$id;
            foreach($arr['payload'] as $k=>$v)
                self::$mqttData['publish'][$id]['payload'][$k]=$v;
            self::$mqttData['publish'][$id]['description']=$arr['channel']['caption'];
            self::$mqttData['publish'][$id]['type']=$arr['channel']['function']['name'];
            self::$mqttData['publish'][$id]['devid']=$arr['dev']['id'];
            self::$mqttData['publish'][$id]['devname']=$arr['dev']['comment'];
        }
    private static function subscribe($arr)
        {
            $id=$arr['channel']['id'];
            self::$mqttData['subscribe'][$id]['description']=$arr['channel']['caption'];
            self::$mqttData['subscribe'][$id]['devid']=$arr['dev']['id'];
            self::$mqttData['subscribe'][$id]['devname']=$arr['dev']['comment'];
            self::$mqttData['subscribe'][$id]['cmd']=self::$mqttPrefix."status/".$arr['status']."/".$id;
            self::$mqttData['subscribe'][$id]['type']=$arr['channel']['function']['name'];
            foreach($arr['payload'] as $k=>$v)
                self::$mqttData['subscribe'][$id]['payload'][$k]=$v;
        }
    private static function addChannel($channel,$dev)
        {
            $id=$channel['id'];
            if(preg_match('/^LIGHTSWITCH$/',$channel['function']['name']))
                {
                    self::publish(array(
                        'channel' => $channel,
                        'dev' => $dev,
                        'payload' => array('on' =>array(0,1)), 
                    ));
                }
            if(preg_match('/^POWERSWITCH$/',$channel['function']['name']))
                {
                    self::publish(array(
                        'channel' => $channel,
                        'dev' => $dev,
                        'payload' => array('on' =>array(0,1)), 
                    ));
                }
            if(preg_match('/^CONTROLLINGTHEGATE|CONTROLLINGTHEGATEWAYLOCK$/',$channel['function']['name']))
                {
                    self::publish(array(
                        'channel' => $channel,
                        'dev' => $dev,
                        'payload' => array('hi' =>array(0,1)), 
                    ));
                }
            if($channel['channelNumber']>0 && $channel['param1'] && !preg_match('/^LIGHTSWITCH|POWERSWITCH$/',$channel['function']['name']))
                {
                    if(preg_match('/^SENSOR.*$/',$channel['type']['name']))
                        {
                            $n=1;
                            foreach(self::$mqttData['subscribe'][$channel['param1']]['payload'] as $pk=>$pv)
                                if(preg_match('/^sensor.*$/',$pk))
                                    $n++;            
                            self::$mqttData['subscribe'][$channel['param1']]['payload']['sensor_'.$n]=array(0,1);
                        }
                }
            else
                {
                    if(preg_match('/^SENSOR.*$/',$channel['type']['name']))
                        {
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 1000,
                                'payload' => array('hi'=> array(0,1)), 
                            ));            
                        }
                    if(preg_match('/^LIGHTSWITCH$/',$channel['function']['name']))
                        {            
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 'relay',
                                'payload' => array('on'=> array(0,1)), 
                            ));            
                        }
                    if(preg_match('/^POWERSWITCH$/',$channel['function']['name']))
                        {            
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 'switch',
                                'payload' => array('on'=> array(0,1)), 
                            ));            
                        }
                    if(preg_match('/^CONTROLLINGTHEGATE$/',$channel['function']['name']))
                        {            
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 'gate',
                                'payload' => array('hi'=> array(0,1)), 
                            ));            
                        }
                    if(preg_match('/^CONTROLLINGTHEGATEWAYLOCK$/',$channel['function']['name']))
                        {            
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 'gatewaylock',
                                'payload' => array('hi'=> array(0,1)), 
                            ));            
                        }
                    if(preg_match('/^THERMOMETER$/',$channel['function']['name']))
                        {            
                            self::subscribe(array(
                                'channel' => $channel,
                                'dev' => $dev,
                                'status' => 'thermometer',
                                'payload' => array('temperature'=> -1), 
                            ));            
                        }
                }
        }
}
?>
<?php
namespace SUPLA\Notify;

class MQTT
{
    private static $channels;
    private static $execute;
    private static $executeTmp;
    public static $c;
    private static $firstStart;
    
    function __construct(&$c)
        {
            self::$c=$c;
            self::$execute=array();
            self::$firstStart=time();
            self::$channels=array();
        }
    function connect()
        {
            self::$c->setCredentials(\ConfigHelper::getConfig('MQTT.user'),\ConfigHelper::getConfig('MQTT.password'));
            self::$c->connect(\ConfigHelper::getConfig('MQTT.host'));
        }
    function reconnect()
        {
            self::$c->disconnect();
            $this->connect();
        }
    public static function getChannelsStatus()
        {
            return self::$channels;
        }
    function updateChannel($chan,$func,$val)
        {
            $id=$this->findChannel($chan);
            if(!$id)
                $id=count(self::$channels)+1;
            if(self::$channels[$id])
                {
                    self::$channels[$id]['old'.$func]=self::$channels[$id][$func];
                    self::$channels[$id]['!lastUpdate!']=self::$channels[$id]['!currUpdate!'];
                    self::$channels[$id]['!currUpdate!']=time();
                }
            else
                self::$channels[$id]['topic']=$chan;
            self::$channels[$id][$func]=strval($val);
            \Logger::Log("Update channel ".$chan." (".$id.") with value ".$val." on element ".$func);           
        }
    private function findChannel($topic)
        {
            foreach(self::$channels as $k=>$v)
                if($v['topic']==$topic)
                    return $k;
            return 0;
        } 
    function onMessage($m)
        {
            $subs=Config::getSubs();
            $payload=json_decode($m->payload,true);

            $arr=array(
                'type'=> 'STATIC',
                'params'=>array(
                'name' => 'SuplaChannels',
                    'class' => 'SuplaChannelsWS',
                    'method' => 'UpdateChannel',
                ),
                'data'=>array(
                        'channel' => $m->topic,
                        'value' => $m->payload, 
                    ),
            );
            \WebSocketServer::CallWS($arr);

            Log::Insert($m->topic,$payload);
            $exists=array();
            foreach($subs as $k=>$v)
                if($v['topic']==$m->topic)
                    foreach($payload as $pk=>$pv)
                        if($pk==$v['toComp'])
                            {
                                $toComp=$v['toComp'];
                                if($exists[$m->topic."|".$toComp])
                                    continue;
                                $exists[$m->topic."|".$toComp]=true;
                                $this->updateChannel($m->topic,$toComp,$payload[$toComp]);
                                $this->checkAction($m->topic,$toComp);
                            }
       }
    private function getCondition($cfg,$checkOwn=true)
        {
            $subs=Config::getSubs();
            $logBefore="Condition ".$cfg['condition'];
            foreach($subs as $sk=>$sv)
                {
                    $id=$this->findChannel($sv['topic']);
                    $rep=self::$channels[$id][$sv['toComp']];
                    $rep=$rep ? $rep : 0;            
                    if(!$checkOwn)
                        $cfg['condition']=preg_replace(array("/%".str_replace("/","\\/",$cfg['name'])."[^%]*%[^>=<]*(>|=|<){1}[^)\s]*/"),array("1==1"),$cfg['condition']);
                    $cfg['condition']=str_ireplace($sv['rep'],$rep,$cfg['condition']);
                }
            $logAfter="Evaluated to: ".$cfg['condition'];
            return array('condition' => $cfg['condition'], 'logBefore'=>$logBefore, 'logAfter'=>$logAfter); 
        }       
    function checkAction($topic,$elem)
        {
            $cfgAll=Config::getConfig();
            foreach($cfgAll as $k=>$cfg)
                {
                    if($cfg['name']!=$topic)
                        continue;
                    if($cfg['elem']!=$elem)
                        continue;
#                    $executeIndex=$topic;
                    $executeIndex=$k;
                    $id=$this->findChannel($cfg['name']);
                    $channelValue=self::$channels[$id][$cfg['elem']] ? self::$channels[$id][$cfg['elem']] : 0;
                    $channelOldValue=self::$channels[$id]['old'.$cfg['elem']] ? self::$channels[$id]['old'.$cfg['elem']] : 0;

                    \Logger::Log(">>> Channel ".$topic." ".$cfg['description']);
                    if($channelValue==$channelOldValue && self::$channels[$id]['!lastUpdate!']+1>time())
                        {
                            \Logger::Log("Channel blocked, skip");
                            continue;
                        }

                    $cond='if('.$cfg['value'].$cfg['comp'].$channelValue.' && (\''.$cfg['type'].'\'==\'S\' || (\''.$cfg['type'].'\'==\'C\' && \''.$channelValue.'\'!==\''.$channelOldValue.'\')))';           
                    \Logger::Log("Condition '".$cond."'");
                    if(eval($cond." return 1; else return 0;"))
                        {
                            \Logger::Log("... OK");
                            $this->addActions($cfg,$executeIndex,$topic);
                        }
                    else
                        { 
                            \Logger::Log("... FAIL");
                        }
                    \Logger::Log("<<< Done.");
                }
        }
    private function addActions($cfg,$executeIndex,$topic,$checkOwn=true)
        {
            self::$executeTmp=array();
            foreach($cfg['elements'] as $ek=>$ev)
                {
                    $cfg['condition']=$ev['condition'];
                    $cond=$this->getCondition($cfg,$checkOwn);
                    \Logger::Log($cond['logBefore']);
                    \Logger::Log($cond['logAfter']);
                    if(eval('if ('.$cond['condition'].') return 1; else 0;'))
                        {
                            \Logger::Log("... OK");
                            $cfg['actions']=$ev['actions'];
                            self::addAction($cfg,$executeIndex,$ek,$topic);
                        }
                    else
                        {
                            \Logger::Log("... FAIL");
                        }
                }
            if(count(self::$executeTmp)>0)
                {
                    self::destroy($executeIndex,$cfg['clear_actions']);
                    self::$execute[$executeIndex]['cfgKey']=$executeIndex;
                    self::$execute[$executeIndex]['action']=self::$executeTmp;                                    
                }
        }
    private static function addAction($cfg,$executeIndex,$elemId,$topic)
        {
            if(!is_array($cfg['actions']))
                return;
            foreach($cfg['actions'] as $ak=>$av)
                {               
                    array_push(self::$executeTmp,array(
                        'action' => $av['action'],
                        'value' => $av['value'],
                        'time' => time()+$av['delay'],
                        'topic' => $topic,
                        'condition' => $elemId,
                    ));
                    \Logger::Log("Adding action ".$av['action']." with value: ".$av['value'].", delay: ".$av['delay']);
                }
        }
    public function executeScene($data)
        {
            \Logger::Log("Receiving action from cloud via SOCKET, ".$cmd.", sending: ".$action);
            $cfgAll=Config::getConfig();
            foreach($cfgAll as $k=>$v)
                if($v['id']==$data['scene'])
                    {
                        $this->addActions($v,$k,$v['name'],false);
                    }
        }  
    private static function destroy($idx,$allClear=false)
        {
            $cfgAll=Config::getConfig();
            if(!$allClear)
                {
                    \Logger::Log("Destroying executing actions for ".$cfgAll[$idx]['name']);
                    unset(self::$execute[$idx]);
                }
            else
                foreach($cfgAll as $k=>$v)
                    if($v['name']==$cfgAll[$idx]['name'])
                        {
                            \Logger::Log("Destroying executing actions for ".$v['name']);
                            unset(self::$execute[$k]);
                        }
        }
    function Execute()
        {
            if(self::$firstStart+5>time())
                {
                    self::$execute=array();
                    return;
                }

            $c=self::$c;
            foreach(self::$execute as $k=>$v)
                {
                    if(!$v['action'])
                        continue;
                    foreach($v['action'] as $ak=>$av)
                        {
                            $cond=Config::getConfig($v['cfgKey']);
                            $cond['condition']=$cond['elements'][$av['condition']]['condition'];
                            $cond=$this->getCondition($cond,false);
                            if(!eval('if ('.$cond['condition'].') return 1; else 0;'))
                                {
                                    unset(self::$execute[$k]);
                                    \Logger::Log("Condition fail, remove from stack.");
                                    continue;
                                }

                            if(time()<$av['time'])
                                continue;
                            \Logger::Log($cond['logBefore']);
                            \Logger::Log($cond['logAfter']);

                            unset(self::$execute[$k]['action'][$ak]);
                            \Logger::Log("Delayed action for ".$av['topic']."...");
                            $one=explode(".",$av['action']);
                            $toPub=explode("/",$one[1]);
                            if($one[0]=='MQTT')
                                {
                                    $pub='{"id": '.$toPub[count($toPub)-1].', "'.$one[2].'": '.$av['value'].'}';
                                    \Logger::Log("Publishing ".$one[1].", ".$pub);
                                    $c->publish($one[1],$pub,0);
                                }
                            if($one[0]=='VIRTUAL')
                                    \Logger::Log("VIRTUAL action ".$one[1]);
                        }
                    if(count(self::$execute[$k]['action'])==0)
                        unset(self::$execute[$k]);
                }
        }
}
?>
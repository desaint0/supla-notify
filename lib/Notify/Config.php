<?php
namespace SUPLA\Notify;

class Config
{
    private static $config;
    private static $subscriptions;
    private static $toSubscribe;
    function __construct()
        {
        }

    function Init()
        {
            $cfg=$this->get();
            $this->getSubscriptions($cfg);
        }
    function check(&$MQTT)
        {
            \Logger::Log("======> Reloading data... ");
            $MQTT->reconnect();

            $this->Init();
            $toSubs=$this->getToSubscribe();
            foreach($toSubs as $k=>$v)
                $MQTT::$c->subscribe($k,0);
        }
    public static function getConfig($key=-1)
        {
            if($key>-1)
                return self::$config[$key];
            else
                return self::$config;
        }
    public static function getSubs()
        {
            return self::$subscriptions;
        }
    public static function getToSubscribe()
        {
            return self::$toSubscribe;
        }
    function get()
        {
            $DB=\LMSDB::getInstance();
            $config=json_decode(json_encode($DB->GetAll("SELECT * from conditions")),true);
            $config=$DB->GetAll("SELECT * from conditions order by description");
            foreach($config as $k=>&$v)
                {
                    $v['elements']=$DB->GetAll("SELECT * from elements where conditions_id=?",array($v['id']));
                    foreach($v['elements'] as $ek=>$element)
                        {
                            $v['elements'][$ek]['actions']=json_decode($element['actions'],true);
                        }
                    Conditions\Parse::Execute($v['elements']);
                    if(!$v['disabled'])
                        $c[]=$v;
                }
            self::$config=$c;
            return $c;
        }
    function getSubscriptions($cfg)
        {
            $subs=array();
            foreach($cfg as $k=>$v)
                {
                    $subs[]=array('topic' => $v['name'], 'toComp' => $v['elem']);
                    self::$toSubscribe[$v['name']]=true;
                    foreach($v['elements'] as $ek=>$ev)
                        {
                            preg_match_all('#%(.*?)%#is', $ev['condition'], $m);
                            foreach($m[1] as $mk=>$mv)
                                {
                                    $s=explode(".",$mv);
                                    $subs[]=array('topic' =>$s[0], 'toComp'=> $s[1], 'rep'=>$m[0][$mk]);
                                    self::$toSubscribe[$s[0]]=true;
                                }
                        }
                }
            $cloud=\Supla\Project\Cloud::getConfig();
            $cloud=json_decode($cloud,true);
            foreach($cloud['subscribe'] as $k=>$v)
                self::$toSubscribe[$v['cmd']]=true;

            self::$subscriptions=$subs;
            return $subs;
        }
}
?>
<?php
namespace Supla\Project;

class Scenes
{
    public function ShowScenes($name)
        {
            $objResponse=new \XajaxResponse;

            $cfg=\SUPLA\Notify\Config::get();
            \Supla\Project\Init::getTemplate()->assign('cfg',$cfg);
            $cfg=\Supla\Project\Cloud::getConfig();
            \Supla\Project\Init::getTemplate()->assign('cloud',json_decode($cfg,true));

            $result=\Supla\Project\Init::getTemplate()->fetch('Scenes.html');
            $script=\LMSWindowManager::getJS($result);
            $objResponse->assign($name,'innerHTML',$result);
            $objResponse->script($script);
            return $objResponse;
        }
    public static function SuplaChannel($action,$cmd,$objName,$idx)
        {
            list($type,$id)=explode(":",$cmd);
            if($type=='channel')
                {
                    $DB=\LMSDB::getInstance();
                    $sub=$DB->GetRow("SELECT * from cloud where type_mqtt='subscribe' and id=?",array($id));
                    $v=$DB->GetRow("SELECT * from cloud where type_mqtt='publish' and type_id=? and cloud=?",array($sub['type_id'],$sub['cloud']));
                    $c = new \Mosquitto\Client("PHP Actions");
                    $MQTT=new \SUPLA\Notify\MQTT($c);
                    $MQTT->connect();
                    if($v)
                        {
                            $p=array_pop(array_keys(json_decode($v['payload'],true)));
                            $arr=array('id'=>$v['type_id'], $p=>$action);
                            $toSend[]=array('cmd' => $v['cmd'], 'payload'=>json_encode($arr));
                            $ret=true;
                        }
                    foreach($toSend as $k=>$v)
                        {
                            $c->publish($v['cmd'],$v['payload'],0);
                            usleep(100000);
                        }
                }
            if($type=='scenes')
                {
                    $arr=array(
                        'type'=> 'CALL',
                        'params'=>array(
                            'name' => 'SuplaChannels',
                            'class' => 'MQTT',
                            'method' => 'executeScene',
                        ),
                        'data'=>array(
                        'scene' => $id,
                        ),
                    );

                    $ret=\WebSocketServer::CallWS($arr,'notify');
                }
            return self::SuplaChannelReturn($objName,$idx,$ret);
        }
    public static function SuplaChannelReturn($objName,$idx,$ret)
        {
            $objResponse=new \XajaxResponse;
            $objResponse->script('
                var d=document.getElementById("gridContent_'.$objName.'_'.$idx.'").querySelectorAll(`[data-name="spin"]`);
                $.each(d, function(key,value) {
                    removeClass(this,"lmswprogressbar lmswprogresssmall");
                    this.innerHTML="<img src=img/'.($ret ? 'ok' : 'fail').'.png width=24>";
                    var me=this;
                    setTimeout(function() {
                        me.innerHTML="";
                    },2000);
                });
            ');
            return $objResponse;
        }
    public static function TemplatesSave($obj,$tpl,$data)
        {
            $DB=\LMSDB::getInstance();
            if($id=$DB->GetOne("SELECT id from scenes_templates where tpl=? and obj=?",array($tpl,$obj)))
                $DB->Execute("UPDATE scenes_templates set data=? where id=?",array($data,$id));
            else
                $DB->Execute("INSERT into scenes_templates (tpl, obj, data) values(?, ?, ?)",array($tpl,$obj,$data));
            $objResponse=self::TemplatesGet('templates','SG_',$tpl);
            $objResponse->script("document.getElementById('templateName').value='';");
            return $objResponse;
        }
    public static function TemplatesGet($name,$type,$tplDefault)
        {
            $objResponse=new \XajaxResponse;
            $DB=\LMSDB::getInstance();
            $lista=$DB->GetAll("SELECT distinct(tpl) as name from scenes_templates order by tpl");
            $b='<select id=templateId onchange="TemplatesLoad(this.value);" class="form-control form-control-xs"><option value="">-- wybierz --';
            foreach($lista as $k=>$v)
                {
                    $b.="<option value='".$v['name']."' ".($v['name']==$tplDefault ? "selected" : "").">".$v['name'];
                }
            $b.="</select>";
            $objResponse->assign($name,'innerHTML',$b);
            return $objResponse;
        }
    public static function TemplatesLoad($obj,$tpl)
        {
            $objResponse=new \XajaxResponse;
            $DB=\LMSDB::getInstance();
            $data=$DB->GetOne("SELECT data from scenes_templates where tpl=? and obj=?",array($tpl,$obj));
            $objResponse->script($obj.".SetTemplate('".$data."');");
            return $objResponse;
        }
    public function getAllChannelsStatus()
        {
            $arr=array(
                'type'=> 'WS',
                'params'=>array(
                'name' => 'SuplaChannels',
                    'class' => 'SuplaChannelsWS',
                    'method' => 'initialStatus',
                ),
                'data'=>array(
                    ),
            );

            \WebSocketServer::CallWS($arr);
        }
    public static function Ping()
        {
            $objResponse=new \XajaxResponse;
            $objResponse->script('setTimeout(xajax_Ping,'.(60*1000).');');
            return $objResponse;
        }
    public function ScenesGet()
        {
            $objResponse=new \XajaxResponse;

            try
                {
                }
            catch(\exception $e)
                {
                }
            return $objResponse;
        }
    
}
?>
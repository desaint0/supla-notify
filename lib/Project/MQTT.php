<?php
namespace Supla\Project;

class MQTT
{
    public function Show($name)
        {
            $objResponse=new \XajaxResponse;
            $DB=\LMSDB::getInstance();
            $config=$DB->GetAll("SELECT * from conditions order by description");
            foreach($config as $k=>&$v)
                {
                    $v['elements']=$DB->GetAll("SELECT * from elements where conditions_id=?",array($v['id']));
                    foreach($v['elements'] as $ek=>$element)
                        {
                            $v['elements'][$ek]['actions']=json_decode($element['actions'],true);
                        }
                    $v=json_encode($v);
                }
            \Supla\Project\Init::getTemplate()->assign('mqttConfig',$config);
            \Supla\Project\Init::getTemplate()->assign('sourcesList',Cloud::getConfig());
            $result=\Supla\Project\Init::getTemplate()->fetch('MQTT_config.html');
            $script=\LMSWindowManager::getJS($result);            
            $objResponse->assign($name,'innerHTML',$result);
            $objResponse->script($script);
            return $objResponse;
        
        }        
    public function Save($data)
        {
            $DB=\LMSDB::getInstance();
            $data=json_decode($data,true);
            $toNdel=array();
            foreach($data as $k=>$v)
                if($v['id'])
                    $toNdel[]=$v['id'];
            if($toNdel)
                $DB->Execute("DELETE from conditions where id not in (".implode(",",array_values($toNdel)).")");
            foreach($data as $k=>$v)
                {
                    if($v==NULL)
                        continue;
                    if(!$DB->GetOne("SELECT id from conditions where id=?",array($v['id'] ? $v['id'] : 0)))
                        {
                            $DB->Execute("INSERT into conditions (name, description, elem, comp, value ,type, disabled, clear_actions) values(?, ?, ? ,?, ?, ?, ?, ?)",array(
                                $v['name'],
                                $v['description'],
                                $v['elem'],
                                $v['comp'],
                                $v['value'],
                                $v['type'],
                                $v['disabled'] ? 1 : 0,
                                $v['clear_actions'] ? 1 : 0,
                            ));
                            $id=$DB->GetLastInsertID('conditions');
                            foreach($v['elements'] as $ek=>$ev)
                                {
                                    if($ev==NULL)
                                        continue;
                                    $DB->Execute("INSERT INTO elements(conditions_id, condition, actions) values (?, ?, ?)",array(
                                        $id,
                                        $ev['condition'],
                                        json_encode($ev['actions']),
                                    ));
                                }
                        }
                    else
                        {
                            $DB->Execute("UPDATE conditions set name=?, description=?, elem=?, comp=?, value=? ,type=?, disabled=?, clear_actions=? where id=?",array(
                                $v['name'],
                                $v['description'],
                                $v['elem'],
                                $v['comp'],
                                $v['value'],
                                $v['type'],
                                $v['disabled'] ? 1 : 0,
                                $v['clear_actions'] ? 1 :0,
                                $v['id'],
                            ));
                            $toNdelE=array();
                            foreach($v['elements'] as $ek=>$ev)
                                {
                                    if($ev['id'])
                                        $toNdelE[]=$ev['id'];
                                }
                            if($toNdelE)
                                $DB->Execute("DELETE from elements where conditions_id=? and id not in (".implode(",",array_values($toNdelE)).")",array($v['id']));
                            foreach($v['elements'] as $ek=>$ev)
                                {
                                    if($ev==NULL)
                                        continue;
                                    if($ev['id'] && $DB->GetOne("SELECT id from elements where conditions_id=? and id=?",array(
                                        $v['id'],
                                        $ev['id'] ? $ev['id'] : 0,
                                    )))
                                        $DB->Execute("UPDATE elements set condition=?, actions=? where conditions_id=? and id=?",array(
                                            $ev['condition'],
                                            json_encode($ev['actions']),
                                            $v['id'],
                                            $ev['id'],
                                        ));
                                    else
                                        $DB->Execute("INSERT INTO elements(conditions_id, condition, actions) values (?, ?, ?)",array(
                                            $v['id'],
                                            $ev['condition'],
                                            json_encode($ev['actions']),
                                        ));
                                }
                        }
                }

            $arr=array(
                'type' => 'SYS',
                'exec' => 'RELOAD',
            );
            \WebSocketServer::CallWS($arr,'notify');

            $objResponse=new \XajaxResponse;
            $objResponse->script("xajax_MQTT('page_content');");
            return $objResponse;
        }        

}
?>
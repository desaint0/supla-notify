<?php
namespace Supla\Project;

class LmsW extends \LMSWindowManager
{
    const init='<BR><span id=lmswprogressbar class="lmswprogressbig text-center"></span><BR> <B>Trwa operacja zapisu/odczytu danych...</B>';
    const objName='SuplaScenes';
    const defRefresh=3000;
    
    function __construct($baseName='',array $regArrObj=array())
        {
            parent::__construct();
            global $LmsWindowManager,$LMS;
            
            $regArr=array(
                '_windowLeft' => 100,
                '_windowTop' => 100,
                'name' => self::objName.$baseName,
                'baseXajaxFunc' => 'SuplaScenes'.$baseName,
                'refreshFname' => '',
                'init' => self::init,
                'array' => array(0 => array('id' => '')),
                'idname' => 'id',
            );
                
            $this->Register($regArr);
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('_'.$regArr['baseXajaxFunc'].'Show',$this,'_'.$regArr['baseXajaxFunc'].'Show')),array('URI'=>'"'.$uri.'"'));
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('_'.$regArr['baseXajaxFunc'].'Set',$this,'_'.$regArr['baseXajaxFunc'].'Set')),array('URI'=>'"'.$uri.'"'));
        }
    function _SuplaScenesShow($name,$id,$opt,$ext)
        {
            $lmsW['_idsArr']=$ext;
            return parent::_Show($name,$id,$opt,$lmsW);
        }
    function _SuplaScenesSet($s, $opt)
        {
            return parent::_Set($s, $opt);
        }
    function Log($lmsW,$id)
        {
            $DB=\LMSDB::getInstance();
            $data=$lmsW['_idsArr'];
            $chan=$DB->GetRow("SELECT * from cloud where id=?",array($data['id']));
            $payload=json_decode($chan['payload'],true);            
            Init::getTemplate()->assign('channel',$chan);
            Init::getTemplate()->assign('payload',$payload);
            Init::getTemplate()->assign('time',array('1d','1w','1m','1y'));
            Init::getTemplate()->assign('grid',array(1,600,3600,86400,(86400*7)));
            if(!$data['payload'] && count($payload)==1)
                $data['payload']=array_shift(array_keys($payload)); 
            if(!$data['time'])
                $data['time']='1w';
            if(!$data['grid'])
                $data['grid']='3600';
            Init::getTemplate()->assign('lmsW',$data);
            if($data['payload'])
                Init::getTemplate()->assign('logs',\SUPLA\Notify\Log::getData(array(
                    'id' => $data['id'],
                    'ptype' => $data['payload'],
                    'time' => $data['time'],
                    'grid' =>$data['grid'],
                )));
            $this->_result.=Init::getTemplate()->fetch('templates/channelLogs.html');
#$this->_result.="asasda
#";            
        }
    function Charts($lmsW,$id)
        {
            $DB=\LMSDB::getInstance();
            $data=$lmsW['_idsArr'];
            $chan=$DB->GetRow("SELECT * from cloud where id=?",array($data['id']));
            $payload=json_decode($chan['payload'],true);            
            Init::getTemplate()->assign('channel',$chan);
            Init::getTemplate()->assign('payload',$payload);
            Init::getTemplate()->assign('time',array('1d','1w','1m','1y'));
            Init::getTemplate()->assign('grid',array(1,600,3600,86400,(86400*7)));
            if(!$data['payload'] && count($payload)==1)
                $data['payload']=array_shift(array_keys($payload)); 
            if(!$data['time'])
                $data['time']='1w';
            if(!$data['grid'])
                $data['grid']='3600';
            Init::getTemplate()->assign('lmsW',$data);
            if($data['payload'])
                Init::getTemplate()->assign('logs',\SUPLA\Notify\Log::getData(array(
                    'id' => $data['id'],
                    'ptype' => $data['payload'],
                    'time' => $data['time'],
                    'grid' =>$data['grid'],
                    'sort' => $data['sort'],
                )));
            $this->_result.=Init::getTemplate()->fetch('templates/channelCharts.html');
#$this->_result.="asasda
#";            
        }
}
?>
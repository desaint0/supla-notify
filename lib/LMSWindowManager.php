<?php
/*
LMSWindowManager by AP-MEDIA (c) 2016
*** Paweł Bajorek ***

Klase podajemy jako extends do pluginu lub tworzymy nowy obiekt w funkcji ajaxa, ktora bedzie wywolywana przez klikniecie w glownym oknie.
*/

class LMSWindowManager
{
    const NO_PRIVILEGES_TXT='Brak uprawnień do wykonania żądanej operacji.';
    const alertGlobal='alertglobal';
    
    var $lmswin_xajax='';
    var $_script='';
    var $_onload='';
    var $_onunload='';
    var $data=array();
    var $_parentReload=false;
    
    function __construct()
        {
            global $AUTH;
            $this->objResponse=new xajaxResponse();
            
            if(get_class($this)!='LMSWindowManager')
                return;
                
            $this->AddOnload("
                xajax.callback.global.onRequest = function(args) {
                    if(!args.parameters.callee)
                        return; 
//                    ClearErrors();
                    xajaxRequest = args;
                    xajaxCallbacks[args.parameters.callee.name]='request';
                    xajaxProcess=args.parameters.callee.name;                                    
                    LmsWindowsManager.progress(true,args.parameters.callee.name);
                }
                xajax.callback.global.onResponseDelay = function(args) {
                    if(!args.parameters.callee)
                        return; 
                    xajaxCallbacks[args.parameters.callee.name]='delay';                    
                }
                xajax.callback.global.onFailure = function(args) {
                    if(!args.parameters.callee)
                        return; 
                    xajaxCallbacks[args.parameters.callee.name]='failure';
                    LmsWindowsManager.progress(false);
                    xajaxProcess=false;
                }
                xajax.callback.global.onExpiration = function(args) {
                    if(!args.parameters.callee)
                        return; 
                    xajaxCallbacks[args.parameters.callee.name]='expire';
                }
                xajax.callback.global.onSuccess = function(args) {
                    if(!args.parameters.callee)
                        return; 
                    xajaxCallbacks[args.parameters.callee.name]=false;
                    LmsWindowsManager.progress(false);
                    xajaxProcess=false;
                }
            ");            
            $this->AddOnload("LmsWindowsManager.LoadCookies();");
            $salt=substr(md5(date("U")),0,12);
            $pass=crypt(ConfigHelper::getConfig('socket.pass'),'$1$'.$salt.'$');
            $this->AddScript(file_get_contents('templates/js/WebSocketServer.js'));
            $this->AddOnLoad("
                var p={
                        WSUri : '".ConfigHelper::getConfig('socket.uri')."',
                        WSen: '".ConfigHelper::getConfig('socket.enabled')."',
                        login: '".ConfigHelper::getConfig('socket.login')."',
                        pass: '".$pass."',
                        salt: '".$salt."',
                        lmswWSStatus: 'WebSocketStatus',
                        authID: '".$AUTH->id."',
                }
                WebSocketServerF=new WebSocketServer(p);
            ");

//WebSocketServerF.Init() w this->Run(), musi byc na koncu aby wykonac wszystkie addQueue                 
        }
    function _Return()
        {
            $r['_error']=$this->error;
            $r['_obj']=$this->objResponse;
            return $r;        
        }
    function _getOpt($var)
        {
            return $this->_opt[$var];
        }

    private function StringToArray($co,$val)
        {
            $v=explode("|",$co);
            if (count($v)==1)
                {
                if(!$v[0])
                    return $val;
                else
                    return array($v[0] => $val);
                }
            $key=array_shift($v);
            
            $r=array($key => $this->StringToArray(implode("|",$v),$val));
            return $r; 
        }
    function parseForm($d)
        {
            if(is_array($d))
            foreach($d as $fk=>$fv)
                {
                    $f=str_ireplace("[","|",$fk);
                    $f=str_ireplace("]","",$f);
                    $na=$this->stringToArray($f,$fv);
                    if(!$n)
                        $n=$na;
                    else
#                        $n=array_merge_recursive($n,$na);
                        $n=$this->MergeArrays($n,$na);
                }
            return $n;
        }

    function MergeArrays($Arr1, $Arr2)
        {
            foreach($Arr2 as $key => $Value)
                {
                    if(array_key_exists($key, $Arr1) && is_array($Value))
                        $Arr1[$key] = $this->MergeArrays($Arr1[$key], $Arr2[$key]);
                    else
                        $Arr1[$key] = $Value;
                }
            return $Arr1;
        }
        
/*
    'refreshFname' => nazwa metody ktora bedzie wykonywana jesli bedzie koniecznosc odsiwezenia okna (np parenta),
    'name' => nazwa obiektu js odpowiedzialnego za to okienko
    'refreshId' => jw tyle ze id ktory bedzie przekazany,
    'baseXajaxFunc' => funckja xajax, ktora bedzie wywolana, np zeby wyswietlic okno, jesli puste bedzie rowne name
    'init' => tresc okienka paska postepu ladowania, zapisywania...,
    'array' => tablica z ktorej bedzie pobieral nazwy obiektow (doklejone do 'name'),
    'idname' => element tablicy ktory bedzie wybierany do nazwy obiektu,  
*/        
    function Register($arr)
        {
#            global $_POST;
#            if($_POST['xjxr'] && $_POST['xjxfun'])
#                return;
                
            $this->data[$arr['name']]['init']=$arr['init'];
            $xajax='';
            if(!is_array($arr['array']))
                return;            
            foreach($arr['array'] as $k=>$v)
                {
                    $xajax.="
                        pp={
                            jsFunc: '".$arr['name'].(isset($v[$arr['idname']]) ? $v[$arr['idname']] : '')."',
                            baseXajaxFunc: '".($arr['baseXajaxFunc'] ? $arr['baseXajaxFunc'] : $arr['name'])."',
                            ".($arr['refreshFname'] ? "refreshFname : '".$arr['refreshFname']."'," : "")."
                            ".($arr['refreshId'] ? "refreshId : '".$arr['refreshId']."'," : "")."                            
                            ".($arr['infoDivClone'] ? "infoDivClone : true," : "")."                            
                            ".($arr['_windowLeft'] ? "_windowLeft : ".$arr['_windowLeft']."," : "")."                            
                            ".($arr['_windowTop'] ? "_windowTop : ".$arr['_windowTop']."," : "")."                            
                            ".($arr['disableCookie'] ? "disableCookie : ".$arr['disableCookie']."," : "")."                            
                            ".($arr['_windowClass'] ? "_windowClass : '".$arr['_windowClass']."'," : "")."                            
                            ".($arr['formName'] ? "formName : '".$arr['formName']."'," : "")."
                        }
                        if(typeof ".$arr['name'].(isset($v[$arr['idname']]) ? $v[$arr['idname']] : '')."=='undefined')
                            ".$arr['name'].(isset($v[$arr['idname']]) ? $v[$arr['idname']] : '')."=new LMSWindows(pp);
                        ".$arr['name'].(isset($v[$arr['idname']]) ? $v[$arr['idname']] : '').".InitLoad('".$arr['init']."');
                    ";
                }
            if($arr['xajax'])
                $xajax.='
                    xajax__'.$arr['name'].' = function() { return xajax.request( { xjxfun: \'_'.$arr['name'].'\' }, { parameters: arguments'.($arr['uri'] ? ', URI: "'.$arr['uri'].'"' : '').' } ); };
                ';
            $this->lmswin_xajax.=$xajax;
            if(get_class($this)!='LMSWindowManager')
                {
                    global $LmsWindowManager;
                    $LmsWindowManager->lmswin_xajax.=$xajax;
                }
        }
/*
    $WindowNamePrefix - array z lista postfixow do nazw obiektow - mozna za jednym razem stworzyc wiele obiektow o podobnych parametrach
    $arr - patrz metoda Register
*/
    function MultiRegister($arr, $WindowNamePrefix)
        {
            if(!is_array($WindowNamePrefix))
                $this->Register($arr);
            foreach($WindowNamePrefix as $k)
                {
                    $arr['name']=$arr['baseXajaxFunc'].$k;
                    $this->Register($arr);
                }
        }
    function AddScript($script)
        {
            if(get_class($this)!='LMSWindowManager')
                {
                    global $LmsWindowManager;
                    $LmsWindowManager->_script.=$script;
                }
            else
                $this->_script.=$script;
        }
    function AddOnload($script)
        {
            if(get_class($this)!='LMSWindowManager')
                {
                    global $LmsWindowManager;
                    $LmsWindowManager->_onload.=$script;
                }
            else
                $this->_onload.=$script;
        }
    function AddOnUnload($script)
        {
            if(get_class($this)!='LMSWindowManager')
                {
                    global $LmsWindowManager;
                    $LmsWindowManager->_onunload.=$script;
                }
            else
                $this->_onunload.=$script;
        }
        
    function Get()
        {
            return $this->lmswin_xajax.$this->_script;
        }
            
    function Run()
        {
            $this->AddOnLoad("
                LmsWindowsManager.SetStatus();
            ");
            
            return "<script>
                var xajaxProcess=false;
                var xajaxCallbacks=new Array();                        
            
                var LmsWindowsManager=new LMSWindowsManager({objName: 'LmsWindowsManager'});
                LmsWindowsManager.progress(true);
                var lmsTimeout=".ConfigHelper::getConfig('phpui.timeout').";

                LMSPTwin=new LMSWindows({ 
                    jsFunc: 'LMSPTwin',
                    baseXajaxFunc: 'LMSPTwin',
                    _windowLeft : 100,                            
                    _windowTop : 100,                            
                    disableCookie : 1,                    
                });
                LMSPTwin.description='Progress';
                LMSPTwin.InitLoad('<span id=lmswprogressbar class=\"lmswprogressbig text-center\"></span><BR> <B>Trwa operacja zapisu/odczytu danych...</B>');

                ".$this->Get()."
                LmsWindowsManager.Run();
                $(document).ready(function(){
                    ".$this->_onload."                                    
                    LmsWindowsManager.progress(false);
                    WebSocketServerF.Init();
                });

                $(window).on('beforeunload', function(){
//                    LmsWindowsManager.progress(true);
//  nie da sie tego zrobic latwo, bo pobieranie plikow tez wywoluje metode unload.
                    ".$this->_onunload."
                });
                </script>
            ";
        }
        
    function ajaxSendError($msg,$elem="",$moreError=null)
        {
            $this->objResponse->assign($this->_getOpt('infoDiv').'_info'.$elem,"innerHTML","<div class=alertLMS style='padding-top:7px;height:20px;width:99%'>Wystąpił błąd podczas wykonywania operacji: ".$msg."</div>");        
            $this->objResponse->assign(self::alertGlobal,"innerHTML","<div class=alertLMSsolid><img src=templates/LmsWindowManager/css/error.png> Wystąpił błąd podczas wykonywania operacji: ".$msg."".($moreError ? " - ".$moreError : "")."</div>");
            $this->objResponse->assign(self::alertGlobal.'table',"style.display",'');
            $this->objResponse->script("LmsWindowsManager.SetStatus();");
        }
    function ajaxSendOK($elem="")
        {
            $this->objResponse->assign($this->_getOpt('infoDiv').'_info'.$elem,"innerHTML","<div class=statusokLMS style='padding-top:7px;height:20px;width:99%'>Operacja została ukończona powodzeniem.</div>");        
            $this->objResponse->assign(self::alertGlobal,"innerHTML","<div class=statusokLMSsolid>Operacja została ukończona powodzeniem.</div>");
            $this->objResponse->assign(self::alertGlobal.'table',"style.display",'');
            $this->objResponse->script("LmsWindowsManager.SetStatus();");
        }
    function ajaxClearErr($elem="")
        {
            $this->objResponse->assign($this->_getOpt('infoDiv').'_info'.$elem,"innerHTML","");        
        }
    function parentSendError($res,$elem,$msg)
        {
            if(!$res)
                {
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].className='alert';");
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].addEventListener('mouseover',function(){popup('".$msg."');});");
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].addEventListener('mouseout',function(){pophide();});");
                    $this->error=true;
                    return true;
                }
            else
                {
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].className='';");
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].addEventListener('mouseover',function(){pophide();});");
                    $this->objResponse->script("document.".$this->_getOpt('formName').".elements['".$elem."'].addEventListener('mouseout',function(){});");
                    return false;
                }
        
        }
    public static function GetJS($result)
        {
//            $script="ResetTimeout();";
            $r=explode("\n",$result);
            if(is_array($r))
                foreach($r as $k=>$v)
                    {
                        preg_match('/^.*<script.*src="(.*)">.*<\/script>.*$/', $v, $m);
                        if($m)
                            {
                                list($s)=explode('?',$m[1]);
                                $script.=file_get_contents(SYS_DIR . DIRECTORY_SEPARATOR . $s);
                            }
                    }
            preg_match_all('#<script(.*?)<\/script>#is', $result, $m);
            $m=implode('',$m[0]);
            $m=explode("\n",$m);
            foreach($m as $k=>$v)
                if(!preg_match('/^.*<script|script>.*$/',$v))
                    $script.=$v."\n";

            return $script;
        }
    static function getJQuery()
        {
            global $SMARTY;
            $jquery="
                $(function() {
                    var autocomplete = \"off\";

                    $('.calendar').datepicker({
                        dateFormat: \"yy/mm/dd\",
                        changeYear: true,
                        changeMonth: true,
                    })
                    .attr(\"autocomplete\", autocomplete);

                    $('[title]').each(function() {
                        tooltipClass = \"\";
                        if ($(this).hasClass(\"alert\")) {
                            tooltipClass += \" alert\";
                            if ($(this).hasClass(\"bold\"))
                                tooltipClass += \" bold\";
                        } else if ($(this).hasClass(\"bold\"))
                        tooltipClass += \"bold\";
                        $(this).tooltip({
				            show: { delay: 500 },
				            track: true,
				            tooltipClass: tooltipClass
                        });
		          });

                });

	$('[title]').each(function() {
		$(this).one('mouseenter', function() {
			tooltipClass = '';
			if ($(this).hasClass('alert')) {
				tooltipClass += ' alert';
				if ($(this).hasClass('bold')) {
					tooltipClass += ' bold';
				}
			} else if ($(this).hasClass('bold')) {
				tooltipClass += 'bold';
			}

			var title = $(this).attr('title');
			$(this).attr('data-tooltip', title).removeAttr('title');
			$(this).tooltip({
				items: '[data-tooltip]',
				content: title,
				show: { delay: 500 },
				track: true,
				classes: {
					'ui-tooltip': tooltipClass
				},
				create: function() {
					$(this).tooltip('open');
				}
			});
		});
	});
    
	$('.lmsbox-titlebar').each(function() {
		$(this).prop('onclick', null);
		$(this).click(function() {
			var elemid = $(this).attr('data-lmsbox-content');
//			showOrHide(elemid);
            setCookie(elemid,$('#'+elemid).is(':visible') ? '0' : '1');
            $('#'+elemid).toggle('slow');
			$('#' + elemid).find('.lms-ui-datatable').each(function() {
				if (!$.fn.dataTable.isDataTable(this)) {
					initDataTable(this);
				}
			});
		});
		$(this).find('td a,td :input').click(function(e) {
			e.stopPropagation();
		});
	});

	$('.lms-ui-sortable-persistent').sortable({
		items: \"> .lms-ui-sortable\",
		handle: \"tr.lmsbox-titlebar\",
		axis: \"y\",
		opacity: 0.9,
		update: function(event, ui) {
			data = {};
			data[$(this).attr('id') + '-order'] = $(this).sortable(\"toArray\").join(';');
//			savePersistentSettings(data);
		}
	});
                
            ";
            return $jquery;
        }
/*
    Wyswietlanie okienka
    name - nazwa okienka do ktorego bedzie zapisana tresc ze Smarty
    id - id elementu ktory bedzie odczytany/pobrany do wyswietlenia danych
    opt - parametry JS do przekazania. 
*/
    function _Show($name, $id, $opt, $lmsW=array())
        {
            global $SMARTY, $_SERVER;
            $this->_opt=json_decode($opt,true);
            if(!$name)
                return false;

            if(is_array($arr=json_decode($lmsW['_idsArr'],true)))
                $lmsW['_idsArr']=$arr ? $arr : null;

            $func=$this->_getOpt('func');
            try
                {
                    $this->_result="";
//                    $lmsW['infoDiv']=$this->_getOpt('infoDiv')."_info".$this->_getOpt('baseWindow');
                    $lmsW['infoDiv']=$this->_getOpt('infoDiv')."_info".$this->_getOpt('baseWindow');
                    $lmsW['current_id']=$id ? $id : 0;
                    $lmsW['formName']=$this->_getOpt('formName');
                    $lmsW['jsFunc']=$this->_getOpt('jsFunc');
                    $lmsW['refreshId']=$this->_getOpt('refreshId');
                    $lmsW['description']=$this->_getOpt('description');
                    $lmsW['path']=get_class($this). ' / '. $this->_getOpt('func');
                    $SMARTY->assign('lmsWindows',$lmsW);
                    $script="";

                    if(method_exists($this,$func))
                        $ret=$this->$func($lmsW,$id);
                    elseif (function_exists($func))
                        {
                            $ret=$func($lmsW,$id);
                            $this->_result=$ret['_result'];
                        }
                    elseif(stristr($func,'?'))
                        {
                            $func=str_ireplace('?','',$func);
                            $l=explode('&',$func);
                            foreach($l as $k=>$v)
                                {
                                    $tmp=explode("=",$v);
                                    $_GET[$tmp[0]]=$tmp[1];
                                }

                            global $LMS, $MAG, $AUTH, $SESSION, $DB, $CONFIG, $access, $modules_dirs;
                            $module=$_GET['m'];
                            $SMARTY->assign('execFunc','?m='.$module);
                            $SMARTY->assign('execFuncAll','?'.$func);
                            ob_start();
                            $SMARTY->_LMSWindowManagerActive=true;
                            $SMARTY->_LMSWindowTemplate=$lmsW['template'];
                            include('index.php');
                            $result=ob_get_contents();

//podmieniamy zwykly link na xajax Load Page Content - ale to testy i nie wiem czy uda sie kiedys to wdrozyc,
//bo rozne moduly w rozny sposob generuja strony - jedne tworza dynamiczne divy, ktore potem zostaja, inne korzystaja z kilku formularzy...
//                            $result=preg_replace('#<a([^(href)]*)href="\?(.[\S^"]*?)"([^>]*?)>#is','<a$1href=javascript:void(); onclick="xajax__LmswPageContent(\'?$2\');"$3>',$result);
//
                            $this->_result=''.$result;
                            ob_end_clean();
                            $SMARTY->_LMSWindowManagerActive=false;
                        }

                    $script.=$this->GetJS($this->_result);
//                    ErrorLogger::write(__METHOD__,__FILE__,'LMSW_S',$script);

                    if($this->_getOpt('infoDivSave'))
                        $this->objResponse->script('infoDivSave='.$this->_getOpt('infoDiv')."_info".$this->_getOpt('baseWindow').'.innerHTML');
                    $this->objResponse->assign($name,'innerHTML',$this->_result);
                    if($this->_getOpt('infoDivSave'))
                        $this->objResponse->script($this->_getOpt('infoDiv')."_info".$this->_getOpt('baseWindow').'.innerHTML=infoDivSave');
                    if($script.=$this->Get())
                        {
                            $this->objResponse->script($script);
                        }
                    if(($script=$this->getJQuery()) && !$lmsW['disableJQuery'])
                        {
                            $this->objResponse->script($script);
                        }
                        
                    if($this->_getOpt('jsFunc'))
                    {
                        $this->objResponse->script($this->_getOpt('jsFunc').".ShowPanelOut();");
                        if($this->_getOpt('SetXYofEl') && !$lmsW['SetXYofEl'])
                            $this->objResponse->script($this->_getOpt('jsFunc').".SetXYofEl();");
                        $this->objResponse->script($this->_getOpt('jsFunc').".requestUri='".$_SERVER['HTTP_REFERER']."';");
                        $this->objResponse->script($this->_getOpt('jsFunc').".RegisterFunction();");
                    }
                    if($ret['_script'])
                        $this->objResponse->script($ret['_script']);
                }
            catch (exception $e)
                {
                    $msg=$e->getMessage();
                    if($msg)
                        $this->ajaxSendError($msg);                
                    $this->objResponse->script($this->_getOpt('jsFunc').".HidePanel();");
                }
            $this->objResponse->script($this->_getOpt('jsFunc').".LoadDone();");
            return $this->objResponse;                    
        }
/*
    Wykonanie operacji i ewentualne odswiezenie okna parent
    s - dane formularza ktore maja byc zapisane w bazie
    opt - parametry JS do przekazania
*/        
    function _Set($s, $opt)
        {
            $this->_opt=json_decode($opt,true);
            $func=$this->_getOpt('func');
            $s=json_decode($s,true);
//  ErrorLogger::write(null,null,null,$this->_opt);
            $this->error=false;
            $this->errorMsg='';
            try
                {
                    $xajax_FILES=$this->_getOpt('files');
                    if(is_array($xajax_FILES))
                        {
                            foreach($xajax_FILES as $k=>$v)
                                {
                                    if(is_array($v))
                                        foreach($v as $kv=>$vv)
                                            $tmp[$kv][]=$vv;
#                            $xajax_FILES[$k]=json_decode($v,true);
                                }
                            foreach($tmp as $k=>$v)
                                if(count($v)==1)
                                    $tmp[$k]=$v[0];
                            $xajax_FILES=$tmp;
                        }
                    $parseForm=$this->parseForm($s);
                    $this->ajaxClearErr($this->_getOpt('baseWindow'));

                    if(method_exists($this,$func))
                        $this->$func(array('s'=>$s, 'parse'=>$parseForm ));
                    elseif(function_exists($func))
                        $func(array('s'=>$s, 'parse'=>$parseForm));
                    elseif(stristr($func,'?'))
                        {
                            $func=str_ireplace('?','',$func);
                            $l=explode('&',$func);
                            foreach($l as $k=>$v)
                                {
                                    $tmp=explode("=",$v);
                                    $_GET[$tmp[0]]=$tmp[1];
                                }
                            global $SMARTY, $LMS, $MAG, $AUTH, $SESSION, $DB, $CONFIG, $access, $modules_dirs, $pdf, $invoice;
                            $_POST=$this->parseForm($parseForm);
                            $_FILES=$xajax_FILES;
                            $module=$_GET['m'];
                            $func='module_'.$_GET['m'];
                            
                            ob_start();
                            $SMARTY->_LMSWindowManagerActive=true;
                            $SESSION->xajax_client=true;
                            include('index.php');
                            ob_get_contents();
                            ob_end_clean();

//clear errors in form (triggers)
                            if($_POST['_trigger'] && $_POST['_trigger']!='-1')
                                foreach($_POST[$_POST['_trigger']] as $k=>$v)
                                    $this->parentSendError(true,$_POST['_trigger']."[".$k."]","");
                            if($_POST['_trigger'] && $_POST['_trigger']=='-1')
                                foreach($_POST as $k=>$v)
                                    $this->parentSendError(true,$k,"");
                            if($error)
                                {
//$_POST['_trigger'] => nazwa obektu array do ktorego sie trzeba odwolywac aby wyrzucic blad via js. 
                                    foreach($error as $k=>$v)
                                        $this->parentSendError(false,$_POST['_trigger']=='-1' ? $k : $_POST['_trigger']."[".$k."]",$v);
                                }                            
                            $SMARTY->_LMSWindowManagerActive=false;
                            $SESSION->xajax_client=false;
                        }
                    
                    $this->objResponse->script($this->_getOpt('jsFunc').'.Error=true;');
                    if($this->error)
                        throw new exception($this->errorMsg ? $this->errorMsg : 'Wystąpiły błędy w formularzu.');

                    $this->ajaxSendOK($this->_getOpt('infoDivMaster'));

                    $afterSet=$func.'_after_set';
                    if(method_exists($this,$afterSet))
                        $this->$afterSet(array('parseForm' => $parseForm, 's' => $s));

                    $this->objResponse->script(''.$this->_getOpt('jsFunc').'.LoadDone();');

                    if($this->_getOpt('refreshFname'))
                        {
                            if($this->_getOpt('refreshId'))
                                $this->objResponse->script('xajax_'.$this->_getOpt('refreshFname').'(\''.$this->_getOpt('refreshId').'\',false);');
                            else
                                $this->objResponse->script($this->_getOpt('refreshFname'));                                
                        }
                    elseif($this->_parentReload)
                        $this->objResponse->script($this->_parentReload.'.Reload(true,false);');
                    elseif($this->_documentReload)
                        $this->objResponse->script('location.reload(true);');
//                    else
                    if(!$this->_Close)
                        $this->objResponse->script($this->_getOpt('jsFunc').".HidePanel();");
                    if(is_array($xajax_FILES))
                        foreach($xajax_FILES as $k=>$v)
                            if(is_array($v))
                                {
                                    foreach($v as $kv=>$vv)
                                        unlink($vv['tmp_name']);
                                }
                            else
                                unlink($v['tmp_name']);
              }
            catch(exception $e)
                {
                    $this->ajaxSendError($e->getMessage(),$this->_getOpt('baseWindow'),implode(',',$error['global'] ? $error['global'] : $error));
#                    .'sdsd sdsdsd asdasdas asdasdqwe  qwe asasd wdasd asd as dasd asd asdqwe ad a sashjsaddhasdhuhda as dausdihasdasuidahwhdsauhdauhdaushduahe qawuh au dhaus asu au hdausdhaushduah ashdauhd as dhausd ashdhsdu ashd asdasdjasdjasidjasjdiasd asidasdasda');
//                    $this->objResponse->script($this->_getOpt('jsFunc').".LoadingData=false;");
                    $this->objResponse->script($this->_getOpt('jsFunc').".LoadDone();");
                    $DB=LMSDB::getInstance();
                    $this->objResponse->assign('lmswsupout','innerHTML',ErrorLogger::write(__METHOD__,__LINE__,'LMSW_SET',$e.$DB->GetErrors()));
                    $this->objResponse->script('ParseSupout();');
                    $this->objResponse->script($this->_getOpt('jsFunc').'.Error=true;');
                }
            $this->objResponse->script("if(typeof ".$this->_getOpt('jsFunc')."_CallBack=='function') ".$this->_getOpt('jsFunc')."_CallBack();");
            return $this->objResponse;            
        }
    function _Close($s=null,$opt=null)
        {
            
            return $this->objResponse;
        }

}
?>
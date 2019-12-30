<?php
class Auth
{
    function __construct()
        {
            global $_SESSION, $_POST, $_GET;

            session_start();
            $SID=$_COOKIE['SID'];
            if($_SESSION[$SID]['authenticated'] && $SID)
                {
                    if(($_SESSION[$SID]['time']<time()-ConfigHelper::getConfig('phpui.timeout') && $_SESSION[$SID]['time']) || (!$_COOKIE['SID']) || $_SESSION[$SID]['vdata']!=serialize($this->makeVData()))
                        {
                            $this->do_logout();
                        }
                    $this->islogged=true;
                    $this->id=$_SESSION[$SID]['login_userid'];
                    $_SESSION[$SID]['time']=time();
                }
            else
                {
                    unset($_SESSION);
                    session_destroy();
                    session_start();
                    if($_POST)
                        {
                            $this->do_login($_POST);
                            Supla\Project\Init::getTemplate()->assign('doauth',true);
                        }
                }

            if(!$this->islogged)
                {
                    Supla\Project\Init::getTemplate()->display('templates/login.html');
                    die();
                }
            if($_GET['m']=='logout')
                $this->do_logout();
        }
    function do_logout()
        {
            session_destroy();
            Header('Location: ?');
            die();
        }
    function do_login($d)
        {
            global $_SESSION;
            if($d['loginform']['login'] && $d['loginform']['pwd'])
                {
                    $DB=LMSDB::getInstance();
                    if($id=$DB->GetOne("SELECT id from users where login=? and passwd=md5(?)",array($d['loginform']['login'],$d['loginform']['pwd'])))
                        {
                            $this->id=$id;
                            $this->islogged=true;
                            $SID=md5(uniqid(rand(), true).time().$d['loginform']['login']);
                            setcookie('SID', $SID);
                            $_SESSION[$SID]['vdata']=serialize($this->makeVData());
                            $_SESSION[$SID]['authenticated']=true;
                            $_SESSION[$SID]['time']=time();
                            $_SESSION['login_userid']=$id;
                            $_SESSION['login_name']=$d['loginform']['login'];
                        }
                }
        }
	function makeVData()
	{
		foreach(array('REMOTE_ADDR', 'REMOTE_HOST', 'HTTP_USER_AGENT', 'HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'SERVER_NAME', 'SERVER_PORT') as $vkey)
			if(isset($_SERVER[$vkey]))
				$vdata[$vkey] = $_SERVER[$vkey];
        $vdata['LOGIN']=$this->id ? $this->id : $_SESSION['login_userid'];
		if(isset($vdata))
			return $vdata;
		else
			return NULL;
	}
        
}
?>
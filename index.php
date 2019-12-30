<?php
require(__DIR__.'/vendor/autoload.php');
 
Supla\Project\Init::Config(__DIR__);
Supla\Project\Init::LoadRequires();
require_once(LIB_DIR.'/common.php');

Supla\Project\Init::Template();
$ajax=new Supla\Project\AJAX;
$DB=LMSDB::getInstance();
$DB->UpgradeDb();
$AUTH=new Auth;
Supla\Project\Init::RegisterXajax();
$LmsWindowManager=new LMSWindowManager;
$lmsW=new Supla\Project\LmsW();

$layout['username']=$_SESSION['login_name'];
$layout['version']="1.00";

#        $cfg=\SUPLA\Notify\Config::get();
#        \Supla\Project\Init::getTemplate()->assign('cfg',$cfg);
#        $cfg=\Supla\Project\Cloud::getConfig();
#        \Supla\Project\Init::getTemplate()->assign('cloud',json_decode($cfg,true));

#Supla\Project\Init::getTemplate()->assign('sd',$r);
Supla\Project\Init::getTemplate()->assign('layout',$layout);
Supla\Project\Init::getTemplate()->assign('xajax',$ajax->RunXajax());

Supla\Project\Init::getTemplate()->display('templates/main.html');
?>
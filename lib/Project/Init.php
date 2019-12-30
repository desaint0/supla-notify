<?php
namespace Supla\Project;

class Init
{
    private static $template=null;
    private static $dir=null;

    public static function LoadRequires() 
        {
            require_once(LIB_DIR.'/language.php');
        }
    public static function Config($dir)
        {
            self::$dir=$dir;
            $CONFIG_FILE = $dir.'/config.ini';
            $CONFIG = (array) parse_ini_file($CONFIG_FILE, true);
            define('CONFIG_FILE', $CONFIG_FILE);
            define('LIB_DIR', $dir.'/lib');
            define('SYS_DIR', $dir);
            ini_set('error_reporting', E_ALL&~E_NOTICE);
            return $CONFIG;
        }
    public static function Template()
        {
            $SMARTY=new \LMSSmarty;
            $SMARTY->addPluginsDir(LIB_DIR.'/SmartyPlugins');
            $SMARTY->setMergeCompiledIncludes(true);
#            $SMARTY->setDefaultResourceType('extendsall');
#            $SMARTY->registerFilter('pre', array('Smarty_Prefilter_Extendsall_Include', 'prefilter_extendsall_include'));
            $SMARTY->template_dir = self::$dir.'/templates';
            $SMARTY->compile_dir = self::$dir.'/templates_c';
            $SMARTY->assignByRef('LANGDEFS', $LANGDEFS);
            $SMARTY->assignByRef('_ui_language', \ConfigHelper::getConfig('phpui.lang'));
            $SMARTY->assignByRef('_language', \ConfigHelper::getConfig('phpui.lang'));
            $SMARTY->assign('menu',array(
                0 => array('title' => 'MQTT', 'desc'=> ' konfiguracja MQTT', 'xajax' => 'MQTT'),
                1 => array('title' => 'SUPLA Cloud', 'desc'=> ' SUPLA Cloud', 'xajax' => 'Cloud'),
                2 => array('title' => 'Sceny', 'desc'=> ' Sceny', 'xajax' => 'Scenes'),
            ));
            self::$template=$SMARTY;
        }
    public static function getTemplate()
        {
            global $SMARTY;
            $SMARTY=self::$template;
            return self::$template;
        }
    public static function RegisterXajax()
        {
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('MQTT',"\Supla\Project\MQTT",'Show')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('MQTTSave',"\Supla\Project\MQTT",'Save')),array('URI'=>'"'.$uri.'"'));        

            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('Cloud',"\Supla\Project\Cloud",'ShowCloud')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('CloudGet',"\Supla\Project\Cloud",'CloudGet')),array('URI'=>'"'.$uri.'"'));        

            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('Scenes',"\Supla\Project\Scenes",'ShowScenes')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('ScenesGet',"\Supla\Project\Scenes",'ScenesGet')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('SuplaChannel',"\Supla\Project\Scenes",'SuplaChannel')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('TemplatesGet',"\Supla\Project\Scenes",'TemplatesGet')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('TemplatesSave',"\Supla\Project\Scenes",'TemplatesSave')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('TemplatesLoad',"\Supla\Project\Scenes",'TemplatesLoad')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('getAllChannelsStatus',"\Supla\Project\Scenes",'getAllChannelsStatus')),array('URI'=>'"'.$uri.'"'));        
            AJAX::getObj()->RegisterXajaxFunction(array('0' => array('Ping',"\Supla\Project\Scenes",'Ping')),array('URI'=>'"'.$uri.'"'));        
        }
} 
?>
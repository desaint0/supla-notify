<?php
class ErrorLogger
{
    const FILE='lms.log';
    const DBERR_FILE='db.log';
    const QUERY_FILE='query.log';
    
    private static $FILE='';
    private static $DIR='';
    private static $_iscalled=array();
    private static $_dblogged=false;
    private static $_data='';

    public static function getDir()
        {
            if(!self::$DIR)
                {
                    if(LMSConfig::getIniConfig()->getSection('directories')->hasVariable('error_logger_dir'))
                        $DIR=LMSConfig::getIniConfig()->getSection('directories')->getVariable('error_logger_dir')->getValue();
                    self::$DIR=$DIR ? $DIR : '/var/log/lms/';
                }        
            return self::$DIR;
        }
    private static function log($msg)
        {
            error_log($msg.'
',3,self::getDir().self::$FILE);
            self::$_data.=$msg.chr(10).chr(13);        
        }
                
    private static function wr($title=null,$msg=null)
        {
            global $AUTH;
            if($title==null && $msg==null)
                {
                    self::log('-------------------------------');
                    if(self::$_iscalled[self::$FILE]==false)
                        {
                            self::$_iscalled[self::$FILE]=true;
                            self::log('---------- NEW LOG ------------');
                            self::log('-------------------------------');
                        }
                    self::wr('AUTH: '.$AUTH->id);
                }
            if($title)
                self::log(date("Y-m-d H:i:s").' '.$title);    
            if($msg)
                self::log($msg);
        }
        
    private static function setFile($file=null)
        {
            if($file)
                self::$FILE=$file;
            else
                self::$FILE=self::FILE;
        }
    private static function bt()
        {
            self::wr('TRACE: ');
            $t=debug_backtrace();
            foreach($t as $k=>$v)
                self::wr(null,$v['file'].":".$v['line']." | ".$v['function']  . " | ");
        }

    private static function parseVar($var)
        {
            if(is_array($var))
                return var_export($var,true);
            else
                return $var;        
        }
    public static function dbwrite($a=null,$b=null,$query, $error)
        {
            self::setFile(self::DBERR_FILE);
            self::wr();
            self::wr('METHOD/FILE: '.$a);
            if($b)
                self::wr('LINE: '.$b);
            self::wr('QUERY: ',$query);
            self::wr('ERROR',$error);
            self::bt();            
            self::$_dblogged=true;            
        }
    public static function dblog($a,$b,$query)
        {
            self::setFile(self::QUERY_FILE);
            self::wr();
            self::bt();
            self::wr('QUERY: ',$query);
        }
    public static function write($a=null,$b=null,$msg, $dump=null)
        {
            global $SMARTY;
            self::setFile();
            
            $m=self::parseVar($msg);
            $d=self::parseVar($dump);                        
            self::wr();
            self::wr('METHOD/FILE: '.$a);
            if($b)
                self::wr('LINE: '.$b);
            self::wr('LOG',$m);
            if($d)
                self::wr('DUMP',$d);
//            $DB=LMSDB::getInstance();
//            if($DB->_gError)
//                {
//                    self::wr('DB_ERROR: '. self::$_dblogged ? 'LOGGED' : '!NOT_LOGGED!');
//                    self::$_dblogged=false;
//                }
            if(is_object($msg))
        	if(!stristr(get_class($msg),'Exception'))
            	    self::bt();
            
            if($SMARTY)
                $SMARTY->assign('_lmswsupout',self::$_data);
            return self::$_data;                    
        }
}
?>
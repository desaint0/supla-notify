<?php

/*
 * LMS version 1.11.10 Kri
 *
 *  (C) Copyright 2001-2010 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id: LMSDB_common.class.php,v 1.52 2010/04/13 17:01:44 alec Exp $
 */

/*
 * LMSDB - klasa wspólna.
 */
define('DBVERSION', '2019122800'); // here should be always the newest version of database!
				 // it placed here to avoid read disk every time when we call this file.

Class LMSDB_common implements LMSDBInterface
{
	var $_version = '1.11.10 Kri';
	var $_revision = '$Revision: 1.52 $';
	
	// Driver powinien nadpisać tą zmienną wartością TRUE, żeby
	// funkcja inicjująca baze danych wiedziała że driver się poprawnie
	// załadował
	
	var $_loaded = FALSE;

	// Wewnętrzne zmienne bazy danych, tj, resource, link, itp.

	var $_dbtype = 'NONE';
	var $_dblink = NULL;
	var $_dbhost = NULL;
	var $_dbuser = NULL;
	var $_dbname = NULL;
	var $_error = FALSE;
	var $_query = NULL;
	var $_result = NULL;

	var $errors = array();
	var $debug = FALSE;
    var $stack = null;
    var $_gError = false;
    var $SHOW_QUERY=false;
    var $LOG_QUERY=false;

	function Connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if(method_exists($this, '_driver_shutdown'))
			register_shutdown_function(array($this, '_driver_shutdown'));
		
		// Inicjuje połączenie do bazy danych, nie musimy zwracać
		// dblinka na zewnątrz gdyż jest to niepotrzebne.
		
		if($this->_driver_connect($dbhost,$dbuser,$dbpasswd,$dbname))
			return $this->_dblink;
		else
		{
			$this->errors[] = array(
					'query' => 'database connect',
					'error' => $this->_driver_geterror(),
					);
			return FALSE;
		}
	}

	function Destroy()
	{
		return $this->_driver_disconnect();
	}

	public function Execute($query, array $inputarray = NULL)
	{
		if(! $this->_driver_execute($this->_query_parser($query, $inputarray))){
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => $this->_driver_geterror()
					);
            ErrorLogger::dbwrite(__METHOD__,__LINE__,$query,$this->_driver_geterror());
        }
		elseif($this->debug)
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => 'DEBUG: NOERROR'
					);
#    echo($this->_query."<BR>$query<BR>");
#    print_r($this->errors);
#    echo("<BR><BR>");

		return $this->_driver_affected_rows();
	}
    public function MultiExecute($query, array $inputarray = NULL)
    {
        if ($this->debug) {
            $start = microtime(true);
        }

        if (!$this->_driver_multi_execute($this->_query_parser($query, $inputarray))) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => $this->_driver_geterror(),
            );
        } elseif ($this->debug) {
            $this->errors[] = array(
                'query' => $this->_query,
                'error' => 'DEBUG: NOERROR',
                'time' => microtime(true) - $start,
            );
        }
        
        return $this->_driver_affected_rows();

    }
    public function GroupConcat($field, $separator = ',', $distinct = false)
    {
        return $this->_driver_groupconcat($field, $separator, $distinct);

    }

    /**
    * Check if database resource exists (table, view)
    *
    * @param string $name
    * @param int $type
    * @return exists boolean
    */
    public function ResourceExists($name, $type) {
	    return $this->_driver_resourceexists($name, $type);
	}

    public function GetVersion()
    {

        return $this->_version;

    }
    public function GetRevision()
    {

        return $this->_revision;

    }
    public function GetDbType()
    {

        return $this->_dbtype;

    }
    public function GetResult()
    {

        return $this->_result;

    }

	function GetAll($query = NULL, array $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_assoc())
			$result[] = $row;
    $this->_result=$result;
#		print_r($result);
		return $result;
	}

	function GetAllByKey($query = NULL, $key = NULL, array $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_assoc())
			$result[$row[$key]] = $row;

		return $result;
	}

	function GetRow($query = NULL, array $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		return $this->_driver_fetchrow_assoc();
	}

	function GetCol($query = NULL, array $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		while($row = $this->_driver_fetchrow_num())
			$result[] = $row[0];
		
		return $result;
	}

	function GetOne($query = NULL, array $inputarray = NULL)
	{
		if($query)
			$this->Execute($query, $inputarray);

		$result = NULL;

		list($result) = $this->_driver_fetchrow_num();

		return $result;
	}

	// with Exec() & FetchRow() we can do big results looping
	// in less memory consumptive way than using GetAll() & foreach()
	function Exec($query, array $inputarray = NULL)
	{
		if(! $this->_driver_execute($this->_query_parser($query, $inputarray)))
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => $this->_driver_geterror()
					);
		elseif($this->debug)
			$this->errors[] = array(
					'query' => $this->_query,
					'error' => 'DEBUG: NOERROR'
					);
#    print_r($this->errors);
		if($this->_driver_num_rows())
			return $this->_result;
		else
			return NULL;
	}
    public function &GetErrors()
    {

        return $this->errors;

    }

    /**
     * Sets errors.
     * 
     * @param array $errors
     */
    public function SetErrors(array $errors = array())
    {

        $this->errors = $errors;

    }

	function FetchRow($result = NULL)
	{
		return $this->_driver_fetchrow_assoc($result);
	}
	
	function Concat()
	{
		return $this->_driver_concat(func_get_args());
	}

	function Now()
	{
		return $this->_driver_now();
	}

	function ListTables()
	{
		return $this->_driver_listtables();
	}

	function BeginTrans()
	{
        if(!$this->stack)
        {
            $this->stack[]=count($this->stack);
            return $this->_driver_begintrans();
        }
        else
            $this->stack[]=count($this->stack);
	}

	function CommitTrans()
	{
        if(count($this->stack)==1)
            {
                array_pop($this->stack);            
                return $this->_driver_committrans();
            }
        else
            array_pop($this->stack);
	}

	function RollbackTrans()
	{
        if(count($this->stack)==1)
            {
                array_pop($this->stack);            
                return $this->_driver_rollbacktrans();
            }
        else
            array_pop($this->stack);
	}

	function LockTables($table, $locktype=null)
	{
		return $this->_driver_locktables($table, $locktype);
	}

	function UnLockTables()
	{
		return $this->_driver_unlocktables();
	}

	function GetDBVersion()
	{
		return $this->_driver_dbversion();
	}

	function SetEncoding($name)
	{
		return $this->_driver_setencoding($name);
	}

	function GetLastInsertID($table = NULL)
	{
		return $this->_driver_lastinsertid($table);
	}

	function Escape($input)
	{
		return $this->_quote_value($input);
	}

	function _query_parser($query, $inputarray = NULL)
	{
		// najpierw sparsujmy wszystkie specjalne meta śmieci.
		$query = preg_replace('/\?NOW\?/i',$this->_driver_now(),$query);
		$query = preg_replace('/\?LIKE\?/i',$this->_driver_like(),$query);

    $q=explode("LIMIT",$query);
#    print_r($q);echo("<BR>"); 
    if (count($q)>1)
     {
      $qq=substr($query,0,strlen($query)-strlen($q[count($q)-1]));
      list($l,$o)=explode(",",$q[count($q)-1]);
      $o=intval($o);
      if ($o>0)
       $query=$qq." ".$this->_driver_limit($l,$o);
#      echo($qq."<BR>");
     }
		if($inputarray)
		{
			$queryelements = explode("\0",str_replace('?',"?\0",$query));
			$query = '';
			foreach($queryelements as $queryelement)
			{
				if(strpos($queryelement,'?') !== FALSE)
				{
					list($key,$value) = each($inputarray);
					$queryelement = str_replace('?',$this->_quote_value($value),$queryelement);
				}
				$query .= $queryelement;
			}
		}

        if($this->SHOW_QUERY)
            echo($query."--(".count($this->stack).")<BR>\n");		    
        if($this->LOG_QUERY)            
            ErrorLogger::dblog(__METHOD__,__LINE__,$query);

		return $query;
	}
    

	function _quote_value($input)
	{
		// jeżeli baza danych wymaga innego eskejpowania niż to, driver
		// powinien nadpisać tą funkcję

		if($input === NULL)
			return 'NULL';
		elseif(gettype($input) == 'string')
			return '\''.addcslashes($input,"'\\\0").'\'';
		else
			return $input;
	}

	// Funkcje bezpieczeństwa, tj. na wypadek gdyby driver ich nie
	// zdefiniował.

	function _driver_now()
	{
		return time();
	}

	function _driver_like()
	{
		return 'LIKE';
	}

	function _driver_setencoding($name)
	{
		$this->Execute('SET NAMES ?', array($name));
	}
    public function IsLoaded()
    {

        return $this->_loaded;

    }
    public function GetDbLink()
    {

        return $this->_dblink;

    }
    public function SetDebug($debug = true)
    {

        $this->debug = $debug;

    }
    
	public function UpgradeDb($dbver = DBVERSION, $pluginclass = null, $libdir = null, $docdir = null) {
		$lastupgrade = null;
		if ($dbversion = $this->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype = ?',
				array('dbversion' . (is_null($pluginclass) ? '' : '_' . $pluginclass)))) {
            $DB=LMSDB::getInstance(); //for backward compatibility. now use $this instead of $DB.    
			if ($dbver > $dbversion) {
				set_time_limit(0);
				$lastupgrade = $dbversion;

				if (is_null($libdir))
					$libdir = LIB_DIR;

				$filename_prefix = $this->_dbtype == LMSDB::POSTGRESQL ? 'postgres' : 'mysql';
				$pendingupgrades = array();
				$upgradelist = getdir($libdir . DIRECTORY_SEPARATOR . 'upgradedb', '^' . $filename_prefix . '\.[0-9]{10}\.php$');
				if (!empty($upgradelist))
					foreach ($upgradelist as $upgrade) {
						$upgradeversion = preg_replace('/^' . $filename_prefix . '\.([0-9]{10})\.php$/', '\1', $upgrade);

						if ($upgradeversion > $dbversion && $upgradeversion <= $dbver)
							$pendingupgrades[] = $upgradeversion;
					}

				$this->BeginTrans();
                if (!empty($pendingupgrades)) {
					sort($pendingupgrades);
					foreach ($pendingupgrades as $upgrade) {
						include($libdir . DIRECTORY_SEPARATOR . 'upgradedb' . DIRECTORY_SEPARATOR . $filename_prefix . '.' . $upgrade . '.php');
						if (empty($this->errors))
							$lastupgrade = $upgrade;
						else
							break;
                        
					}
                    if(!$this->_gError)
                        $this->Execute("UPDATE dbinfo set keyvalue=? where keytype=?",array($lastupgrade,'dbversion' . (is_null($pluginclass) ? '' : '_' . $pluginclass)));
                    
				}
                $this->CommitTrans();
			}
		} else {
			// save current errors
			$err_tmp = $this->errors;
			$this->errors = array();

			if (is_null($pluginclass)) {
				// check if dbinfo table exists (call by name)
				$dbinfo = $this->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?', array('dbinfo'));
				// check if any tables exists in this database
				$tables = $this->GetOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema NOT IN (?, ?)', array('information_schema', 'pg_catalog'));
			} else {
				$dbinfo = $this->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype = ?', array('dbinfo_' . $pluginclass));
				$tables = 0;
			}
			// if there are no tables we can install lms database
			if ($dbinfo == 0 && $tables == 0 && empty($this->errors)) {
				// detect database type and select schema dump file to load
				$schema = '';
				if ($this->_dbtype == LMSDB::POSTGRESQL)
					$schema = 'lms.pgsql';
				elseif ($this->_dbtype == LMSDB::MYSQL || $this->_dbtype == LMSDB::MYSQLI)
					$schema = 'lms.mysql';
				else
					die ('Could not determine database type!');

				if (is_null($docdir))
					$docdir = SYS_DIR . DIRECTORY_SEPARATOR . 'doc';

				if (!$sql = file_get_contents($docdir . DIRECTORY_SEPARATOR . $schema))
					die ('Could not open database schema file ' . $docdir . DIRECTORY_SEPARATOR . $schema);
				if (!$this->MultiExecute($sql))    // execute
					die ('Could not load database schema!');
			} else
				// database might be installed so don't miss any error
				$this->errors = array_merge($err_tmp, $this->errors);
		}
		return isset($lastupgrade) ? $lastupgrade : $dbver;
	}

}

?>

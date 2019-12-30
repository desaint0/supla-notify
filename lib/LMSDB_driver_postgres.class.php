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
 *  $Id: LMSDB_driver_postgres.class.php,v 1.49 2010/03/11 13:07:34 alec Exp $
 */

/*
 * To jest pseudo-driver dla LMSDB, dla bazy danych 'postgres'.
 */

class LMSDB_driver_postgres extends LMSDB_common
{
	var $_loaded = TRUE;
	var $_dbtype = 'postgres';

	function __construct($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		if(!extension_loaded('pgsql'))
		{
			trigger_error('PostgreSQL extension not loaded!', E_USER_WARNING);
			$this->_loaded = FALSE;
			return;
		}

		$this->_version .= ' ('.preg_replace('/^.Revision: ([0-9.]+).*/','\1',$this->_revision).'/'.preg_replace('/^.Revision: ([0-9.]+).*/','\1','$Revision: 1.49 $').')';
		$this->Connect($dbhost,$dbuser,$dbpasswd,$dbname);
	}

	function _driver_dbversion()
	{
		return $this->GetOne("SELECT split_part(version(),' ',2)");
	}

	function _driver_connect($dbhost,$dbuser,$dbpasswd,$dbname)
	{
		$cstring = join(' ',array(
			($dbhost != '' && $dbhost != 'localhost' ? 'host='.$dbhost : ''),
			($dbuser != '' ? 'user='.$dbuser : ''),
			($dbpasswd != '' ? 'password='.$dbpasswd : ''),
			($dbname != '' ? 'dbname='.$dbname : '')
		));

		if($this->_dblink = @pg_connect($cstring))
		{
			$this->_dbhost = $dbhost;
			$this->_dbuser = $dbuser;
			$this->_dbname = $dbname;
			$this->_error = FALSE;
		}
		else
			$this->_error = TRUE;

		return $this->_dblink;
	}

	function _driver_disconnect()
	{
		$this->_loaded = FALSE;
		@pg_close($this->_dblink);
	}

	function _driver_geterror()
	{
		if($this->_dblink)
                        return pg_last_error($this->_dblink);
                else
            		return 'We\'re not connected!';
	}
	
	function _driver_execute($query)
	{
		$this->_query = $query;

		if($this->_result = @pg_query($this->_dblink,$query))
			$this->_error = FALSE;
		else
			$this->_error = TRUE;
        if ($this->_error && !$this->_gError)
            $this->_gError=$query;
		return $this->_result;
	}

    public function _driver_multi_execute($query)
    {
        return $this->_driver_execute($query);

    }

	function _driver_fetchrow_assoc($result = NULL)
	{
		if(! $this->_error)
			return @pg_fetch_array($result ? $result : $this->_result,NULL, PGSQL_ASSOC);
		else
			return FALSE;
	}

	function _driver_fetchrow_num()
	{
		if(! $this->_error)
			return @pg_fetch_array($this->_result,NULL,PGSQL_NUM);
		else
			return FALSE;
	}
	
	function _driver_affected_rows()
	{
		if(! $this->_error)
			return @pg_affected_rows($this->_result);
		else
			return FALSE;
	}

	function _driver_num_rows()
	{
		if(! $this->_error)
			return @pg_num_rows($this->_result);
		else
			return FALSE;
	}
/*	
	// added 'E' for postgresql 8.2 to skip warnings in error log:
	// HINT:  Use the escape string syntax for backslashes, e.g., E'\\'.
	// WARNING:  nonstandard use of escape in a string literal at character...  */

	function _quote_value($input)
        {
                if($input === NULL)
			return 'NULL';
		elseif(gettype($input) == 'string')
			return '\''.@pg_escape_string($this->_dblink, $input).'\'';
		else
			return $input;
	}

	function _driver_now()
	{
		return 'EXTRACT(EPOCH FROM CURRENT_TIMESTAMP(0))';
	}

	function _driver_like()
	{
		return 'ILIKE';
	}

	function _driver_concat($input)
	{
		return implode(' || ',$input);
	}

	function _driver_listtables()
	{
		return $this->GetCol('SELECT relname AS name FROM pg_class WHERE relkind = \'r\' and relname !~ \'^pg_\' and relname !~ \'^sql_\'');
	}

	function _driver_begintrans()
	{
		return $this->Execute('BEGIN');
	}

	function _driver_committrans()
	{
		return $this->Execute('COMMIT');
	}

	function _driver_rollbacktrans()
	{
		return $this->Execute('ROLLBACK');
	}

	// @todo: locktype
	function _driver_locktables($table, $locktype=null)
        {
	        if(is_array($table))
		        $this->Execute('LOCK '.implode(', ', $table));
		else
		        $this->Execute('LOCK '.$table);
	}

	function _driver_unlocktables()
	{
		return TRUE;
	}

											
	function _driver_lastinsertid($table)
	{
        return $this->GetOne('SELECT currval(\''.$table.'_id_seq\')');
	}

    public function _driver_groupconcat($field, $separator = ',', $distinct = false)
    {
	if ($distinct === false) {
	    return 'array_to_string(array_agg(' . $field . '), \'' . $separator . '\')';
	} else {
	    return 'array_to_string(array_agg(DISTINCT ' . $field . '), \'' . $separator . '\')';
	}
    }

 function _driver_groupconcatTable($w,$c,$f,$s,$func="",$where="")
  {
   $t2=$f."_gc";
   $what=$w;
   if ($func) {$what="$func($w)";}
   $q="(SELECT $c,array_to_string(ARRAY(SELECT $what from $f WHERE $f.$c=$t2.$c $where),'".$s."') as $w from (SELECT $c from $f group by $c) as $t2 )";
   return $q;  
  }  
  function _driver_limit($l,$o)
   {
    if (empty($o)) return $l;
     else
      return "$o offset $l";
   }

      public function _driver_resourceexists($name, $type) {
                switch ($type) {
                        case LMSDB::RESOURCE_TYPE_TABLE:
                                $table_type = 'BASE TABLE';
                                break;
                        case LMSDB::RESOURCE_TYPE_VIEW:
                                $table_type = 'VIEW';
                                break;
                        case LMSDB::RESOURCE_TYPE_COLUMN:
                                list ($table_name, $column_name) = explode('.', $name);
                                break;
                }
                if (isset($table_name))
                        return $this->GetOne('SELECT COUNT(*) FROM information_schema.columns
                                WHERE table_catalog = ? AND table_name = ? AND column_name = ?',
                                array($this->_dbname, $table_name, $column_name)) > 0;
                else
                        return $this->GetOne('SELECT COUNT(*) FROM information_schema.tables
                                WHERE table_catalog=? AND table_name=? AND table_type=?',
                                array($this->_dbname, $name, $table_type)) > 0;
        }

}

?>

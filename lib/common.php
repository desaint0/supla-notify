<?php


function ip_long($sip)
{
	if(check_ip($sip)){
		return sprintf('%u',ip2long($sip));
	}else{
		return 0;
	}
}

function check_ip($ip)
{
	return (bool) preg_match('/^((25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)$/', $ip);
}

function check_ipv6($ip)
{
        // fast exit for localhost
	if (strlen($ip) < 3)
	        return $IP == '::';
	
	// Check if part is in IPv4 format
	if (strpos($ip, '.')) {
		$lastcolon = strrpos($ip, ':');
		if (!($lastcolon && check_ip(substr($ip, $lastcolon + 1))))
		        return false;
							    
		// replace IPv4 part with dummy
		$ip = substr($ip, 0, $lastcolon) . ':0:0';
	}
	
	// check uncompressed
	if (strpos($ip, '::') === false) {
		return preg_match('/^(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}$/i', $ip);
	}
	
	// check colon-count for compressed format
	if (substr_count($ip, ':') < 8) {
		return preg_match('/^(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?$/i', $ip);
	}
	
	return false;
}

function check_mask($mask)
{
	$i=0;
	$j=0;
	$maskb=decbin(ip2long($mask));
	if (strlen($maskb) < 32)
		return FALSE;
	else
	{
		while (($i<32) && ($maskb[$i] == '1'))
		{
			$i++;
		}
		$j=$i+1;
		while (($j<32) && ($maskb[$j] == '0'))
		{
			$j++;
		}
		if ($j<32)
			return FALSE;
		else
			return TRUE;
	}
}

function getbraddr($ip,$mask)
{
	if(check_ip($ip) && check_mask($mask))
	{
		$net = ip2long(getnetaddr($ip, $mask));
		$mask = ip2long($mask);

		return long2ip($net | (~$mask));
	}
	else
		return false;
}

function getnetaddr($ip,$mask)
{
	if(check_ip($ip) && check_mask($mask))
	{
		$ip = ip2long($ip);
		$mask = ip2long($mask);
		
		return long2ip($ip & $mask);
	}
	else
		return false;
}

function prefix2mask($prefix)
{
	if($prefix>=0&&$prefix<=32)
	{	
		$out = '';
		for($ti=0;$ti<$prefix;$ti++)
			$out .= '1';
		for($ti=$prefix;$ti<32;$ti++)
			$out .= '0';
		return long2ip(bindec($out));
	}
	else
		return false;
}

function mask2prefix($mask)
{
	if(check_mask($mask))
	{
		return strlen(str_replace('0','',decbin(ip2long($mask))));
	}
	else
	{
		return -1;
	}
}

/*
 * mac checking function - requires macaddr passed as reference,
 * so it can fix mac address instantly to valid string
 */

function check_mac(&$macaddr)
{
	// save passed macaddr for future use
	
	$oldmac = $macaddr;

	// strip EVERYTHING that doesnt match 0-9 and a-f,
	// so $macaddr should contains 12 hex digits, and that's
	// will be base for our test

	$macaddr = preg_replace('/[^0-9a-f]/i', '', $macaddr);

	if(!preg_match('/^[0-9a-f]{12}$/i', $macaddr))
	{
		// mac address isn't valid, restore it (cause we working on
		// reference) and return false
	
		$macaddr = $oldmac;
	
		return FALSE;
	}
	else
	{
		// mac address is valid, return nice mac address that LMS
		// uses.

		$macaddr = $macaddr[0].$macaddr[1].':'.
			$macaddr[2].$macaddr[3].':'.
			$macaddr[4].$macaddr[5].':'.
			$macaddr[6].$macaddr[7].':'.
			$macaddr[8].$macaddr[9].':'.
			$macaddr[10].$macaddr[11];
		return TRUE;
	}
}

function getmicrotime()
{
	// This function has been taken from PHP manual

	list($usec, $sec) = explode(' ',microtime());
	return ((float)$usec + (float)$sec);
}

function getdir($pwd = './', $pattern = '^.*$') {
	$files = array();
	if ($handle = @opendir($pwd)) {
		while (($file = readdir($handle)) !== FALSE)
			if (preg_match('/' . $pattern . '/', $file))
				$files[] = $file;                        
		closedir($handle);
	}
	return $files;
}

?>

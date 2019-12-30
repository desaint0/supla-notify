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
 *  $Id: checkdirs.php,v 1.38 2010/03/11 13:07:34 alec Exp $
 */

if(!is_dir(SMARTY_COMPILE_DIR))
	die('Missing directory <B>'.SMARTY_COMPILE_DIR.'</B>. Can anybody make them?');

if(!is_writable(SMARTY_COMPILE_DIR))
	die('Can\'t write to directory <B>'.SMARTY_COMPILE_DIR.'</B>. Run: <BR><PRE>chown '.posix_geteuid().':'.posix_getegid().' '.SMARTY_COMPILE_DIR."\nchmod 755 ".SMARTY_COMPILE_DIR.'</PRE>This helps me to work. Thanks.');

?>

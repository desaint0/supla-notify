<?php
$this->Execute("ALTER TABLE conditions add clear_actions smallint default 0");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019122400', 'dbversion'));
?>
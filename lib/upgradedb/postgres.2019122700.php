<?php
$this->BeginTrans();
$this->Execute("
    CREATE TABLE elements (
        id serial,
        conditions_id integer,
        condition text,
        actions text,
        primary key(id)
    );
");
$this->Execute("ALTER TABLE elements add constraint elements_conditions_id_fkey FOREIGN KEY (conditions_id) references conditions(id) on delete cascade on update cascade");
$this->Execute("INSERT INTO elements (conditions_id, condition,actions) SELECT id, condition,actions from conditions");
$this->Execute("ALTER TABLE conditions drop condition");
$this->Execute("ALTER TABLE conditions drop actions");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019122700', 'dbversion'));
$this->CommitTrans();
?>
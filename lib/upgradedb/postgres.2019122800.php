<?php
$this->Execute("CREATE TABLE channels_log (
    id serial,
    channel_id integer,
    timestamp timestamp,
    ptype varchar,
    value numeric(7,3),
    primary key(id)
);");
$this->Execute("ALTER TABLE channels_log add constraint channels_log_channel_id_fkey foreign key(channel_id) references cloud(id)");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2019122800', 'dbversion'));
?>
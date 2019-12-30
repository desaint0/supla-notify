DROP SEQUENCE if exists users_id_seq;
CREATE SEQUENCE users_id_seq;
DROP TABLE if exists users;
CREATE TABLE users (
	id integer DEFAULT nextval('users_id_seq'::text) NOT NULL,
	login varchar(32) 	DEFAULT '' NOT NULL,
	passwd varchar(255) 	DEFAULT '' NOT NULL,
	PRIMARY KEY (id),
	UNIQUE (login)
);

insert into users (login, passwd) values ('admin', md5('admin'));
DROP TABLE if exists dbinfo;
CREATE TABLE dbinfo (
    keytype 	varchar(255) 	DEFAULT '' NOT NULL,
    keyvalue 	varchar(255) 	DEFAULT '' NOT NULL,
    PRIMARY KEY (keytype)
);

INSERT INTO dbinfo (keytype,keyvalue) values('dbversion', '2019112200');

DROP TABLE if exists conditions cascade;
CREATE TABLE conditions (
    id serial,
    name varchar,
    description varchar,
    condition text,
    actions text,
    elem varchar,
    comp varchar(2) default '==',
    value varchar,
    type varchar(1) default 'S',
    disabled smallint default 1,
    primary key(id)
);

DROP TABLE if exists cloud cascade;
CREATE TABLE cloud (
    id serial,
    cloud varchar,
    type_mqtt varchar,
    type_id integer,
    description varchar,
    devid integer,
    devName varchar,
    cmd varchar,
    type varchar,
    payload text,
    primary key(id)
);

DROP TABLE if exists scenes_templates cascade;
CREATE TABLE scenes_templates (
    id serial,
    tpl varchar,
    obj varchar,
    data text,
    primary key(id)
);


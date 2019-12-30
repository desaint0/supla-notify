<?php
namespace SUPLA\Notify;

class Log
{
    public static function Insert($chan,$payload)
        {
            $DB=\LMSDB::getInstance();
            foreach($payload as $k=>$v)
                {
                    if(!preg_match('/^on|hi|temperature|sensor_1$/',$k))
                        continue;
                    $DB->Execute("INSERT into channels_log (channel_id, timestamp, ptype, value) values ((SELECT id from cloud where type_mqtt='subscribe' and cmd=?), now(), ?, ?)",array(
                        $chan,
                        $k,
                        $v,
                    ));
                }
        }
    public static function getData($d)
        {
            $DB=\LMSDB::getInstance();
            preg_match('/^(asc|desc)$/',$d['sort'],$m);
            $sort=$m[1] ? $m[1] : 'desc';
            return $DB->GetAll("SELECT ch.value, ch.timestamp, extract(epoch from timestamp::timestamp with time zone)::bigint as time from channels_log ch where id in (
                SELECT max(ch.id) from channels_log ch where ch.channel_id=? and ch.ptype=? and timestamp>now() - interval ? 
                group by extract(epoch from timestamp::timestamp with time zone)::bigint/?
                ) order by timestamp ".$sort,array(
                $d['id'],
                $d['ptype'],
                $d['time'],
                $d['grid'],
            ));
        }
}
?>
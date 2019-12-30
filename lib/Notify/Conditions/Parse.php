<?php
namespace SUPLA\Notify\Conditions;

class Parse
{
    private static function getCond($c)
        {
            if($c=="AND")
                return "&&";
            if($c=="OR")
                return "||";
        } 
    private static function parseCondition(&$c)
        {
            foreach($c as $k=>&$v)
                {
                    if($v['rules'])
                        {
                            self::parseCondition($v['rules']);

                            $v=($v['not'] ? "!" : "")."(".implode(" ".self::getCond($v['cond'])." ",$v['rules']).")";
                        }
                    else
                        {
                            $v['cond_NOT']=$v['cond_NOT'] ? "!" : "";
                            $v=implode($v);
                        }
                }
        }
    
    public static function Execute(&$cfg)
        {
            foreach($cfg as $k=>&$v)
                {
                    $j=json_decode($v['condition'],true);
                    self::parseCondition($j);
                    $v['condition']=array_pop($j);
                }
        }
}
?>
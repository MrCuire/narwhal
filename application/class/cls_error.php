<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/19
 * Time: 17:51
 */

class cls_error
{
    public static function output_error(array $ex){
        $zero="";
        $data=array("errfile"=>$ex["errfile"],"errline"=>$ex["errline"]);
        if(strlen($ex["errno"])!=7){
            $zn=7-strlen($ex["errno"])-2;
            for($n=1;$n<=$zn;$n++){
                $zero.=0;
            }
            $ex["errno"]="E4".$zero.$ex["errno"];
        }
        cls_output::error_msg($ex["errno"],$ex["errstr"],$data);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/15
 * Time: 16:33
 */

include_once ROOT_FW_PATH."application/class/cls_init.php";
spl_autoload_register("cls_init::autoload");

// composer的自动加载
//require_once ROOT_FW_PATH . 'vendor/autoload.php';

$init=new cls_init();

$_G["userInfo"]=$init->user;

//$_G['isHTTPS'] = isset($_SERVER['HTTPS'])&&($_SERVER['HTTPS'] && strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
//$_G["serverRoot"]="http".($_G['isHTTPS']?"s":"")."://".$_G["serverIp"].(empty($_G['serverPort'])?"":":".$_G['serverPort']);
//$_G["siteUrl"]='http'.($_G['isHTTPS']?"s":"").'://'.$_SERVER['HTTP_HOST']."/";
if (isset($_SERVER['PHP_SELF']))
{
    $_G["phpSelf"]=$_SERVER['PHP_SELF'];
}
else
{
    $_G["phpSelf"]= $_SERVER['SCRIPT_NAME'];
}

#数据库
$db=$init->db;

#载入语言包
require_once LANGUAGE_PATH."common.php";

#实例化路由
$api=$init->api;

$api->init($init->input);


<?php
// database host
$db_host   = "127.0.0.1";
$config['db_host']=$db_host;
// database name
$db_name   = "tc";
$config['db_name']=$db_name;
// database username
$db_user   = "root";
$config['db_user']=$db_user;
// database password
$db_pass   = "";
$config['db_pass']=$db_pass;
// table prefix
$prefix    = "tc_";
$config['prefix']=$prefix;
$timezone    = "PRC";
$config['timezone']=$timezone;
$cookie_path    = "/";
$config['cookie_path']=$cookie_path;
$cookie_domain    = "";
$config['cookie_domain']=$cookie_domain;
$session = "1440";
$config['session']=$session;
define('EC_CHARSET','utf-8');

//内存变量前缀, 可更改,避免同服务器中的程序引用错乱
$config['memory']['prefix'] = 'ecshop_';

/* reids设置, 需要PHP扩展组件支持, timeout参数的作用没有查证 */
$config['memory']['redis']['server'] = '127.0.0.1';
$config['memory']['redis']['port'] = 6379;
$config['memory']['redis']['pconnect'] = 1;
$config['memory']['redis']['timeout'] = 0;
$config['memory']['redis']['requirepass'] = '';
$config['memory']['redis']['serializer'] = 1;


/*start  By  QQ:485329944 */
if(!defined('ADMIN_PATH'));
{
}
/*start  By  QQ:485329944 */
if(!defined('ADMIN_PATH_M'));
{
define('ADMIN_PATH_M','admin');
}
define('AUTH_KEY', 'this is a key');

define('OLD_AUTH_KEY', '');

define('API_TIME', '2018-05-04 19:21:43');

define('SUB_DIR','/');
//消息推送端口
define('MSG_PORT','0.0.0.0:3456');
define('HTML_MSG_PORT','ws://39.108.100.17:3456');
//内部消息推送端口
define('INNER_MSG_PORT','127.0.0.1:5678');

/**
 * es服务器的配置
 */
$config['es']['host'] = $es_host = "39.108.103.57";
$config['es']['port'] = $es_port= "9200";
$config['es']['scheme'] = $es_scheme = "http";

define('ES_CON','0');  //elasticsearch开关

?>
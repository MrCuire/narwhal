<?php
header("Access-Control-Allow-Credentials: true");
//header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);

header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');

define("IN_ECS",true);
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', 1);


$script=$_SERVER["SCRIPT_FILENAME"];
$arr = explode("/",$script);
define("FW_NAME",$arr[count($arr)-2]);

define('ROOT_PATH', str_replace(FW_NAME.'/index.php', '', str_replace('\\', '/', __FILE__)));
define('ROOT_FW_PATH', str_replace('index.php', '', str_replace('\\', '/', __FILE__)));
define('OPEN_PATH', ROOT_FW_PATH . '/application/source/open/');

define("LIB_PATH",str_replace('/index.php', '', str_replace('\\', '/', __FILE__))."/application/function/includes/");

define("LANGUAGE_PATH",str_replace('/index.php', '', str_replace('\\', '/', __FILE__))."/application/function/languages/zh_cn/");
define("SYSTEM_PATH",str_replace('/index.php', '', str_replace('\\', '/', __FILE__)));
define('BASEPATH', "system/");

date_default_timezone_set("PRC");

require_once BASEPATH.'/init.php';

switch (ENVIRONMENT)
{
    case 'development':
        error_reporting(-1);
//        ini_set('display_errors', 0);
        set_error_handler(array("cls_init","handle_error"));
        register_shutdown_function(array("cls_init","handle_fatal_error"));
        set_exception_handler("cls_init::handle_exception");
        break;

    case 'testing':
        break;
    case 'production':
        ini_set('display_errors', 0);
        if (version_compare(PHP_VERSION, '5.3', '>='))
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        }
        else
        {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }

        break;

    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1); // EXIT_ERROR
}



#执行业务函数
$return = $api->use_function();

?>
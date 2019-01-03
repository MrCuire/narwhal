<?php
defined('IN_ECS') or define('IN_ECS', true);
defined('ROOT_PATH') or define('ROOT_PATH', realpath(__DIR__ . '/../../../'));

require_once(ROOT_PATH . '/config/tc_database_config.php');
require_once(ROOT_PATH . '/application/function/includes/cls_ecshop.php');



if(isset($config)) {
    $GLOBALS['db_conf'] = $config;
}

$GLOBALS['db'] = DB::instance();
$GLOBALS['ecs'] = DB::ecs();

class DB
{
    protected static $instance;
    protected static $ecs;

    private function __construct()
    {

    }

    /**
     * @return ECS
     */
    public static function ecs()
    {
        if(! static::$ecs) {

            static::$ecs = new ECS($GLOBALS['db_conf']['db_name'], $GLOBALS['db_conf']['prefix']);
        }
        return static::$ecs;
    }

    /**
     * @param $table
     * @return string
     */
    public static function table($table)
    {
        /** @var ECS $ecs */
        $ecs = static::ecs();
        return $ecs->table($table);
    }

    /**
     *
     * @return cls_mysql
     */
    public static function instance()
    {
        if(! static::$instance) {
            require_once(ROOT_PATH . '/config/cls_mysql.php');

            static::$instance = new cls_mysql(
                $GLOBALS['db_conf']['db_host'],
                $GLOBALS['db_conf']['db_user'],
                $GLOBALS['db_conf']['db_pass'],
                $GLOBALS['db_conf']['db_name']
            );
        }
        return static::$instance;
    }

    /**
     * @return PDO
     */
    public static function pdo()
    {
        return static::instance()->pdo;
    }

    private function __clone()
    {

    }

}
<?php
function &DB($params = '', $query_builder_override = NULL)
{
    // Load the DB config file if a DSN string wasn't passed
//    if (is_string($params) && strpos($params, '://') === FALSE)
//    {
//        // Is the config file in the environment folder?
//        if ( ! file_exists($file_path = ROOT_PATH.'config/database.php')
//            && ! file_exists($file_path = ROOT_PATH.'config/database.php'))
//        {
//            die('The configuration file database.php does not exist.');
//        }
//
//        include_once($file_path);
//
//
//        if ( ! isset($db) OR count($db) === 0)
//        {
//            die('No database connection settings were found in the database config file.');
//        }
//
//        if ($params !== '')
//        {
//            $active_group = $params;
//        }
//
//        if ( ! isset($active_group))
//        {
//            die('You have not specified a database connection group via $active_group in your config/database.php file.');
//        }
//
//        elseif ( ! isset($db[$active_group]))
//        {
//            die('You have specified an invalid database connection group ('.$active_group.') in your config/database.php file.');
//        }
//
//        $params = $db[$active_group];
//    }
//    elseif (is_string($params))
//    {
//        /**
//         * Parse the URL from the DSN string
//         * Database settings can be passed as discreet
//         * parameters or as a data source name in the first
//         * parameter. DSNs must have this prototype:
//         * $dsn = 'driver://username:password@hostname/database';
//         */
//        if (($dsn = @parse_url($params)) === FALSE)
//        {
//            die('Invalid DB Connection String');
//        }
//
//        $params = array(
//            'dbdriver'	=> $dsn['scheme'],
//            'hostname'	=> isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
//            'port'		=> isset($dsn['port']) ? rawurldecode($dsn['port']) : '',
//            'username'	=> isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
//            'password'	=> isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
//            'database'	=> isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : ''
//        );
//
//        // Were additional config items set?
//        if (isset($dsn['query']))
//        {
//            parse_str($dsn['query'], $extra);
//
//            foreach ($extra as $key => $val)
//            {
//                if (is_string($val) && in_array(strtoupper($val), array('TRUE', 'FALSE', 'NULL')))
//                {
//                    $val = var_export($val, TRUE);
//                }
//
//                $params[$key] = $val;
//            }
//        }
//    }
    // No DB specified yet? Beat them senseless...
    if (empty($params['dbdriver']))
    {
        die('You have not selected a database type to connect to.');
    }

    // Load the DB classes. Note: Since the query builder class is optional
    // we need to dynamically create a class that extends proper parent class
    // based on whether we're using the query builder class or not.
    if ($query_builder_override !== NULL)
    {
        $query_builder = $query_builder_override;
    }
    // Backwards compatibility work-around for keeping the
    // $active_record config variable working. Should be
    // removed in v3.1
    elseif ( ! isset($query_builder) && isset($active_record))
    {
        $query_builder = $active_record;
    }
    require_once ("DB_driver.php");
    require_once ('DB_query_builder.php');
    class CI_DB extends CI_DB_query_builder { }

    // Load the DB driver
    $driver_file =ROOT_FW_PATH.'config/drivers/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php';

//    file_exists($driver_file) OR die('Invalid DB driver');
//    die($driver_file);
    if(file_exists($driver_file)){
        require_once($driver_file);
    }else{
        die("Invalid DB driver");
    }

    // Instantiate the DB adapter
    $driver = 'CI_DB_'.$params['dbdriver'].'_driver';

    $DB = new $driver($params);

    // Check for a subdriver

    $DB->initialize();
    return $DB;
}



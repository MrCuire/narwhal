<?php

/**
 * ECSHOP MYSQL 公用类库
 * ============================================================================
 * 版权所有 2005-2011 亚洲网视股份有限公司，并保留所有权利。
 * 网站地址: http://www.charse.net；
 * ----------------------------------------------------------------------------
 * 
 *  
 * ============================================================================
 * $Author: liubo $
 * $Id: cls_mysql.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
class cls_mysql
{
    var $link_id    = NULL;
    /**
     * @var $pdo PDO
     */
    var $pdo        =NULL;
    var $settings   = array();

    var $queryCount = 0;
    var $queryTime  = '';
    var $queryLog   = array();

    var $max_cache_time = 300; // 最大的缓存时间，以秒为单位

    var $cache_data_dir = 'temp/query_caches/';
    var $root_path      = '';

    var $error_message  = array();
    var $platform       = '';
    var $version        = '';
    var $dbhash         = '';
    var $starttime      = 0;
    var $timeline       = 0;
    var $timezone       = 0;
    //调试用
    var $save_mode      = true;
    var $queries        = array();

    var $mysql_config_cache_file_time = 0;

    var $mysql_disable_cache_tables = array(); // 不允许被缓存的表，遇到将不会进行缓存

    function __construct($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'gbk', $pconnect = 0, $quiet = 0)
    {
        $this->cls_mysql($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
    }

    function cls_mysql($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'gbk', $pconnect = 0, $quiet = 0)
    {
        if (defined('EC_CHARSET'))
        {
            $charset = strtolower(str_replace('-', '', EC_CHARSET));
        }

        if (defined('ROOT_PATH') && !$this->root_path)
        {
            $this->root_path = ROOT_PATH;
        }

        if ($quiet)
        {
            $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
        }
        else
        {
            $this->settings = array(
                                    'dbhost'   => $dbhost,
                                    'dbuser'   => $dbuser,
                                    'dbpw'     => $dbpw,
                                    'dbname'   => $dbname,
                                    'charset'  => $charset,
                                    'pconnect' => $pconnect
                                    );
        }
    }

    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'gbk', $pconnect = 0, $quiet = 0)
    {

        if ($pconnect)
        {

            if (!($this->pdo=new PDO("mysql:host=$dbhost;dbname=$dbname",$dbuser,$dbpw,array(PDO::ATTR_PERSISTENT => true))))
            {
                if (!$quiet)
                {
                    $this->ErrorMsg("Can't pConnect MySQL Server($dbhost)!");
                }

                return false;
            }
        }

        else
        {
            //PHP_VERSION >= '5'
//            var_dump("mysql:host=$dbhost;dbname=$dbname",$dbuser,$dbpw);die();
                $this->pdo=new PDO("mysql:host=$dbhost;dbname=$dbname",$dbuser,$dbpw);

                if (!$this->pdo)
                {
                    if (!$quiet)
                    {
                        $this->ErrorMsg("Can't Connect MySQL Server($dbhost)!");
                    }

                    return false;
                }


        }

        $this->dbhash  = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version =$this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        /* 如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        if ($this->version > '4.1')
        {
            if ($charset != 'latin1')
            {
                $this->pdo->exec("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary");
               // mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
            }
            if ($this->version > '5.0.1')
            {
                $this->pdo->exec("SET sql_mode=''");
                //mysql_query("SET sql_mode=''", $this->link_id);
            }
        }

        $sqlcache_config_file = $this->root_path . $this->cache_data_dir . 'sqlcache_config_file_' . $this->dbhash . '.php';

        if(is_file($sqlcache_config_file)){
            include_once($sqlcache_config_file);
        }

        $this->starttime = time();

        if ($this->max_cache_time && $this->starttime > $this->mysql_config_cache_file_time + $this->max_cache_time)
        {
            if ($dbhost != '.')
            {
                //$result = mysql_query("SHOW VARIABLES LIKE 'basedir'", $this->link_id);
                //$row    = mysql_fetch_assoc($result);
                $result=$this->pdo->query("SHOW VARIABLES LIKE 'basedir'");
               $row=$result->fetch(PDO::FETCH_ASSOC);
                if (!empty($row['Value']{1}) && $row['Value']{1} == ':' && !empty($row['Value']{2}) && $row['Value']{2} == "\\")
                {
                    $this->platform = 'WINDOWS';
                }
                else
                {
                    $this->platform = 'OTHER';
                }
            }
            else
            {
                $this->platform = 'WINDOWS';
            }

            if ($this->platform == 'OTHER' &&
                ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') ||
                (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC'))
            {
               // $result = mysql_query("SELECT UNIX_TIMESTAMP() AS timeline, UNIX_TIMESTAMP('" . date('Y-m-d H:i:s', $this->starttime) . "') AS timezone", $this->link_id);
               // $row    = mysql_fetch_assoc($result);
                    $result=$this->pdo->query("SELECT UNIX_TIMESTAMP() AS timeline, UNIX_TIMESTAMP('" . date('Y-m-d H:i:s', $this->starttime) . "') AS timezone");
                    $row=$result->fetch(PDO::FETCH_ASSOC);
                if ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306')
                {
                    $this->timeline = $this->starttime - $row['timeline'];
                }

                if (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC')
                {
                    $this->timezone = $this->starttime - $row['timezone'];
                }
            }

            $content = '<' . "?php\r\n" .
                       '$this->mysql_config_cache_file_time = ' . $this->starttime . ";\r\n" .
                       '$this->timeline = ' . $this->timeline . ";\r\n" .
                       '$this->timezone = ' . $this->timezone . ";\r\n" .
                       '$this->platform = ' . "'" . $this->platform . "';\r\n?" . '>';

            if(is_file($sqlcache_config_file)){
                file_put_contents($sqlcache_config_file, $content);
            }

        }
            return true;
    }

    function select_database($dbname)
    {
        //return mysql_select_db($dbname, $this->link_id);
        $this->pdo->query("use $dbname");
    }

    function set_mysql_charset($charset)
    {
        /* 如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        if ($this->version > '4.1')
        {
            if (in_array(strtolower($charset), array('gbk', 'big5', 'utf-8', 'utf8')))
            {
                $charset = str_replace('-', '', $charset);
            }
            if ($charset != 'latin1')
            {
               // mysql_query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary", $this->link_id);
                $this->pdb->query("SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary");
                $this->queries[]="SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary";
            }
        }
    }

    function fetch_array($query, $result_type = PDO::FETCH_ASSOC)
    {
        $res=$query->fetch($result_type);
        return $res;
    }

    function query($sql, $type = '')
    {

        if ($this->pdo === NULL)
        {

            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
           // $this->settings = array();

        }

        if ($this->queryCount++ <= 99)
        {
            $this->queryLog[] = $sql;
        }
        if ($this->queryTime == '')
        {
            if (PHP_VERSION >= '5.0.0')
            {
                $this->queryTime = microtime(true);
            }
            else
            {
                $this->queryTime = microtime();
            }
        }

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (PHP_VERSION >= '4.3' && time() > $this->starttime + 1)
        {
            //mysql_ping($this->link_id);//重连
            if(!$this->pdo_ping($this->pdo)){
                $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            }
        }

        if (!($query =$this->pdo->query($sql)) && $type != 'SILENT')
        {
            //$this->error_message[]['message'] = 'MySQL Query Error';
            //$this->error_message[]['sql'] = $sql;
            //$this->error_message[]['error'] = mysql_error($this->link_id);
            //$this->error_message[]['errno'] = mysql_errno($this->link_id);
            
            $this->error_message[]['消息'] = 'MySQL Query Error';
            $this->error_message[]['SQL'] = $sql;
            $e=$this->pdo->errorInfo();
            $this->error_message[]['错误'] = $e[2];
            $this->error_message[]['代码'] =  $this->pdo->errorCode();

            $this->ErrorMsg();

            return false;
        }

        if (defined('DEBUG_MODE') && (DEBUG_MODE & 8) == 8)
        {
            $logfilename = $this->root_path . DATA_DIR . '/mysql_query_' . $this->dbhash . '_' . date('Y_m_d') . '.log';
            $str = $sql . "\n\n";

            if (PHP_VERSION >= '5.0')
            {
                file_put_contents($logfilename, $str, FILE_APPEND);
            }
            else
            {
                $fp = @fopen($logfilename, 'ab+');
                if ($fp)
                {
                    fwrite($fp, $str);
                    fclose($fp);
                }
            }
        }

        if($this->save_mode){
            $this->queries[]=$sql;
        }

        return $query;
    }

    function affected_rows()
    {
        return mysql_affected_rows($this->link_id);
    }

    function error()
    {
        return $this->pdo->errorInfo();
    }

    function errno()
    {
        return $this->pdo->errorCode();
    }

    function result($query, $row)
    {
        $result=$this->pdo->query($query);
        $res=$result->fetch_row($row);
        return $res;
       // return @mysql_result($query, $row);
    }

    function num_rows($query)
    {
        $res=$this->pdo->prepare($query);
        $res->execute();
        return $res->rowCount();
        //return mysql_num_rows($query);
    }

    function num_fields($query)
    {

        $res=$this->pdo->prepare($query);
        $res->execute();
        return $res->columnCount();
       // return mysql_num_fields($query);
    }

    function free_result($query)
    {
        return mysql_free_result($query);
    }

    function insert_id()
    {
        return $this->pdo->lastInsertId();
        //return mysql_insert_id($this->link_id);
    }

    function fetchRow($query)
    {
        return $query->fetch(PDO::FETCH_ASSOC);
        //return mysql_fetch_assoc($query);
    }

    function fetch_fields($query)
    {
        $result=$this->pdo->query($query);
        return $result->getColumnMeta();
        //return mysql_fetch_field($query);
    }

    function version()
    {
        return $this->version;
    }

    function ping()
    {
        if (PHP_VERSION >= '4.3')
        {
            return $this->pdo_ping($this->pdo);
            //return mysql_ping($this->link_id);
        }
        else
        {
            return false;
        }
    }

    function escape_string($unescaped_string)
    {
        if (PHP_VERSION >= '4.3')
        {
            return $this->pdo->quote($unescaped_string);
            //return mysql_real_escape_string($unescaped_string);
        }
        else
        {
            return mysql_escape_string($unescaped_string);
        }
    }

    function close()
    {
        $this->pdo=NULL;
        return true;
        //return mysql_close($this->link_id);
    }

    function ErrorMsg($message = '', $sql = '')
    {
        if ($message)
        {
        	
        	throw new Exception ($message);
        	
            echo "<b>ECSHOP info</b>: $message\n\n<br /><br />";
            //print('<a href="http://faq.comsenz.com/?type=mysql&dberrno=2003&dberror=Can%27t%20connect%20to%20MySQL%20server%20on" target="_blank">http://faq.comsenz.com/</a>');
        
        }
        else
        {
        	$message = '<table border="1" style="" align="center">';

        	foreach($this->error_message as $error)
        	{
        		foreach($error as $key => $value)
        		{
        			$message = $message . '<tr><td style="padding: 10px;">' . $key . '</td><td style="padding: 10px;">' . $value . '</td></tr>';
        		}
        	}
        	$message = $message . '</table>';

        	throw new Exception (addslashes($message));

        	echo $message;
        	
//             echo "<b>MySQL server error report:";
            
//             echo "<br /><br /><a href='http://faq.comsenz.com/?type=mysql&dberrno=" . $this->error_message[3]['errno'] . "&dberror=" . urlencode($this->error_message[2]['error']) . "' target='_blank'>http://faq.comsenz.com/</a>";
        }

        exit;
    }

/* 仿真 Adodb 函数 */
    function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0)
        {
            $sql .= ' LIMIT ' . $num;
        }
        else
        {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    function getOne($sql, $limited = false)
    {
        if ($limited == true)
        {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false)
        {
            $row = $res->fetch(PDO::FETCH_NUM);

            if ($row !== false)
            {
                return $row[0];
            }
            else
            {
                return '';
            }
        }
        else
        {
            return false;
        }
    }

    function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getOne($sql, true);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getAll($sql)
    {

        $res = $this->query($sql);

        if($this->save_mode){
            $this->queries[]=$sql;
        }

        if ($res !== false)
        {
            $arr = array();
            while ($row = $res->fetch(PDO::FETCH_ASSOC))
            {
                $arr[] = $row;
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getAll($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getRow($sql, $limited = false)
    {
        if ($limited == true)
        {
            $sql = trim($sql . ' LIMIT 1');
        }
//        die($sql);
        $res = $this->query($sql);

        if($this->save_mode){
            $this->queries[]=$sql;
        }

        if ($res !== false)
        {
            return $res->fetch(PDO::FETCH_ASSOC);
        }
        else
        {
            return false;
        }
    }

    function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getRow($sql, true);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function getCol($sql)
    {
        $res = $this->query($sql);

        if($this->save_mode){
            $this->queries[]=$sql;
        }

        if ($res !== false)
        {
            $arr = array();
            while ($row = $res->fetch(PDO::FETCH_NUM))
            {
                $arr[] = $row[0];
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }

    function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst)
        {
            return $this->getCol($sql);
        }
        else
        {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true)
            {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst)
        {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        $mode=strtoupper($mode);
        if ($mode == 'INSERT')
        {
            $fields = $values = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }

            if($this->save_mode){

                $this->queries[]=$sql;

            }

            return $this->setExec($sql);

//            return $this->query($sql, $querymode);
//            return $sql;
        }
        else
        {
            $sets = array();
            foreach ($field_names AS $value)
            {
                if (array_key_exists($value, $field_values) == true)
                {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets))
            {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
            if($this->save_mode){

                $this->queries[]=$sql;

            }
            return $this->pdo->exec($sql);
        }

        if (!$sql)
        {
            return false;
        }
    }

    function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs AS $value)
        {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI')
            {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names AS $value)
        {
            if (array_key_exists($value, $field_values) == true)
            {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values AS $key => $value)
        {
            if (array_key_exists($key, $field_values) == true)
            {
                if (is_int($value) || is_float($value))
                {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                }
                else
                {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys))
        {
            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        }
        else
        {
            if ($this->version() >= '4.1')
            {
                if (!empty($fields))
                {
                    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                    if (!empty($sets))
                    {
                        $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                    }
                }
            }
            else
            {
                if (empty($where))
                {
                    $where = array();
                    foreach ($primary_keys AS $value)
                    {
                        if (is_numeric($value))
                        {
                            $where[] = $value . ' = ' . $field_values[$value];
                        }
                        else
                        {
                            $where[] = $value . " = '" . $field_values[$value] . "'";
                        }
                    }
                    $where = implode(' AND ', $where);
                }

                if ($where && (!empty($sets) || !empty($fields)))
                {
                    if (intval($this->getOne("SELECT COUNT(*) FROM $table WHERE $where")) > 0)
                    {
                        if (!empty($sets))
                        {
                            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
                        }
                    }
                    else
                    {
                        if (!empty($fields))
                        {
                            $sql = 'REPLACE INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                        }
                    }
                }
            }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    function getSqlCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->root_path . $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $data = @file_get_contents($result['filename']);
        if (isset($data{23}))
        {
            $filetime = substr($data, 13, 10);
            $data     = substr($data, 23);

            if (($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) || ($cached == 'MYSQLFIRST' && $this->table_lastupdate($this->get_table_name($sql)) > $filetime))
            {
                $result['storecache'] = true;
            }
            else
            {
                $result['data'] = @unserialize($data);
                if ($result['data'] === false)
                {
                    $result['storecache'] = true;
                }
                else
                {
                    $result['storecache'] = false;
                }
            }
        }
        else
        {
            $result['storecache'] = true;
        }

        return $result;
    }

    function setSqlCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename'])
        {
            @file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));
            clearstatcache();
        }
    }

    /* 获取 SQL 语句中最后更新的表的时间，有多个表的情况下，返回最新的表的时间 */
    function table_lastupdate($tables)
    {
        if ($this->link_id === NULL)
        {
            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            $this->settings = array();
        }

        $lastupdatetime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->mysql_disable_cache_tables = str_replace('`', '', $this->mysql_disable_cache_tables);

        foreach ($tables AS $table)
        {
            if (in_array($table, $this->mysql_disable_cache_tables) == true)
            {
                $lastupdatetime = '2037-12-31 23:59:59';

                break;
            }

            if (strstr($table, '.') != NULL)
            {
                $tmp = explode('.', $table);
                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . "` LIKE '" . trim($tmp[1]) . "'";
            }
            else
            {
                $sql = "SHOW TABLE STATUS LIKE '" . trim($table) . "'";
            }
            //$result = mysql_query($sql, $this->link_id);
                $result=$this->pdo->query($sql);
            //$row = mysql_fetch_assoc($result);
                $row=$result->fetchAll(PDO::FETCH_ASSOC);
            if ($row['Update_time'] > $lastupdatetime)
            {
                $lastupdatetime = $row['Update_time'];
            }
        }
        $lastupdatetime = strtotime($lastupdatetime) - $this->timezone + $this->timeline;

        return $lastupdatetime;
    }

    function get_table_name($query_item)
    {
        $query_item = trim($query_item);
        $table_names = array();

        /* 判断语句中是不是含有 JOIN */
        if (stristr($query_item, ' JOIN ') == '')
        {
            /* 解析一般的 SELECT FROM 语句 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is', $query_item, $table_names))
            {
                $table_names = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $table_names[1]);

                return preg_split('/\s*,\s*/', $table_names);
            }
        }
        else
        {
            /* 对含有 JOIN 的语句进行解析 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is', $query_item, $table_names))
            {
                $other_table_names = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $query_item, $other_table_names);

                return array_merge(array($table_names[1]), $other_table_names[1]);
            }
        }

        return $table_names;
    }

    /* 设置不允许进行缓存的表 */
    function set_disable_cache_tables($tables)
    {
        if (!is_array($tables))
        {
            $tables = explode(',', $tables);
        }

        foreach ($tables AS $table)
        {
            $this->mysql_disable_cache_tables[] = $table;
        }

        array_unique($this->mysql_disable_cache_tables);
    }
    /**
     * 检查连接是否可用
     * @param  Link $dbconn 数据库连接
     * @return Boolean
     */
    function pdo_ping($dbconn){
        try{
            $dbconn->getAttribute(PDO::ATTR_SERVER_INFO);
        } catch (PDOException $e) {
            if(strpos($e->getMessage(), 'MySQL server has gone away')!==false){
                return false;
            }
        }
        return true;
    }
    //返回最后一条执行的sql语句
    function last_sql(){
        return end($this->queries);
    }

    function search($_select="",$_where="",$table,$_order="",$_limit=""){
        $_select=$_select!=""?$_select:"*";
        $select="select ".$_select;
        $where=$this->explode_condition($_where);
        $sql=$select." from {$GLOBALS["ecs"]->table($table)} where".$where;
        if($_order){
            $sql.=" order by ".$_order;
        }
        if($_limit){
            $sql.=" limit ".$_limit;
        }
        $res = $this->getAll($sql);
        return $res;
    }

    function edit($updateData,$_where,$table){
        if(empty($updateData)){
            trigger_error("不允许插入空数据");
        }
        if(is_array($_where)){
            $where=$this->explode_condition($_where);
        }elseif(is_string($_where)){
            $where=$_where;
        }

        return $this->autoExecute($GLOBALS["ecs"]->table($table),$updateData,"UPDATE",$where);
    }

    function add($insertData,$table){
        if(empty($insertData)){
            trigger_error("不允许插入空数据");
        }
        $res = $this->autoExecute($GLOBALS["ecs"]->table($table),$insertData,"INSERT");
        if($res){
            $id = $this->insert_id();
            if($id){
                return $id;
            }else{
                return $res;
            }
        }

    }

    function delete($where="",$table){
        $sql="delete from ".$GLOBALS["ecs"]->table($table)." where ";
        if(is_array($where)){
            $sql.=$this->explode_condition($where);
        }elseif (is_string($where)){
            $sql.=$where;
        }

        $this->query($sql);
        return true;
    }

    public function explode_condition($_where){
        $where=" 1";
        if($_where){
            foreach ($_where as $key=> $value){
                $key=trim($key);
                $l_s_l=substr($key,-2);
                $l_o_l=substr($key,-1);
                $compare=array(">","<",">=","<=","!=");
                if(((!is_array($value) && strtolower($value)==='null') || is_null($value))&&$l_s_l!='in' ){
                    if(in_array($l_s_l,$compare)){
                        $key=str_replace([$l_s_l,$l_o_l],['',''],$key);
                        $where.=" and {$key} is not null";
                    }else{
                        $where.=" and {$key} is null";
                    }
                    continue;
                }
                //非等号的比较符
                if(in_array($l_s_l,$compare) || in_array($l_o_l,$compare)){
                    $where.=" and {$key}'{$value}'";
                    continue;
                }

                #in查询
                if(strtolower($l_s_l)=="in"){
                    if(is_array($value)){
                        $range = db_create_in($value);
                        $range= trim(str_replace("IN" ,"",$range));
                        $where.=" and {$key} {$range}";
                    }elseif(is_string($value)){
                        $where.=" and {$key} ($value)";
                    }elseif (is_null($value) || $value=='null'){
                       $where.=" and {$key} (null)";
                    }
                }
                //模糊查询
                else if(strtolower($l_o_l)=="e" && strtolower(substr($key,-4))== "like"){
                    $where.=" and {$key}'{$value}'";
                    continue;
                }else{
                    $where.=" and {$key}='{$value}'";
                    continue;
                }
            }
        }
        return $where;
    }

    public function explode_condition_e($_where)
    {
        $where = " 1";
        if ($_where) {
            $index = 1;
            foreach ($_where as $key => $value) {
                $key = trim($key);
                $l_s_l = substr($key, - 2);
                $l_o_l = substr($key, - 1);
                $compare = array(
                    ">",
                    "<",
                    ">=",
                    "<=",
                    "!="
                );
                if(((!is_array($value) && strtolower($value)==='null') || is_null($value))&&$l_s_l!='in' ){
                    if(in_array($l_s_l,$compare)){
                        $key=str_replace([$l_s_l,$l_o_l],['',''],$key);
                        $where.=" and {$key} is not null";
                    }else{
                        $where.=" and {$key} is null";
                    }
                    continue;
                }
                // 非等号的比较符
                if (in_array($l_s_l, $compare) || in_array($l_o_l, $compare)) {
                    $where .= " and {$key}'{$value}'";
                    continue;
                }
                // in查询
                if (strtolower($l_s_l) == "in") {
                    if (is_array($value)) {
                        $range = db_create_in($value);
                        $range = trim(str_replace("IN", "", $range));
                        $where .= " and {$key} {$range}";
                    } elseif (is_string($value)) {
                        $where .= " and {$key} ($value)";
                    } elseif (is_null($value) || (!is_array($value) && strtolower($value)==='null')){
                        $where.=" and {$key} (null)";
                    }
                }                 // 模糊查询
                else 
                    if (strtolower($l_o_l) == "e" && strtolower(substr($key, - 4)) == "like") {
                        if($index ==1){
                            $where .= " and {$key}'{$value}'";
                        }else{
                            $where .= " or {$key}'{$value}'";
                        }
                        $index++;
                        continue;
                    } else {
                        $where .= " and {$key}='{$value}'";
                        continue;
                    }
            }
        }
        return $where;
    }

    /**获取详细的数据 (可带分页数据)
     * create by yh
     * $sql     string  sql语句
     * $countSql string  查询总数的sql语句
     * $where   array   sql语句where条件
     * 如 : $where['a.status']=array('condition'=>'=','content'=>$status);
     * key 为 判断条件  condition 为判断操作  content为判断内容
     * $join    array   sql语句join条件
     * $orderby array   sql语句order by 条件
     * $group   array   sql语句group by 条件
     * $ispage  array   sql语句limit 条件  需要传 当前页数 : $ispage['page'], 每页返回条数: $ispage['page_size']
     **/
    function getDetailData($sql,$countSql=null,$where=null,$join=null,$orderby=null,$groupby=null,$ispage=null){
        $result=array();
        if(!empty($countSql)){
            $csql=$countSql;
        }else{
            $csql='';
        }

        if(!empty($join)){
            $sql.=" join ";
            $csql.=" join ";
            foreach ($join as $k=> $v){
                $sql.=" $k $v  INNER JOIN" ;
                $csql.=" $k $v  INNER JOIN" ;
            }
            $sql=trim($sql,"INNER JOIN");
            $csql=trim($csql,"INNER JOIN");
        }

        if(!empty($where)){
            $sql.=" where ";
            $csql.=" where ";
            foreach ($where as $k=> $v){
                $condition=$v['condition'];
                $content=$v['content'];
                $sql.=" $k $condition '$content' and" ;
                $csql.=" $k $condition '$content' and" ;
            }
            $sql=trim($sql,"and");
            $csql=trim($csql,"and");
        }

        if(!empty($groupby)){
            $sql.=" group by ";
            $csql.=" group by ";
            foreach ($groupby as $k=> $v){
                $sql.="  $v  ," ;
                $csql.="  $v  ," ;
            }
            $sql=trim($sql,",");
            $csql=trim($csql,",");
        }

        if(!empty($orderby)){
            $sql.=" order by ";
            $csql.=" order by ";
            foreach ($orderby as $k=> $v){
                $sql.=" $k  $v  ," ;
                $csql.=" $k  $v  ," ;
            }
            $sql=trim($sql,",");
            $csql=trim($csql,",");
        }

        if(!empty($countSql)){

            $record_count=$this->getOne($csql);
            //返回总数
            $result['totalLog']=$record_count;
        }

        if(!empty($ispage)){
            $page=$ispage['curPage']?$ispage['curPage']:1;
            $page_size=$ispage['page_size']?$ispage['page_size']:1;
            $start=($page-1)*$page_size;
            $sql.=" limit $start , $page_size ";
        }

        $resData=$this->getAll($sql);
        $result['resData']=$resData;
        
//        if($resData){
//            $result['msg']="查询成功";
//        }else{
//            $result['msg']="没有数据";
//        }

        ////查看sql语句
//      $result['resDatasql']=$sql;
//      $result['cresDatasql']=$csql;

        return $result;

    }

    //设置是否自动提交
    function setAutocommit ($status=0){
        $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, $status);
    }
    //设置错误提示
    function setAttrErrmode (){
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    //开启事务
    function beginTrans(){
        if(!$this->pdo->inTransaction()){
            $this->pdo->beginTransaction();
        }
    }
    //执行sql语句 用于insert update
    function setExec($sql){
       return $this->pdo->exec($sql);
    }

    //开启事务的回滚
    function setRollback()
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollback();
        }
    }

    //开启事务的提交
    function setCommit()
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

}

?>
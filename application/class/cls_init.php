<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/19
 * Time: 17:15
 */

class cls_init
{
    private static $_application = "";
    var $db = null;
    var $sess = null;
    var $user = null;
    var $api = null;
    var $input = null;
    static $obj = array();
    #公共初始化项
    var $config_list = array("api", "db", "config", "sess");
    #公共类文件
    var $common_files = array("cls_api", "cls_output", "cls_error");

    function __construct($application = "")
    {
        /* 初始化设置 */
        @ini_set('memory_limit', '64M');
        @ini_set('session.cache_expire', 1800);
        @ini_set('session.use_trans_sid', 0);
        @ini_set('session.use_cookies', 1);
        @ini_set('session.auto_start', 0);
        @ini_set('display_errors', 1);

//        require_once(LIB_PATH . 'inc_constant.php');
        require_once(LIB_PATH . 'lib_time.php');
        require_once(LIB_PATH . 'lib_base.php');
        require_once(LIB_PATH . 'lib_common.php');
        require_once(ROOT_FW_PATH . 'application/function/functions.php');

        #载入公共文件
        $this->autoload_common_file();

        #初始化输入内容
        $this->_input_init();
        #检查实例化内容
        //$application_init_name = "cls_" . self::$_application . "_init";

        //$obj = self::make_obj($application_init_name);
//        var_dump($obj);die();
//        $this->init($this->input);

        foreach ($this->config_list as $item) {
//            $i = $item . "_init";
//            if (isset($obj->$i) && $obj->$i == true) {
//                $this->$item = $obj->$item;
//            } else {
                $f = "_{$item}_init";
                call_user_func(array($this, $f));
            }
//        }

        require_once(LANGUAGE_PATH . '/admin/common.php');
        require_once(LANGUAGE_PATH . '/admin/log_action.php');
        $GLOBALS["_LANG"] = $_LANG;

        #保存请求信息
        $this->_save_req();
    }


    private function _sess_init()
    {
//        $session_id = isset($GLOBALS["header"]->session) ? $GLOBALS["header"]->session : "";
//
//        $this->sess = new cls_session($this->db, $GLOBALS["ecs"]->table("sessions"), $GLOBALS["ecs"]->table('sessions_data'), 'user_sess_id', self::$_application, $session_id);
//
//        define("SESS_ID", $this->sess->get_session_id());


    }

    private function _input_init()
    {
        //设置 path_info 模式
        $this->setPathInfo();

        if(isset($_SERVER['CONTENT_TYPE'])){
            $p='/^multipart\/form-data;/';
            preg_match($p,$_SERVER['CONTENT_TYPE'],$p2);
            if($p2){
                if(isset($_POST['input'])){
                    $post_input=json_decode($_POST['input'],true);
                    if(empty($post_input['business'])){
                        $post_input['business']=$_POST;
                        unset($post_input['business']['input']);
                    }
                    $_REQUEST=array('input'=>json_encode($post_input));
                }
            }
        }
        if(empty($_REQUEST)){
            $_REQUEST=array("input"=>file_get_contents("php://input"));
        }
        if(!empty($_GET)){
            $header_field = array("a" => "application", "m" => "module", "c" => "controller", "f" => "function", 's' => 'session');
            $other_field = array('e' => 'encode', 'k' => 'key');
            foreach ($_GET as $key => $item) {
                #过滤
                $item=trim($item)?trim($item):"";

                if (isset($header_field[$key])) {
                    if ($key != "s") {
                        $input['header']['route'][$header_field[$key]] = $item;
                        unset($header_field[$key]);
                        continue;
                    } else {
                        $input['header'][$header_field[$key]] = $item;
                        unset($header_field[$key]);
                        continue;
                    }
                }
                if (isset($other_field[$key])) {
                    $input["other"][$other_field[$key]] = $item;
                    unset($other_field[$key]);
                    continue;
                }
                $input['business'][$key] = $item;
            }
            if(isset($_POST) && !empty($_POST)){
                if(isset($input['business'])){
                        $temp=$input['business'];
                        $business=array_merge($temp,$_POST);
                        $input['business']=$business;
                    }else{
                        $input['business']=$_POST;
                    }
            }else{
                if(isset($_REQUEST['input']) && !empty($_REQUEST['input'])){
                    $request = json_decode($_REQUEST['input'],true);
                }else{
                    $request = json_decode(file_get_contents('php://input'),true);
                }
                if(isset($input['business'])){
                    $temp=$input['business'];
                    $business=array_merge($temp,$request);
                    $input['business']=$business;
                }else{
                    $input['business']=$request;
                }
            }

            $in=json_encode($input);
        }else{
            $in = array_shift($_REQUEST);
        }

//        if (!empty($_GET)) {
//            #$_GET
//
//        } else {
//            #$_POST
//            $in = array_shift($_REQUEST);
//        }
        $input = json_decode($in);

        if (is_null($input)) {
            cls_output::out("E010017", "请求的参数格式必须是json", "", false);
        } else {
            $this->input = $input;
        }
//        }

        if (isset($this->input->header->route->application) && !empty($this->input->header->route->application)) {
            self::$_application = $this->input->header->route->application;
        } else {
            cls_output::out("E010017", "产品名称不存在", "", false);
        }
        $file_path = ROOT_FW_PATH . "application/source/" . $this->input->header->route->application . "/" . $this->input->header->route->module . "/" . $this->input->header->route->controller . ".php";

        if (!is_file($file_path)) {
            cls_output::out("E010017", "访问路径有误", array("file_path" => $file_path), false);
        }

        #判断用不用加密

        if (isset($this->input->other->encode) && $this->input->other->encode) {
            #载入加密类
            require_once ROOT_FW_PATH . "application/function/aes.php";
            $key = $this->input->other->key;
            $business = $this->input->business;
//            $business=json_encode($business);

            $GLOBALS["business"] = trim(aes::AesDecode($business, $key));

            $GLOBALS["other"] = $this->input->other;
        } else {
            $GLOBALS["business"] = isset($this->input->business) && !empty($this->input->business) ? $this->input->business : "";
            $GLOBALS["other"] = "";
        }
        $GLOBALS["header"] = $this->input->header;
        #过滤
        if (isset($_GET['GLOBALS']) || isset($_POST['GLOBALS']) || isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            cls_output::out("E010017", "请求的参数非法", "", false);
        }
        if (!get_magic_quotes_gpc()) {
            if (!empty($_GET)) {
                $_GET = addslashes_deep($_GET);
            }
            if (!empty($_POST)) {
                $_POST = addslashes_deep($_POST);
            }
            $_COOKIE = addslashes_deep($_COOKIE);
            $_REQUEST = addslashes_deep($_REQUEST);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
            $_GET = array_merge($_GET, $_POST);
        }

    }

    /**
     * 设置 path_info 模式
     */
    protected function setPathInfo()
    {
        if(isset($_SERVER['PATH_INFO'])) {
            $pathInfo = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            $pathInfo = array_chunk($pathInfo, 2);
            foreach ($pathInfo as $item) {
                $item = array_values($item);
                $pathInfoCount = count($item);

                if($pathInfoCount == 2) {
                    $_GET[$item[0]] = $item[1];
                }elseif($pathInfoCount == 1){
                    $_GET[$item[0]] = null;
                }
            }
        }
    }



    private function _api_init()
    {
        $this->api = cls_api::get_instance();
    }

    private function _db_init()
    {
        require_once(ROOT_FW_PATH . 'config/database_config.php');
        require_once(ROOT_FW_PATH . 'config/database.php');
//        $this->db = $db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
//        $this->prefix = $prefix;
        $this->db = $db=DB($db_config[ENVIRONMENT]);
    }

    private function _config_init()
    {

    }


    static function autoload($class)
    {
        //$class = strtolower($class);
        try {
            self::import($class);
            return true;
        } catch (Exception $exc) {

            $trace = $exc->getTrace();
            foreach ($trace as $log) {
                if (empty($log['class']) && $log['function'] == 'class_exists') {
                    return false;
                }
            }
            trigger_error("无法定位该文件");
//            cls_error::output_error($exc);
        }
    }

    public static function import($name)
    {
        if (is_string($name)) {
            $file_path = ROOT_FW_PATH . "application/module/" . self::$_application . "/" . $name . ".php";
        } else if (is_array($name)) {
            $file_path = ROOT_FW_PATH . "application/source/" . $name["application"] . "/" . $name["module"] . "/" . $name["controller"];
        } else {
//            cls_output::out("E010017", "文件路径信息错误", "", false);
        }

        if (is_file($file_path)) {

            require_once($file_path);

        } else {
//            cls_output::out("E010017", "文件路径信息错误：" . $file_path, false);
        }
        return true;
    }

    static function make_obj($name)
    {
        if (!in_array($name, self::$obj)) {
            $obj = new $name();
            self::$obj[] = $name;
            return $obj;
        }
    }

    private function autoload_common_file()
    {
        foreach ($this->common_files as $item) {
            $path = ROOT_FW_PATH . "application/class/" . $item . ".php";
            require_once $path;
        }
    }

    public static function handle_error($errno, $errstr, $errfile, $errline)
    {
        $error = array(
            "errno" => $errno,
            "errstr" => $errstr,
            "errfile" => $errfile,
            "errline" => $errline
        );
        cls_error::output_error($error);
    }

    public static function handle_fatal_error()
    {
        if (($e = error_get_last()) && in_array($e['type'], array(1,4))) {
            $error = array(
                "errno" => $e["type"],
                "errstr" => $e["message"],
                "errfile" => $e["file"],
                "errline" => $e["line"]
            );
            cls_error::output_error($error);
        }
    }

    public static function handle_exception(Exception $exception)
    {
        $code=$exception->getCode();
        $message=$exception->getMessage();
        $error = array(
            "errno" =>  empty($code) ? "E000002" : $code,
            "errstr" =>  empty($message) ? "运行失败" : $message,
            "errfile" => $exception->getFile(),
            "errline" => $exception->getLine()
        );
        $pattern = "/<td (?:.*)>(.*)<\/td>/Us";
        $result = preg_match_all($pattern, $error["errstr"], $array);
        if ($result) {
            #mysql抛出的异常
            $message = $array[1][1] . ':' . $array[1][5] . ';sql:' . $array[1][3] . ";mysql 错误提示编码:" . $array[1][7];
            $error['errstr'] = $message;
        }
        cls_error::output_error($error);
    }
    private function _save_req()
    {
        $insertData = array(
            'module' => $this->input->header->route->module,
            'req_code' => $this->input->header->route->function,
            'role_type' => "1",
            'role_id' => "10",
            'time' => time(),
        );
//        $res = $this->db->add($insertData,"req");
        $this->db->insert('req',$insertData);
    }
}
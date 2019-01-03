<?php
require_once ROOT_FW_PATH.'/application/source/tc/common/Common.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class table{

    use Common;
    protected $db;
    protected $_table="";
    public $transcation=false;#事务开关
    public $is_log=false;
    function __construct()
    {
        global $init;
        $this->db=$init->db;
       $this->log = new Logger('log_info');
    }
    /**
     * 获取表字段
     * @param string $table 表名称
     * @return array|bool
     */
    public function get_table_cols($table=''){
        if(!$table){
            if(empty($this->_table)){
                return false;
            }else{
                $table=$this->_table;
            }
        }
        $cols= $this->db->getAll('desc '.$GLOBALS['ecs']->table($table));
        return $cols;
    }

    /**
     * 反选字段 输入字段和表名称 返回该表除了输入字段之外剩下所有的字段
     * @param array $fields 字段
     * @param string $table 表名 空则使用chose_table设定的表
     * @param string $nick_name 表名别称
     * @return array
     */
    function invert_fields(array $fields,$table='',$nick_name=''){
        if(empty($table)){
            $table=$this->getTable();
        }
        $nick_name=empty($nick_name)?'':$nick_name.'.';
        $cols = $this->db->getAll('desc '.$GLOBALS['ecs']->table($table));
        $field = array_column($cols,'Field');

        $new_field = array_filter($field, function ($value) use ($fields) {
            if (!in_array($value, $fields)) {
                return $value;
            }
        });
        array_walk($new_field, function (&$value) use ($nick_name) {
            $value = $nick_name . $value;
        });
        return $new_field;
    }

    /**
     * 快速查询
     * @param string $query 查询字段
     * @param string $where 查询条件
     * @param string $order 排序
     * @param string $limit 限制
     * @return bool
     */
    public function simple_query($query="*",$where="",$order="",$limit=""){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        $result = $this->db->search($query,$where,$this->_table,$order,$limit);
        if(count($result)==1){
            return $result[0];
        }else{
            return $result;
        }
    }


    /**
     * 快速查询 返回二维数组
     * @param string $query 查询字段
     * @param string $where 查询条件
     * @param string $order 排序
     * @param string $limit 限制
     * @return bool
     */
    public function simple_query1($query="*",$where="",$order="",$limit=""){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        $result = $this->db->search($query,$where,$this->_table,$order,$limit);
        return $result;
        /*if($limit==1){
            return $result[0];
        }else{
            return $result;
        }*/
    }

    /**
     * 快速插入
     * @param array $field 插入字段
     * @return bool
     */
    public function simple_add($field){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        $result = $this->db->add($field,$this->_table);
        if($result){
            return $result;
        }else{
            return false;
        }
    }

    /**
     * @param string $where 删除条件
     * @return bool
     */
    public function simple_delete($where=""){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        return $this->db->delete($where,$this->_table);
    }

    /**
     * @param $field  array  修改字段
     * @param string $where  修改条件
     * @return bool
     */
    public function simple_edit($field,$where=""){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        if($this->is_log==true){
            $this->logs($field,$this->db->explode_condition($where),$this->_table);
        }
        return $this->db->edit($field,$where,$this->_table);
    }

    #选择数据表 用于简易查询方法
    public function chose_table($table){
        $this->_table=$table;
    }

    public function get_last_sql(){
        return $this->db->last_sql();
    }

    public function getTable()
    {
        return $GLOBALS['ecs']->table($this->_table);
    }

    public function begin($table=''){
        if($this->transcation){
            return true;
        }
        if(empty($table) && (!isset($this->_table) || empty($this->_table))){
            return false;
        }
        if(empty($table)){
            $table=$this->_table;
        }
        $sql='select engine from information_schema.TABLES where TABLE_SCHEMA=\'tc\'and TABLE_NAME = \''.'tc_'.$table.'\'';
        $engine = $this->db->getOne($sql);
        if(strtoupper($engine)!='INNODB'){
            return false;
        }else{
            $this->db->beginTrans();
            $this->transcation=true;
            return true;
        }
    }

    public function commit(){
        if($this->transcation){
            $this->db->setCommit();
            $this->transcation=false;
            return true;
        }else{
            return false;
        }
    }

    public function roll_back(){
        //rollback
        if($this->transcation){
            $this->db->setRollback();
            $this->transcation=false;
            return true;
        }else{
            return false;
        }
    }

    public function view($view_name,$_sql){
        $sql='DROP VIEW IF EXISTS '.$view_name;
        $this->db->query($sql);
        $this->db->query('CREATE VIEW '.$view_name.' as '.$_sql);
        return true;
    }

    function allow_empty(){
        if(!isset($this->_table) || empty($this->_table)){
            return false;
        }
        $cols = $this->get_table_cols();//执行 desc $this->_table

        $allow_empty=array();
        array_walk_recursive($cols,function($value,$key)use(&$allow_empty){
            static $field;
            if($key=='Field'){
                $field=$value;
            }
            if($key=='Null' && $value=='YES'){
//               array_push($allow_empty,$field);
                $allow_empty[]=$field;
            }
        });
        return $allow_empty;
    }

    private function logs($data,$where,$table){
        $this->log->pushHandler(new StreamHandler(ROOT_FW_PATH.'logs/log_info/'.date('Y-m-d').'.log', Logger::INFO));
        $content=array(
            'time'=>time(),
            'content'=>array(),
            'user_id'=>$this->userId()
        );
        //$content='time:'.time().',date:'.date('Y-m-d').PHP_EOL;
        $sql='select * from tc_'.$table.' where '.$where;
        $result = $this->db->getRow($sql);
        foreach ($data as $key => $value){
            if(isset($result[$key]) && $result[$key]!=$value){
                $content['content'][]=array(
                    'key'=>$key,
                    'before'=>$result[$key],
                    'after'=>$value
                );
            }
        }
        $this->log->addInfo('tc_'.$table,$content);
//        if(!is_file($path=ROOT_FW_PATH.'/logs/'.date('Ymd').'/'.$table.'.txt')){
//
//        }

//        file_put_contents(ROOT_FW_PATH.'/logs/'.date('Ymd').'/'.$table.'.txt',json_encode($content,256),8);
    }
}
class cls_base_controller
{
    public $data="";

    function __construct()
    {
        if(isset($GLOBALS["business"])&&!empty($GLOBALS["business"])){
            $this->data=$GLOBALS["business"];
            #如果加密过 那么全局business得到的结果就是json格式的
            if(isset($GLOBALS["other"]->encode) && $GLOBALS["other"]->encode == true){
                $this->data=json_decode($GLOBALS["business"]);
            }
        }

        //tc应用中, 除了loan模块, 其他的模块都需要登录验证
        if(
            count((array)$GLOBALS['header']->route)
            && $GLOBALS['header']->route->application == 'tc'
            && $GLOBALS['header']->route->module != 'loan'
        ) {
            //检查登陆
            if(CHECK_LOGIN === true) {
                $this->checkLogin();
            }
            //权限检查
            if(CHECK_AUTH === true && $this->allowAuth()) {
                $this->checkAuth();
            }
        }

    }


    private function allowAuth()
    {
        //不在ignore_action列表中
        return (! $this->isIgnoreAction()) &&
            //不在nologin_ignore列表中
            (!$this->isNologinIgnore()) &&
            //平台用户不需要检查权限
            ($_SESSION['user']['type'] !== '0') &&
            //普通用户不需要检查权限
            ($_SESSION['platform'] != 3);
    }

    //检查用户的权限
    private function checkAuth()
    {
        require_once ROOT_FW_PATH . '/application/source/tc/auth/auth.php';
        $auth = new auth();

        //路由信息
        $route = $GLOBALS['header']->route;
        //手机号码
        $phone = $_SESSION['user']['phone'];

        //当前用户所属的角色
        $roleId = $auth->byPhoneToRoleId($phone);

        if(empty($roleId)) {
            cls_output::out('E120118', '该用户不属于某个角色');
        }

        //当前访问的节点id
        $nodeId = $auth->getNodeId($route->module, $route->controller, $route->function);
        if(empty($nodeId)) {
            cls_output::out('E120118', '该节点尚未录入');
        }

        //判断是否有权限访问
        if(! $auth->hasAccess($roleId, $nodeId)) {
//            var_dump($_SESSION);exit;
            cls_output::out('E120118', '您没有权限访问');
        }
    }

    //没有登录的用户自动跳转到登录页面
    private function checkLogin()
    {
        if(isset($GLOBALS["header"]->session)) {
            $sessionKey = $GLOBALS["header"]->session;
        }elseif(isset($_GET['session'])) {
            $sessionKey = $_GET['session'];
        }else{
            $sessionKey = null;
        }

        //不需要验证登录的方法
        if($this->isIgnoreAction()){
            $sessionKey ? @session_id($sessionKey) : @session_id(SESS_ID.gen_session_key(SESS_ID . time()));
            @session_start();
            return true;
        }elseif ($this->isNologinIgnore() && $sessionKey){
            //不需要走小柜后台的登录流程的方法
            @session_id($sessionKey);
            @session_start();
            return true;
        }elseif($sessionKey){
            //小柜后台登录
            @session_id($sessionKey);
            @session_start();
        }else{
             cls_output::out('E120002', '没有传递session_key');
        }

        //用户已经登录
        if(isset($_SESSION['user']) && count($_SESSION['user'])>2){
            //其他客户端使用该账户登录了系统, 本账户被挤出
            $this->checkLocation($sessionKey);

            //登陆超时设置
            $this->loginTimeout();
            return true;
        }

        //用户没有登录
        cls_output::out('E120000', '您没有登录');
    }

    //其他客户端使用该账户登录了系统, 本账户被挤出
    private function checkLocation($oldSessionKey)
    {
        $table = $GLOBALS['ecs']->table('login_pool');
        $where = "user_id={$_SESSION['user']['id']} and from_platform={$_SESSION['platform']}";
        //获取数据表最新的session_key
        $sessionKey = $GLOBALS['db']->getOne("select session_id from {$table} where {$where}");

        if($sessionKey && ($oldSessionKey != $sessionKey)){
            cls_output::out('E120000', '您已经在其他地方登录了, 如非本人操作, 请修改您的密码');
        }
    }

    //不需要验证的方法
    private function isIgnoreAction()
    {   
        $route = $this->getPointRoute();
        $allowAction = require ROOT_FW_PATH . 'config/tc_login_ignore.php';

        return in_array($route, $allowAction);
    }

    //不需要走小柜后台登录流程的方法
    private function isNologinIgnore()
    {
        $route = $this->getPointRoute();
        $allowAction = require ROOT_FW_PATH . 'config/tc_nologin_ignore.php';

        return in_array($route, $allowAction);
    }

    /**
     * 获取点语法表示的路由
     * @return [type] [description]
     */
    private function getPointRoute()
    {
        $array = (array) $GLOBALS['header']->route;
        $rules = ['application', 'module', 'controller', 'function'];
        uksort($array, function($a, $b)use($rules){
            $ak = array_search($a, $rules);
            $bk = array_search($b, $rules);
            if($ak == $bk) {
                return 0;
            }
            return $ak > $bk ? 1 : -1;
        });
        
        return implode('.', array_values($array));
    }


    private function loginTimeout()
    {
        @ini_set('session.gc_maxlifetime', LOGIN_TIMEOUT + 2);
        @ini_set("session.cookie_lifetime",LOGIN_TIMEOUT + 2);

        //session有效期
        if(isset($_SESSION['expiretime']) && $_SESSION['expiretime'] < time()) {
            logout();
            cls_output::out('E120000', '登陆超时, 请重新登陆');
        }
        //无活动时间超时设置
        if(isset($_SESSION['activetime']) && $_SESSION['activetime'] < time()) {
            logout();
            cls_output::out('E120000', '您太长时间没有操作了, 为了您的账号安全, 请重新登陆');
        }

        //如果没有超出规定的活动时间, 则更新最后一次的活动时间
        $activeTime = time() + LOGIN_ACTIVE_TIME;
        //更新session
        $_SESSION['activetime'] = $activeTime;
        //更新登录池
        $GLOBALS['db']->edit(['active_time' => $activeTime], ['user_id'=>$_SESSION['user']['id']], 'login_pool');
    }

    public static function newInstance(callable $callback)
    {
        //缓存路由信息
        $route = $GLOBALS['header']->route;
        //清空路由信息
        $GLOBALS['header']->route = new stdClass();

        //获取类的实例
        $instance = $callback();
        //还原路由信息
        $GLOBALS['header']->route = $route;
        return $instance;
    }



    function load_table($table){
        $_table="table_".$table;
        $path=ROOT_FW_PATH."application/class/table/".$_table.".php";
        if(is_file($path)){
           require_once $path;
           $obj = new $_table();
           return $obj;
        }else{
            #如果传入的是个路径
            $_info=pathinfo($table);
            $_table='table_'.$_info['basename'];
            $path= ROOT_FW_PATH."application/class/table/".$_info['dirname'].'/'.$_table.'.php';
            if(is_file($path)){
                require_once $path;
                $obj = new $_table();
                return $obj;
            }else{
                trigger_error("cannot get file:".$path);
            }
        }
    }

    function check_input($check_array){
        $fields=array();
        foreach($check_array as $item){
            if(isset($this->data->$item) && !empty($this->data->$item)){
                $fields[$item]=$this->data->$item;
            }else{
                cls_output::out("E000203",$item.'--不能为空');
            }
        }
        return $fields;
    }


}
trait zhbgcommon{
    /*
     * $perm_zhlist 可使用综合产品ids
     * $prod_perm  可使用产品编号
     * */
    function get_server_list(&$perm_zhlist=null,&$prod_perm=null){
        global $db,$ecs;
        $supid=$_SESSION['supplier_id'];
        $serverList=$this->table_zhbg->get_server();
        //根据权限筛选可用的业务
        $pdtperm=$this->table_zhbg->get_sup_perm($supid);
        $perm_zhlist=explode(',',$pdtperm['zhperm']);
        $prod_perm=unserialize($pdtperm['perm']);
        $perm_sids=$this->table_zhbg->get_perm_sids($perm_zhlist);
        foreach($serverList as $key=>$s){
            if(!in_array($s['id'],$perm_sids)){
                unset($serverList[$key]);
            }
        }
        return $serverList;
    }
}
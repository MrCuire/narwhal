<?php
require_once ROOT_FW_PATH.'config/common/Common.php';
require_once ROOT_FW_PATH.'config/common/Files.php';
require_once ROOT_FW_PATH.'config/common/Info.php';
require_once ROOT_FW_PATH.'config/common/Notify.php';
require_once ROOT_FW_PATH.'config/common/Tree.php';
//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;
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
//       $this->log = new Logger('log_info');
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
    use Common,Files,Info,Notify,Tree;
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
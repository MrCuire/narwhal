<?php
/**
 * Created by PhpStorm.
 * User: 唐焕能
 * Date: 2017/12/15
 * Time: 11:38
 * 单例模式
 */

class cls_api
{
    private static $_instance=null;

    var $application;
    var $module;
    var $controller;
    var $function;
    var $business=null;
    var $root;
    var $session;
    //不能实例化
    private function __construct()
    {
    }
    //不能克隆
    private function __clone()
    {
        // TODO: Implement __clone() method.
    }
    #实例化
    static public function get_instance(){
        if(is_null(self::$_instance)||!self::$_instance){
            self::$_instance=new self();
        }
        return self::$_instance;
    }

    public function init($data)
    {

        //接收进来的参数
        #四个少任何一个就返回错误信息
        #接收模块
        if(isset($data->header->route->application)){
            $this->application=$data->header->route->application;
        }else{
            throw new Exception("非法application",-1);
        }

        if (isset($data->header->route->module)) {
            $this->module = $data->header->route->module;
        }else{
            throw new Exception("非法module",-2);
        }
        #接收控制器文件
        if (isset($data->header->route->controller)) {
            $this->controller = $data->header->route->controller.".php";
        }else{
            throw new Exception("非法controller",-3);
        }
        #接收方法名
        if (isset($data->header->route->function)) {
            $this->function = $data->header->route->function;
        }else{
            throw new Exception("非法function",-4);
        }
        #接收业务参数
        if(isset($data->business)){
            $this->business=$data->business;
        }
        #设置路径
        $this->root= str_replace("includes/class/".$this->application."/cls_api.php", '', str_replace('\\', '/', __FILE__)).'application/';

        $this->session = isset($data->header->session)?$data->header->session:"";
    }

    #调用方法
    function use_function(){

        #基础类
        $base_controller=ROOT_FW_PATH."application/class/class_core.php";
        require_once $base_controller;
        #目录不对或者文件不存在就返回错误信息
        $route=array(
            "application"=>$this->application,
            "module"=>$this->module,
            "controller"=>$this->controller
        );
        cls_init::import($route);
        #实例化控制器
        $object=str_replace(".php","",$this->controller);
        $obj=new $object();

        $function=trim($this->function);
        #方法不存在就返回错误信息
        if(method_exists($obj,$function)){
            #调用方法
            $res = call_user_func(array($obj,$function));
            if($res!=null){
                //判断方法有没有返回值
                return $res;
            }
        }else{
//            trigger_error("指定的方法不存在");
            cls_output::out("E010007","指定的方法[{$object}->{$function}]不存在","",false);
        }
    }


    function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}
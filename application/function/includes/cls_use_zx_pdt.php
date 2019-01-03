<?php
require_once(LIB_PATH . 'modules/zxdata/zxTool.class.php');
class use_zx_pdt{
    private $zxTool;
    static $errorno;
    static $msg;
    function __construct(){
        $this->zxTool=new zxTool();
    }
    //实名验证
    public function idcardValidate($name,$idcard,$mobileNo){
        $route=array(
            'module'=>'product',
            'action'=>'single2',
            'apiVer'=>'v1',
        );
        $busiData=array(
            'productId'=>'QZFX00001',
            'records'=>array(
                'idNo'=>$idcard,
                'name'=>$name,
                'mobileNo'=>$mobileNo,
                'entityAuthCode'=>'11233',
                'entityAuthDate'=>date("Y-m-d H:i:s",time())
            )
        );
        $res=$this->zxTool->_getData($route,$busiData);
        if($res===false){
            return self::setError(zxTool::$state,zxTool::$msg);
        }
        $dataRes=zxTool::$result;

        $returnRes=$dataRes['records']['result'];
        if(!isset($returnRes['isRealIdentity'])){
            return self::setError(1,'数据更新维护中');
        }
        elseif($returnRes['isRealIdentity']==1){
            return true;
        }
        else{
            return self::setError(2,$returnRes['msg']);
        }
    }
    //四元素验证
    public function bankCard4Validate($name,$idcard,$mobileNo,$bankCard){
        $route=array(
            'module'=>'product',
            'action'=>'single2',
            'apiVer'=>'v1',
        );
        $busiData=array(
            'productId'=>'QZFX00012',
            'records'=>array(
                'idNo'=>$idcard,
                'name'=>$name,
                'bankPreMobile'=>$mobileNo,
                'accountNo'=>$bankCard,
                'entityAuthCode'=>'1123344',
                'entityAuthDate'=>date("Y-m-d H:i:s",time())
            )
        );
        $res=$this->zxTool->_getData($route,$busiData);
        if($res===false){
            return self::setError(zxTool::$state,zxTool::$msg);
        }
        $dataRes=zxTool::$result;

        $returnRes=$dataRes['records']['result'];
        if(!isset($returnRes['code'])){
            return self::setError(1,'数据更新维护中');
        }
        elseif($returnRes['code']==1){
            return true;
        }
        else{
            return self::setError(2,$returnRes['message']);
        }
    }
    //获取风控产品
    public function getRiskProducts(){
        $route=array(
            'module'=>'user',
            'action'=>'zhengxininfo',
            'apiVer'=>'v1',
            'do'=>'get_product_list'
        );
        $res=$this->zxTool->_getData($route);
        if($res===false){
            return self::setError(zxTool::$state,zxTool::$msg);
        }
        $dataRes=zxTool::$result;

        return $dataRes['productList'];
    }
    static function setError($errorno,$msg=''){
        self::$errorno=$errorno;
        self::$msg=$msg;
        return false;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/10/16
 * Time: 10:53
 */

trait Info {

    public function getUserInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_orduser WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getBusinessInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_business WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getServiceInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_service_provider WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getDeviceInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_device WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getLoanProductInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_product WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getCreditCardProductInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_product_credit_card WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getCreditInfoProductInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_product_zx WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getSucceedLoanInfo($search, $where)
    {
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM tc_loan_log WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        return current($result);
    }

    public function getDeviceId($table,$search, $where,$left)
    {
        $deciveId=array();
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM '.$table.$left.' WHERE '.$db->explode_condition($where);
        $result = $db->getAll($sql);
        if(!empty($result)){
            foreach($result as $k=>$v){
                $deciveId[]=$v['id'];
            }
        }
        return $deciveId;
    }

    /*
     * 获取产品id及收益
     * */
    public function getProductInfo($table,$search='*', $left='',$where=array(),$group='',$limit=''){
        $db = $GLOBALS['db'];
        $sql = 'SELECT '.$search.' FROM '.$table.$left.' WHERE '.$db->explode_condition($where).$group.$limit;
        $result = $db->getAll($sql);
        return $result;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/22
 * Time: 18:05
 */

class cls_output
{
    /**
     * 输出函数
     *
     * @param string $status           输出的状态编码
     * @param string $msg       输出信息
     * @param string $data      输出数据
     * @param string $encode    是否加密
     */
    public static function out($status, $msg = "", $data = "", $encode = "")
    {
//        if($session == false){
//            $session_id="";
//        }else{
//            $session_id=SESS_ID.gen_session_key(SESS_ID);
//        }
        $header = array("status" => $status, "msg" => $msg);
        if ((empty($encode) && isset($GLOBALS["other"]->encode) && $GLOBALS["other"]->encode === true) || $encode == true) {
            require_once ROOT_FW_PATH."application/function/aes.php";
            $key = substr(md5(rand(1000, 9999)), 1, 16);
            if (is_array($data)) {
                $data = json_encode($data);
            }

            $data = aes::AesEncrypt($data, $key);
            $other = array(
                "encode" => true,
                "key" => $key
            );
        } else {
            $other = array();
        }
        $return = array("header" => $header, "business" => $data, "other" => $other);
        echo json_encode($return);
        exit();
    }

    public static function error_msg($status, $msg, $data)
    {
        $header = array("status" => $status, "msg" => $msg);
        $other = array("encode" => false);
        $return = array("header" => $header, "business" => $data, "other" => $other);
        echo json_encode($return);
        exit();
    }

}
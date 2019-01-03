<?php
/*
 * Rsa 加解密 签名 验签
 * */
class cls_rsa{
    static $config;
    static $config_is_loaded=false;
    static $orgId=0;
    static $transNo='';

    /*
     * 服务器发送到客户的数据 经过此函数加密
     * */
    public static function s2c($header,$retrunData){
        if(!empty(self::$orgId)&&!empty(self::$transNo)){
            $uno=0;
            if(isset($retrunData['uno'])&&!empty($retrunData['uno'])){
               $uno= $retrunData['uno'];
            }
            if(empty($uno)&&isset($retrunData['records']['uno'])&&!empty($retrunData['records']['uno'])){
                $uno= $retrunData['records']['uno'];
            }
            $msg=$header['code'];
            C::t('org_trans')->update_req(self::$orgId,self::$transNo,$uno,$msg);
            $header['transNo']=self::$transNo;
            $header['orgId']=self::$orgId;
        }
        $cfg=self::getConfig(false);
        $busiData = self::encodeData($retrunData,$cfg);
        $sigValue = self::getSigValue($busiData,$cfg);
        $post_data=array(
            'header'=>$header,
            'busiData'=>$busiData,
            'securityInfo'=>array('signatureValue'=>$sigValue),
        );
        return json_encode($post_data);
    }

    /*
     * 客户端发送至服务器的数据 经过此函数解析
     * 验签通过 则返回解析后的数据
     * 验签失败 则返回false 并记录日志
     * */
    public static function c2s($receive_data){
        $data=json_decode($receive_data,true);
        if(empty($data['header']['orgId'])){
            debug($data,'data_error');
            throw new Exception('机构码缺失','1001');
        }else{
            self::$orgId=$data['header']['orgId'];
        }
        $cfg=self::getConfig(true);
        if(!isset($data['securityInfo']) ||!isset($data['busiData']) || !isset($data['header'])||!is_array($data['header'])){
           debug($data,'data_error');
            throw new Exception('数据异常','1002'); //返回异常
        }
        //验签
        if(!self::verifyData($data['busiData'],$data['securityInfo']['signatureValue'],$cfg)){
           debug($data,'verify_fail');
            throw new Exception('验证签名失败','1003');
        }
        //验证用户名和密码
        if(!isset($data['securityInfo']['user_name'])||!isset($data['securityInfo']['pwd'])){
            throw new Exception('用户名或密码缺失','1015');
        }
        elseif($data['securityInfo']['user_name']!=$cfg['user_name']||$data['securityInfo']['pwd']!=$cfg['pwd']){
            throw new Exception('用户名或密码不匹配','1016');
        }
        //请求交易号
        if(empty($data['header']['transNo'])){
            throw new Exception('请求交易标识为空','1017');
        }
        //请求交易时间
        if(empty($data['header']['transDate'])||!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/s",$data['header']['transDate'])||($data['header']['transDate']===false)){
            throw new Exception('请求交易时间为空或格式错误','1018');
        }
        //将交易请求写入 机构请求表

        self::$transNo=$data['header']['transNo'];
        $decode_data=self::decodeData($data['busiData'],$cfg);
        $return_data['busiData']=json_decode($decode_data,true);
        if(isset($return_data['busiData']['productId'])&&$return_data['busiData']['productId']=='PRT000200'){
            C::t('org_trans')->insert_req($data['header']['orgId'], self::$transNo,$data['header']['transDate'],'');
        }
        else{
            C::t('org_trans')->insert_req($data['header']['orgId'], self::$transNo,$data['header']['transDate'],json_encode($return_data['busiData'],JSON_UNESCAPED_UNICODE));
        }
        $return_data['header']=$data['header'];
        if(is_array($data['header'])&&isset($data['header']['ukey'])&&is_string($data['header']['ukey'])){
            $return_data['header']['ukey']=self::rsa_decode($data['header']['ukey'],$cfg);
        }
        return addslashes_deep($return_data);

    }
    /**
     * 获取配置信息
     */
    private static function getConfig($in=false){//信息输入时 需要检测 公钥 对称密钥 ；信息输出时 没有公钥 对称密钥 则采用测试 的 公钥 对称密钥 经数据加工再输出
        if(!self::$config_is_loaded){
            $app_config=array();
            @require_once ORG_PATH.'config/org_config.php';
            self::$config=&$app_config;
            if(defined('IN_ORG')){
                $orgInfo=C::t('org_users')->get_org_info(self::$orgId);
            }
            else{
                global $db,$ecs;
                $orgInfo=$db->getRow("select * from ".$ecs->table('org_users')." where `orgId`=".intval(self::$orgId).' limit 1');
            }
            if(!empty($orgInfo)){
                if(!empty($orgInfo['pubkey'])){
                    self::$config['app_public_key']='/'.self::$orgId.'/'.$orgInfo['pubkey'];
                }elseif($in){
                    throw new Exception('公钥缺失','1005');
                }
                if(!empty($orgInfo['3deskey'])){
                    self::$config['keyStr']=$orgInfo['3deskey'];
                }elseif($in){
                    throw new Exception('对称密钥缺失','1006');
                }
            }elseif($in){
                throw new Exception('不存在的机构商','1007');
            }
            self::$config['user_name']=$orgInfo['user_name'];
            self::$config['pwd']=$orgInfo['pwd'];

            self::$config_is_loaded=true;
        }
        return  self::$config;
    }
    /**
     * 获取证书路径
     * @return string
     * User: qiaokeer
     */
    private static function getRsaPath(){
        return ORG_PATH.'appcert/';
    }

    /**
     * 解析数据
     * @param $data
     * @param $cfg
     * @return bool|string
     * User: qiaokeer
     */
    private static function decodeData($data,$cfg){
        return cls_crypt3Des::decrypt($data,$cfg['keyStr']);
    }


    /**
     * 加密数据
     * @param $params
     * @param $cfg
     * @return string
     */
    private static function encodeData($params,$cfg){
      /*  if(isset($params['tokens'])&&$params['tokens']&&is_string($params['tokens'])){
            $params['tokens']=self::rsa_encode( $params['tokens'],$cfg);
        }*/
        $params = json_encode($params);
        return cls_crypt3Des::encrypt($params,$cfg['keyStr']);
    }

    /*
     * rsa 加密 由客户端的公钥2 加密
     * */
    private static function rsa_encode($str,$cfg){
        $p_sPubKey = self::getRsaPath().$cfg['app_public_key'];
        $sPubKeyContent =  file_get_contents($p_sPubKey);
        $pu_key =  openssl_pkey_get_public($sPubKeyContent);
        openssl_public_encrypt($str,$sSign,$pu_key);
        openssl_free_key($pu_key);
        return  base64_encode($sSign);
    }
    /*
     * 函数function rsa_decode 的外部使用函数
     * */
    public static function use_ras_decode($str){
        $cfg=self::getConfig();
        return self::rsa_decode($str,$cfg);
    }
    /*
    * rsa 解密 由服务端的私钥1 解密
    * */
    private static function rsa_decode($str,$cfg){
        $p_sPrivateKey = self::getRsaPath().$cfg['pc_private_key'];
        $sPrivateKeyContent =  file_get_contents($p_sPrivateKey);
        $pi_key =  openssl_get_privatekey($sPrivateKeyContent,$cfg['private_secret']);
        openssl_private_decrypt(base64_decode($str), $cli_result, $pi_key);
        openssl_free_key($pi_key);
        return $cli_result;
    }
    /**
     * 获取签名 用私钥1
     * @param $data
     * @param $cfg
     * @return string
     */
    private static function getSigValue ($data,$cfg){
        $p_sPrivateKey = self::getRsaPath().$cfg['pc_private_key'];
        $sPrivateKeyContent =  file_get_contents($p_sPrivateKey);
        $pi_key =  openssl_get_privatekey($sPrivateKeyContent,$cfg['private_secret']);
        openssl_sign($data, $sSign, $pi_key, OPENSSL_ALGO_SHA1);
        openssl_free_key($pi_key);
        return base64_encode($sSign);
    }
    /**
     * 验签 用App 公钥2
     * @param $data
     * @param $signValue
     * @return bool
     */
    private static function verifyData($data,$signValue,$cfg){
        $p_sPublicKey = self::getRsaPath().$cfg['app_public_key'];
        $sPublicKeyContent =  file_get_contents($p_sPublicKey);
        $pu_key = openssl_pkey_get_public($sPublicKeyContent);//这个函数可用来判断公钥是否是可用的
        return openssl_verify($data, base64_decode($signValue), $pu_key, OPENSSL_ALGO_SHA1) ? true : false;
    }
}
?>
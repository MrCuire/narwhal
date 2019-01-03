<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/8/29
 * Time: 14:14
 */

class cls_rsa
{
    private $privateKey;//私钥（用于用户加密）
    private $publicKey;//公钥（用于服务端数据解密）

    public function __construct(){
        $this->privateKey = '';//私钥，用于加密
        $this->publicKey = '';//公钥，用于解密
    }

    public function setPrivateKey($path)
    {
        $this->privateKey = openssl_pkey_get_private(file_get_contents($path));//私钥，用于加密
    }

    public function setPublicKey($path)
    {
        $this->publicKey = openssl_pkey_get_public(file_get_contents($path));//公钥，用于解密
    }



    /**
     * 私钥加密
     * @param 原始数据 $data
     * @return 密文结果 string
     */
    public function encryptByPrivateKey($data) {
        openssl_private_encrypt($data,$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);//私钥加密
        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    /**
     * 私钥解密
     * @param 密文数据 $data
     * @return 原文数据结果 string
     */
    public function decryptByPrivateKey($data){
        $data = base64_decode($data);
        openssl_private_decrypt($data,$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);//私钥解密
        return $encrypted;
    }

    /**
     * 私钥签名
     * @param unknown $data
     * @return string
     */
    public function signByPrivateKey($data){
        openssl_sign($data, $signature, $this->privateKey);
        $encrypted = base64_encode($signature);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }


    /**
     * 公钥加密
     * @param 原文数据 $data
     * @return 加密结果 string
     */
    public function encryptByPublicKey($data) {
        openssl_public_encrypt($data,$decrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);//公钥加密
        return base64_encode($decrypted);
    }

    /**
     * 公钥解密
     * @param 密文数据 $data
     * @return 原文结果 string
     */
    public function decryptByPublicKey($data) {
        $data = base64_decode($data);
        openssl_public_decrypt($data,$decrypted,$this->publicKey,OPENSSL_PKCS1_PADDING);//公钥解密
        return $decrypted;
    }

    /**
     * 公钥验签
     * @param unknown $data
     * @param unknown $sign
     * @return int
     */
    public function verifyByPublicKey($data,$sign){
        $sign = base64_decode($sign);
        return openssl_verify($data, $sign, $this->publicKey);
    }

    public function __destruct(){
        openssl_free_key($this->privateKey);
        openssl_free_key($this->publicKey);
    }
}
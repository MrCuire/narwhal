<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/8/6
 * Time: 16:10
 */

class cls_aes
{
    //aes加密
    /**
     * @param $string
     * @param $screct_key
     * @return string
     */
    public static function AesEncrypt($string, $screct_key)
    {
        $string =self::PKCS7Padding($string);
        $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
        $Encrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $string, MCRYPT_MODE_ECB, $iv);
//        return str_replace(['+','/','='],['-','_',''],base64_encode($Encrypt));
        return base64_encode($Encrypt);
    }

    /**
     * ASE 解密
     * @param string $content 加密内容
     * @param $screct_key
     * @return bool|string
     */
    public static function AesDecode($content, $screct_key)
    {
//        $content=str_replace(['-','_',''],['+','/','='],$content);
        $content = base64_decode($content);
        $iv = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
        $encrypt_str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $content, MCRYPT_MODE_ECB, $iv);
        $content = self::UnPKCS7Padding($encrypt_str);
        return $content;
    }

    /**
     * 为字符串添加PKCS7 Padding
     * @param string $str 源字符串
     * @return string
     */
    private static function PKCS7Padding($str)
    {
        $block=mcrypt_get_block_size(MCRYPT_RIJNDAEL_128,'cbc');
        $pad=$block-(strlen($str)%$block);
        if($pad<=$block)
        {
            $char=chr($pad);
            $str.=str_repeat($char,$pad);
        }

        return $str;
    }

    /**
     * 去除字符串末尾的PKCS7 Padding
     * @param string $data 带有Padding字符的字符串
     * @return bool|string
     */
    private static function UnPKCS7Padding($data)
    {
        $length = strlen($data);
        $unpadding = ord($data[$length - 1]);
        return substr($data, 0, $length - $unpadding);
    }
}
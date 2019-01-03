<?php
#小树普惠的加解密方法
class DepoistEncrypted
{
    /**
     * 通过AES加密请求数据
     *
     * @param $encryptKey
     * @param array $query
     * @return string
     */
    public static function AESEncryptRequest($encryptKey, array $query)
    {
        return self::encrypt(json_encode($query), $encryptKey);
    }

    /**
     * 通过AES解密响应数据
     *
     * @param $encryptKey
     * @param $data
     * @return string
     * @internal param array $query
     */
    public static function AESDecryptResponse($encryptKey, $data)
    {
        return self::decrypt($data, $encryptKey);
    }

    public static function encrypt($input, $pu_key)
    {
        $data = "";
        $dataArray = str_split($input, 245);
        foreach ($dataArray as $value) {
            $encryptedTemp = "";
            openssl_public_encrypt($value, $encryptedTemp, $pu_key);//公钥加密
            $data .= $encryptedTemp;
        }
        return base64_encode($data);
    }

    public static function decrypt($eccryptData, $decryptKey)
    {
        $decrypted = "";
        $decodeStr = base64_decode($eccryptData);
        $enArray = str_split($decodeStr, 256);

        foreach ($enArray as $va) {
            $decryptedTemp = '';
            openssl_private_decrypt($va, $decryptedTemp, $decryptKey);//私钥解密
            $decrypted .= $decryptedTemp;
        }
        return $decrypted;
    }

    public static function getPrivateKey($cert_path)
    {
        $decryptKey4Server = @file_get_contents($cert_path);
        $privateKey = openssl_pkey_get_private($decryptKey4Server);
        return $privateKey;
    }

    public static function getPublicKey($cert_path)
    {
        $encryptionKey4Server = @file_get_contents($cert_path);
        $publicKey = openssl_pkey_get_public($encryptionKey4Server);
        return $publicKey;
    }


    protected static function convertJson($data)
    {
        $str = "";
        foreach ($data as $k => $v) {
            if ((string)$k != "sign") {
                if (is_array($v)) {
                    if (empty($str)) {
                        if (is_numeric($k)) {
                            $str .= self::convertJson($v);
                        } else {
                            $str .= $k . '=' . self::convertJson($v);
                        }
                    } else {
                        if (is_numeric($k)) {
                            $str .= '&' . self::convertJson($v);
                        } else {
                            $str .= '&' . $k . '=' . self::convertJson($v);
                        }
                    }
                } else {
                    if (empty($str)) {
                        $str .= $k . '=' . $v;
                    } else {
                        $str .= '&' . $k . '=' . $v;
                    }
                }
            }
        }
        return $str;
    }


    public static function dgksort($data)
    {

        ksort($data);
        //如果由下一级  对下一集使用排序  返回
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $v = self::dgksort($v);
            }
            $data[$k] = $v;
        }
        return $data;
    }

    public static function createSign($data, $key)
    {

        $data = self::dgksort($data);

        $str = self::convertJson($data);

//        print_r($str);
        $result = md5($str . md5($key));

        return $result;
    }


    public static function guid()
    {

        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $uuid;
        }
    }

}

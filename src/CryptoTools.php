<?php

namespace wllxxxy\wllsdk;


class CryptoTools
{
    protected static $key = '';

    /**
     * 设置加密用的key
     * @param $key
     */
    public static function setKey($key)
    {
        self::$key = $key;
    }

    /**
     * 数据加密方法
     * @param $data
     * @return string
     */
    public static function AES256ECBEncrypt($data)
    {
        $decrypted = openssl_encrypt($data, 'AES-256-ECB', self::$key, OPENSSL_RAW_DATA);
        return base64_encode($decrypted);
    }

    /**
     * 数据解密方法
     * @param $data
     * @return string
     */
    public static function AES256ECBDecrypt($data)
    {
        $data = str_replace(' ', '+', $data);
        $decrypted = openssl_decrypt(base64_decode($data), 'AES-256-ECB', self::$key, OPENSSL_RAW_DATA);
        return $decrypted;
    }

    /**
     * 获取要加密的参数
     * @param $data 要加密的数组
     * @return array
     */
    public static function getEncryptArray($data)
    {
        return [
            'data' => self::AES256ECBEncrypt(json_encode($data))
        ];
    }

    /**
     * 获取解密后的数据
     * @param $string  加密内容
     * @param int $type 1=响应数据 2请求数据
     * @return array|mixed|null
     */
    public static function getDecryptedArray($string, $type = 1)
    {
        $string = str_replace(' ', '+', $string);
        switch ($type) {
            case 1: // 解密响应的数据{data:"加密串"}
                $string = json_decode($string, true);
                $string = isset($string['data']) ? $string['data'] : '';
                break;
            case 2: // 解密请求的数据 data="加密串",传递进来的sting 为加密串

                break;
            default:
                return null;
        }
        //处理get请求时,base64编码中的+被空格替换的情况
        $string = json_decode(self::AES256ECBDecrypt($string), true);
        return is_array($string) ? $string : null;

    }
}

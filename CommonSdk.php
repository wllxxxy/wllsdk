<?php
/**
 * 公共的sdk调用方法
 * @file CommonSdk.php
 * @author lixinhan <lixinhan@yuanxin-inc.com>
 * @date 2017-12-27
 * @version 2.0
 */

namespace wllxxxy\wllsdk;
class CommonSdk
{
    private $urlMapping = [];
    private $memberLoginToken = '';
    protected $errorMessage;
    protected $errorArray;
    private $appid;
    private $appkey;
    private $baseParams;
    private $isPowerError = false;

    /**
     * @return bool
     */
    public function isPowerError()
    {
        return $this->isPowerError;
    }

    /**
     * @param bool $isPowerError
     */
    public function setIsPowerError($isPowerError)
    {
        $this->isPowerError = $isPowerError;
    }

    protected $log = [
        'baseurl' => '',
        'requestUrl' => '',
        'method' => '',
        'baseparams' => '',
        'requestparams' => '',

    ];

    public function __construct()
    {
        $this->appid = Config::getConfig('appid');
        $this->appkey = Config::getConfig('appkey');
        $this->urlMapping = Config::getConfig('urlmapping');
        $this->memberLoginToken = Config::getMemberLoginToken();

        $this->baseParams = [
            'appid' => $this->appid,
            'time' => time(),
            'os' => Config::getConfig('os'),
            'version' => Config::getConfig('version'),
            'member_login_token' => Config::getMemberLoginToken(),
        ];
    }

    /**
     * 发送请求
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @param $params
     * @param $method
     * @param string $type
     * @return bool|mixed
     */
    protected function send($params, $method, $type = 'get')
    {
        $type = strtolower($type);
        CryptoTools::setKey($this->appkey);
        $method = explode('\\', $method);
        $project = strtolower($method[2]);
        $url = strtolower(str_replace('Sdk::', '/', end($method)));
        $requestUrl = rtrim($this->urlMapping[$project], '/') . '/' . $url;
        $curl = curl_init();
        $requestData = [];
        //设置展示方式
        $this->setlog("method", $type);
        $this->setlog("baseurl", $requestUrl);
        $this->setlog('baseparams', $this->baseParams);
        $this->setlog('requestparams', $params);
        switch ($type) {
            case 'post':
                //如果是post请求
                curl_setopt($curl, CURLOPT_POST, 1);
                if (is_array($params) && count($params)) {
                    //把参数加密后放到post参数的key:data中
                    $requestData['data'] = CryptoTools::AES256ECBEncrypt(json_encode($params));
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestData);
                }
                $requestUrl = $requestUrl . '?' . http_build_query($this->baseParams);
                break;
            case 'get':
                //如果是get请求
                if (is_array($params) && count($params)) {
                    //把参数加密后放到get参数的key:data中
                    $this->baseParams['data'] = CryptoTools::AES256ECBEncrypt(json_encode($params));
                }
                $requestUrl = $requestUrl . '?' . http_build_query($this->baseParams);
                curl_setopt($curl, CURLOPT_POST, 0);
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        $this->setlog("requestUrl", $requestUrl);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (strstr(PHP_OS, 'WIN') !== false) {
            //如果是windows下不验证ssl
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $startTime = microtime(true);
        $data = curl_exec($curl);
        $endTime = microtime(true);
        $this->setlog("startTime", $startTime);
        $this->setlog("endTime", $endTime);
        $this->setlog("networkTime", $endTime - $startTime);
        if (curl_errno($curl)) {
            $curlErrorMessage = curl_error($curl);
            $this->setError($curlErrorMessage);
            $this->setErrorArray([
                'code' => 400,
                'msg' => $curlErrorMessage
            ]);
            return false;
        }
        $returnData = json_decode($data, true);
        $this->setlog("responseData", $returnData);
        $returnCode = $returnData['code'] ?? '';
        switch ($returnCode) {
            case 200:
                $data = json_decode(CryptoTools::AES256ECBDecrypt($returnData['data']), true);
                return $data;
                break;
            case -1:
                $returnMessage = $returnData['msg'] ?? '用户权限验证异常';
                $this->setError($returnMessage);
                $this->setIsPowerError(true);
                $this->setErrorArray([
                    'code' => $returnCode,
                    'msg' => $returnMessage
                ]);
                Config::callAuthorizationFailureEvent();
                return false;
            default:
                $returnMessage = "未知错误";
                if (is_array($returnData) && isset($returnData['msg'])) {
                    $returnMessage = $returnData['msg'];
                }
                $this->setError($returnMessage);
                $this->setErrorArray([
                    'code' => 400,
                    'msg' => $returnMessage
                ]);
                return false;
        }

    }

    protected function setErrorArray($data)
    {
        return $this->errorArray = $data;
    }

    public function getErrorArray()
    {
        return $this->errorArray;
    }

    /**
     * 设置错误信息
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @param $error
     */
    protected function setError($error)
    {
        $this->errorMessage = $error;
    }

    /**
     * 获取错误信息
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @return mixed
     */
    public function getError()
    {
        return $this->errorMessage;
    }

    /**
     *设置日志
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @param $key
     * @param $value
     */
    protected function setlog($key, $value)
    {
        $this->log[$key] = $value;
    }

    /**
     *获取日志
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     *获取格式化后的日志
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @return string
     */
    public function getFormatLog()
    {
        $return = '';
        $return .= $this->formatArray($this->log);
        return $return;
    }

    /**
     *格式化数组方法
     * @author lixinhan <lixinhan@yuanxin-inc.com>
     * @date 2017-12-27
     * @param $value
     * @param int $paddingLength
     * @return string
     */
    private function formatArray($value, $paddingLength = 0)
    {
        if (is_scalar($value)) {
            return $value . "\n";
        }
        $return = '';
        $length = 0;
        foreach ($value as $k => $v) {
            if (($tempLength = strlen($k)) > $length) {
                $length = $tempLength;
            }
        }
        foreach ($value as $k => $v) {
            $return .= str_repeat(' ', $paddingLength) . str_pad($k, $length, ' ') . " : " . (is_array($v) ? "\n" : "") . $this->formatArray($v, $paddingLength + $length + 3);
        }
        return $return;

    }

}

<?php
namespace Tool\YouZan;

use Tool\Util\Http;

class YzBase
{
    const VERSION = '1.0';

    private static $apiEntry = 'https://open.koudaitong.com/api/entry';

    private $appId;
    private $appSecret;
    private $format = 'json';
    private $signMethod = 'md5';

    public function __construct($appId, $appSecret)
    {
        if ('' == $appId || '' == $appSecret) {
            _pack("参数错误！", false);
        }

        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function get($method, $params = array())
    {
        $res = Http::get(self::$apiEntry, $this->buildRequestParams($method, $params));
        if (!$res['result']) {
            return $res;
        } else {
            return $this->parseResponse($res['data']);
        }
    }

    public function post($method, $params = array(), $files = array())
    {
        $res = Http::post(self::$apiEntry, $this->buildRequestParams($method, $params), $files);
        if (!$res['result']) {
            return $res;
        } else {
            return $this->parseResponse($res['data']);
        }
    }


//    public function setFormat($format)
//    {
//        if (!in_array($format, KdtApiProtocol::allowedFormat()))
//            throw new Exception('设置的数据格式错误');
//
//        $this->format = $format;
//
//        return $this;
//    }

//    public function setSignMethod($method)
//    {
//        if (!in_array($method, KdtApiProtocol::allowedSignMethods()))
//            throw new Exception('设置的签名方法错误');
//
//        $this->signMethod = $method;
//
//        return $this;
//    }


    private function parseResponse($responseData)
    {
        $data = json_decode($responseData, true);
        if (null === $data) {
            return _output("无效的字符串", false);
        }
        return _output($data);
    }

    private function buildRequestParams($method, $apiParams)
    {
        if (!is_array($apiParams)) $apiParams = array();
        $pairs = $this->getCommonParams($method);
        foreach ($apiParams as $k => $v) {
            if (!isset($pairs[$k])) {
                $pairs[$k] = $v;
            }
        }

        $pairs['sign'] = $this->sign($this->appSecret, $pairs, $this->signMethod);
        return $pairs;
    }

    private function getCommonParams($method)
    {
        $params = array();
        $params["app_id"] = $this->appId;
        $params["method"] = $method;
        $params["timestamp"] = date('Y-m-d H:i:s');
        $params["format"] = $this->format;
        $params["sign_method"] = $this->signMethod;
        $params["v"] = self::VERSION;
        return $params;
    }

    public function sign($appSecret, $params, $method = 'md5')
    {
        if (!is_array($params)) $params = array();

        ksort($params);
        $text = '';
        foreach ($params as $k => $v) {
            $text .= $k . $v;
        }

        return $this->hash($method, $appSecret . $text . $appSecret);
    }

    private function hash($method, $text)
    {
        switch ($method) {
            case 'md5':
            default:
                $signature = md5($text);
                break;
        }
        return $signature;
    }
}

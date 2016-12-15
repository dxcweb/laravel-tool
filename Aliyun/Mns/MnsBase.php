<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/27
 * Time: 13:59
 */

namespace Tool\Aliyun\Mns;


use Tool\Util\ArrayToXml;
use Tool\Util\Http;

class MnsBase
{
    private $accessId;
    private $accessKey;
    private $endPoint;
    private $scheme;
    private $host;

    public function __construct($accessId, $accessKey, $endPoint)
    {
        $this->accessId = $accessId;
        $this->accessKey = $accessKey;
        $this->endPoint = $endPoint;
        $url_arr = parse_url($this->endPoint);
        $this->scheme = $url_arr['scheme'];
        $this->host = $url_arr['host'];
    }

    public function http($method, $path, $xml_start_element, $query = [], $headers = [], $files = [])
    {
        $method = strtoupper($method);
        $body = $this->getBody($xml_start_element, $query);
        $required_headers = $this->addRequiredHeaders($body);
        $headers = array_merge($required_headers, $headers);
        $sign = $this->getSign($method, $path, $headers);
        $headers['Authorization'] = "MNS " . $this->accessId . ":" . $sign;
        $url = $this->scheme . "://" . $this->host . $path;
        switch ($method) {
            case 'PUT':
                $res = Http::put($url, $body, $headers);
                break;
            case 'POST':
                $res = Http::post($url, $body, $files, $headers);
                break;
            default:
                return _output("无效的请求类型", false);
        }
        return $res;
    }

    private function addRequiredHeaders($body)
    {
        $headers['Date'] = gmdate('D, d M Y H:i:s \G\M\T');
        $headers['Content-Length'] = strlen($body);
        $headers['Content-Type'] = "text/xml";
        $headers['x-mns-version'] = "2015-06-06";
        $headers['Host'] = $this->host;
        return $headers;
    }

    private function getBody($xml_start_element, $query)
    {
        $a2x = new ArrayToXml($xml_start_element, 'http://mns.aliyuncs.com/doc/v1/');
        return $a2x->toXml($query);
    }

    private function getSign($method, $path, $headers)
    {
        $contentMd5 = "";
        if (isset($headers['Content-MD5'])) {
            $contentMd5 = $headers['Content-MD5'];
        }

        $contentType = "";
        if (isset($headers['Content-Type'])) {
            $contentType = $headers['Content-Type'];
        }

        $date = $headers['Date'];


        $tmpHeaders = array();
        foreach ($headers as $key => $value) {
            if (0 === strpos($key, 'x-mns')) {
                $tmpHeaders[$key] = $value;
            }
        }
        ksort($tmpHeaders);

        $canonicalizedMNSHeaders = implode("\n", array_map(function ($v, $k) {
            return $k . ":" . $v;
        }, $tmpHeaders, array_keys($tmpHeaders)));

        $stringToSign = strtoupper($method) . "\n" . $contentMd5 . "\n" . $contentType . "\n" . $date . "\n" . $canonicalizedMNSHeaders . "\n" . $path;
        return base64_encode(hash_hmac("sha1", $stringToSign, $this->accessKey, $raw_output = TRUE));
    }
}
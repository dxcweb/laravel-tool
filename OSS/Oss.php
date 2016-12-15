<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/19
 * Time: 17:16
 */

namespace Tool\OSS;


class Oss
{
    public $access_id;
    public $access_key;
    public $host;
    public $bucket;
    public $oss_dir;

    /**
     * 验证并且执行请求，按照OSS Api协议，执行操作
     */
    public function auth($options)
    {
        $hostname=$this->bucket.'.'.$this->host;
        $this->generateHeaders($options, $hostname);
        $signable_query_string=$this->sign($options);
        $conjunction = '?';
        if ($signable_query_string !== '') {
            $signable_query_string = $conjunction . $signable_query_string;
            $conjunction = '&';
        }
        $query_string = $this->generateQueryString($options);
    }

    private function sign($options)
    {
        $signableQueryStringParams = array();
        $signableList = array(
            'partNumber',
            'response-content-type',
            'response-content-language',
            'response-cache-control',
            'response-content-encoding',
            'response-expires',
            'response-content-disposition',
            'uploadId'
        );
        foreach ($signableList as $item) {
            if (isset($options[$item]))
            {
                $signableQueryStringParams[$item] = $options[$item];
            }
        }
        return $this->toQueryString($signableQueryStringParams);
    }
    /**
     * 获得当次请求的资源定位字段
     *
     * @param $options
     * @return string 资源定位字段
     */
    private function generateResourceUri($options)
    {
        $resource_uri = "";
        // resource_uri + object
        if (isset($options['object']) && '/' !== $options['object']) {
            $resource_uri .= '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options['object']));
        }
        return $resource_uri;
    }
    public static function toQueryString($options = array())
    {
        $temp = array();
        uksort($options, 'strnatcasecmp');
        foreach ($options as $key => $value) {
            if (is_string($key) && !is_array($value)) {
                $temp[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return implode('&', $temp);
    }
    /**
     * 初始化headers
     */
    /**
     * 初始化headers
     *
     * @param mixed $options
     * @param string $hostname hostname
     * @return array
     */
    private function generateHeaders($options, $hostname)
    {
        $headers = array(
            'Content-Md5' => '',
            'Content-Type' => $options['Content-Type'],
            'Date' => gmdate('D, d M Y H:i:s \G\M\T'),
            'Host' => $hostname,
        );
        return $headers;
    }
    public function __construct()
    {
        $this->access_id = config("myapp.oss_access_id");
        $this->access_key = config("myapp.oss_access_key");
        $this->host = config("myapp.oss_host");
        $this->bucket = config("myapp.oss_bucket");
        $this->oss_dir = config("myapp.oss_dir");
    }

    /**
     * 校验bucket,options参数
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $is_check_object
     */
    public function common_check($bucket, $object, &$options, $is_check_object = true)
    {
        if ($is_check_object) {
            $this->check_object($object);
        }
        $this->check_options($options);
        $this->check_bucket($bucket);
    }

    /**
     * 校验object参数
     */
    private function check_object($object)
    {
        if (empty($object)) {
            _pack("Object不允许为空", false);
        }
    }

    /**
     * 检测options参数
     */
    private function check_options(&$options)
    {
        if ($options != NULL && !is_array($options)) {
            _pack('$option必须为数组', false);
        }
        if (!$options) {
            $options = array();
        }
    }
    /**
     *  检查bucket
     */
    private function check_bucket($bucket)
    {
        if (empty($bucket)) {
            _pack("bucket不允许为空", false);
        }
    }
}
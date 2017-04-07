<?php
namespace Tool\Util;

use phpseclib\Crypt\RSA;

class Http
{
    private static $boundary = '';

    /**
     * 错误通知到微信企业号上
     */
    public static function notice($log_path = "", $operation_name = "", $msg = "", $content = "")
    {
        $wxqy_notice = config("myapp.wxqy_notice");
        if (empty($wxqy_notice)) {
            return false;
        }
        $data['agent_id'] = config("myapp.error_notice_agent_id");
        if (empty($data['agent_id'])) {
            return false;
        }

        if (!is_string($msg)) {
            $msg = json_encode_cn($msg);
        }
        $msg = str_replace(":", " = ", "$msg");
        $msg = str_replace('"', "``", "$msg");
        if (!is_string($content)) {
            $content = json_encode_cn($content);
        }
        $content = str_replace(":", " = ", "$content");
        $content = str_replace('"', "``", "$content");
        $notice_content =
            "项目：" . config('myapp.app_name') . "\n" .
            "环境：" . config('myapp.env') . "\n" .
            "日志路径：" . $log_path . "\n" .
            "操作名称：" . $operation_name . "\n" .
            "错误：" . $msg . "\n" .
            "内容：" . $content;
        $data['content'] = mb_substr($notice_content, 0, 600, 'utf-8');
        $res = self::post($wxqy_notice . "service/send-text/to-all", $data, [], [], false);
        return $res;
    }

    public static function get($url, $params = [], $headers = [], $error_notice = true)
    {
        if (!empty($params)) {
            $url = $url . '?' . http_build_query($params);
        }
        $return = self::curl($url, 'GET', null, $headers);
        if ($return['httpCode'] != 200 && $return['httpCode'] != 201) {
            log_file("error/get", "GET非200", ["url" => $url, "params" => $params, "headers" => $headers], $return['httpCode'], $return['response']);
            if ($error_notice) {
                self::notice("error/get", "GET非200", "url：$url", "httpCode:{$return['httpCode']}");
            }
            return _output($return, false);
        }
        return _output($return["response"]);
    }

    public static function rsaPost($key, $url, $params = [], $connection = 'default', $error_notice = true)
    {
        $password = config("private_key.$connection.password");
        $private_key = config("private_key.$connection.key");
        if (empty($password) || empty($private_key)) {
            return _output("缺少配置private_key", false, 7);
        }
        $rsa = new RSA();
        $rsa->setPassword($password);
        $rsa->loadKey($private_key);
        $http_params = [];
        $http_params['key'] = $key;
        $http_params['timeStamp'] = _now();
        $http_params['randStr'] = create_guid();
        $http_params['data'] = json_encode_cn($params);
        $http_params['sign'] = base64_encode($rsa->sign(md5($http_params['data']) . $http_params['timeStamp'] . $http_params['randStr']));
        if (empty($http_params['sign'])) {
            return _output("生成签名错误,请检测私钥密码是否正确！", false);
        }
        return self::post($url, $http_params, [], [], $error_notice);
    }

    public static function post($url, $params = [], $files = [], $headers = [], $error_notice = true, $timeout = 30)
    {
        if (!$files) {
            if (is_array($params)) {
                $body = http_build_query($params);
            } else {
                $body = $params;
            }
        } else {
            $body_res = self::build_http_query_multi($params, $files);
            if (!$body_res['result']) {
                if ($error_notice) {
                    log_file("error/post", "build_http_query_multi", ["url" => $url, "params" => $params, "files" => $files, "headers" => $headers], $body_res['data']);
                    self::notice("error/post", "build_http_query_multi", $body_res['data']);
                }
                return $body_res;
            }
            $body = $body_res['data'];
            $headers['Content-Type'] = "multipart/form-data; boundary=" . self::$boundary;
        }
        $return = self::curl($url, 'POST', $body, $headers, $timeout);
        if ($return['httpCode'] != 200 && $return['httpCode'] != 201) {
            log_file("error/post", "POST非200", ["url" => $url, "params" => $params, "files" => $files, "headers" => $headers], $return['httpCode'], $return['response']);
            if ($error_notice) {
                self::notice("error/post", "POST非200", "url：$url", "httpCode:{$return['httpCode']}");
            }
            return _output($return, false);
        }
        return _output($return["response"]);
    }

    public static function put($url, $body = "", $headers = [], $error_notice = true)
    {
        $return = self::curl($url, 'PUT', $body, $headers);
        if ($return['httpCode'] != 200 && $return['httpCode'] != 201) {
            log_file("error/post", "POST非200", ["url" => $url, "body" => $body, "headers" => $headers], $return['httpCode'], $return['response']);
            if ($error_notice) {
                self::notice("error/post", "POST非200", "url：$url", "httpCode:{$return['httpCode']}");
            }
            return _output($return, false);
        }
        return _output($return["response"]);
    }

    private static function curl($url, $method, $postfields = NULL, $headers = [], $timeout = 30)
    {
        $default_headers = [
            "Expect" => ''
        ];
        $headers = array_merge($default_headers, $headers);
        $header_arr = [];
        foreach ($headers as $key => $val) {
            $header_arr[] = $key . ":" . $val;
        }

        $ci = curl_init();
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);//设置超时
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);//要求结果为字符串且输出到屏幕上
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);//设置为 true ，说明进行SSL证书认证
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, 1);//设置header

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'PUT':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $header_arr);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
        $response = curl_exec($ci);
        $httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
//        $httpInfo = curl_getinfo($ci);
        $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
        curl_close($ci);
        //$header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        return ["response" => $body, "httpCode" => $httpCode];
    }

    private static function build_http_query_multi($params, $files)
    {
        if (!is_array($params)) {
            $params = [];
        }
        self::$boundary = $boundary = uniqid('------------------');
        $MPboundary = '--' . $boundary;
        $endMPboundary = $MPboundary . '--';
        $multipartbody = '';

        foreach ($params as $key => $value) {
            $multipartbody .= $MPboundary . "\r\n";
            $multipartbody .= 'content-disposition: form-data; name="' . $key . "\"\r\n\r\n";
            $multipartbody .= $value . "\r\n";
        }
        foreach ($files as $key => $value) {
            if (!$value) {
                continue;
            }

            if (is_array($value)) {
                $url = $value['url'];
                if (isset($value['name'])) {
                    $filename = $value['name'];
                } else {
                    $parts = explode('?', basename($value['url']));
                    $filename = $parts[0];
                }
                $field = isset($value['field']) ? $value['field'] : $key;
            } else {
                $url = $value;
                $parts = explode('?', basename($url));
                $filename = $parts[0];
                $field = $key;
            }
            try {
                $content = file_get_contents($url);
            } catch (\Exception $e) {
                return _output("url:" . $url . "错误:" . $e->getMessage(), false);
            }


            $multipartbody .= $MPboundary . "\r\n";
            $multipartbody .= 'Content-Disposition: form-data; name="' . $field . '"; filename="' . $filename . '"' . "\r\n";
            $multipartbody .= "Content-Type: image/unknown\r\n\r\n";
            $multipartbody .= $content . "\r\n";
        }

        $multipartbody .= $endMPboundary;
        return _output($multipartbody);
    }
}
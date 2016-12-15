<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 14:09
 */

namespace Tool\Wx;
class WxBasic
{

    public function curl($url, $query = '', $setHeader = ["Expect:"])
    {
        $ch = curl_init();
        //curl_setopt($ch, CURLOP_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);//设置URL
        curl_setopt($ch, CURLOPT_POST, 1);//post
        curl_setopt($ch, CURLOPT_HTTPHEADER, $setHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);//传递一个作为HTTP “POST”操作的所有数据的字符串
        curl_setopt($ch, CURLOPT_HEADER, 1);//返回response头部信息
        curl_setopt($ch, CURLOPT_NOBODY, 0);//不返回response body内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不直接输出response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//如果返回的response 头部中存在Location值，就会递归请求
        $response = curl_exec($ch);

        if (!$response) {
            //$this->error("curl_error",curl_error($ch));
            log_file('curl', "curl错误", $query, curl_error($ch), $url);
            return false;
        }
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            //$header = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
        } else {
            log_file('curl', "非200", $query, $response, $url);
            return false;
        }
        curl_close($ch);//关闭
        return $body;
    }

    public function checkError($res, $type = '', $remarks = '')
    {
        if ($res == false) {
//            error_to_db("请求失败", $type, $remarks);
            _pack($type . "请求失败", false);
        }
        $arr = json_decode($res, true);
        if ($arr == false || !is_array($arr) || (isset($arr['errcode']) && $arr['errcode'] !== 0)) {
//            error_to_db($res, $type, $remarks);
            _pack($type . "失败！ " . $res, false);
        }
        return $arr;
    }

    /**
     *    作用：产生随机字符串，不长于32位
     */
    public function createRandStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function createRandNumber($length = 10)
    {
        $chars = "0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    /*
    * 	作用：格式化参数，签名过程需要使用
    */
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = "";
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     *    作用：array转xml
     */
    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     *    作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        if (!is_string($xml)) {
            return null;
        }
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    public function json_encode_cn($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function mkDirs($dir)
    {
        if (!is_dir($dir)) {
            if (!$this->mkDirs(dirname($dir))) {
                return false;
            }
            if (!mkdir($dir, 0777)) {
                return false;
            }
        }
        return true;
    }

    public function payCurl($xml, $url, $apiclient_cert_config = null, $apiclient_key_config = null)
    {
        if (empty($apiclient_cert_config) || empty($apiclient_key_config)) {
            $apiclient_cert_config = config("myapp.apiclient_cert");
            $apiclient_key_config = config("myapp.apiclient_key");
        }
        if (empty($apiclient_cert_config) || empty($apiclient_key_config)) {
            _pack("缺少支付证书配置文件", false);
        }

        $apiclient_cert_path = base_path($apiclient_cert_config);
        $apiclient_key_path = base_path($apiclient_key_config);
        if (!is_file($apiclient_cert_path) || !is_file($apiclient_key_path)) {
            _pack("缺少支付证书", false);
        }
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        //如果有配置代理这里就设置代理

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $apiclient_cert_path);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $apiclient_key_path);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }

    //支付返回检查
    public function payReturnCheck($res)
    {
        $data['status'] = false;
        if (empty($res)) {
            $data['type'] = '【CURL错误】';
        } else if ($res["return_code"] == "FAIL") {
            $data['type'] = '【通信出错】';
        } elseif ($res["result_code"] == "FAIL") {
            $data['type'] = '【业务出错】';
        } else {
            $data['type'] = '【支付成功】';
            $data['status'] = true;
        }
        return $data;
    }

    public function checkSign($data)
    {
        $tmpData = $data;
        unset($tmpData['sign']);
        $sign = $this->getSign($tmpData);//本地签名
        if ($data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 14:15
 */

namespace Tool\WxQy;

use Tool\Wx\WxBasic;

class WxQyUtil extends WxBasic
{
    public $corpId;
    public $corpSecret;

    public function __construct()
    {
        $this->corpId = config('myapp.corp_id');
        $this->corpSecret = config('myapp.corp_secret');
        if (empty($this->corpId) || empty($this->corpSecret)) {
            $this->corpId = config('myapp.corpId');
            $this->corpSecret = config('myapp.corpSecret');
        }
        if (empty($this->corpId) || empty($this->corpSecret)) {
            _pack("缺少corpId或corpSecret配置！", false);

        }
    }

    public function execute($uri, $type = "", $data = "", $remarks = "")
    {
        //尝试3次
        $error = "";
        for ($i = 0; $i < 3; $i++) {
            $AccessToken = $this->getAccessToken();
            $url = str_replace("ACCESS_TOKEN", $AccessToken, $uri);
            $res = $this->curl($url, $this->json_encode_cn($data));
            if ($res == false) {
                $error = "请求失败";
                continue;
            }
            $arr = json_decode($res, true);
            if ($arr == false || !is_array($arr) || (isset($arr['errcode']) && $arr['errcode'] !== 0)) {
                if (isset($arr['errcode']) && $arr['errcode'] == 40014) {
                    $this->getAccessToken(true);
                }
                $error = $res;
                continue;
            }
            return $arr;
        }
        error_to_db($type, $data, $error, $remarks);
        _pack($type . "错误。请管理员查看日志！" . $error, false);
        return false;
    }

    /**
     * 执行返回
     */
    public function execute_return($uri, $type = "", $data = "", $remarks = "")
    {
        //尝试3次
        $arr = [];
        $url = "";
        for ($i = 0; $i < 3; $i++) {
            $AccessToken = $this->getAccessToken();
            $url = str_replace("ACCESS_TOKEN", $AccessToken, $uri);
            $res = $this->curl($url, $this->json_encode_cn($data));

            if ($res == false) {
                $arr = "请求失败";
                continue;
            }
            $arr = json_decode($res, true);
            if ($arr == false || !is_array($arr) || (isset($arr['errcode']) && $arr['errcode'] !== 0)) {
                if (isset($arr['errcode']) && $arr['errcode'] == 40014) {
                    $this->getAccessToken(true);
                }
                continue;
            }
            return ["success" => true, "res" => $arr, "input" => $data, "curl_url" => $url];
        }
        return ["success" => false, "res" => $arr, "input" => $data, "curl_url" => $url];
    }

    public function getAccessToken($reload = false)
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $log_file_path = config('myapp.log_file_path');
        if (empty($log_file_path)) {
            _pack("找不到log_file_path配置文件", false);
        }
        $path = $log_file_path . "wxCache/qy/access_token.json";
        if ($reload && is_file($path)) {
            $data = json_decode(file_get_contents($path), true);
            if ($data['expire_time'] > time()) {
                return $data['access_token'];
            }
        }
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->corpId&corpsecret=$this->corpSecret";
        $res = $this->curl($url);
        $res = $this->checkError($res, "获得AccessToken");
        $access_token = $res['access_token'];
        $data['expire_time'] = time() + 7000;
        $data['access_token'] = $access_token;
        $this->mkDirs(dirname($path));
        $fp = fopen($path, "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
        return $access_token;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/1
 * Time: 16:16
 */

namespace Tool\Wx;


use Illuminate\Support\Facades\Redis;

class WxToken extends WxBasic
{
    protected $appId;
    protected $appSecret;


    public function __construct($appId = null, $appSecret = null)
    {
        if (!empty($appId) && !empty($appSecret)) {
            $this->appId = $appId;
            $this->appSecret = $appSecret;
        } else {
            $this->appId = config('myapp.appId');
            $this->appSecret = config('myapp.appSecret');
            if (empty($this->appId) || empty($this->appSecret)) {
                _pack("缺少appId或appSecret配置！", false);
            }
        }

    }

    /**
     * 获取请求地址
     */
    public function getRequestUrl($uri, $reload = false)
    {
        if (strpos($uri, 'ACCESS_TOKEN')) {
            $AccessToken = $this->getAccessToken($reload);
            $url = str_replace('ACCESS_TOKEN', $AccessToken, $uri);
        } else if (strpos($uri, 'TOKEN')) {
            $AccessToken = $this->getAccessToken($reload);
            $url = str_replace('TOKEN', $AccessToken, $uri);
        } else {
            $url = $uri;
        }
        return $url;
    }

    /**
     * 检查AccessToken是否有效
     */
    public function checkAccessToken()
    {
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        $data['expire_seconds'] = 100;
        $data['action_name'] = "QR_SCENE";
        $data["action_info"] = "测试";
        $wx = new WxBasic();
        $data['scene_id'] = $wx->createRandNumber(32);
        $res = $wx->curl($url, json_encode($data));
        if ($res == false) {
            log_file('error/tool/invalid_access_token', "检查AccessToken是否有效", $data, $res, "检查AccessToken是否有效,请求失败");
            return false;
        }
        $arr = json_decode($res, true);
        if ($arr == false || !is_array($arr) || (isset($arr['errcode']) && $arr['errcode'] !== 0)) {
            log_file('error/tool/invalid_access_token', "检查AccessToken是否有效", $data, $res, "检查AccessToken无效");
            return false;
        }
        return true;
    }

    /**
     * 是否是无效的AccessToken
     */
    public function isInvalidAccessToken($error_code)
    {
        if ($error_code == 40014 || $error_code == 40001) {
            return true;
        }
        return false;
    }

    /**
     * 获取AccessToken
     */
    public function getAccessToken($reload = false)
    {
        //获取appId
        $access_token = false;
        //是否需要重新获取
        if (!$reload) {
            //获取缓存中的AccessToken
            $access_token = $this->getCacheAccessToken();
        }
        if (!$access_token) {
            //缓存中无token，请求获取AccessToken
            $access_token = $this->requestAccessToken();
            //保存在缓存中
            $this->setCacheAccessToken($access_token);
        }
        return $access_token;
    }

    /**
     * 通过微信获取AccessToken
     */
    private function requestAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
        $wx = new WxBasic();
        $res = $wx->curl($url);
        if ($res == false) {
            log_file('error/access_token', "获取AccessToken", $url, $res, "获取AccessToken请求失败");
            _pack("获取AccessToken请求失败", false);
        }
        $arr = json_decode($res, true);
        if ($arr == false || !is_array($arr) || (isset($arr['errcode']) && $arr['errcode'] !== 0)) {
            log_file('error/access_token', "获取AccessToken", $url, $res, "获取AccessToken解析失败");
            _pack("获取AccessToken解析失败", false);
        }
        return $access_token = $arr['access_token'];
    }

    /*
    * 获取缓存中的AccessToken
    */
    private function getCacheAccessToken()
    {
        $type = config("myapp.cache_type");
        $data = [];
        if ($type == 'redis') {
            $data = json_decode(Redis::get("access_token_" . $this->appId), true);
        } else {
            //默认使用文件
            $log_file_path = config('myapp.log_file_path');
            if (empty($log_file_path)) {
                _pack("找不到log_file_path配置文件", false);
            }
            $path = $log_file_path . "wxCache/" . $this->appId . "/access_token.json";
            if (is_file($path)) {
                $data = json_decode(file_get_contents($path), true);
            }
        }
        if (!empty($data) && !empty($data['expire_time']) && $data['expire_time'] > time() && !empty($data['access_token'])) {
            return $data['access_token'];
        }
        return false;
    }

    /**
     * 获取缓存中的AccessToken
     */
    private function setCacheAccessToken($access_token)
    {
        $type = config("myapp.cache_type");
        $data['expire_time'] = time() + 7000;
        $data['access_token'] = $access_token;
        if ($type == 'redis') {
            Redis::setex("access_token_" . $this->appId, 7000, json_encode($data));
        } else {
            //默认使用文件
            $log_file_path = config('myapp.log_file_path');
            if (empty($log_file_path)) {
                _pack("找不到log_file_path配置文件", false);
            }
            $path = $log_file_path . "wxCache/" . $this->appId . "/access_token.json";
            mkDirs(dirname($path));
            $fp = fopen($path, "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/1
 * Time: 16:16
 */

namespace Tool\WxQy;


use Illuminate\Support\Facades\Redis;
use Tool\Wx\WxBasic;

class WxQyToken extends WxBasic
{
    protected $corp_id;
    protected $corp_secret;

    public function __construct($corp_id = null, $corp_secret = null)
    {
        if (!empty($corp_id) && !empty($corp_secret)) {
            $this->corp_id = $corp_id;
            $this->corp_secret = $corp_secret;
        } else {
            $this->corp_id = config('myapp.corp_id');
            $this->corp_secret = config('myapp.corp_secret');
            if (empty($this->corp_id) || empty($this->corp_secret)) {
                $this->corp_id = config('myapp.corpId');
                $this->corp_secret = config('myapp.corpSecret');
            }
            if (empty($this->corp_id) || empty($this->corp_secret)) {
                _pack("缺少corp_id或corp_secret配置！", false);
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
        //获取corp_id
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
        $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->corp_id&corpsecret=$this->corp_secret";
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
        $data = json_decode(Redis::get("access_token_" . $this->corp_id), true);
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
        $data['expire_time'] = time() + 7000;
        $data['access_token'] = $access_token;
        Redis::setex("access_token_" . $this->corp_id, 7000, json_encode($data));
    }
}
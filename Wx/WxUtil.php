<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/28
 * Time: 15:25
 */

namespace Tool\Wx;


class WxUtil extends WxBasic
{
    public $appId;
    public $appSecret;

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
     * 执行请求记录结果，失败直接返回错误！
     */
    public function execute($uri, $type = "", $data = "", $remarks = "")
    {
        $res = $this->execute_return($uri, $data, false);
        if ($res['success']) {
            return $res['res'];
        } else {
            error_to_db($type, $data, $res['res'], $remarks);
            _pack($type . "错误。请管理员查看日志！" . $res['res'], false);
            return false;
        }
    }

    /**
     * 执行请求返回结果
     */
    public function execute_return($uri, $data = "", $reload = false)
    {
        //获取请求地址
        $url = $this->getRequestUrl($uri, $reload);
        //发放请求
        $res = $this->curl($url, $this->json_encode_cn($data));
        //检查
        return $this->checkData($res, $uri, $data, $reload);
    }

    /**
     * 检查微信返回的数据
     */
    public function checkData($res, $uri, $data, $reload)
    {
        $return = [
            "success" => false,
            "res" => $res,
            "input" => $data,
            "curl_url" => $uri
        ];
        if ($res == false) {
            $return['res'] = "请求失败！";
            return $return;
        }
        $res_arr = json_decode($res, true);
        if ($res_arr == false || !is_array($res_arr) || (isset($res_arr['errcode']) && $res_arr['errcode'] !== 0)) {
            //请求失败
            if (!$reload && isset($res_arr['errcode']) && $res_arr['errcode'] == 40001) {
                //AccessToken错误，不是重新加载的AccessToken则重发
                return $this->execute_return($uri, $data, true);
            } else {
                //重新加载的AccessToken依然错误！！！
                return $return;
            }
        } else {
            $return['success'] = true;
            return $return;
        }
    }

    /**
     * 获取请求地址
     */
    private function getRequestUrl($uri, $reload = false)
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

    public function getAccessToken($reload = false)
    {
        return $this->_getAccessToken($reload);
        $url = ACCESS_TOKEN;
        if ($reload) {
            $url .= "?reload=1";
        }
        return $this->curl($url);
    }

    public function _getAccessToken($reload = false)
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $log_file_path = config('myapp.log_file_path');
        if (empty($log_file_path)) {
            _pack("找不到log_file_path配置文件", false);
        }
        $path = $log_file_path . "wxCache/access_token.json";
        if (!$reload && is_file($path)) {
            $data = json_decode(file_get_contents($path), true);
            if ($data['expire_time'] > time()) {
                return $data['access_token'];
            }
        }

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
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
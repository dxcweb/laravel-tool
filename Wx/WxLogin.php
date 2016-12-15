<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/28
 * Time: 15:23
 */

namespace Tool\Wx;

use Tool\Routing\Url;

class WxLogin extends WxUtil
{

    public function getCode($state = '', $isUserInfo = false, $redirectAction = 'Wx@anyWxGetUserInfo')
    {
        $url_tool = new Url();
        $redirectUrl = $url_tool->action($redirectAction);
        $url = $this->createOauthUrlForCode($redirectUrl, $isUserInfo, $state);
        return $url;
    }

    /**
     * 作用：生产获取CodeURL
     * @param $redirectUrl 授权后重定向的回调链接地址
     * @param bool $isUserInfo 是否获取用户信息。true：弹出授权页面。false：不弹出授权页面，直接跳转，只能获取用户openid
     * @param string $state 重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
     * @return string 授权地址
     */
    protected function createOauthUrlForCode($redirectUrl, $isUserInfo = false, $state = '')
    {
        if ($isUserInfo) {
            $scope = 'snsapi_userinfo';
        } else {
            $scope = 'snsapi_base';
        }
        $urlObj["appid"] = $this->appId;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "$scope";
        $urlObj["state"] = "$state" . "#wechat_redirect";
        $bizString = http_build_query($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     * 作用：重令牌中取出Openid
     */
    public function getOpenid()
    {
        $data = $this->getToken();
        return $data['openid'];
    }

    public function getUserInfo()
    {
        $data = $this->getToken();
        $url = $this->createOauthUrlForUserInfo($data['access_token'], $data['openid']);
        $res = $this->curl($url);
        $res=$this->checkError($res,"获取用户详细信息");
        return $res;
    }
    /**
     *    作用：生成可以获得令牌的url
     */
    protected function createOauthUrlForUserInfo($access_token, $openid, $lang = 'zh_CN')
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = $lang;
        $bizString = http_build_query($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?" . $bizString;
    }

    /**
     *    作用：通过curl向微信提交code，以获取令牌
     */
    public function getToken()
    {
        static $token;
        if (!empty($token)) {
            return $token;
        }
        $code = $_REQUEST['code'];
        $url = $this->createOauthUrlForToken($code);
        $res = $this->curl($url);
        $token = $this->checkError($res, "获取openid中的获取Token");
        return $token;
    }

    /**
     *    作用：生成可以获得令牌的url
     */
    protected function createOauthUrlForToken($code)
    {
        $urlObj["appid"] = $this->appId;
        $urlObj["secret"] = $this->appSecret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = http_build_query($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 16:07
 */

namespace Tool\WxQy;

use Tool\Routing\Url;

/**
 * 用户
 * Class WxOauth
 * @package App\Tool\WxQy
 */
class WxQyLogin extends WxQyExecute
{
    public function getCode($state = '', $redirectAction = 'Wx@anyWxQyGetUserInfo')
    {
        $url_tool = new Url();
        $redirectUrl = $url_tool->action($redirectAction);
        $url = $this->createOauthUrlForCode($redirectUrl, $state);
        return $url;
    }

    public function getUserId()
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=ACCESS_TOKEN&code={$_GET['code']}";
        return $this->execute_return($url);
    }

    public function getUserInfo($user_id)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&userid=$user_id";
        return $this->execute_return($url)['data'];
    }

    public function getOpenId($data)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=ACCESS_TOKEN";
        return $this->execute_return($url, $data)['data'];
    }

    private function createOauthUrlForCode($redirectUrl, $state)
    {
        $urlObj["appid"] = $this->corp_id;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "$state" . "#wechat_redirect";
        $bizString = http_build_query($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }
}
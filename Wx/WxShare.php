<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/28
 * Time: 17:12
 */

namespace Tool\Wx;

use Tool\Routing\Url;

class WxShare extends WxUtil
{
    public function getSignPackage($url)
    {
        $jsapiTicket = $this->getApiTicket("jsapi");
        $timestamp = time();
        $nonceStr = $this->createRandStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * Api_票据
     *
     * @param $type = jsapi || wx_card
     * @return $ticket
     */
    public function getApiTicket($type)
    {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $log_file_path = config('myapp.log_file_path');
        if (empty($log_file_path)) {
            _pack("找不到log_file_path配置文件", false);
        }
        $path = $log_file_path . "wxCache/{$type}_ticket.json";
        if (is_file($path)) {
            $data = json_decode(file_get_contents($path), true);
            if ($data['expire_time'] > time()) {
                return $data['jsapi_ticket'];
            }
        }
        $accessToken = $this->_getAccessToken();
        // 如果是企业号用以下 URL 获取 ticket
        // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=$type&access_token=$accessToken";
        $res = $this->curl($url);
        $res = $this->checkError($res, "获得Api_票据type=$type");
        $ticket = $res['ticket'];
        if ($ticket) {
            $data['expire_time'] = time() + 7000;
            $data['jsapi_ticket'] = $ticket;
            $this->mkDirs(dirname($path));
            $fp = fopen($path, "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
        }
        return $ticket;
    }
}
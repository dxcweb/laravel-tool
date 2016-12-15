<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/11
 * Time: 16:27
 */

namespace Tool\WxQy;


class WxQyShare extends WxQyUtil
{
    public function getSignPackage($url)
    {
        $jsapiTicket = $this->getApiTicket("jsapi");
        $timestamp = time();
        $nonceStr = $this->createRandStr();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->corpId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function getApiTicket($type)
    {
        $log_file_path = config('myapp.log_file_path');
        if (empty($log_file_path)) {
            _pack("找不到log_file_path配置文件", false);
        }
        $path = $log_file_path . "wxCache/qy/{$type}_ticket.json";
        if (is_file($path)) {
            $data = json_decode(file_get_contents($path), true);
            if ($data['expire_time'] > time()) {
                return $data['jsapi_ticket'];
            }
        }
        $accessToken = $this->getAccessToken();
        // 如果是企业号用以下 URL 获取 ticket
        $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=ACCESS_TOKEN";
//        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=$type&access_token=$accessToken";
        $res = $this->execute($url);
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
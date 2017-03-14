<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/1
 * Time: 17:57
 */

namespace Tool\WxQy;


use Illuminate\Support\Facades\Redis;

class WxQyJsapiTicket extends WxQyExecute
{
    /**
     * 获取票据
     */
    public function getTicket()
    {
        $ticket = $this->getCacheTicket();
        if (!$ticket) {
            //缓存中无Ticket，请求获取Ticket
            $ticket = $this->requestTicket();
            if (!$ticket) {
                return false;
            }
            $this->setCacheTicket($ticket);
        }
        return $ticket;
    }

    public function getSignPackage($url)
    {
        $jsapiTicket = $this->getTicket();
        if (!$jsapiTicket) {
            return false;
        }
        $timestamp = time();
        $nonceStr = $this->createRandStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->corp_id,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 获取缓存Ticket
     */
    private function getCacheTicket()
    {
        $data = json_decode(Redis::get('jsapi_ticket' . $this->corp_id), true);
        if (!empty($data) && !empty($data['expire_time']) && $data['expire_time'] > time() && !empty($data['access_token'])) {
            return $data['access_token'];
        }
        return false;
    }

    /**
     * 获取缓存中的AccessToken
     */
    private function setCacheTicket($ticket)
    {
        $data['expire_time'] = time() + 7000;
        $data['ticket'] = $ticket;
        Redis::setex('jsapi_ticket_' . $this->corp_id, 7000, json_encode($data));
    }

    /**
     * 通过微信获取Ticket
     */
    private function requestTicket()
    {
        $uri = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=ACCESS_TOKEN";
        $data = $this->execute_return($uri);
        if ($data['result']) {
            $res = $data['data'];
            return $res['ticket'];
        }
        return false;
    }
}
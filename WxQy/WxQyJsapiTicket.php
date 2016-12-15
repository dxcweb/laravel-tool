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
        $cache_type = config("myapp.cache_type");
        $data = [];
        if ($cache_type == 'redis') {
            $data = json_decode(Redis::get('jsapi_ticket' . $this->corp_id), true);
        } else {
            //默认使用文件
            $log_file_path = config('myapp.log_file_path');
            if (empty($log_file_path)) {
                _pack("找不到log_file_path配置文件", false);
            }
            $path = $log_file_path . "wxCache/" . $this->corp_id . '/' . 'jsapi_ticket.json';
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
    private function setCacheTicket($ticket)
    {
        $cache_type = config("myapp.cache_type");
        $data['expire_time'] = time() + 7000;
        $data['ticket'] = $ticket;
        if ($cache_type == 'redis') {
            Redis::setex('jsapi_ticket_' . $this->corp_id, 7000, json_encode($data));
        } else {
            //默认使用文件
            $log_file_path = config('myapp.log_file_path');
            if (empty($log_file_path)) {
                _pack("找不到log_file_path配置文件", false);
            }
            $path = $log_file_path . "wxCache/" . $this->corp_id . '/' . 'jsapi_ticket.json';
            mkDirs(dirname($path));
            $fp = fopen($path, "w");
            fwrite($fp, json_encode($data));
            fclose($fp);
        }
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
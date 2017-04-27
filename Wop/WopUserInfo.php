<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/3/25
 * Time: 下午11:46
 */

namespace Tool\Wop;


use Tool\Util\Http;

class WopUserInfo
{
    public static function getWxOpenId()
    {
        $user_info = self::getWxUserInfo();
        if (empty($user_info['wx_open_id'])) {
            return null;
        }
        return $user_info['wx_open_id'];
    }

    public static function getWxUserInfo()
    {
        static $user_info;
        if (empty($user_info)) {
            $url = env('WOP_URL');
            if (empty($url)) {
                dd('.env文件中未定义WOP_URL');
            }
            $wop_app_id = env('WOP_APP_ID');
            if (empty($wop_app_id)) {
                dd('.env文件中未定义WOP_APP_ID');
            }
            $cookie_name = env('WOP_COOKIE', 'wop2');
            if (empty($_COOKIE[$cookie_name])) {
                return [];
            }
            $cookie = $_COOKIE[$cookie_name];
//            dd($url . 'wx-base/get-user-info');
            $res = Http::post($url . 'wx-base/get-user-info', ['app_id' => $wop_app_id, 'cookie' => $cookie]);
            if (!$res['result']) {
                return [];
            }
            $data = json_decode($res['data'], true);
            if (!$data['result']) {
                return [];
            }
            $user_info = $data['data'];
        }
        return $user_info;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/4/16
 * Time: 下午10:03
 */

namespace Tool\Wop;


use Tool\Util\Http;

class WopAdmin
{
    public static function getUserInfo()
    {
        static $user_info;
        if (empty($user_info)) {
            $url = env('WOP_URL');
            if (empty($url)) {
                dd('.env文件中未定义WOP_URL');
            }
            $cookie_name = env('WOP_COOKIE', 'wop_admin');
            if (empty($_COOKIE[$cookie_name])) {
                return [];
            }
            $cookie = $_COOKIE[$cookie_name];
            $res = Http::post($url . 'admin/user/me', ['cookie' => $cookie]);
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
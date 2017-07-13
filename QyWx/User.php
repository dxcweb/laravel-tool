<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/7/3
 * Time: 上午10:12
 */

namespace Tool\QyWx;


use Tool\Util\Http;

class User
{
    public static function getUserInfo()
    {
        static $user_info;
        if (empty($user_info)) {
            $url = env('QY_WX_URL');
            if (empty($url)) {
                dd('.env文件中未定义QY_WX_URL');
            }
            $cookie_name = env('QY_WX_COOKIE', 'qy_wx');
            if (empty($_COOKIE[$cookie_name])) {
                return [];
            }
            $cookie = $_COOKIE[$cookie_name];
            $res = Http::post($url . 'login/get-user-info-by-cookie', ['cookie' => $cookie]);
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
<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/29
 * Time: 23:33
 */

namespace Tool\Util;


use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class UserInfo
{
    static public function getMyInfo($user_info = null)
    {
        static $my_info;
        if (!empty($user_info)) {
            $my_info = $user_info;
            return true;
        }
        if (!empty($my_info)) {
            return $my_info;
        }
        $url = config('myapp.wxqy_base_local');
        $cookie = config('myapp.cookie');
        if (empty($url) || empty($_COOKIE[$cookie])) {
            return [];
        }
        $session_id = $_COOKIE[$cookie];
        $res = Http::post($url . 'wx-base/login/get-user-info-by-session-id', ['session_id' => $session_id]);
        $res_arr = json_decode($res['data'], true);
        if (!$res_arr['result']) {
            return [];
        }
        return $my_info = $res_arr['data'];
    }

    static public function getMyId()
    {
        $my_info = self::getMyInfo();
        if (empty($my_info['emp_id'])) {
            return null;
        }
        return $my_info['emp_id'];
    }

    static public function delUserInfo($user_id = null)
    {
        if (empty($user_id)) {
            $user_id = self::getUserId();
        }
        $session_id_key = self::getSessionIdKey($user_id);
        $session_id = Redis::get($session_id_key);
        if (!empty($session_id)) {
            self::getUserIdKey($session_id);
            $user_info_key = self::getUserInfoKey($session_id);
            Redis::del($user_info_key);
            $get_user_id_key = self::getUserIdKey($session_id);
            Redis::del($get_user_id_key);
            Redis::del($session_id_key);
        }
    }

    static public function getUserInfo($user_id = null, $session_id = null)
    {
        if (empty($user_id)) {
            $user_info_key = self::getUserInfoKey($session_id);
        } else {
            $session_id_key = self::getSessionIdKey($user_id);
            $session_id = Redis::get($session_id_key);
            $user_info_key = self::getUserInfoKey($session_id);
        }
        return json_decode(Redis::get($user_info_key), true);
    }

    static public function getUserId($session_id = null)
    {
        $user_id = self::getUserIdKey($session_id);
        return Redis::get($user_id);
    }

    static public function setUserInfo($user_id, $user_info = [], $session_id = null)
    {
        self::delUserInfo($user_id);
        $user_info_key = self::getUserInfoKey($session_id);
        Redis::setex($user_info_key, 86400, json_encode($user_info));
        $session_id_key = self::getSessionIdKey($user_id);
        if (empty($session_id))
            $session_id = Session::getId();
        Redis::setex($session_id_key, 86400, $session_id);
        $get_user_id_key = self::getUserIdKey($session_id);
        Redis::setex($get_user_id_key, 86400, $user_id);
    }

    static private function getUserInfoKey($session_id = null)
    {
        if (empty($session_id)) {
            $session_id = Session::getId();
        }
        $session_prefix = config('myapp.session_prefix');
        return md5($session_prefix . '_' . $session_id . '_user_info');
    }

    static private function getUserIdKey($session_id = null)
    {
        if (empty($session_id)) {
            $session_id = Session::getId();
        }
        $session_prefix = config('myapp.session_prefix');
        return md5($session_prefix . '_' . $session_id . '_user_id');
    }

    static public function getSessionIdKey($user_id)
    {
        $session_prefix = config('myapp.session_prefix');
        return md5($session_prefix . '_' . $user_id . '_user_id');
    }
}
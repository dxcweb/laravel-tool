<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/4/18
 * Time: 上午11:43
 */

namespace Tool\Wop;


use phpseclib\Crypt\AES;
use Tool\Util\Http;

class WopHttp
{
    public static function send($path, $param = [], $error_notice = true)
    {
        $wop_url = env('WOP_URL');
        if (empty($wop_url)) {
            dd('.env中没有设置WOP_URL');
        }
        $url = $wop_url . $path;
        $app_id = env('WOP_APP_ID');
        $app_secret = env('WOP_APP_SECRET');
        if (empty($app_id)) {
            dd('.env中没有设置WOP_URL');
        }
        if (empty($app_secret)) {
            dd('.env中没有设置WOP_APP_SECRET');
        }
        $http_params = [];
        $http_params['app_id'] = $app_id;
        $iv = substr($app_secret, 0, 16);
        $cipher = new AES();
        $cipher->setKey($app_secret);
        $cipher->setIV($iv);
        $data = [];
        $data['rand_str'] = create_guid();
        $data['time_stamp'] = _now();
        $data['param'] = $param;
        $json = json_encode($data);
        $crypt = $cipher->encrypt($json);
        $http_params['encrypt'] = base64_encode($crypt);
        $res = Http::post($url, $http_params, [], [], $error_notice);
        if (!$res['result']) {
            return $res;
        }
        $res = json_decode($res['data'], true);
        if (!$res || !isset($res['result'])) {
            return _output('请求输入格式错误!', false);
        }
        return $res;
    }
}
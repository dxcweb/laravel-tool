<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/4/1
 * Time: 17:02
 */

namespace Tool\WxQy;

use Tool\Util\Http;

class  WxQyExecute extends WxQyToken
{
//    public function execute($uri, $type = "", $data = "", $remarks = "")
//    {
//        $res = $this->execute_return($uri, $data);
//        if (!$res['result']) {
//            $error = $res['msg'];
//            error_to_db($type, $data, $error, "URL:" . $uri);
//            Http::notice("DB", "同步报错！", json_encode_cn($error));
//            _pack($type . "错误。请管理员查看日志！" . json_encode_cn($error), false);
//            return false;
//        } else {
//            return $res['data'];
//        }
//    }

    /**
     * 执行返回
     */
    public function execute_return($uri, $data = "", $reload = false)
    {
        $url = $this->getRequestUrl($uri, $reload);
        $res = $this->curl($url, $this->json_encode_cn($data));
        $check_data = $this->checkData($res);

        if ($check_data['result']) {
//            log_file("log/wxqy/execute_return", "执行返回", $data, $check_data['data'], $uri, "成功！");
            return $check_data;
        } else {
            if ($reload) {
                //重发的
                if ($this->isInvalidAccessToken($check_data['errorcode'])) {
                    $content = "重发后ACCESS_TOKE一样失败！";
                } else {
                    $content = "重发后失败！";
                }
                log_file("error/wxqy/execute_return", "执行返回", $data, $check_data['data'], $uri, $content);
                return $check_data;
            } else {
                //不是重发的
                if ($this->isInvalidAccessToken($check_data['errorcode'])) {
                    //ACCESS_TOKEN错误,重发
                    return $this->execute_return($uri, $data, true);
                } else {
                    //有错误！！！
                    log_file("error/wxqy/execute_return", "执行返回", $data, $check_data['data'], $uri, "失败！");
                    return $check_data;
                }
            }
        }
    }

    /**
     * 检查微信返回的数据
     */
    public function checkData($res)
    {
        if ($res == false) {
            return _output('请求失败!', false, -100);
        }
        $res_arr = json_decode($res, true);
        if ($res_arr == false || !is_array($res_arr) || (isset($res_arr['errcode']) && $res_arr['errcode'] !== 0)) {
            //请求失败
            if (isset($res_arr['errcode'])) {
                return _output($res_arr, false, $res_arr['errcode']);
            }
            return _output($res_arr, false, -100);
        } else {
            return _output($res_arr);
        }
    }
}
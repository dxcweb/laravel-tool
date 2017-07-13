<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 14:58
 */
function _output($data = "", $result = true, $errorcode = 0, $parameter = [])
{
    $res = [];
    $res['result'] = $result;
    $res['data'] = $data;
    if (!$result)
        $res['msg'] = $data;

    $res['errorcode'] = $errorcode;
    foreach ($parameter as $key => $val) {
        $res[$key] = $val;
    }
    return $res;
}

function _outTotal($data, $total)
{
    $res = _output($data);
    $res['total'] = $total;
    return $res;
}

function json_encode_cn($res)
{
    return json_encode($res, JSON_UNESCAPED_UNICODE);
}

function _pack($data = "", $result = true, $errorcode = 0, $parameter = [])
{
    $res = [];
    $res['result'] = $result;
    $res['data'] = $data;
    if (!$result)
        $res['msg'] = $data;

    $res['errorcode'] = $errorcode;
    foreach ($parameter as $key => $val) {
        $res[$key] = $val;
    }
    exit(json_encode($res, JSON_UNESCAPED_UNICODE));
}

function _packTotal($data, $total)
{
    $parameter['total'] = $total;
    _pack($data, true, 0, $parameter);
}

function _getInput($is_filters = false)
{
    return \Tool\Input::getInput($is_filters);
}

function _setData($data)
{
    \Tool\Input::setData($data);
}

function create_guid()
{
    return md5(uniqid(mt_rand(), true));
}

function _now()
{
    static $now;
    if (isset($now))
        return $now;
    return $now = time();
}

function log_file($prefix = 'log_file', $operation_name = '', $request_data = "", $return_data = "", $remarks = "", $content = "")
{

    $path = base_path() . '/storage/logs/' . $prefix . '/' . date("Y_m_d") . '.log';
    mkDirs(dirname($path));

    if (is_string($request_data))
        $request_str = $request_data;
    else
        $request_str = json_encode($request_data, JSON_UNESCAPED_UNICODE);
    if (is_string($return_data))
        $return_src = $return_data;
    else
        $return_src = json_encode($return_data, JSON_UNESCAPED_UNICODE);


    if (empty($_SERVER['REQUEST_URI'])) {
        $_SERVER['REQUEST_URI'] = '';
    }
    $HTTP_HOST = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $HTTP_HOST = $_SERVER['HTTP_HOST'];
    }
    $text[] = "操作名称：$operation_name";
    $text[] = "日期：" . date("Y-m-d H:i:s");
    $text[] = '请求地址：http://' . $HTTP_HOST . $_SERVER["REQUEST_URI"];
    $text[] = 'GET数据：' . urldecode(http_build_query($_GET));
    $text[] = 'POST数据：' . urldecode(http_build_query($_POST));
    $put_data = file_get_contents('php://input', 'r');
    $text[] = 'PUT数据：' . $put_data;
    $text[] = "输入数据：" . $request_str;
    $text[] = "输出数据：" . $return_src;
    $text[] = "备注：" . $remarks;
    $text[] = "内容：" . $content;
    $text[] = '';//多个换行
    $text[] = '';//多个换行
    file_put_contents($path, implode("\n", $text), FILE_APPEND);
}

function mkDirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkDirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}

/**
 * 字符串长度检查
 */
function stringLengthCheck($str, $len)
{
    if (!is_string($str)) {
        return false;
    }
    if (strlen($str) > $len) {
        return false;
    }
    return true;
}

function numericLengthCheck($numeric, $min = -99999999.99, $max = 99999999.99)
{
    if (!isset($numeric) || $numeric == '') {
        return false;
    }
    if (!is_numeric($numeric)) {
        return false;
    }
    if ($numeric > $max || $numeric < $min) {
        return false;
    }
    return true;
}

function print_stack_trace()
{
    $html = "";
    $array = debug_backtrace();
    //print_r($array);//信息很齐全
    unset($array[0]);
    foreach ($array as $row) {
        if (isset($row['file']))
            $html .= "文件：" . $row['file'] . "<br>";
        if (isset($row['line']))
            $html .= "行：" . $row['line'] . "<br>";
        if (isset($row['function']))
            $html .= "方法：" . $row['function'] . "<br>";
        $html .= "<br><br>";
    }
    return $html;
}

function notice($content = "错误！请查看日志！")
{
    $wxqy_notice = config("myapp.wxqy_notice");
    $wx = new \Tool\Wx\WxBasic();
    $data['agent_id'] = config("myapp.error_notice_agent_id");
    $data['content'] = "环境：" . config('myapp.env') . "\n" . $content;
    $wx->curl($wxqy_notice . "service/send-text/to-all", $data);
}

function str_replace_once($needle, $replace, $haystack)
{
    $pos = strpos($haystack, $needle);
    if ($pos === false) {
        // Nothing found
        return $haystack;
    }
    return substr_replace($haystack, $replace, $pos, strlen($needle));
}

function createRandStr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

function createRandNumber($length = 10)
{
    $chars = "0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

function paramsCheck($arr)
{
    $p = _getInput();
    $data = [];
    foreach ($arr as $value) {
        if (array_key_exists($value,$p)) {
            $data[$value] = $p[$value];
        } else {
            if (env('APP_DEBUG')) {
                return _output('缺少' . $value);
            } else {
                return _output('参数错误！');
            }
        }
    }
    return _output($data);
}
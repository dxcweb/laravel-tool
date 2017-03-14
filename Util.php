<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 14:58
 */
function error_to_db($operation_name = '', $request_data = '', $return_data = '', $remarks = '', $connection = 'mysql')
{
//    $t_log = M('error');
    if (is_string($request_data))
        $request_str = $request_data;
    else
        $request_str = json_encode($request_data, JSON_UNESCAPED_UNICODE);

    if (is_string($return_data))
        $return_src = $return_data;
    else
        $return_src = json_encode($return_data, JSON_UNESCAPED_UNICODE);

    $put_data = file_get_contents('php://input', 'r');
    $data['request_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
    $data['operation_name'] = $operation_name;
    $data['remarks'] = $remarks;
    $data['request_data'] = $request_str;
    $data['return_data'] = $return_src;
    $data['get_data'] = http_build_query($_GET);
    $data['post_data'] = http_build_query($_POST);
    $data['put_data'] = $put_data;
    $data['created_date'] = date("Y-m-d H:i:s", _now());
    $data['created_at'] = _now();
    \Tool\DB::connection($connection)->table('error_log')->insert($data);

//    $t_log->add($log_data);
//    $t_log->getLastSql();
}

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

/**
 * @param string $name 只有name则是去该name的值
 * @param string $value
 * @return mixed
 */
function _session($name = '', $value = '')
{
    $session_prefix = config('myapp.session_prefix');
    if ($name == '') {
        return \Illuminate\Support\Facades\Session::all();
    } else if ($value == '') {
        $res = \Illuminate\Support\Facades\Session::get($session_prefix . $name);
        $arr = json_decode($res, true);
        if ($res == '[]') {
            return [];
        } else if ($arr) {
            return $arr;
        } else {
            return $res;
        }
    } else {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        \Illuminate\Support\Facades\Session::put($session_prefix . $name, $value);
        return $value;
    }
}

function _unsetSession($name)
{
    $session_prefix = config('myapp.session_prefix');
    \Illuminate\Support\Facades\Session::forget($session_prefix . $name);
}


function _getInput($is_filters = false)
{
    return \Tool\Input::getInput($is_filters);
}

function _setData($data)
{
    \Tool\Input::setData($data);
}

function get_original_data()
{
    static $_PUT = null;
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $input = $_POST;
            break;
        case 'PUT':
            if (is_null($_PUT)) {
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input = $_PUT;
            break;
        default:
            $input = $_GET;
    }
    if (isset($input['data'])) {
        $data = json_decode($input['data'], true);

    }
    if (!isset($data)) {
        $data = $input;
    }
    return $data;
}

function check_original_data($data)
{
    if (!$data || !isset($data['token'])) {
        _pack("无数据！", false);
    }
    $token = $data['token'];
    $data['dxc_key'] = '6863e2f52513a3d71d8391b50150de35';
    unset($data['token']);
    if (md5(json_encode($data, JSON_UNESCAPED_UNICODE)) == $token) {
        unset($data['dxc_key']);
        return $data;
    }
    _pack("非法篡改！", false);
}

function data_filters($data)
{
    $filters = 'addslashes,htmlspecialchars';
    if ($filters) {
        if (is_string($filters)) {
            if (0 === strpos($filters, '/')) {
                if (1 !== preg_match($filters, (string)$data)) {
                    // 支持正则验证
                    return isset($default) ? $default : null;
                }
            } else {
                $filters = explode(',', $filters);
            }
        } elseif (is_int($filters)) {
            $filters = array($filters);
        }

        if (is_array($filters)) {
            foreach ($filters as $filter) {
                if (function_exists($filter)) {
                    $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return isset($default) ? $default : null;
                    }
                }
            }
        }
    }
    return $data;
}

function _getUserInfo()
{
    $user_info_key = _getUserInfoKey();
    return \Illuminate\Support\Facades\Redis::get($user_info_key);
}


function _setUserInfo($user_id, $user_info)
{
    $user_info_key = _getUserInfoKey();
    $session_id_key = _getSessionIdKey($user_id);
    \Illuminate\Support\Facades\Redis::setex($user_info_key, 86400, json_encode($user_info));
    \Illuminate\Support\Facades\Redis::setex($session_id_key, 86400, $user_id);
}

function _getUserInfoKey()
{
    $session_prefix = config('myapp.session_prefix');
    $session_id = \Illuminate\Support\Facades\Session::getId();
    return md5($session_prefix . '_' . $session_id . '_user_info');
}

function _getUserIdKey()
{
    $session_prefix = config('myapp.session_prefix');
    $session_id = \Illuminate\Support\Facades\Session::getId();
    return md5($session_prefix . '_' . $session_id . '_user_id');
}

function _getSessionIdKey($user_id)
{
    $session_prefix = config('myapp.session_prefix');
    return md5($session_prefix . '_' . $user_id . '_user_id');
}

function _userInfo($arr = null)
{
    if (empty($arr)) {
        $user_info = _session('user_info');
        return $user_info;
    } else {
        _session('user_info', $arr);
        return $arr;
    }
}

function _userID($id = null)
{
    if (isset($id) && $id != "") {
        _session('user_id', $id);
        return $id;
    } else {
        static $user_id;
        if (isset($user_id)) {
            return $user_id;
        }
        $user_id = _session('user_id');
        return $user_id;
    }
}

function _delUserID()
{
    _unsetSession('user_id');
}

/**
 * erp pc端用
 */
function _erpUserInfo($user = null)
{
    if (isset($user) && $user != "") {
        if (!is_array($user)) {
            _pack("保存UserInfo错误！", false);
            return false;
        }
        foreach ($user as $key => $val) {
            _erpSession($key, $val);
        }
        return $user;
    } else {
        static $user_info;
        if (isset($user_info)) {
            return $user_info;
        }
        return $user_info = _erpSession();
    }
}

/**
 * erp专用
 */
function _erpUserID($id = null)
{
    if (isset($id) && $id != "") {
        _erpSession('emp_id', $id);
        return $id;
    } else {
        static $user_id;
        if (isset($user_id)) {
            return $user_id;
        }
        return $user_id = _erpSession('emp_id');
    }
}

/**
 * erp专用
 */
function _erpSession($name = '', $value = '')
{
    if ($name == '') {
        $data = Fserp\Utils\SessionAPI::getAll();
        return $data;
    } else if ($value == '') {
        $data = Fserp\Utils\SessionAPI::get($name);
        return $data;
    } else {
        Fserp\Utils\SessionAPI::set($name, $value);
        return $value;
    }
}


function _getWxUserInfo($app_id)
{
    return \Illuminate\Support\Facades\Session::get($app_id);
}

function _setWxUserInfo($app_id, $user)
{
    \Illuminate\Support\Facades\Session::put($app_id, $user);
}

function _delWxUserInfo($app_id)
{
    \Illuminate\Support\Facades\Session::forget($app_id);
}

function _getWxQyUserInfo()
{
    return \Illuminate\Support\Facades\Session::get('wxqy-user-info');
}

function _setWxQyUserInfo($user)
{
    \Illuminate\Support\Facades\Session::put('wxqy-user-info', $user);
}

function _delWxQyUserInfo()
{
    \Illuminate\Support\Facades\Session::forget('wxqy-user-info');
}

function array_map_recursive($filter, $data)
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
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
    $text[] = "操作名称：$operation_name";
    $text[] = "日期：" . date("Y-m-d H:i:s");
    $text[] = '请求地址：http://' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
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
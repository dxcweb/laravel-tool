<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/6/13
 * Time: 10:59
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Tool\Util\UserInfo;

class WxQyAuth
{
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle($request, Closure $next)
    {
        $user_info = UserInfo::getMyInfo();
        if (empty($user_info)) {
            return _output("没有登录！", false, 1);
        }
        $action = $this->getCurrentRoute();
        if (empty($action['key'])) {
            return _output("没有配置功能节点项key！", false, 2);
        }
        $special = $this->special($user_info['emp_id']);
        if ($special) {
            $user_info['dept_id'] = $special;
            $user_info['permission_key'][] = 'SPECIAL';
            UserInfo::getMyInfo($user_info);
            return $next($request);
        }
        if (is_string($action['key'])) {
            if (in_array($action['key'], $user_info['permission_key'])) {
                return $next($request);
            }
        } else {
            foreach ($action['key'] as $value) {
                if (in_array($value, $user_info['permission_key'])) {
                    return $next($request);
                }
            }
        }
        return _output("没有权限！", false, 2);
    }

    protected function getCurrentRoute()
    {
        $method = $this->getMethod();
        $pathInfo = $this->getPathInfo();
        $routes = app()->getRoutes();
        $action = $routes[$method . $pathInfo]['action'];
        return $action;
    }

    public function special($user_id)
    {
        $special = config("myapp.special");
        if (empty($special)) {
            return false;
        }
        foreach ($special as $key => $val) {
            if (in_array($user_id, $val)) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Get the current HTTP request method.
     *
     * @return string
     */
    protected function getMethod()
    {
        if (isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        } else {
            return $_SERVER['REQUEST_METHOD'];
        }
    }

    /**
     * Get the current HTTP path info.
     *
     * @return string
     */
    protected function getPathInfo()
    {
        $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        $uri = $_SERVER['REQUEST_URI'];
        $script_path = dirname($_SERVER['SCRIPT_NAME']);
        $uri = str_replace_once('?' . $query, '', $uri);
        $path_info = '/' . trim(str_replace_once($script_path, '', $uri), '/');
        return $path_info;
    }
}
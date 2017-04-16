<?php

namespace Tool\Wop;

use Closure;

/**
 * $app->group(['middleware' => 'WopAdminAuth'], function ($app) {
 *
 * });
 */
class WopAdminAuth
{
    public function permission($permission_key, $permission_value)
    {
        switch ($permission_key) {
            case 'xxx':
                if ($permission_value == 100) {
                    return false;
                }
                break;
        }
        return true;
    }

    public function handle($request, Closure $next)
    {
        $user_info = WopAdmin::getUserInfo();
        if (empty($user_info)) {
            return _output("没有登录！", false, 1);
        }
        $action = $request->route();
        if (empty($action[1]) || empty($action[1]['key'])) {
            return $next($request);
        } else {
            $permission_key = $action[1]['key'];
        }
        if (is_string($permission_key)) {
            if (isset($user_info['permission'][$permission_key])) {
                if ($this->permission($permission_key, $user_info['permission'][$permission_key])) {
                    return $next($request);
                }
            }
        } else {
            foreach ($permission_key as $value) {
                if (isset($user_info['permission'][$value])) {
                    if ($this->permission($value, $user_info['permission'][$value])) {
                        return $next($request);
                    }
                }
            }
        }
        return _output("没有权限！", false, 2);
    }
}

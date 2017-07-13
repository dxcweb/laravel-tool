<?php
/**
 * Created by PhpStorm.
 * User: guowei
 * Date: 17/7/3
 * Time: 上午10:12
 */

namespace Tool\QyWx;
use Closure;

/*
 $app->routeMiddleware([
    'QyWxAuth' => \Tool\QyWx\Auth::class,
]);
$app->group(['namespace' => 'App\Http\Controllers\Wx','prefix' => 'wx' ,'middleware' => 'QyWxAuth'], function ($app) {
    require __DIR__.'/../routes/wx.php';
});
 */
class Auth
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
        $user_info = User::getUserInfo();
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
            if (array_key_exists($permission_key,$user_info['tag'])) {
                if ($this->permission($permission_key, $user_info['tag'][$permission_key])) {
                    return $next($request);
                }
            }
        } else {
            foreach ($permission_key as $value) {
                if (array_key_exists($value,$user_info['tag'])) {
                    if ($this->permission($value, $user_info['tag'][$value])) {
                        return $next($request);
                    }
                }
            }
        }
        return _output("没有权限！", false, 2);
    }
}
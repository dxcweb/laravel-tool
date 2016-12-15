<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2015/12/25
 * Time: 15:10
 */

namespace Tool\Routing;

use Laravel\Lumen\Application;

class Url
{
    private $urlGenerator;
    public function __construct()
    {
        $this->urlGenerator=new \Laravel\Lumen\Routing\UrlGenerator(app());
    }

    /**
     * 获得当前请求的全部网址。
     * @return string
     */
    public function full()
    {
        return $this->urlGenerator->full();
    }

    /**
     * 为先前的请求得到的网址。
     * @return string
     */
    public function current()
    {
        return  $this->urlGenerator->current();
    }

    /**
     * 为先前的请求得到的网址。
     * @return string
     */
    public function previous()
    {
        return  $this->urlGenerator->previous();
    }

    /**
     * 为给定相对路径生成一个绝对地址。
     * @param $path
     * @param array $extra
     * @param null $secure
     * @return string
     */
    public function to($path="", $extra = [], $secure = null)
    {
        return  $this->urlGenerator->to($path, $extra, $secure);
    }

    /**
     * 获得一个action的网址。如（HomeController@anyIndex）
     * @param $action
     * @param array $parameters
     * @param bool|true $absolute
     * @return string
     */
    public function action($action, $parameters = [], $absolute = true)
    {
        return $this->urlGenerator->route($action, $parameters, $absolute);
    }
}
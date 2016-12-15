<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/4/22
 * Time: 14:50
 */

namespace Tool\Util;


class GetBrowserInfo
{
    private $user_agent;

    public function __construct($user_agent = null)
    {
        if (empty($user_agent)) {
            $this->user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        } else {
            $this->user_agent = strtolower($user_agent);
        }
    }

    public function run()
    {
        $browser = $this->fsIos();
        if ($browser)
            return $browser;

        $browser = $this->fsAndroid();
        if ($browser)
            return $browser;

        $browser = $this->weChat();
        if ($browser)
            return $browser;

        $browser = $this->mobileQQ();
        if ($browser)
            return $browser;

        $browser = $this->pcQQ();
        if ($browser)
            return $browser;


        $browser = $this->mobileUC();
        if ($browser)
            return $browser;

        $browser = $this->edge();
        if ($browser)
            return $browser;

        $browser = $this->opera();
        if ($browser)
            return $browser;


        $browser = $this->chrome();
        if ($browser)
            return $browser;

        $browser = $this->safari();
        if ($browser)
            return $browser;

        $browser = $this->ie11();
        if ($browser)
            return $browser;
        log_file('browser', '未知的浏览器', $this->user_agent);
        notice("未知的浏览器\n日志：browser");
        return [
            "name" => "未知",
            "version" => "未知",
        ];
    }

    private function ie11()
    {
        $user_agent = $this->user_agent;
        preg_match('/rv:([\S]+)/', $user_agent, $data);
        if ($data) {
            $browser = [];
            $browser['name'] = 'ie';
            $browser['version'] = $data[1];
            return $browser;
        } else {
            return false;
        }
    }

    private function ie()
    {
        $user_agent = $this->user_agent;
        preg_match('/msie ([\S]+)/', $user_agent, $data);
        if ($data) {
            $browser = [];
            $browser['name'] = 'ie';
            $browser['version'] = $data[1];
            return $browser;
        } else {
            return false;
        }
    }

    private function edge()
    {
        return $this->commonGetBrowserInfo('edge', 'edge');
    }

    private function safari()
    {
        return $this->commonGetBrowserInfo('safari', 'safari');
    }

    private function opera()
    {
        return $this->commonGetBrowserInfo('opr', 'opera');
    }

    private function mobileUC()
    {
        return $this->commonGetBrowserInfo('ucbrowser', 'mobileUC');
    }

    private function weChat()
    {
        return $this->commonGetBrowserInfo('micromessenger', 'wechat');
    }

    private function mobileQQ()
    {
        return $this->commonGetBrowserInfo('mqqbrowser', 'mobileQQ');
    }

    private function pcQQ()
    {
        return $this->commonGetBrowserInfo('qqbrowser', 'pcQQ');
    }

    private function fsIos()
    {
        return $this->commonGetBrowserInfo('fsios', 'FSiOS');
    }

    private function fsAndroid()
    {
        return $this->commonGetBrowserInfo('fsandroid', 'FSAndroid');
    }

    private function chrome()
    {
        return $this->commonGetBrowserInfo('chrome', 'chrome');
    }

    private function commonGetBrowserInfo($key, $name)
    {
        $user_agent = $this->user_agent;
        preg_match('/' . $key . '\/([\S]+)/', $user_agent, $data);
        if ($data) {
            $browser = [];
            $browser['name'] = $name;
            $browser['version'] = $data[1];
            return $browser;
        } else {
            return false;
        }
    }
}
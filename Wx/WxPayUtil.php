<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/22
 * Time: 16:40
 */

namespace Tool\Wx;


class WxPayUtil extends WxExecute
{
    protected $mchId;
    protected $payKey;
    protected $apiclientCert;
    protected $apiclientKey;

    public function __construct($mchId = null, $payKey = null, $appId = null, $appSecret = null, $apiclientCert = null, $apiclientKey = null)
    {
        if (!empty($payKey)) {
            $this->payKey = $payKey;
        } else {
            $this->payKey = config('myapp.payKey');
            if (empty($this->payKey)) {
                _pack("缺少payKey配置！", false);
            }
        }
        if (!empty($mchId)) {
            $this->mchId = $mchId;
        } else {
            $this->mchId = config('myapp.mchId');
            if (empty($this->mchId)) {
                _pack("缺少mchId配置！", false);
            }
        }
        $this->apiclientCert = $apiclientCert;
        $this->apiclientKey = $apiclientKey;
        parent::__construct($appId, $appSecret);
    }

    /**
     *    作用：生成签名
     */
    public function getSign($arr)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->payKey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams($arr)
    {
        $buff = "";
        foreach ($arr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}
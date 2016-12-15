<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/3/17
 * Time: 0:00
 */

namespace Tool\WxQy;


class WxQyPayUtil extends WxQyExecute
{
    public $mchId;
    protected $payKey;

    public function __construct()
    {
        $this->mchId = config('myapp.mchId');
        if (empty($this->mchId)) {
            _pack("缺少mchId配置！", false);
        }
        $this->payKey = config('myapp.payKey');
        if (empty($this->payKey)) {
            _pack("缺少payKey配置！", false);
        }
        parent::__construct();
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

    /**
     * @return string 商户订单号 商户订单号（每个订单号必须唯一）
     * 组成：mch_id+yyyymmdd+10位一天内不能重复的数字。
     */
    public function mchBillNo()
    {
        return $this->mchId . date("Ymd") . $this->createRandNumber();
    }

    /**
     * userid转换成openid接口
     */
    public function getOpenid($user_id, $agent_id = null)
    {
        $data['userid'] = $user_id;
        if (!empty($agent_id))
            $data['agentid'] = $agent_id;
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=ACCESS_TOKEN";
        $res = $this->execute_return($url, $data);
        return $res;
    }
}
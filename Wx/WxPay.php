<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/22
 * Time: 16:50
 */

namespace Tool\Wx;


class WxPay extends WxPayUtil
{
    //统一订单
    public function unifiedOrder($data)
    {
        $res = $this->checkOrderData($data);
        if (!$res['result']) {
            return $res;
        }
        $data = array_merge($this->orderBaseData(), $res['data']);
        $data['sign'] = $this->getSign($data);
        $xml = $this->arrayToXml($data);
        $res = $this->curl('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $res_arr = $this->xmlToArray($res);
        if ($res_arr['return_code'] != 'SUCCESS') {
            log_file('WxPay/unifiedOrder', "统一订单", $data, $res_arr);
            return _output($res_arr['return_msg'], false);
        } else {
            $res_arr['out_trade_no'] = $data['out_trade_no'];
            return _output($res_arr);
        }
    }

    //获取JS签名包
    public function getJsPaySignPackage($prepay_id)
    {
        $sign_package = [];
        $sign_package['appId'] = $this->appId;
        $time = _now();
        $sign_package['timeStamp'] = "$time";
        $sign_package['nonceStr'] = $this->createRandStr();
        $sign_package['package'] = "prepay_id=" . $prepay_id;
        $sign_package['signType'] = 'MD5';
        $sign_package['paySign'] = $this->getSign($sign_package);
        $sign_package['timestamp']=$sign_package['timeStamp'];
        unset($sign_package['appId']);
        unset($sign_package['timeStamp']);
        return $sign_package;
    }

    private function checkOrderData($data)
    {
        if (empty($data['body'])) {
            return _output("缺少，商品或支付单简要描述body", false);
        }
        if (!stringLengthCheck($data['body'], 128)) {
            return _output("超长，商品或支付单简要描述body", false);
        }
        if (empty($data['detail'])) {
            return _output("缺少，商品名称明细列表detail", false);
        }
        if (!stringLengthCheck($data['detail'], 8192)) {
            return _output("超长，商品名称明细列表detail", false);
        }
        if (empty($data['total_fee'])) {
            return _output("缺少，订单总金额total_fee", false);
        }
        if (empty($data['trade_type'])) {
            return _output("缺少，交易类型trade_type,[JSAPI，NATIVE，APP]", false);
        }
        if (empty($data['notify_url'])) {
            return _output("缺少，回调地址notify_url", false);
        }
        if (empty($data['openid'])) {
            return _output("缺少，openid", false);
        }
        if (empty($data['attach']))
        {
            $data['attach']="";
        }
        $res = [];
        $res['openid'] = $data['openid'];
        $res['notify_url'] = $data['notify_url'];
        $res['trade_type'] = $data['trade_type'];
        $res['body'] = $data['body'];
        $res['detail'] = $data['detail'];
        $res['attach'] = $data['attach'];
        $res['total_fee'] = $data['total_fee'] * 100;
        return _output($res);
    }

    private function orderBaseData()
    {
        $data['appid'] = $this->appId;
        $data['mch_id'] = $this->mchId;
        $data['device_info'] = 'web';
        $data['nonce_str'] = $this->createRandStr();
        $data['out_trade_no'] = $this->createRandStr();
        $data['fee_type'] = 'CNY';
        $data['spbill_create_ip'] = '127.0.0.1';
        $data['time_start'] = date("YmdHis");
        $data['time_expire'] = date("YmdHis", time() + 600);
        return $data;
    }


}
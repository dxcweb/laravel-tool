<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/5/25
 * Time: 12:46
 */

namespace Tool\Wx;


class WxPayNotify extends WxPayOrder
{
    //获得需要返回的数据
    public function getReturn($data)
    {
        $res = $this->checkNotify($data);
        if (!$res['result']) {
            $data['return_code'] = "FAIL";
            $data['return_msg'] = $res['msg'];
            return _output($this->arrayToXml($data), false);
        } else {
            $data['return_code'] = "SUCCESS";
            $data['return_msg'] = "OK";
            return _output($this->arrayToXml($data));
        }
    }

    private function checkNotify($data)
    {
        if (empty($data['sign'])) {
            return _output("签名错误", false, 3);
        }
        if ($this->getSign($data) != $data['sign']) {
            return _output("签名错误", false, 3);
        }
        if (empty($data['transaction_id'])) {
            return _output("输入参数不正确", false, 3);
        }

        $res = $this->queryOrder($data['transaction_id']);
        if (!$res['result']) {
            return $res;
        }
        $order = $res['data'];
        //查询订单，判断订单真实性
        $res = $this->checkOrder($order, $data);
        if (!$res['result']) {
            log_file("error/wx/WxPayNotify", "判断订单真实性", $data, $order, $res['msg']);
            notice("微信支付回调,判断订单真实性，发现不一致！{$res['msg']}\n日志：error/WxPayNotify");
            return $res;
        }
        return _output();
    }

    //检查订单
    private function checkOrder($order, $data)
    {
        if ($order['trade_state'] != 'SUCCESS') {
            return _output("支付未成功!", false);
        }
        if ($order['total_fee'] != $data['total_fee']) {
            return _output("金额不对!", false);
        }
        if ($order['fee_type'] != $data['fee_type']) {
            return _output("货币种类不对!", false);
        }
        if ($order['fee_type'] != $data['fee_type']) {
            return _output("货币种类不对!", false);
        }
        return _output();
    }

}
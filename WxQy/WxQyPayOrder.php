<?php
/**
 * Created by PhpStorm.
 * User: dxc
 * Date: 2016/6/15
 * Time: 14:17
 */

namespace Tool\WxQy;


class WxQyPayOrder extends WxQyPayUtil
{
    /**
     *  查询订单输入（$transaction_id||$out_trade_no二选一）
     */
    public function queryOrder($transaction_id = null, $out_trade_no = null)
    {
        if (!empty($transaction_id)) {
            $data['transaction_id'] = $transaction_id;
        } else if (!empty($out_trade_no)) {
            $data['out_trade_no'] = $out_trade_no;
        } else {
            return _output("参数错误！", false);
        }
        $data['appid'] = $this->corp_id;
        $data['mch_id'] = $this->mchId;
        $data['nonce_str'] = $this->createRandStr();
        $data['sign'] = $this->getSign($data);
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $res_xml = $this->curl($url, $this->arrayToXml($data));
        $res = $this->xmlToArray($res_xml);
        if ($res['result_code'] != 'SUCCESS') {
            return _output($res['err_code_des'], false);
        }
        return _output($res);
    }
}
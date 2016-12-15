<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/3/16
 * Time: 23:55
 */

namespace Tool\WxQy;


class WxQyRedPack extends WxQyPayUtil
{
    /**
     * 发送普通红包
     * send_name 商户名称 String(32)
     * re_openid
     * total_amount 付款金额(注：单位元)
     * wishing 红包祝福语 String(128)
     * act_name 活动名称 String(32)
     * remark 备注 String(256)
     */
    public function sendRedPack($data,$log_remarks="")
    {
        $this->sendRedPackCheckData($data);
        $data['nonce_str'] = $this->createRandStr();//随机字符串
        $data['mch_billno'] = $this->mchBillNo();
        $data['mch_id'] = $this->mchId;
        $data['total_num']=1;//红包发放总人数
        $data['client_ip']="127.0.0.1";//调用接口的机器Ip地址
        $data['sign'] = $this->getSign($data);
        $url='https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $xml=$this->arrayToXml($data);
        $res=$this->payCurl($xml,$url);
        $res_arr=$this->xmlToArray($res);
        $result=$this->payReturnCheck($res_arr);
        if($result['status'])
        {
            log_file('sendRedPack','发送普通红包',$data,$res_arr,$log_remarks,"成功");
            return true;
        }else
        {
            log_file('sendRedPack','发送普通红包',$data,$res_arr,$log_remarks,"失败");
            return false;
        }
    }

    /**
     * 发送普通红包数据检查
     */
    private function sendRedPackCheckData(&$data)
    {
        if (!stringLengthCheck($data['send_name'], 32)) {
            _pack("send_name参数错误！", false);
        }
        if (!stringLengthCheck($data['re_openid'], 32)) {
            _pack("re_openid参数错误！", false);
        }
        if (!stringLengthCheck($data['wxappid'], 32)) {
            _pack("wxappid参数错误！", false);
        }
        if (!numericLengthCheck($data['total_amount'], 1, 200)) {
            _pack("total_amount参数错误！", false);
        } else {
            $data['total_amount'] = $data['total_amount'] * 100;
        }
        if (!stringLengthCheck($data['wishing'], 128)) {
            _pack("wishing参数错误！", false);
        }
        if (!stringLengthCheck($data['act_name'], 32)) {
            _pack("act_name参数错误！", false);
        }
        if (!stringLengthCheck($data['remark'], 256)) {
            _pack("remark！", false);
        }
    }


}
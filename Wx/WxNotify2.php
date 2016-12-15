<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/4
 * Time: 13:53
 */

namespace Tool\Wx;


class WxNotify2 extends WxUtil
{
    public function getPayUrl($data)
    {
        $data['appid']=config("myapp.corpId");
        $data['mch_id']=config("myapp.mchId");
        $data['spbill_create_ip']=$_SERVER['REMOTE_ADDR'];
        $data['nonce_str']=$this->createRandStr();
        $data['sign']=$this->getSign($data);
        $xml=$this->arrayToXml($data);
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $res=$this->payCurl($xml,$url);
        $data=json_decode($res,true);
        return $data['code_url'];
    }
}
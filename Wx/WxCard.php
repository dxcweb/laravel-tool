<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/2/5
 * Time: 20:05
 */

namespace Tool\Wx;

//微信卡卷
class WxCard extends WxBasic
{
    public function cardExt($card_id,$code)
    {
        $wx=new WxShare();
        $apiTicket=$wx->getApiTicket('wx_card');
        $time=time();
        $data['api_ticket']=$apiTicket;
        $data['timestamp']="$time";
        $data['card_id']=$card_id;
        $data['code']=$code;
//        $data['openid']='';
        $data['nonce_str']=$wx->createRandStr();
        $data['signature']=$this->codeSign($data);
        unset($data['api_ticket']);
        unset($data['card_id']);
        return $wx->json_encode_cn($data);
    }
    public function codeSign($data)
    {
        $res = [];
        foreach ($data as $val)
        {
            $res[]=$val;
        }
        sort($res);
        $src=implode('',$res);
        return sha1($src);
    }

}
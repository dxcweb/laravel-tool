<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/5
 * Time: 12:41
 */

namespace Tool\WxQy;


class WxQySend extends WxQyUtil
{
    public function sendText($content,$agentid,$touser=null)
    {
        if($touser!=null)
        {
            $data['touser']=$touser;
        }else
        {
            $data['touser']="@all";
        }
        $data['msgtype']="text";
        $data['agentid']=$agentid;
        $data['safe']=0;
        $data['text']['content']=$content;
        $url="https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=ACCESS_TOKEN";
        $this->execute($url,"发送Text请求",$data);
    }
    public function news($articles,$agentid,$touser)
    {
        $data['touser']=$touser;
        $data['msgtype']="news";
        $data['agentid']=$agentid;
        $data['news']['articles'][]=$articles;
        $url="https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=ACCESS_TOKEN";
        $this->execute2($url,"发送news请求",$data);
    }
}
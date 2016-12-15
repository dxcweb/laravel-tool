<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/7
 * Time: 10:52
 */

namespace Tool\WxQy;

class WxQyTag extends WxQyExecute
{
    public function create($data)
    {
        $url='https://qyapi.weixin.qq.com/cgi-bin/tag/create?access_token=ACCESS_TOKEN';
        $this->execute($url,"创建标签",$data);
    }
    public function createGetId($name)
    {
        $data['tagname']=$name;
        $url='https://qyapi.weixin.qq.com/cgi-bin/tag/create?access_token=ACCESS_TOKEN';
        return $this->execute($url,"创建标签",$data)['tagid'];
    }
    public function addTagUsers($data)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/tag/addtagusers?access_token=ACCESS_TOKEN";
        return $this->execute($url,"增加标签成员",$data);
    }
    public function get($tagid)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/tag/get?access_token=ACCESS_TOKEN&tagid=$tagid";
        return $this->execute($url,"查询标签");
    }
    public function delTagUsers($data)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/tag/deltagusers?access_token=ACCESS_TOKEN";
        return $this->execute($url,"删除标签成员",$data);
    }
    public function delTag($tag_id)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/tag/delete?access_token=ACCESS_TOKEN&tagid=".$tag_id;
        return $this->execute($url,"删除标签");
    }
}
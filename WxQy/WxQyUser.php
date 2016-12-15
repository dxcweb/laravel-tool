<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/6
 * Time: 18:47
 */

namespace Tool\WxQy;


class WxQyUser extends WxQyExecute
{
    public function create($data)
    {
        $new_data = [];
        $new_data['userid'] = $data['emp_id'];
        $new_data['name'] = $data['emp_name'];
        $new_data['department'] = $data['dept_id'];
        $new_data['mobile'] = $data['mobile'];
        $url="https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token=ACCESS_TOKEN";
        return $this->execute($url,"创建用户",$new_data);
    }
    public function update($data)
    {
        $new_data = [];
        $new_data['userid'] = $data['emp_id'];
        $new_data['name'] = $data['emp_name'];
        $new_data['department'] = $data['dept_id'];
        $new_data['mobile'] = $data['mobile'];
        $url="https://qyapi.weixin.qq.com/cgi-bin/user/update?access_token=ACCESS_TOKEN";
        return $this->execute($url,"更新用户",$new_data);
    }
    public function del($id)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token=ACCESS_TOKEN&userid=$id";
        return $this->execute($url,"删除用户");
    }
    public function getUser($user_id)
    {
        $url="https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&userid=$user_id";
        return $this->execute_return($url,"获取成员");
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 单线程
 * Date: 2016/1/6
 * Time: 12:56
 */

namespace Tool\WxQy;


class WxQyDepartment extends WxQyExecute
{
    public function getList($id)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=ACCESS_TOKEN&id=$id";
        return $this->execute($url, "查询部门列表");
    }

    public function create($data)
    {
        $wx_data['id'] = $data['dept_id'];
        $wx_data['name'] = $data['dept_name'];
        $wx_data['parentid'] = $data['dept_parent_id'];
        $url = "https://qyapi.weixin.qq.com/cgi-bin/department/create?access_token=ACCESS_TOKEN";
        return $this->execute($url, "创建部门", $wx_data);
    }

    public function update($data)
    {
        $wx_data['id'] = $data['dept_id'];
        $wx_data['name'] = $data['dept_name'];
        $wx_data['parentid'] = $data['dept_parent_id'];
        $url = "https://qyapi.weixin.qq.com/cgi-bin/department/update?access_token=ACCESS_TOKEN";
        return $this->execute($url, "更新部门", $wx_data);
    }

    public function del($id)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/department/delete?access_token=ACCESS_TOKEN&id=$id";
        return $this->execute($url, "删除部门");
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: xiaowenzi
 * Date: 2019/3/20
 * Time: 21:39
 */

namespace app\student\model;

use think\Session;
class Index extends MyModel
{
    protected $name = 'student';

    /**
     * 得到姓名
     * @param $id
     * @return mixed
     */
    public function getSName($id)
    {
        $name = $this->where('id',$id)->value('name');
        return $name;
    }

    /**
     * 得到基本信息
     * @param $id
     * @return array|object
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($id)
    {
        $info = $this->where('id',$id)->find();
        $result = $info->data;
        return $result;
    }

    /**
     * @param $id
     * @param $param
     * @return Index
     */
    public function updateInfo($id, $param)
    {
       //TODO 更新个人信息数据库数据
        $data = [];
        foreach ($param as $key => $value) {
            $data[$key] = $value;
        }
        $result = $this->where('id', $id)->update($data);

        if ($result) {
           $name = $this->getSName($id);
            Session::set('name', $name);
        }

        return $result;
    }
}


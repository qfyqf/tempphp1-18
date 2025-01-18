<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 9:56
 */

namespace app\index\model;

use think\Model;
use think\Db;
class User extends Model
{
    protected $name = 'student';
    protected $role = 0;

    /**
     * 设置用户角色
     * @param $option
     * @return int
     */
    public function setRole($option)
    {
        $this->role = is_null($option) ? $this->role : $option;
        if($this->role == 1) {
            $this->name = 'teacher';
        }
        return $this->role;
    }

    /**
     * 验证用户密码
     * @param array $param
     * @return bool
     */
    public function verify($param)
    {
        $password = Db::name($this->name)->where('account',$param['account'])->value('password');
        if (is_null($password) || empty($password)) {
           $result = false;
        } else {
            $result = password_verify($param['password'],$password);
        }

        return $result;
    }

    /**
     * 得到ID
     * @param array $param
     * @return int
     */
    public function getId($param)
    {

        $id = Db::name($this->name)
            ->where('account',$param['account'])
            ->value('id');
        if (is_null($id)) {
            $id = 0;
        }
        return $id;
    }

    /**
     * 得到姓名
     * @param int $id
     * @return string
     */
    public function getName($id)
    {
        $name = Db::name($this->name)->where('id',$id)->value('name');
        return $name;
    }

    /**
     * 获取用户信息
     * @param $id
     * @param $role
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($id, $role)
    {
        if ($role == 1) {
            $this->name = 'teacher';
            $info = Db::name($this->name)
                ->where('id',$id)
                ->field('sid as ID,name')
                ->find();
        } else {
            $info = Db::name($this->name)
                ->where('id',$id)
                ->field('sid as ID,name,classID')
                ->find();
        }

        return $info;
    }
}

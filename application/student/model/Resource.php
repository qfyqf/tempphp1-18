<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/23
 * Time: 15:20
 */

namespace app\student\model;

use think\Model;
use think\Db;

class Resource extends MyModel
{
    protected $name = 'resource';
    /**
     * 获取资源列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getResource()
    {
        $info = Db::name($this->name)
            ->alias('r')
            ->join('course c', 'c.id = r.course_id')
            ->join('teacher t', 't.id = r.teacher_id')
            ->where('r.auth', 1)
            ->field('r.name,c.name as cname,t.name as tname,r.type,r.desc,r.resource,r.add_time,r.id')
            ->paginate(10);
        $result = $this->result($info);
        return $result;

    }

    public function getResource2()
    {
        $info = Db::name($this->name)
            ->alias('r')
            ->join('course c', 'c.id = r.course_id')
            ->join('teacher t', 't.id = r.teacher_id')
            ->where('r.auth', 0)
            ->field('r.name,c.name as cname,t.name as tname,r.type,r.desc,r.resource,r.add_time,r.id')
            ->paginate(10);
        $result = $this->result($info);
        return $result;

    }

    /**
     * 获取资源信息
     * @param $id int 资源ID
     * @return array|false|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getResourceInfo($id)
    {
        $info  = Db::name($this->name)
            ->alias('r')
            ->join('course c', 'c.id = r.course_id')
            ->join('teacher t', 't.id = r.teacher_id')
            ->where('r.id',$id)
            ->field('r.name,c.name as cname,t.name as tname,r.type,r.desc,r.resource,r.add_time,r.id,r.auth')
            ->find();

        $result = $this->result($info);
        return $result;
    }

    /**
     * 获取收藏资源信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCollectInfo()
    {
        $info = Db::name($this->name)
            ->alias('r')
            ->join('student_collect s', 'r.id = s.resource_id')
            ->join('course c', 'c.id = r.course_id')
            ->join('teacher t', 't.id = r.teacher_id')
            ->where('s.student_id','=',$this->studentId)
            ->field('r.name,c.name as cname,t.name as tname,r.type,r.desc,r.resource,r.add_time,r.id')
            ->select();

        $result = $this->result($info);
        return $result;
    }

    /** 收藏资源
     * @param $id int 资源ID
     * @return array
     */
    public function collect($id)
    {
        $data = ['student_id' => $this->studentId, 'resource_id' => $id];
        $info = Db::name('student_collect')
            ->insert($data);

        $result = $this->result($info);
        return $result;
    }

    /**
     * 返回收藏状态
     * @param $id integer 资源ID
     * @return int|mixed
     */
    public function getStatus($id)
    {
        $select = Db::name('student_collect')
            ->where('student_id', $this->studentId)
            ->where('resource_id', $id)
            ->value('id');

        if(isset($select) && !empty($select)) {
            return $select;
        } else {
            return 0;
        }
    }

    /**
     * 取消收藏（硬删除）
     * @param $id integer 资源ID
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cancel($id)
    {
        $info  = Db::name('student_collect')
            ->where('student_id', $this->studentId)
            ->where('resource_id', $id)
            ->delete();
        return $info;
    }

    /**
     * 获取资源信息
     * @param $id integer 资源ID
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($id)
    {
        $info  = Db::name($this->name)
            ->where('id', $id)
            ->find();
        return $info;
    }

    /**
     * 获取搜索结果
     * @param $name string 名称
     * @return array
     * @throws \think\exception\DbException
     */
    public function getSearchInfo($name)
    {
        $info = Db::name($this->name)
            ->alias('r')
            ->join('course c', 'c.id = r.course_id')
            ->join('teacher t', 't.id = r.teacher_id')
            ->where('r.auth', 1)
            ->where('r.name', 'like', "%$name%")
            ->field('r.name,c.name as cname,t.name as tname,r.type,r.desc,r.resource,r.add_time,r.id')
            ->paginate(10);
        $result = $this->result($info);
        return $result;
    }
}

<?php

namespace app\teach\model;

use think\Exception;
use think\Model;
use think\Db;

class TeacherClass extends Model
{
    protected $table = 'vr_teacher_class';
    protected $field = 'id, teacher_id, class_id, create_time,status';

    /**
     * 获取指定老师负责的班级
     * @param int $teacherID
     * @return array
     */
    public static function getListByTeacherID(int $teacherID): array
    {
        $where = [
            'teacher_id' => $teacherID,
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 批量获取老师们的班级信息
     * @param array $teacherIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getListByTeacherIds(array $teacherIds): array
    {
        $where = [];
        $where['teacher_id'] = ['in', $teacherIds];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 获取指定班级的所有记录
     * @param int $classID
     * @return array
     */
    public static function getListByClassID(int $classID): array
    {
        $where = [
            'class_id' => $classID,
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 清除指定老师的班级关联信息
     * @param int $teacherID
     */
    public static function clearByTeacherID(int $teacherID)
    {
        $where = [
            'teacher_id' => $teacherID,
        ];
        self::where($where)->delete();
    }

    /**
     * 批量清除指定老师的班级关联信息
     * @param array $teacherIds
     */
    public static function clearByTeacherIds(array $teacherIds)
    {
        $where = [];
        $where['teacher_id'] = ['in', $teacherIds];
        self::where($where)->delete();
    }
}
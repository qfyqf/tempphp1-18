<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/25
 * Time: 11:52
 */

namespace app\student\model;


use think\Db;
use think\Exception;

class Student extends MyModel
{

    protected $name = 'student';

    /**
     * 根据学生ID获取学生信息
     * @param int $studentID
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getInfo(int $studentID): array
    {
        $result = self::where('id', $studentID)->find();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * 更新记录
     * @param int $ID
     * @param array $updateData
     */
    public static function updateInfo(int $ID, array $updateData)
    {
        self::where('id', 'eq', $ID)->update($updateData);
    }

    /**
     * 根据账号获取学生信息
     * @param string $account
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getInfoByAccount(string $account): array
    {
        $result = self::where('account', $account)->find();
        return !empty($result) ? $result->toArray() : [];
    }

    /**
     * 批量根据班级获取学生信息
     * @param array $classIds
     * @return array
     */
    public static function getListByClassIds(array $classIds, array $limitStatus = []): array
    {
        $where = [];
        $where['classID'] = ['in', $classIds];
        if (!empty($limitStatus)) {
            $where['status'] = ['in', $limitStatus];
        }
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 批量获取学生信息
     * @param array $studentIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getListByStudentIds(array $studentIds): array
    {
        $where = [];
        $where['id'] = ['in', $studentIds];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 根据学生名字查询
     * @param string $studentName
     * @return array
     */
    public static function getListByStudentName(string $studentName): array
    {
        $where = [
            'name' => ['like', '%' . $studentName . '%'],
            'status' => 1,
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }
}

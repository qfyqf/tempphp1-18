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

class StudentOption extends MyModel
{
    protected $name = 'student_option';

    /**
     * 获取学生的选课信息列表
     * @param int $studentID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getStudentOption(int $studentID): array
    {
        $where = [
            'student_id' => $studentID,
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }
}

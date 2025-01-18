<?php

namespace app\teach\model;

use think\Model;
use think\Db;

class TestPaper extends Model
{
    protected $table = 'vr_test_paper';

    /**
     * 批量获取课程关联的试卷
     * @param array $courseIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getListByCourseIds(array $courseIds): array
    {
        $where = [
            'course_id' => ['IN', $courseIds],
        ];
        $result = self::where($where)->select();
        return !empty($result) ? $result : [];
    }

    /**
     * 获取试卷信息
     * @param int $ID
     * @return array
     */
    public static function getInfo(int $ID): array
    {
        $where = [
            'id' => $ID,
        ];
        $result = self::where($where)->find();
        return !empty($result) ? collection($result)->toArray() : [];
    }

    /**
     * 删除指定的试卷
     * @param int $ID
     */
    public static function deleteTestPaper(int $ID): void
    {
        $where = [
            'id' => $ID,
        ];
        self::where($where)->delete();
    }
}
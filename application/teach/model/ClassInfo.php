<?php

namespace app\teach\model;

use think\Exception;
use think\Model;
use think\Db;

class ClassInfo extends Model
{
    protected $table = 'vr_classinfo';
    protected $field = 'id,grade,classID,className,schoolName,collegeName,specialtyName';

    /**
     * 批量获取班级信息
     * @param array $classIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getListByIds(array $classIds): array
    {
        $returnArr = $where = [];
        $where['id'] = ['in', $classIds];
        $result = self::where($where)->select();
        if (!empty($result)) {
            $result = collection($result)->toArray();
            foreach ($result as $value) {
                $returnArr[$value['id']] = $value;
            }
            unset($result, $value);
        }
        return $returnArr;
    }

    /**
     * 获取班级信息
     * @param int $ID
     * @return array
     */
    public static function getInfo(int $ID): array
    {
        $where = [
            'id' => $ID,
        ];
        $result = self::where($where)->find();
        return !empty($result) ? $result->toArray() : [];
    }


}
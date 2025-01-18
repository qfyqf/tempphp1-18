<?php

namespace app\admin\model;

use think\Exception;
use think\Model;
use think\Db;

class Admin extends Model
{
    protected $table = 'vr_admin';

    /**
     * 根据管理员ID获取管理员信息
     * @param int $adminID
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getInfo(int $adminID): array
    {
        $result = self::where('id', $adminID)->find();
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

}
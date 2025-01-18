<?php

namespace app\teach\model;

use think\Exception;
use think\Model;
use think\Db;

class Teacher extends Model
{
    protected $table = 'vr_teacher';
    protected $field = 'id, sid, name, account, email, sex, phone, birthday, card_id, degree, homepage, address, classID';


//    /**
//     * 得到个人信息
//     * @param int $id
//     * @return array
//     */
//    public function getInfo($id)
//    {
//        return DB::table($this->table)->field($this->field)->where('id',$id)->find();
//    }

    // 获取教师列表
    public function getall()
    {
        return DB::table($this->table)->field($this->field)->where('status', 1)->select();
    }

    // 修改教师资料
    public function updone($id, $data)
    {
        return DB::table($this->table)->where('id', $id)->update($data);
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
     * 根据ID获取信息
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
     * 根据账号获取教师信息
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
<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 10:09
 */

namespace app\index\validate;


use think\Validate;

class KC extends Validate
{
    protected $rule = [
        'kecheng' => 'require|max:100',
        'title' => 'require|max:100',
        'start' => 'require|date',
        'end' => 'require|date',
        'teacher' => 'require|max:30',
    ];

    protected $message = [
        'kecheng.require' => '课程名称必须填写',
        'kecheng.max' => '课程名称最多不超过100字符',
        'title.require' => '实验名称必须填写',
        'title.max' => '实验名称最多不超过100字符',
        'teacher.require' => '教师必须填写',
        'teacher.max' => '教师最多不超过30字符',
        'start.require' => '开始时间必须填写',
        'start.date' => '开始时间必须是日期类型',
        'end.require' => '结束时间必须填写',
        'end.date' => '结束时间必须是日期类型',
    ];
}

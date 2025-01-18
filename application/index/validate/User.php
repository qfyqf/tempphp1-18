<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 10:09
 */

namespace app\index\validate;


use think\Validate;

class User extends Validate
{
    protected $rule = [
        'account'  =>  'require|max:25',
        'password' =>  'require',
    ];

    protected $message = [
        'account.require'  =>  '用户名必须',
        'account.max' => '账号最多不超过25',
        'password.require' =>  '密码必须',
    ];
}

<?php
/**
 * Created by PhpStorm.
 * User: qlkj
 * Date: 2019/3/18
 * Time: 10:09
 */

namespace app\index\validate;


use think\Validate;

class Report extends Validate
{
    protected $rule = [
        'sid'  =>  'require',
        'planned_cost' =>  'require',
        'actual_cost'=>'require',
        'cost_data'=>'require',
        'planned_day'=>'require',
        'actual_day'=>'require',
        'planned_net_image'=>'require',
        'actual_net_image'=>'require',
        'award'=>'require',
        'correct_measures'=>'require',
        'validity_analysis'=>'require',
        'summary'=>'require',
        'process_images'=>'require',
    ];
}

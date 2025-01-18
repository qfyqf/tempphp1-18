<?php

return [
	
	// 主导航页
	'Index' => [
            ['name' => '学生账号',      'url' => 'index/index' ,           'icon'=>'th-list'       ],
            ['name' => '批量导入',      'url' => 'index/import_user',         'icon'=>'ok'            ],
            ['name' => '教师账号',      'url' => 'index/teacher' ,           'icon'=>'th-list'       ],
            ['name' => '管理员账号',    'url' => 'index/admin' ,           'icon'=>'th-list'       ],
            // ['name' => '添加账号',      'url' => 'index/addaccount' ,        'icon'=>'heart-empty'   ],
            
            ['name' => '班级管理',      'url' => 'index/classinfo',         'icon'=>'ok'            ],
		],

    // 课程管理
//    'Course' => [
//        ],

    // 页面管理
    'Page' => [
        ],

    // 用户信息
    'User' => [
            ['name' => '个人信息',      'url' => 'user/index' ,             'icon'=>'th-list'       ],
            ['name' => '修改资料',      'url' => 'user/upd' ,               'icon'=>'plus'          ],
            ['name' => '修改密码',      'url' => 'user/updatePassword' ,               'icon'=>'plus'          ]
        ],

];
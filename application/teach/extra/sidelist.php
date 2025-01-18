<?php

return [

    // 主导航页
    'Index' => [

    ],

    // 班级学生
    'ClassesAndStudents' => [
        ['name' => '我的班级', 'url' => 'ClassesAndStudents/myClasses', 'icon' => 'icon-building'],
        ['name' => '我的学生', 'url' => 'ClassesAndStudents/myStudents', 'icon' => 'icon-building'],
    ],


    // 设备管理
    /*'Equipment' => [
            ['name' => '设备借出', 		'url' => 'equipment/index' , 		'icon'=>'th-list'		],
            ['name' => '我的借出',      'url' => 'equipment/mylending',     'icon'=>'heart-empty'   ],
            ['name' => '查看学生借出', 	'url' => 'equipment/stulending', 	'icon'=>'heart-empty'	]
        ],*/
    // 用户信息
    'User' => [
        ['name' => '个人信息', 'url' => 'user/index', 'icon' => 'th-list'],
        ['name' => '修改资料', 'url' => 'user/upd', 'icon' => 'plus'],
        ['name' => '修改密码', 'url' => 'user/updatePassword', 'icon' => 'plus']
    ],
    'Report' => [
        ['name' => '报告信息', 'url' => 'report/index', 'icon' => 'th-list'],
    ],

    // 帮助文档管理
    /*'Help' => [
            ['name' => '查看帮助文档',    'url' => 'index/index/help?type=1' ,            'icon'=>'th-list'          ],
            ['name' => '添加文档',      'url' => 'help/add' ,                'icon'=>'plus'         ],
            ['name' => '修改学生端文档',    'url' => 'help/index' ,              'icon'=>'th-list'          ],
            ['name' => '修改教师端文档',    'url' => 'help/teacher' ,            'icon'=>'th-list'          ]

        ]*/

];
<?php
return[

'guards'=>[
    'user'=>[
        'driver'=>'jwt',
        'provider'=>'users',
    ],
    'admin'=>[
      'driver'=>'jwt',
      'provider'=>'users',
    ],
],


'providers'=>[
    'users'=>[
    'driver' => 'eloquent',
    'model' => App\Models\User::class,
     ],
     'admins'=>[
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
         ],
     
]


];
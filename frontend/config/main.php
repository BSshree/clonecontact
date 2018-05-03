<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
     'modules' => [
        'api' => [
            'class' => 'app\modules\api\Module',
        ],
    ],
    'components' => [
//        'request' => [
//            'csrfParam' => '_csrf-frontend',
//        ],
        'request' => [
            'class' => '\yii\web\Request',
            'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'baseUrl' => '',
        ],
            'user' => [
            'identityClass' => 'common\models\User',
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
             'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                 ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/users'],
                 ['class' => 'yii\rest\UrlRule', 'controller' => 'api/v1/contacts'],
                
            ],
        ],
        
    ],
    'params' => $params,
];

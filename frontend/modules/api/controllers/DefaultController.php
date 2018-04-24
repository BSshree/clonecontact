<?php

namespace app\modules\api\controllers;

use common\models\LoginForm;
use common\models\Logins;
use common\models\User;
use yii\web\Controller;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;

/**
 * Default controller for the `api` module
 */
class DefaultController extends ActiveController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public $modelClass = 'common\models\Logins';

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'only' => ['index'],
        ];
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
//        $behaviors['access'] = [
//            'class' => AccessControl::className(),
//            'only' => ['index'],
//            'rules' => [
//                    [
//                    'actions' => ['index'],
//                    'allow' => true,
//                    'roles' => ['@'],
//                ],
//            ],
//        ];

        return $behaviors;
    }
    
    public function actionLogin() {
                        echo 'helol'; exit;

        $model = new LoginForm();
        $user = new User();
        $admin_typeid = UserTypes::AD_USER_TYPE;
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            $user_login = Logins::find()
                    ->user($post['username'])
                    ->status()
                    ->active()
                    ->one();

            if ($user_login && $user_login->user->user_type_id != $admin_typeid) {
                $password = $user_login->password_hash;
                if (Yii::$app->security->validatePassword($post['password'], $password)) {
                    return [
                        'success' => 'true',
                        'message' => 'Login successful',
                        'user_id' => $user_login->user->user_id,
                        'access_token' => $user_login->auth_key,
                    ];
                } else {
                    return [
                        'success' => 'false',
                        'message' => 'Email / Password Combination is wrong',
                    ];
                }
            } else {
                return [
                    'success' => 'false',
                    'message' => 'Invalid request'
                ];
            }
        }
    }
    
    public function actionTest()
    {
                echo 'helzzzzzl'; exit;

        $model = new LoginForm();
        $user = new User();
        $admin_typeid = UserTypes::AD_USER_TYPE;
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            $user_login = Logins::find()
                    ->user($post['username'])
                    ->status()
                    ->active()
                    ->one();

            if ($user_login && $user_login->user->user_type_id != $admin_typeid) {
                $password = $user_login->password_hash;
                if (Yii::$app->security->validatePassword($post['password'], $password)) {
                    return [
                        'success' => 'true',
                        'message' => 'Login successful',
                        'user_id' => $user_login->user->user_id,
                        'access_token' => $user_login->auth_key,
                    ];
                } else {
                    return [
                        'success' => 'false',
                        'message' => 'Email / Password Combination is wrong',
                    ];
                }
            } else {
                return [
                    'success' => 'false',
                    'message' => 'Invalid request'
                ];
            }
        }
    }
}

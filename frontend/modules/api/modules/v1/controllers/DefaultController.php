<?php

namespace app\modules\api\modules\v1\controllers;
use common\models\User;
use yii\web\Controller;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;

/**
 * Default controller for the `v1` module
 */
class DefaultController extends ActiveController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    
     public $modelClass = 'common\models\User';
    
     public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'only' => ['test'],
        ];
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        
        return $behaviors;
    }
    
    public function actionRegister() {
        $user = new User();
        $post = Yii::$app->request->getBodyParams();
        $password = $post['password'];
        if (!empty($post)) {
            $user->load(Yii::$app->request->getBodyParams(), '');
             $user->auth_key = User::generateAuthKey();
             $user->password = User::setPassword($password);
            
             
                 if( $user->save()){
                     
                       $values[] =  [
                            'id' => $user->id,
                            'auth_key' =>$user->auth_key, 
                            'username' => $user->username,
                            'email' => $user->email,
                          
                ];
                       return [
                            'success' => true,
                            'message' => 'Success',
                           'data' => $values
                            
                        ];

                   }else{
                        return [
                            'success' => false,
                            'message' => 'Email Already Exists'
                        ];
                       
//                    print_r($user->getErrors()); exit;
                   }
        } else {
            return [
                'success' => false,
                'message' => 'Invalid request'
            ];
        }
    }

    
    public function actionLogin()
    {
        echo 'hiv1new1234'; exit;
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

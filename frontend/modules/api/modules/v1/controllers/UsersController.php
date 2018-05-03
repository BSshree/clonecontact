<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\Users;
use common\models\Logins;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;

/**
 * Default controller for the `v1` module
 */
class UsersController extends ActiveController {

    /**
     * Renders the index view for the module
     * @return string
     */
    public $modelClass = 'common\models\Users';

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
        return $behaviors;
    }

    public function actionRegister() {
        $user = new Users();
        $post = Yii::$app->request->getBodyParams();
        $password = $post['password'];
        if (!empty($post)) {
            $user->load(Yii::$app->request->getBodyParams(), '');
            $user->auth_key = $user->generateAuthKey();
            $user->password = $user->setPassword($password);
            if ($user->save()) {
                $values[] = [
                    'id' => $user->id,
                    'auth_key' => $user->auth_key,
                    'username' => $user->username,
                    'email' => $user->email,
                ];
                return [
                    'success' => true,
                    'message' => 'Success',
                    'data' => $values
                ];
            } else {
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

    public function actionLogin() {
        $post = Yii::$app->request->getBodyParams();
        $email = $post['email'];
        if (!empty($post)) {
            if (Users::find()->where(['email' => $email])->one()) {
                $user = Users::find()->where(['email' => $email])->one();
                $password = $user->password;
                $valid_pass = Yii::$app->security->validatePassword($post['password'], $password);
                if ($valid_pass) {
                    $values[] = [
                        'id' => $user->id,
                        'auth_key' => $user->auth_key,
                        'username' => $user->username,
                        'email' => $user->email,
                    ];
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => $values
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Password is wrong',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid Email',
                ];
            }
        }
    }

    public function actionEditprofile() {
        $post = Yii::$app->request->getBodyParams();
        $user = Users::findOne($post['id']);
        $password = $post['password'];
        if (!empty($post)) {
            $user->load(Yii::$app->request->getBodyParams(), '');
            $password = $user->password;
            $user->password = $user->setPassword($password);
            $user->save();

            $profile = Users::findOne($post['id']);

            $values[] = [
                'id' => $user->id,
                'auth_key' => $user->auth_key,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $user->password,
            ];
            if (!empty($profile)) {
                return [
                    'success' => true,
                    'message' => 'Success',
                    'data' => $values
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No records found',
                ];
            }
        } else {
            return [
                'success' => true,
                'message' => 'Invalid request'
            ];
        }
    }

    public function actionViewprofile() {
        $post = Yii::$app->request->getBodyParams();
        $user = Users::find()->where(['id' => $post['id']])->one();
        if (($user)) {
            $values[] = [
                'username' => $user->username,
                'email' => $user->email,
            ];
            return [
                'success' => true,
                'message' => 'Success',
                'data' => $values
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid request'
            ];
        }
    }
    
     public function actionChangepassword() {
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            $model = Users::findOne(Yii::$app->user->getId());
            $model->scenario = 'changepassword';
            if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
                $model->password_hash = Yii::$app->getSecurity()->generatePasswordHash($model->new_pass);
                $model->save();
                return [
                    'success' => 'true',
                    'message' => 'Password change successfully',
                ];
            } else {
                return [
                    'success' => true,
                    'message' => 'Incorrect password',
                ];
            }
        } else {
            return [
                'success' => true,
                'message' => 'Invalid request'
            ];
        }
    }
     public function actionForgotpassword() {
          $model = new Logins();
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->authenticate()) {
                
                return [
                    'success' => 'true',
                    'message' => 'Please verify your gmail account',
                ];
            } else {
                return [
                    'success' =>'false',
                    'message' => 'Incorrect email address',
                ];
            }
        } else {
            return [
                'success' =>'false',
                'message' => 'Invalid request'
            ];
        }
    }

}

<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\Users;
use common\models\Logins;
use common\models\LoginForm;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\web\UploadedFile;

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
            $user->status = 0;
            if ($profile_img = UploadedFile::getInstancesByName("profile_image")) {
                foreach ($profile_img as $file) {
                    $file_name = str_replace(' ', '-', $file->name);
                    $randno = rand(11111, 99999);
                    $path = Yii::$app->basePath . '/web/uploads/images/' . $randno . $file_name;
                    $file->saveAs($path);
                    $user->profile_image = $randno . $file_name;
                }
            } else {
                $user->profile_image = 'default.png';
            }
            if ($user->save()) {
                $values[] = [
                    'id' => $user->id,
                    'auth_key' => $user->auth_key,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image,
                    'status' => $user->status
                ];
                $auth_key = $user->auth_key;
                $uid = $user->id;
                $mail_sub = 'Clone Contact Account Activation';
                $mail_body = "Hi " . $user->username . ",<br><br>";
                $mail_body .= "Please click the below link to activate your account. <br><br>";
                $mail_body .= " http://clonecontacts.arkinfotec.in/api/v1/users/emailverification?auth_key=$auth_key&uid=$uid";
                $mail_body .= " <br><br>Thank you. <br><br> <b>Regards, <br> Clone Contact team</b><br>";
                $emailSend = Yii::$app->mailer->compose()
                        ->setFrom(['sumanasdev@gmail.com'])
                        ->setTo($user->email)
                        ->setSubject($mail_sub)
                        ->setHtmlBody($mail_body)
                        ->send();

                if ($emailSend) {
                    return [
                        'success' => false,
                        'message' => 'Please check your email to active your account',
                        'data' => $values
                    ];
                }
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

    public function actionEmailverification() {
        $user = Users::find()->where(['id' => $_GET['uid']])->one();
        $db_key = $user['auth_key'];
        if ($_GET['auth_key'] == $db_key) {

            $user = new Users();
            $user = Users::find()->where(['id' => $_GET['uid']])->one();
            $user->status = 1;
            $user->save(false);
            return $this->redirect(['/site/emailverification']);
        }
    }
   

    public function actionLogin() {
        $post = Yii::$app->request->getBodyParams();
        $email = $post['email'];
        if (!empty($post)) {

            if (Users::find()->where(['email' => $email])->one()) {
                if (Users::find()->where(['status' => 1])->andWhere(['email' => $email])->one()) {

                    $user = Users::find()->where(['email' => $email])->one();
                    $password = $user->password;
                    $valid_pass = Yii::$app->security->validatePassword($post['password'], $password);
                    if ($valid_pass) {
                        $values[] = [
                            'id' => $user->id,
                            'auth_key' => $user->auth_key,
                            'username' => $user->username,
                            'email' => $user->email,
                            'profile_image' => $user->profile_image,
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
                        'message' => 'Sorry, Your account is inactive. Please check email to activate your account',
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
        $user = new Users();
        $post = Yii::$app->request->getBodyParams();
        $userinfo = Users::findOne($post['id']);
        $oldimage = $userinfo['profile_image'];
        if (!empty($post)) {
            $user->load(Yii::$app->request->getBodyParams(), '');
            $userinfo->username = $post['username'];
            if ($profile_img = UploadedFile::getInstancesByName("profile_image")) {
                foreach ($profile_img as $file) {
                    $file_name = str_replace(' ', '-', $file->name);
                    $randno = rand(11111, 99999);
                    $path = Yii::$app->basePath . '/web/uploads/images/' . $randno . $file_name;
                    $file->saveAs($path);
                    $userinfo->profile_image = $randno . $file_name;
                }
                if ($oldimage != 'default.png') {
                    unlink(Yii::$app->basePath . '/web/uploads/images/' . $oldimage);
                }
            }
            $userinfo->save();

            $profiles = Users::findOne($post['id']);
            $values[] = [
                'id' => $userinfo->id,
                'username' => $userinfo->username,
                'email' => $userinfo->email,
                'profile_image' => $userinfo->profile_image
            ];
            if (!empty($profiles)) {
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
                'profile_image' => $user->profile_image,
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
            $model = Users::findOne($post['id']);
            $model->scenario = 'changepassword';
            if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
                $model->password = Yii::$app->getSecurity()->generatePasswordHash($model->new_pass);
                $model->save();
                return [
                    'success' => true,
                    'message' => 'Password changed successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Password Mismatch',
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Invalid request'
            ];
        }
    }

    public function actionForgotpassword() {
        $model = new Users();
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->authenticate()) {

                return [
                    'success' => 'true',
                    'message' => 'Please check your email to reset password',
                ];
            } else {
                return [
                    'success' => 'false',
                    'message' => 'Incorrect email address',
                ];
            }
        } else {
            return [
                'success' => 'false',
                'message' => 'Invalid request'
            ];
        }
    }

    public function actionAboutus() {
        return [
            'success' => true,
            'message' => 'About us',
            'data' => "<p align='justify'>Cloud contact sounds people's kind of dairy marking their necessary contacts to get in touch with. "
            . "Most people find it easier since its user-friendly app.</p> "
            . "<p align='justify'>This has become one of the most handy APP's in android world. Majority usage of this APP has reached its safest heights as well. It keeps you far away from hacking and hanging."
            . " </p>"
            . "<p align='justify'>Its been trusted because of its security where third parties cannot easily been taken off ones information."
            . " If you think it sounds super smart then go for it.</p>",
        ];
    }

    public function actionContactus() {

        $values[] = [
            'address' => 'No-01, Gandhiji St, Rasi Towers, Near Aparna Enclave',
//            'address_line_2' => 'Rasi Towers',
            'url' => 'http://www.sumanastech.com/',
//            'landmark' => 'Near Aparna Enclave',
            'city' => 'Madurai',
            'pincode' => '625010',
            'contact' => '9894837443',
            'email' => 'info@sumanastech.com ',
        ];


        return [
            'success' => true,
            'message' => 'Contact us',
            'data' => $values
        ];
    }

}

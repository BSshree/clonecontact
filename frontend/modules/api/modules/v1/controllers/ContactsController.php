<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\Contacts;
use common\models\Users;
use common\models\Logins;
use yii\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;

class ContactsController extends ActiveController {

    public $modelClass = 'common\models\Contacts';

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

    public function actionAddcontact() {
        $contacts = new Contacts();
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            $contacts->load(Yii::$app->request->getBodyParams(), '');
            $user = Users::find()->where(['id' => $post['user_id']])->one();
            if (!empty($user['auth_key'])) {
                $contact_user = Contacts::find()->where(['user_id' => $post['user_id']])->all();
                $check = false;
                foreach ($contact_user as $con) {
                    $mobile_no = $con['mobile_no'];
                    $name = $con['name'];
                    if (($mobile_no == $post['mobile_no']) || ($name == $post['name'])) {
                        $check = true;
                        return [
                            'success' => false,
                            'message' => 'Name / Number already exists',
                        ];
                    }
                }
                if ($check) {
                    return [
                        'success' => false,
                        'message' => 'Name / Number already exists',
                    ];
                } else {
                    $contacts->save();
                    $values[] = [
                        'contact_id' => $contacts->contact_id,
                    ];
                    return [
                        'success' => true,
                        'message' => 'Success',
                        'data' => $values
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Cannot Add Contact',
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Invalid request'
            ];
        }
    }

    public function actionAddmulticontact() {

        $contacts = new Contacts();
        $post = Yii::$app->request->getBodyParams();
        $arr = $post['contact_arrayList'];
        $check = false;
        $arr_name = array();
        $arr_number = array();
        $values = array();
        if (!empty($post)) {
            $user = Users::find()->where(['id' => $post['user_id']])->one();
            if (!empty($user['auth_key'])) {

                foreach ($arr as $key => $contact_arrayList) {
                    $contacts_all = Contacts::find()->where(['user_id' => $post['user_id']])->all();

                    foreach ($contacts_all as $key2) {
                        $arr_name [] = $key2['name'];
                        $arr_number[] = $key2['mobile_no'];
                    }
                    foreach ($contact_arrayList as $key1 => $getValue) {
                        if (in_array($key1, $arr_name)) {
                            $values[] = [
                                $key1 => $getValue
                            ];
                            break;
                        } else {
                            if (in_array($getValue, $arr_number)) {
                                $values[] = [
                                    $key1 => $getValue
                                ];
                                break;
                            } else {
                                $check = true;
                                $contacts = new Contacts();
                                $contacts->load(Yii::$app->request->getBodyParams(), '');
                                $contacts->name = $key1;
                                $contacts->mobile_no = $getValue;
                                $contacts->save();
                                break;
                            }
                        }
                    }
                }
                if ($check) {
                    if (!empty($values)) {
                        return [
                            'success' => true,
                            'message' => 'Success',
                            'data' => $values
                        ];
                    } else {
                        return [
                            'success' => true,
                            'message' => 'Success',
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Name / Number already exists',
                        'data' => $values
                    ];
                }
            }
        }
    }

    public function actionListcontact() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->where(['user_id' => $post['user_id']])->all();
        foreach ($contacts as $cont):
            $values[] = [
                'contact_id' => $cont->contact_id,
                'name' => $cont->name,
                'mobile_no' => $cont->mobile_no,
            ];

        endforeach;
        return [
            'success' => true,
            'message' => 'Success',
            'data' => $values
        ];
    }

    public function actionEditcontact() {
        $contacts = new Contacts();
        $post = Yii::$app->request->getBodyParams();
        if (!empty($post)) {
            $contacts->load(Yii::$app->request->getBodyParams(), '');
            $user = Users::find()->where(['id' => $post['user_id']])->one();
            $contact_user = Contacts::find()->where(['contact_id' => $post['contact_id']])->one();
            $contacts_all = Contacts::find()->where(['user_id' => $post['user_id']])->all();
            if ($post['user_id'] == $contact_user['user_id']) {
                $check = false;
                $mobile_no = $contact_user['mobile_no'];
                $name = $contact_user['name'];
                foreach ($contacts_all as $contactz) {
                    $arr_name [] = $contactz['name'];
                    $arr_number[] = $contactz['mobile_no'];
                }
                if (($mobile_no == $post['mobile_no']) && ($name == $post['name'])) {
                    $contact_user->name = $post['name'];
                    $contact_user->mobile_no = $post['mobile_no'];
                    $contact_user->user_id = $post['user_id'];
                    $contact_user->save();
                    return [
                        'success' => true,
                        'message' => 'Success',
                    ];
                } else {
                    if ($mobile_no != $post['mobile_no'] && (!in_array($post['mobile_no'], $arr_number))) {
                        $contact_user->name = $post['name'];
                        $contact_user->mobile_no = $post['mobile_no'];
                        $contact_user->user_id = $post['user_id'];
                        $contact_user->save();
                        return [
                            'success' => true,
                            'message' => 'Success',
                        ];
                    } elseif ($name != $post['name'] && (!in_array($post['name'], $arr_name))) {
                        $contact_user->name = $post['name'];
                        $contact_user->mobile_no = $post['mobile_no'];
                        $contact_user->user_id = $post['user_id'];
                        $contact_user->save();
                        return [
                            'success' => true,
                            'message' => 'Success',
                        ];
                    }
                }
                if ($mobile_no != $post['mobile_no'] && (in_array($post['mobile_no'], $arr_number))) {

                    return [
                        'success' => false,
                        'message' => 'Number already exists',
                    ];
                } elseif ($name != $post['name'] && (in_array($post['name'], $arr_name))) {

                    return [
                        'success' => false,
                        'message' => 'Name already exists',
                    ];
                }
            }
        }
    }

  

    public function actionDeletecontact() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->where(['contact_id' => $post['contact_id']])->one();
        if ($post['user_id'] == $contacts['user_id']) {
            $contacts->delete();
            return [
                'success' => true,
                'message' => 'Success',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No Contact Exists',
            ];
        }
    }
    
    
     public function actionMultideletecontact() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->where(['user_id' => $post['user_id']])->one();
        if ($post['user_id'] == $contacts['user_id']) {
            $check=false;
            foreach($contacts as $del){
                $check=true;
                 $contacts->delete();
                
                
            }
            
            if($check){
            return [
                'success' => true,
                'message' => 'Success',
            ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'No Contact Exists',
            ];
        }
    }


}

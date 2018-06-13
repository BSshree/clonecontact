<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\Contacts;
use common\models\ContactsBackup;
use common\models\Users;
use common\models\Logins;
use yii\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\web\UploadedFile;

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
                    $contacts->created_at = time();
                    $contacts->updated_at = time();
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
        $saves = array();
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
                                'name' => $key1,
                                'mobile_no' => $getValue,
                            ];
                            $exists_count = sizeof($values);
                            break;
                        } else {
                            if (in_array($getValue, $arr_number)) {
                                $values[] = [
                                    'name' => $key1,
                                    'mobile_no' => $getValue,
                                ];
                                $exists_count = sizeof($values);
                                break;
                            } else {
                                $check = true;
                                $contacts = new Contacts();
                                $contacts->load(Yii::$app->request->getBodyParams(), '');
                                $contacts->name = $key1;
                                $contacts->mobile_no = $getValue;
                                $contacts->created_at = time();
                                $contacts->updated_at = time();
                                $contacts->save();
                                $saves[] = [
                                    'name' => $key1,
                                    'mobile_no' => $getValue,
                                ];
                                $saved_count = sizeof($saves);
                                break;
                            }
                        }
                    }
                }
                if ($check) {
                    if (!empty($values)) {
                        return [
                            'success' => true,
                            'message' => $saved_count . ' Contacts saved ' . $exists_count . ' Contacts already exists',
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
                        'message' => 'Name/Number already exists. Please save new contact',
                        'data' => $values
                    ];
                }
            }
        }
    }

    public function actionListcontact() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->orderBy(['updated_at' => SORT_DESC])->where(['user_id' => $post['user_id']])->all();
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

    public function actionSortbyname() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->orderBy(['name' => SORT_ASC])->where(['user_id' => $post['user_id']])->all();
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

    public function actionImportphonecontacts() {
        $contacts = new Contacts();
        $post = Yii::$app->request->getBodyParams();
        $arr_name = array();
        $arr_number = array();
        $check = false;
        if ($profile_img = UploadedFile::getInstancesByName("import_phone")) {
            foreach ($profile_img as $file) {
                $file_name = str_replace(' ', '-', $file->name);
                $randno = rand(11111, 99999);
                $path = Yii::$app->basePath . '/web/uploads/files/' . $randno . $file_name;
                $file->saveAs($path);
                $flz = $randno . $file_name;
                //$user->profile_image = $randno . $file_name;
            }
        }

        $contacts_all = Contacts::find()->where(['user_id' => $post['user_id']])->all();

        foreach ($contacts_all as $key2) {
            $arr_name [] = $key2['name'];
            $arr_number[] = $key2['mobile_no'];
        }

        $path = Yii::$app->basePath . '/web/uploads/files/' . $randno . $file_name;
        $fileHandler = fopen($path, 'r');
        if ($fileHandler) {
            while ($line = fgetcsv($fileHandler, 1000)) {
                $contacts = new Contacts;
                $name = $line[0];
                $number = $line[1];

                if ((in_array($number, $arr_number)) || (in_array($name, $arr_name))) {
                    $values[] = [
                        'name' => $name,
                        'mobile_no' => $number,
                    ];
                } else {
                    $check = true;
                    $contacts = new Contacts();
                    $contacts->name = $name;
                    $contacts->mobile_no = $number;
                    $contacts->user_id = $post['user_id'];
                    $contacts->created_at = time();
                    $contacts->updated_at = time();
                    $contacts->save();
                }
            }
        }
        if ($check) {
            return [
                'success' => true,
                'message' => 'Success',
                'data' => $values
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Contacts already exists',
                'data' => $values
            ];
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

    public function actionDeletemulticontact() {
        $post = Yii::$app->request->getBodyParams();
        $arr = $post['del_contact'];
        $contacts = Contacts::find()->where(['user_id' => $post['user_id']])->one();
        if ($post['user_id'] == $contacts['user_id']) {
            $check = false;
            foreach ($arr as $key => $contact_array) {

                foreach ($contact_array as $key1 => $getValue) {
                    $contacts_all = Contacts::find()->where(['contact_id' => $getValue])->all();

                    foreach ($contacts_all as $del) {
                        $check = true;
                        $del->delete();
                    }
                }
            }
            if ($check) {
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
        } else {
            return [
                'success' => false,
                'message' => 'No Contact Exists',
            ];
        }
    }

    public function actionSearchby() {
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->where(['user_id' => $post['user_id']])->all();
        $na = $post['search_by'];
        $id = $post['user_id'];
        $check = false;
        $query = "SELECT * FROM contacts WHERE user_id = $id AND (mobile_no LIKE '%$na%' OR name LIKE '%$na%' )";
        $result = Contacts::findBySql($query)->all();
        foreach ($result as $cont) {
            $check = true;
            $values[] = [
                'name' => $cont['name'],
                'mobile_no' => $cont['mobile_no'],
                'contact_id' => $cont['contact_id'],
            ];
        }
        if ($check) {
            return [
                'success' => true,
                'message' => 'Success',
                'data' => $values
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No contact exists',
            ];
        }
    }

    public function actionContactsbackup() {
        $cont_backup = new ContactsBackup();
        $post = Yii::$app->request->getBodyParams();
        $user = Users::find()->where(['id' => $post['user_id']])->one();
        if (!empty($user['auth_key'])) {
            date_default_timezone_set('Asia/Kolkata');
            if ($backup_file = UploadedFile::getInstancesByName("file_name")) {

                foreach ($backup_file as $file) {
                    $file_name = str_replace(' ', '-', $file->name);
                    $randno = rand(11111, 99999);
                    $path = Yii::$app->basePath . '/web/uploads/files/' . $randno . $file_name;
                    $file->saveAs($path);
                }
                if ($file->extension == 'vcf' || $file->extension == 'csv' || $file->extension == 'xls' || $file->extension == 'xlsv') {
                    $cont_backup = new ContactsBackup();
                    $cont_backup->user_id = $post['user_id'];
                    $cont_backup->file_name = $randno . $file_name;
                    $cont_backup->created_at = date('Y-m-d H:i:s');
                    $cont_backup->save();

                    $values[] = [
                        'user_id' => $post['user_id'],
                        'file_name' => $cont_backup->file_name,
                        'created_at' => $cont_backup->created_at,
                    ];
                    return [
                        'success' => true,
                        'message' => 'Success',
                        'data' => $values
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid file format '
                    ];
                }
            }
        }
    }

    public function actionFilelist() {
        $cont_backup = new ContactsBackup();
        $post = Yii::$app->request->getBodyParams();
        $back_up = ContactsBackup::find()->orderBy(['created_at' => SORT_DESC])->where(['user_id' => $post['user_id']])->all();
        date_default_timezone_set('Asia/Kolkata');
        $check = false;
        foreach ($back_up as $cont):
            $check = true;
            $values[] = [
                'file_name' => $cont->file_name,
                'created_at' => $cont->created_at,
            ];

        endforeach;
        if ($check) {
            return [
                'success' => true,
                'message' => 'Success',
                'data' => $values
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No file exists',
            ];
        }
    }

    public function actionContactscount() {
        $contacts = new Contacts();
        $post = Yii::$app->request->getBodyParams();
        $contacts = Contacts::find()->where(['user_id' => $post['user_id']])->all();
        $user_id = $post['user_id'];
        $count = Contacts::find()->where(['user_id' => $user_id])->count();
        if($count == !null){
             return [
                'success' => true,
                'message' => 'Success',
                'data' => $count
            ];
        }else{
            return [
                'success' => false,
                'message' => 'No contacts exists',
            ];
        }
        
    }

}

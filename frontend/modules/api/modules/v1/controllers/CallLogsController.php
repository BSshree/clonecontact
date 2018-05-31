<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\CallLogs;
use common\models\Contacts;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\web\UploadedFile;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

class CallLogsController extends \yii\web\Controller {

    public $modelClass = 'common\models\Calllogs';

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

    public function actionLogs() {
        $logs = new CallLogs();
        $post = Yii::$app->request->getBodyParams();
        $arr = $post['contact_log'];
        $check = false;
        $id = $post['user_id'];
        $arr_time = array();
        $ar_num = array();
        if (!empty($post)) {

            $logs->load(Yii::$app->request->getBodyParams(), '');
            foreach ($arr as $keys => $contact_logs) {
                $check = true;

                $minutes = floor($contact_logs['duration'] / 60);
                $seconds = $contact_logs['duration'] % 60;
                $duration = "$minutes min(s) " . "$seconds sec(s) ";

                $date = date("Y-m-d", strtotime($contact_logs['time']));
                $time = date("H:i:s", strtotime($contact_logs['time']));


                $contz = Contacts::find()->where(['user_id' => $post['user_id']])->all();

                foreach ($contz as $conz) {
                    $ar_num[] = $conz['mobile_no'];
                }
                if (in_array($contact_logs['number'], $ar_num)) {

                    $contact = Contacts::find()->where(['user_id' => $post['user_id']])->andWhere(['mobile_no' => $contact_logs['number']])->all();

                    foreach ($contact as $contactz => $con) {
                        $cont_id = [
                            $con['contact_id']
                        ];

                        $logs = new CallLogs();
                        $logs->user_id = $post['user_id'];
                        $logs->contact_id = $cont_id[$contactz];
                        $logs->name = base64_encode($contact_logs['name']);
                        $logs->number = base64_encode($contact_logs['number']);
                        $logs->time = $date . ' ' . $time;
                        $logs->duration = $duration;
                        $logs->call_type = $contact_logs['call_type'];
                        $logs->save(false);

                        $values[] = [
                            'user_id' => $post['user_id'],
                            'contact_id' => $cont_id[$contactz],
                            'name' => base64_decode($logs->name),
                            'number' => base64_decode($logs->number),
                            'duration' => $duration,
                            'time' => $contact_logs['time'],
                            'call_type' => $contact_logs['call_type'],
                        ];
                    }
                } else {

                    $logs = new CallLogs();
                    $logs->user_id = $post['user_id'];
                    $logs->contact_id = '';
                    $logs->name = base64_encode($contact_logs['name']);
                    $logs->number = base64_encode($contact_logs['number']);
                    $logs->time = $date . ' ' . $time;
                    $logs->duration = $duration;
                    $logs->call_type = $contact_logs['call_type'];
                    $logs->save(false);

                    $values[] = [
                        'user_id' => $post['user_id'],
                        'contact_id' => '',
                        'name' => base64_decode($logs->name),
                        'number' => base64_decode($logs->number),
                        'duration' => $duration,
                        'time' => $contact_logs['time'],
                        'call_type' => $contact_logs['call_type'],
                    ];
                }
            }



            if ($check) {
                $counts = CallLogs::find()->select('date(time)')->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->distinct()->count();
                if ($counts > 30) {
                    $res_count = $counts - 30;
                    $query = "SELECT * FROM call_logs WHERE user_id = $id and isDeleted = 0 GROUP BY DATE(time) limit $res_count";
                    $result = CallLogs::findBySql($query)->all();
                    foreach ($result as $res) {
                        $arr_time [] = $res['time'];
                    }

                    if ($arr_time) {
                        $del = CallLogs::find()->where(['in', 'time', $arr_time])->andWhere(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->all();
                        foreach ($del as $rec) {
                            $rec->delete();
                        }
                    }
                }
                return [
                    'success' => true,
                    'message' => 'Success',
                    'data' => $values
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid request',
                ];
            }
        }
    }

    public function actionLogslists() {
        $logs = new CallLogs();
        $post = Yii::$app->request->getBodyParams();
        $arr_null = array();
        $check = false;
        $logs = CallLogs::find()->orderBy(['call_id' => SORT_ASC])->where(['user_id' => $post['user_id']])->andWhere(['contact_id' => NULL])->andWhere(['isDeleted' => 0])->all();

        foreach ($logs as $log) {
            $null_logs = Contacts::find()->where(['user_id' => $post['user_id']])->all();

            foreach ($null_logs as $null) {
                $arr_null[] = $null['mobile_no'];
            }

            if (in_array($log['number'], $arr_null)) {

                $null_logs = Contacts::find()->where(['user_id' => $post['user_id']])->andWhere(['mobile_no' => $log['number']])->one();
                $log->contact_id = $null_logs['contact_id'];
                $log->name = $null_logs['name'];
                $log->save();
            }
        }

        $logz = CallLogs::find()->orderBy(['call_id' => SORT_DESC])->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->all();
        if ($logz) {
            foreach ($logz as $log_values) {
                $values[] = [
                    'contact_id' => $log_values->contact_id,
                    'name' => base64_decode($log_values->name),
                    'number' => base64_decode($log_values->number),
                    'time' => $log_values->time,
                    'call_type' => $log_values->call_type,
                    'duration' => $log_values->duration,
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'No logs found.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Success',
            'data' => $values
        ];
    }

    public function actionSinglelog() {
        $logs = new CallLogs();
        $post = Yii::$app->request->getBodyParams();
        $check = false;
        $id = $post['user_id'];
        $ar_num = array();
        $arr_time = array();
        if (!empty($post)) {
            $logs->load(Yii::$app->request->getBodyParams(), '');
            $check = true;
            $contacts = Contacts::find()->where(['mobile_no' => $post['number']])->andWhere(['user_id' => $post['user_id']])->one();
            $contact_id = $contacts['contact_id'];

            $minutes = floor($post['duration'] / 60);
            $seconds = $post['duration'] % 60;
            $duration = "$minutes min(s) " . "$seconds sec(s) ";


            $date = date("Y-m-d", strtotime($post['time']));
            $time = date("H:i:s", strtotime($post['time']));


            $logs = new CallLogs();
            $logs->user_id = $post['user_id'];
            $logs->contact_id = $contact_id;
            $logs->name = base64_encode($post['name']);
            $logs->number = base64_encode($post['number']);
            $logs->time = $date . ' ' . $time;
            $logs->duration = $duration;
            $logs->call_type = $post['call_type'];
            $logs->save(false);


            $values[] = [
                'user_id' => $logs->user_id,
                'contact_id' => $logs->contact_id,
                'name' => base64_decode($logs->name),
                'number' => base64_decode($logs->number),
                'duration' => $logs->duration,
                'time' => $post['time'],
                'call_type' => $logs->call_type,
            ];

            if ($check) {
                $counts = CallLogs::find()->select('date(time)')->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->distinct()->count();
                if ($counts > 30) {
                    $res_count = $counts - 30;
                    $query = "SELECT * FROM call_logs WHERE user_id = $id and isDeleted = 0 GROUP BY DATE(time) limit $res_count";
                    $result = CallLogs::findBySql($query)->all();
                    foreach ($result as $res) {
                        $arr_time [] = $res['time'];
                    }

                    if ($arr_time) {
                        $del = CallLogs::find()->where(['in', 'time', $arr_time])->andWhere(['user_id' => $post['user_id']])->all();
                        foreach ($del as $rec) {
                            $rec->delete();
                        }
                    }
                }
                return [
                    'success' => true,
                    'message' => 'Success',
                    'data' => $values
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid request',
                ];
            }
        }
    }

    public function actionSearchlogs() {
        $post = Yii::$app->request->getBodyParams();
        $logs = CallLogs::find()->where(['user_id' => $post['user_id']])->all();
        $na = base64_encode($post['search_log']);
        //print_r($na); exit;
        $id = $post['user_id'];
        $check = false;
        $query = "SELECT * FROM call_logs WHERE user_id = $id AND (number LIKE '%$na%' OR name LIKE '%$na%' ) AND isDeleted = 0";
        $result = CallLogs::findBySql($query)->all();
        foreach ($result as $log) {
            $check = true;
            $values[] = [
                'name' => base64_decode($log['name']),
                'number' => base64_decode($log['number']),
                'contact_id' => $log['contact_id'],
                'duration' => $log['duration'],
                'call_type' => $log['call_type'],
                'time' => $log['time'],
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

    public function actionSearchbydate() {
        $post = Yii::$app->request->getBodyParams();
        $from = $post['from'];
        $to = $post['to'];
        $id = $post['user_id'];
        $check = false;
        $query = "SELECT * FROM call_logs WHERE user_id = $id and (DATE(time) between  '$from' and '$to' ) and isDeleted = 0";
        $result = CallLogs::findBySql($query)->all();
        foreach ($result as $log) {
            $check = true;
            $values[] = [
                'name' => base64_decode($log['name']),
                'number' => base64_decode($log['number']),
                'contact_id' => $log['contact_id'],
                'duration' => $log['duration'],
                'call_type' => $log['call_type'],
                'time' => $log['time'],
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

    public function actionLogdetails() {
        $post = Yii::$app->request->getBodyParams();
        $num = base64_encode($post['number']);
        $id = $post['user_id'];
        $check = false;
        $arr_number = array();
        $call_logs = CallLogs::find()->where(['user_id' => $post['user_id']])->all();
        foreach ($call_logs as $calls) {
            $arr_number [] = $calls['number'];
        }
        if (in_array($num, $arr_number)) {

            $query = "SELECT * FROM call_logs WHERE number = '$num' and isDeleted = 0 and user_id = '$id' ";
            $result = CallLogs::findBySql($query)->all();
            foreach ($result as $log) {

                $date = date("d-M-Y", strtotime($log['time']));
                $time = date("H:i:s", strtotime($log['time']));


                $check = true;
                $values[] = [
                    'duration' => $log['duration'],
                    'call_type' => $log['call_type'],
                    'time' => $date . ' ' . $time,
                ];
            }
            if ($check) {
                return [
                    'success' => true,
                    'message' => 'Success',
                    'name' => base64_decode($log['name']),
                    'number' => base64_decode($log['number']),
                    'contact_id' => $log['contact_id'],
                    'data' => $values
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No contact exists',
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'No contact exists',
            ];
        }
    }

    public function actionDeletelog() {
        $post = Yii::$app->request->getBodyParams();
        $num = base64_encode($post['number']);
        $check = false;
        $logs = CallLogs::find()->where(['number' => $num])->one();
        if ($num == $logs['number']) {
            $check = false;
            if ($num == $logs['number']) {
                $logs->delete();
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
    }

    public function actionRemovelog() {
        $post = Yii::$app->request->getBodyParams();
        $num = base64_encode($post['number']);
        $flag = $post['flag'];
        $check = false;
        $logs = CallLogs::find()->where(['call_id' => $post['call_id']])->one();
        if ($flag == 0) {
            if ($num == $logs['number']) {
                $check = false;
                if ($num == $logs['number']) {
                    $check = true;
                    $logs->delete();
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
        } else {
            $logs_all = CallLogs::find()->where(['user_id' => $post['user_id']])->all();
            foreach ($logs_all as $all) {
                if ($all['number'] == $num) {
                    $check = true;
                    $all->delete();
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
    }

}

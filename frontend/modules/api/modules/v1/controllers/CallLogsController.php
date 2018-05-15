<?php

namespace app\modules\api\modules\v1\controllers;

use common\models\CallLogs;
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
        $time = array();
        if (!empty($post)) {
            $logs->load(Yii::$app->request->getBodyParams(), '');
            foreach ($arr as $keys => $contact_logs) {
                $check = true;
                $values[] = [
                    'user_id' => $post['user_id'],
                    'contact_id' => $contact_logs['contact_id'],
                    'name' => $contact_logs['name'],
                    'number' => $contact_logs['number'],
                    'duration' => $contact_logs['duration'],
                    'time' => $contact_logs['time'],
                    'call_type' => $contact_logs['call_type'],
                ];

                $logs = new CallLogs();
                $logs->user_id = $post['user_id'];
                $logs->contact_id = $contact_logs['contact_id'];
                $logs->name = $contact_logs['name'];
                $logs->number = $contact_logs['number'];
                $logs->time = $contact_logs['time'];
                $logs->duration = $contact_logs['duration'];
                $logs->call_type = $contact_logs['call_type'];
                $logs->save(false);
            }

            if ($check) {
                $counts = CallLogs::find()->select(['time'])->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->distinct()->count();
                if ($counts > 30) {
                    $res_count = $counts - 30;
                    $query = "SELECT * FROM call_logs WHERE user_id = $id and isDeleted = 0 GROUP BY DATE(time) limit $res_count";
                    $result = CallLogs::findBySql($query)->all();
                    foreach ($result as $res) {
                        $time[] = $res['time'];
                    }
                    if ($time) {
                        $del = CallLogs::find()->where(['in', 'time', $time])->all();
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
        $logs = CallLogs::find()->orderBy(['call_id' => SORT_DESC])->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->all();
        foreach ($logs as $log):
            $values[] = [
                'contact_id' => $log->contact_id,
                'name' => $log->name,
                'number' => $log->number,
                'time' => $log->time,
                'call_type' => $log->call_type,
            ];
        endforeach;
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
        if (!empty($post)) {
            $logs->load(Yii::$app->request->getBodyParams(), '');
            $check = true;
            $logs = new CallLogs();
            $logs->user_id = $post['user_id'];
            $logs->contact_id = $post['contact_id'];
            $logs->name = $post['name'];
            $logs->number = $post['number'];
            $logs->time = $post['time'];
            $logs->duration = $post['duration'];
            $logs->call_type = $post['call_type'];
            $logs->save(false);

            $values[] = [
                'user_id' => $logs->user_id,
                'contact_id' => $logs->contact_id,
                'name' => $logs->name,
                'number' => $logs->number,
                'duration' => $logs->duration,
                'time' => $logs->time,
                'call_type' => $logs->call_type,
            ];

            if ($check) {
                $counts = CallLogs::find()->select(['time'])->where(['user_id' => $post['user_id']])->andWhere(['isDeleted' => 0])->distinct()->count();
                if ($counts > 30) {
                    $res_count = $counts - 30;
                    $query = "SELECT * FROM call_logs WHERE user_id = $id and isDeleted = 0 GROUP BY DATE(time) limit $res_count";
                    $result = CallLogs::findBySql($query)->all();
                    foreach ($result as $res) {
                        $time[] = $res['time'];
                    }
                    if ($time) {
                        $del = CallLogs::find()->where(['in', 'time', $time])->all();
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
        $na = $post['search_log'];
        $id = $post['user_id'];
        $check = false;
        $query = "SELECT * FROM call_logs WHERE user_id = $id AND (number LIKE '%$na%' OR name LIKE '%$na%' ) AND isDeleted = 0";
        $result = CallLogs::findBySql($query)->all();
        foreach ($result as $log) {
            $check = true;
            $values[] = [
                'name' => $log['name'],
                'number' => $log['number'],
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
                'name' => $log['name'],
                'number' => $log['number'],
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
        $num = $post['number'];
        $check = false;
        $query = "SELECT * FROM call_logs WHERE number = $num and isDeleted = 0";
        $result = CallLogs::findBySql($query)->all();
        foreach ($result as $log) {
            $check = true;
            $values[] = [
                'name' => $log['name'],
                'number' => $log['number'],
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

    public function actionDeletelog() {
        $post = Yii::$app->request->getBodyParams();
        $num = $post['number'];
        $check = false;
        $logs = CallLogs::find()->where(['number' => $post['number']])->one();
        if ($post['number'] == $logs['number']) {
            $check = false;
            if ($post['number'] == $logs['number']) {
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
        $num = $post['number'];
        $flag = $post['flag'];
        $check = false;
        $logs = CallLogs::find()->where(['call_id' => $post['call_id']])->one();
        if ($flag == 0) {
            if ($post['number'] == $logs['number']) {
                $check = false;
                if ($post['number'] == $logs['number']) {
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
                if ($all['number'] == $post['number']) {
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

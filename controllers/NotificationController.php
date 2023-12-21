<?php

  namespace app\controllers;

  use app\models\Notifications;
  use Yii;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;
  use yii\web\Controller;
  use yii\web\ServerErrorHttpException;

  class NotificationController extends Controller {

    public function beforeAction($action) {
      Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
      if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
        Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');
        Yii::$app->end();
      }
      $this->enableCsrfValidation = false;
      return parent::beforeAction($action);
    }

    public function behaviors() {
      $behaviors = parent::behaviors();
      $behaviors['authenticator'] = [
        'class' => HttpBearerAuth::class,
        'except' => ['options'],
      ];

      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
      ];

      $behaviors['verbs'] = [
        'class' => VerbFilter::className(),
        'actions' => [
          'notis' => ["get"],
          'notis-movil' => ["get"]
        ],
      ];
      return $behaviors;
    }

    /**
     * Funcion que lista las notifcaciones recientes en la aplicacion web.
     * endpoint: /notification/notis
     * @author Cristhian Mercado
     * @method actionNotis
     */
    public function actionNotis() {
      return Notifications::find()
        ->where(["canal" => "web",])
        ->andWhere(['not', ['emmit_at' => null]])
        ->orderBy(['id' => SORT_DESC])
        ->limit(5)
        ->all();
    }

    /**
     * Funcion que devuelve el historia de notificaciones de la aplicacion movil
     * * Endpoint: /notification/notis-movil
     * @param JSON $body los datos de los usuarios;
     * @author Yurguen Pariente
     * @method actionNotisMovil
     */
    public function actionNotisMovil() {
      $user = Yii::$app->user->identity->codigo_sis;
      return Notifications::find()->where(["canal" => "movil", "codigo_sis" => $user])
        ->orderBy(['id' => SORT_DESC])
        ->limit(15)
        ->all();
    }

    /**
     * Funcion que crea registra una notificacion, recibe un objeto JSON con
     * los datos
     * @param $params
     * @return true
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method createNoti
     */
    public static function createNoti($params) {
      $canal = $params["canal"];
      if($canal === "movil"){
        $users = UserController::getUsersMovil();
        foreach ($users as $user){
          $params["codigo_sis"] = $user->codigo_sis;
          $notiModel = new Notifications($params);
          $notiModel->save();
        }
          
      }else{
        $notiModel = new Notifications($params);
        if ($notiModel->save()) {
          return true;
        } else {
          throw new ServerErrorHttpException("no se creo la notificacion");
        }
      }
    }
  }

<?php

  namespace app\controllers;

  use app\models\Empresa;
  use app\models\Sucursal;
  use Yii;
  use yii\base\ExitException;
  use yii\base\InvalidConfigException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;
  use yii\web\NotFoundHttpException;
  use yii\filters\VerbFilter;
  use yii\web\ServerErrorHttpException;

  class SucursalController extends Controller {

    public function init() {
      Yii::warning(getallheaders());
      parent::init();
    }

    /**
     * @throws ExitException
     * @throws BadRequestHttpException
     */
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
        'except' => ['options']
      ];
      $behaviors['verbs'] = [
        'class' => VerbFilter::className(),
        'actions' => [
          'update' => ['post', 'put'],
          'delete' => ['delete'],
          'create' => ['post']
        ]
      ];
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className()
      ];
      return $behaviors;
    }

    /**
     * Accion que permite registrar una sucursal de una empresa
     * endpoint: sucursal/create
     * @return array
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->getBodyParams();
      $sucursal = new Sucursal($params);
      $sucursal->created_at = date("Y-m-d H:i:s");
      $sucursal->updated_at = date("Y-m-d H:i:s");
      if ($sucursal->save()) {
        $r = ["status" => true, "msg" => "Sucursal creado exitosamente!"];
      } else {
        throw new ServerErrorHttpException("Algo salio mal vuelva a intentarlo", $sucursal->getErrors());
      }
      return $r;
    }

    /**
     * Accion que permite actualizar una sucursal
     * endpoint: sucursal/update
     * @param $id
     * @return array
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionUpdate
     */
    public function actionUpdate($id) {
      $params = Yii::$app->request->getBodyParams();
      $params['updated_at'] = date("Y-m-d H:i:s");
      if ($sucursal = Sucursal::findOne($id)) {
        if ($sucursal->load($params, '') && $sucursal->save()) {
          $r = ["status" => true, "msg" => "Sucursal actualizado exitosamente!"];
        } else {
          throw new ServerErrorHttpException("Algo salio mal al actualizar el sucursal");
        }
      } else {
        throw new ServerErrorHttpException("No existe la sucursal");
      }
      return $r;
    }

    /**
     * Accion que permite eliminar de forma logica una sucursal.
     * endpoint: sucursal/delete
     * @param $id
     * @return array
     * @author Cristhian Mercado
     * @method actionDelete
     */
    public function actionDelete($id) {
      $r = null;
      $removed["removed"] = date("Y-m-d H:i:s");
      if ($sucursal = Sucursal::findOne($id)) {
        if ($sucursal->load($removed, '') && $sucursal->save()) {
          $r = ["status" => true, "msg" => "Sucursal eliminado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          $r = ["status" => false, "msg" => "Algo salio mal al eliminar la sucursal!",
                "error" => $sucursal->getErrors()];
        }
      }
      return $r;
    }


  }

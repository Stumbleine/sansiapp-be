<?php

  namespace app\controllers;

  use app\models\Location;
  use Yii;
  use yii\base\ExitException;
  use yii\base\InvalidConfigException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;

  class LocationController extends Controller {
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
          'create' => ['post']
        ]
      ];
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
        'only' => ['list']
      ];
      return $behaviors;
    }

    /**
     * @return array
     * @throws InvalidConfigException
     * Accion que permite el registro de una locacion, recibe un objeto JSON
     * con los datos.
     * * endpoint: location/create
     * @author Cristhian Mercado
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->getBodyParams();
      $location = new Location($params);
      $location->created_at = date("Y-m-d H:i:s");
      if ($location->save()) {
        UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $location->attributes], "location", "CREATE");
        return ["status" => true, "msg" => "Registro de locacion existoso"];
      } else {
        Yii::$app->response->statusCode = 400;
        return [
          "status" => false, "msg" => "Algo salio mal al registrar la locacion",
          "errors" => $location->getErrors()
        ];
      }
    }

    /**
     * Accion que permite lista todas las locaciones, recibe parametro search
     * para la busqueda por caracteres
     * * endpoint: location/list
     * @param string $search
     * @return array
     * @author Cristhian Mercado
     * @method actionList
     */
    public function actionList($search = 'All') {
      if ($search === 'All') {
        $locations = Location::find()->where(['removed' => null])->all();
      } else {
        $locations = Location::find()->where(['removed' => null])
          ->andFilterWhere([
            'or',
            ['ilike', 'location.name', $search],
            ['ilike', 'location.description', $search],
            ['ilike', 'location.type', $search],
          ])
          ->all();
      }
      if ($locations) {
        return $locations;
      } else {
        return null;
      }
    }

    /**
     * Accion que permite actualizar la informacion de una locacion, recibe el
     * identificador como parametro
     * * endpoint: location/update
     * @param  $id
     * @return array
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionUpdate
     */
    public function actionUpdate($id) {
      $params = Yii::$app->request->getBodyParams();
      if ($location = Location::findOne(['removed' => null, 'id' => $id])) {
        $location->updated_at = date("Y-m-d H:i:s");
        if ($location->load($params, '') && $location->save()) {
          return ["status" => true, "msg" => "Location actualizado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          return [
            "status" => false, "msg" => "Algo salio mal al actualizar el location",
            "error" => $location->getErrors()
          ];
        }
      } else {
        Yii::$app->response->statusCode = 404;
        return ["status" => false, "msg" => "No existe el producto"];
      }
    }

    /**
     * Accion que permite eliminar de forma logica una locacion, recibe el
     * identificador como parametro
     * * endpoint: location/delete
     * @param  $id
     * @return array
     * @author Cristhian Mercado
     * @method actionDelete
     */
    public function actionDelete($id) {
      $model["removed"] = date("Y-m-d H:i:s");
      if ($location = Location::findOne($id)) {
        if ($location->load($model, '') && $location->save()) {
          return ["status" => true, "msg" => "location eliminado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          return [
            "status" => false, "msg" => "Algo salio mal al eliminar el location!",
            "error" => $location->getErrors()
          ];
        }
      } else {
        Yii::$app->response->statusCode = 404;
        return ["status" => false, "msg" => "No existe el location"];
      }
    }
  }

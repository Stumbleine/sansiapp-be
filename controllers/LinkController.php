<?php

  namespace app\controllers;

  use app\models\Link;
  use Yii;
  use yii\base\ExitException;
  use yii\base\InvalidConfigException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;

  class LinkController extends Controller {
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
          'create' => ['post'],
          'list' => ['get'],
          'update' => ['post'],
          'delete' => ['delete']
        ]
      ];
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
        'only' => ['list']
      ];
      return $behaviors;
    }

    /**
     * Funcion que lista los enlaces segun su prioridad, recibe parametro
     * search para la busqueda por caracteres
     * * Endpoint: link/list
     * @param string $search Busqueda de un enlace
     * @author Cristhian Mercado - Yurguen Pariente
     * @method actionList
     */
    public function actionList($search = 'All') {
      if ($search === 'All') {
        $links = Link::find()->where(['removed' => null])
          ->orderBy(["priority" => SORT_DESC])->all();
      } else {
        $links = Link::find()->where(['removed' => null])
          ->andFilterWhere([
            'or',
            ['ilike', 'link.title', $search],
            ['ilike', 'link.description', $search],
          ])
          ->orderBy(["priority" => SORT_DESC])
          ->all();
      }
      if ($links) {
        return $links;
      } else {
        return null;
      }
    }

    /**
     * Accion que permite crear nuevos links de sitios web, documentos, etc.
     * importantes para la umss, recibe un objecto JSON con los datos
     * * endpoint: link/create
     * @return array
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->getBodyParams();
      $link = new Link($params);
      $link->created_at = date("Y-m-d H:i:s");
      if ($link->save()) {

        UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $link->attributes], "link", "CREATE");
        return ["status" => true, "msg" => "Registro de link existoso"];
      } else {
        Yii::$app->response->statusCode = 400;
        return [
          "status" => false, "msg" => "Algo salio mal al registrar el link",
          "errors" => $link->getErrors()
        ];
      }
    }

    /**
     * Accion que permite actualizar los datos de un link, recibe un objecto
     * JSON con los datos
     * * endpoint: link/update
     * @param  $id
     * @return array
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionUpdate
     */
    public function actionUpdate($id) {
      $params = Yii::$app->request->getBodyParams();
      if ($link = Link::findOne(['removed' => null, 'id' => $id])) {
        $link->updated_at = date("Y-m-d H:i:s");
        if ($link->load($params, '') && $link->save()) {
          return ["status" => true, "msg" => "Link actualizado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          return [
            "status" => false, "msg" => "Algo salio mal al actualizar el link",
            "error" => $link->getErrors()
          ];
        }
      } else {
        Yii::$app->response->statusCode = 404;
        return ["status" => false, "msg" => "No existe el producto"];
      }
    }

    /**
     * Accion que permite eliminar de forma logica un link, recibe el
     * identificador como parametro
     * * endpoint: link/delete
     * @param  $id
     * @return array
     * @author Cristhian Mercado
     * @method actionDelete
     */
    public function actionDelete($id) {
      $model["removed"] = date("Y-m-d H:i:s");
      if ($link = Link::findOne($id)) {
        if ($link->load($model, '') && $link->save()) {
          return ["status" => true, "msg" => "Link eliminado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          return [
            "status" => false, "msg" => "Algo salio mal al eliminar el link!",
            "error" => $link->getErrors()
          ];
        }
      } else {
        Yii::$app->response->statusCode = 404;
        return ["status" => false, "msg" => "No existe el link"];
      }
    }
  }

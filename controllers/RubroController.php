<?php

  namespace app\controllers;

  use app\models\Empresa;
  use Yii;
  use app\models\Rubro;
  use yii\base\InvalidConfigException;
  use yii\db\StaleObjectException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\web\Controller;
  use yii\filters\VerbFilter;
  use yii\web\ServerErrorHttpException;

  class RubroController extends Controller {
    /**
     * @inheritDoc
     */
    public function init() {
      Yii::warning(getallheaders());
      parent::init();
    }

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
          'list' => ['get'],
          'create' => ['post'],
        ]
      ];
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
      ];
      return $behaviors;
    }

    /**
     * Accion que permite listar los rubros, recibe parametros para los filtros
     * endpoint: rubro/list
     * @param  $search
     * @return array
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionList
     */
    public function actionList($search = 'All') {
      $rubros = null;
      if ($search === 'All') {
        $rubros = Rubro::find()->orderBy(['updated_at' => SORT_DESC])->all();
      } else {
        $rubros = Rubro::find()
          ->andFilterWhere(['or',
                            ['ilike', 'rubro.nombre', $search],
                            ['ilike', 'rubro.descripcion', $search]])
          ->all();
        if (!$rubros) {
          throw new ServerErrorHttpException("No se encontraron coincidencias.");
        }
      }
      if ($rubros) {
        $r = null;
        foreach ($rubros as $rubro) {
          $haveCompanie = Empresa::find()->where(["rubro" => $rubro->nombre])
            ->one();
          $r[] = [
            "nombre" => $rubro->nombre,
            "icono" => $rubro->icono,
            "descripcion" => $rubro->descripcion,
            "tieneEmpresas" => (bool)$haveCompanie
          ];
        }
        return $r;
      } else {
        Yii::$app->response->statusCode = 400;
      }
    }

    /**
     * Accion que permite registrar un rubro nuevo, recibe un objeto JSON con
     * los datos.
     * endpoint: rubro/create
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->post();
      $model = new Rubro($params);
      $model->updated_at = date("Y-m-d H:i:s");
      if ($model->save()) {
        UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $model->attributes], "rubro", "CREATE");
        return $model;
      } else {
        throw new ServerErrorHttpException("Algo salio mal revise sus datos");
      }
    }

    /**
     * Accion que permite actualizar la informacion de un rubro
     * endpoint: rubro/update
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionUpdate
     */
    public function actionUpdate($id) {
      $params = Yii::$app->request->getBodyParams();
      if ($rubro = Rubro::findOne($id)) {
        $rubro->created_at = date("Y-m-d H:i:s");
        if ($rubro->load($params, '') && $rubro->save()) {
          $r = ["status" => true, "msg" => "Rubro actualizado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          $r = ["status" => false, "msg" => "Algo salio mal al actualizar el rubro", "error" => $rubro->getErrors()];
        }
      } else {
        throw new ServerErrorHttpException("No existe el rubro");
      }
      return $r;
    }

    /**
     * Accion que permite eliminar un rubro
     * endpoint: rubro/delete
     * @throws StaleObjectException
     * @throws \Throwable
     * @author Cristhian Mercado
     * @method actionDelete
     */
    public function actionDelete($id) {
      $r = null;
      if ($rubro = Rubro::findOne($id)) {
        if ($rubro->delete()) {
          $r = ["status" => true, "msg" => "Rubro eliminado exitosamente!"];
        } else {
          Yii::$app->response->statusCode = 400;
          $r = ["status" => false, "msg" => "Algo salio mal al eliminar el rubro!", "error" => $rubro->getErrors()];
        }
      }
      return $r;
    }


  }

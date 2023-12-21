<?php

namespace app\controllers;

use app\models\Empresa;
use Yii;
use app\models\Producto;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\filters\auth\HttpBearerAuth;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\ServerErrorHttpException;

class ProductoController extends Controller
{

  public function init()
  {
    Yii::warning(getallheaders());
    parent::init();
  }

  /**
   * @throws ExitException
   * @throws BadRequestHttpException
   */
  public function beforeAction($action)
  {
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
      Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');
      Yii::$app->end();
    }
    $this->enableCsrfValidation = false;
    return parent::beforeAction($action);
  }

  public function behaviors()
  {
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
        'update' => ['post', 'put'],
        'delete' => ['delete']
      ]
    ];
    $behaviors['access'] = [
      'class' => \mdm\admin\components\AccessControl::className()
    ];
    return $behaviors;
  }

  /**
   * Funcion que consulta una lista de productos con los filtros, idc y search
   * @param $idc
   * @param $search
   * @return array|null
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method getProducts
   */
  protected function getProducts($idc, $search)
  {
    $searchWhere = [
      'or',
      ['ilike', 'producto.nombre', $search],
      ['ilike', 'producto.tipo', $search],
      ['ilike', 'producto.descripcion', $search],
    ];
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $products = null;
    if (isset($roles['ADM']) || isset($roles['SADM'])) {
      //          Administrador
      if ($idc === 'All' && $search === 'All') {
        $products = Producto::find()->where(['removed' => null])->orderBy(['updated_at' => SORT_DESC])
          ->all();
      } else if ($idc !== 'All' && $search !== 'All') {
        $products = Producto::find()->where(['removed' => null, 'id_empresa' => $idc])
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])
          ->all();
      } else if ($idc !== 'All') {
        $products = Producto::find()->where(['removed' => null, 'id_empresa' => $idc])->orderBy(['updated_at' => SORT_DESC])
          ->all();
      } else if ($search !== 'All') {
        $products = Producto::find()->where(['removed' => null])
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])
          ->all();
      }
    } else if (isset($roles['PRV'])) {
      $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
      if ($empresa) {
        if ($search !== 'All') {
          $products = Producto::find()
            ->where(['removed' => null, 'id_empresa' => $empresa->id_empresa])
            ->andFilterWhere($searchWhere)->orderBy(['updated_at' =>
            SORT_DESC])
            ->all();
        } else {
          $products = Producto::find()
            ->where(['removed' => null, 'id_empresa' =>
            $empresa->id_empresa])->orderBy(['updated_at' => SORT_DESC])
            ->all();
        }
      } else {
        throw new ServerErrorHttpException("El proveedor no registro su empresa");
      }
    }
    return $products;
  }

  /**
   * Accion que lista los productos, recibe dos parametros para los filtros.
   * endpoint: product/list
   * @param string $idc
   * @param string $search
   * @return array|null
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionList
   */
  public function actionList($idc = 'All', $search = 'All')
  {
    $r = null;
    $productos = $this->getProducts($idc, $search);
    if ($productos) {
      foreach ($productos as $p) {
        $r[] = [
          'id_product' => $p->id_producto,
          'id_companie' => $p->id_empresa,
          "image" => $p->image,
          "name" => $p->nombre,
          "description" => $p->descripcion,
          "type" => $p->tipo,
          "companie" => $p->empresa->razon_social,
          "price" => $p->precio
        ];
      }
      return $r;
    } else {
      return null;
    }
  }
  //            Yii::$app->response->statusCode = 401;
  //            throw new ServerErrorHttpException("Algo salio al consultar los productos.");
  /**
   * Accion que permite registrar un producto
   * endpoint: product/create
   * @return array
   * @author Cristhian Mercado
   * @method actionCreate
   */
  public function actionCreate()
  {
    $params = Yii::$app->request->post();
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    if (!isset($roles['ADM']) && !isset($roles['SADM'])) {
      $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
      $params["id_empresa"] = $empresa->id_empresa;
    }
    $model = new Producto($params);
    $model->created_at = date("Y-m-d H:i:s");
    if ($model->save()) {
      UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $model->attributes], "producto", "CREATE");
      return ["status" => true, "msg" => "Producto actualizado exitosamente!"];
    } else {
      Yii::$app->response->statusCode = 400;
      return [
        "status" => false, "msg" => "Algo salio mal al actualizar el producto",
        "error" => $model->getErrors()
      ];
    }
  }

  /**
   * Accion que permite actualizar lo datos de un producto.
   * endpoint: product/update
   * @param $id
   * @return array
   * @throws InvalidConfigException
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionUpdate
   */
  public function actionUpdate($id)
  {
    $params = Yii::$app->request->getBodyParams();
    if ($producto = Producto::findOne($id)) {
      $producto->updated_at = date("Y-m-d H:i:s");
      if ($producto->load($params, '') && $producto->save()) {
        $r = ["status" => true, "msg" => "Producto actualizado exitosamente!"];
      } else {
        Yii::$app->response->statusCode = 500;
        $r = [
          "status" => false, "msg" => "Algo salio mal al actualizar el producto",
          "error" => $producto->getErrors()
        ];
      }
    } else {
      throw new ServerErrorHttpException("No existe el producto");
    }
    return $r;
  }

  /**
   * Accion que permite eliminar un producto de forma logica
   * endpoint: product/delete
   * @param $id
   * @return array
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionUpdate
   */
  public function actionDelete($id)
  {
    $removed["removed"] = date("Y-m-d H:i:s");
    if ($product = Producto::findOne($id)) {
      if ($product->load($removed, '') && $product->save()) {
        $r = ["status" => true, "msg" => "Producto eliminado exitosamente!"];
      } else {
        Yii::$app->response->statusCode = 400;
        $r = [
          "status" => false, "msg" => "Algo salio mal al eliminar el producto!",
          "error" => $product->getErrors()
        ];
      }
    } else {
      throw new ServerErrorHttpException("No existe el producto");
    }
    return $r;
  }
}

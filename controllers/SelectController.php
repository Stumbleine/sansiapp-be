<?php

  namespace app\controllers;

  use app\models\AuthItem;
  use app\models\Empresa;
  use app\models\Producto;
  use app\models\Rubro;
  use app\models\Sucursal;
  use app\models\User;
  use Yii;
  use yii\base\ExitException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;
  use yii\rbac\Role;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;

  class SelectController extends Controller {
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
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
      ];
      return $behaviors;
    }

    public function actionRubros() {
      return Rubro::find()->select('nombre')->all();
    }

    public function actionCompanies() {
      return Empresa::find()->where(['removed' => null, "verified" => true])
        ->select("id_empresa,razon_social")->all();
    }

    public function actionSucursales($empresa = null) {
      $r = null;
      $branchs = Sucursal::find()->where(['removed' => null, 'id_empresa' =>
        $empresa])
        ->select("id_sucursal,nombre,direccion")->all();
      foreach ($branchs as $b) {
        $r[] = ["id_branch" => $b->id_sucursal,
                "name" => $b->nombre,
                "address" => $b->direccion,
        ];
      }
      return $r;
    }

    /**
     * Accion que permite listar los productos de una empresa para un select
     * endpoint: select/product
     * @param $empresa
     * @return array
     * @author Cristhian Mercado
     * @method actionProducts
     */
    public function actionProducts($empresa = null) {
      $r = null;
      $products = Producto::find()->where(['removed' => null, 'id_empresa' =>
        $empresa])
        ->select("id_producto,nombre")->all();
      foreach ($products as $product) {
        $r[] = ["id_product" => $product->id_producto,
                "name" => $product->nombre
        ];
      }
      return $r;
    }

    /**
     * Accion que permite listar los roles para un select
     * endpoint: select/roles
     * @return array
     * @author Cristhian Mercado
     * @method actionRoles
     */
    public function actionRoles() {
      $r = null;
      $roles = Yii::$app->authManager->getRoles();
      foreach ($roles as $role) {
        $rol = AuthItem::find()->where(['name' => $role->name])->select(['name', 'label'])->one();
        $r[] = $rol;
      }
      return $r;
    }

    /**
     * Accion que permite listar los usuarios proveedores disponibles para
     * relacionarse con una empresa.
     * endpoint: select/providers
     * @return array
     * @author Cristhian Mercado
     * @method actionProviders
     */
    public function actionProviders() {
      $proveedores = null;
      $users = User::find()->select("id,nombres,apellidos")->where(['removed' => null])->all();
      foreach ($users as $user) {
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        if (isset($roles['PRV'])) {
          $proveedores [] = $user;
        }
      }
      $r = null;
      foreach ($proveedores as $prv) {
        $empresa = Empresa::find()->where(['id_proveedor' => $prv->id])->select('razon_social')->one();

        if (is_null($empresa)) {
          $r [] = $prv;
        }
      }
      return $r;
    }
  }


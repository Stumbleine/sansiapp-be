<?php

  namespace app\controllers;

  use app\models\AfilitationResponseMail;
  use app\models\Beneficio;
  use app\models\Producto;
  use app\models\Sucursal;
  use app\models\User;
  use Yii;
  use app\models\Empresa;
  use yii\base\ExitException;
  use yii\base\InvalidConfigException;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;
  use yii\filters\VerbFilter;
  use yii\web\ServerErrorHttpException;
  use yii\filters\auth\HttpBearerAuth;


  class EmpresaController extends Controller {
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
          'list' => ['get'],
          'create' => ['post'],
          'profile' => ['get'],
          'update' => ['post', 'put'],
          'delete' => ['delete'],
          'perfil' => ['get'],
          'buscar-movil' => ["get"]
        ]
      ];
      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
      ];
      return $behaviors;
    }

    public function actionList($search = 'All', $rubro = 'All') {
      $companies = null;
      $columns = 'id_empresa,razon_social,descripcion,logo, facebook,instagram,email,sitio_web,rubro';
      $searchWhere = [
        'or',
        ['ilike', 'empresa.razon_social', $search],
        ['ilike', 'empresa.descripcion', $search],
      ];
      if ($search === 'All' && $rubro === 'All') {
        $companies = Empresa::find()->select($columns)
          ->where(['removed' => null, 'verified' => true])->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($search !== 'All' && $rubro !== 'All') {
        $companies = Empresa::find()->select($columns)
          ->where(['removed' => null, 'verified' => true, 'rubro' => $rubro])
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($rubro !== 'All') {
        $companies = Empresa::find()->select($columns)
          ->where(['removed' => null, 'verified' => true, 'rubro' => $rubro])
          ->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($search !== 'All') {
        $companies = Empresa::find()->select($columns)
          ->where(['removed' => null, 'verified' => true])
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      }
      if ($companies) {
        return $companies;
      } else {
        return null;
      }

    }

    public function actionPerfil($value = null, $limit = 5, $offset = 0) {
      $arreglo = [];
      if (!is_null($value)) {
        $empresas = Empresa::find()
          ->where(['removed' => null, 'verified' => true])
          ->andWhere(['or',
            ['ilike',"razon_social", $value],
            ['ilike', "rubro",$value]])
           ->limit($limit)
          ->offset($offset)
          ->all();
      } else {
        $empresas = Empresa::find()
          ->where(['removed' => null, 'verified' => true])
          ->limit($limit)
          ->offset($offset)
          ->all();
      }

      foreach ($empresas as $e) {
        $arreglo[] = [
          "id_empresa" => $e->id_empresa,
          "razon_social" => $e->razon_social,
          "rubro" => $e->rubro,
          "telefono" => $e->telefono,
          "facebook" => $e->facebook,
          "instagram" => $e->instagram,
          "logo" => $e->logo,
          "sitio_web" => $e->sitio_web,
          "email" => $e->email,
          "descripcion" => $e->descripcion,
          "sucursales" => $e->sucursals
        ];
      }
      return $arreglo;
    }

    public function actionGetEmpresa($id){
      $e = Empresa::find()->where(["id_empresa" => $id])->one();

      return [
        "id_empresa" => $e->id_empresa,
          "razon_social" => $e->razon_social,
          "rubro" => $e->rubro,
          "telefono" => $e->telefono,
          "facebook" => $e->facebook,
          "instagram" => $e->instagram,
          "logo" => $e->logo,
          "sitio_web" => $e->sitio_web,
          "email" => $e->email,
          "descripcion" => $e->descripcion,
          "sucursales" => $e->sucursals
      ];
    }

    /**
     * Accion que devuelve toda la informacion de una empresa
     * * endpoint: empresa/profile
     * @return array
     * @author Cristhian Mercado
     * @method actionProfile
     */
    public function actionProfile($id = null) {
      $empresa = Empresa::find()->where(["id_empresa" => $id])
        ->select("id_empresa,razon_social,descripcion,logo,facebook,instagram,sitio_web,email,rubro,nit,telefono,verified,id_proveedor,rejected")
        ->one();
      if ($empresa === null || $empresa->removed) {
        Yii::$app->response->statusCode = 400;
        return ["status" => false, "msg" => "No se encontro la empresa"];
      }
      $sucursales = Sucursal::find()->where(['id_empresa' => $id,
                                             "removed" => null])->all();
      //        $ofertas = $empresa->beneficios ?? null;
      $ofertas = Beneficio::find()
        ->where(['removed' => null, "id_empresa" => $empresa->id_empresa])->limit(4)
        ->all();
      $productos = Producto::find()
        ->where(["removed" => null, "id_empresa" => $empresa->id_empresa])->limit(4)
        ->all();
      $offers = null;
      $provider = User::find()->where(["id" => $empresa->id_proveedor, 'removed' =>
        null])->select('id,nombres,apellidos,picture,email')->one();
      $cashiers = User::find()
        ->where(['cashier' => true, 'cajero_de' => $empresa->id_empresa, 'removed' => null])
        ->select('id,nombres,apellidos,picture,email')->all();
      foreach ($ofertas as $o) {
        $descuento = UtilController::getDiscount($o);
        $offers[] = [
          "id_offer" => $o->id_beneficio,
          "title" => $o->titulo,
          "discount_type" => $o->tipo_descuento,
          "discount" => $descuento,
        ];
      }
      $products = null;
      foreach ($productos as $p) {
        $products[] = [
          "id_product" => $p->id_producto,
          "name" => $p->nombre,
          "price" => $p->precio,
        ];
      }
      return [
        "companie" => $empresa,
        "branch_offices" => $sucursales,
        "offers" => $offers,
        "products" => $products,
        "users" => ["provider" => $provider, "cashiers" => $cashiers]
      ];
    }

    /**
     * @return array
     * Accion que permite registrar una empresa, recibe un body JSON con los
     * datos
     * * endpoint: empresa/create
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionCreate
     */
    public function actionCreate() {
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      $params = Yii::$app->request->getBodyParams();
      $empresa = new Empresa($params['empresa']);
      if (isset($roles['PRV']) && $empresa['id_proveedor'] === '') {
        $empresa->id_proveedor = Yii::$app->user->identity->id;
      }
      if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $empresa->verified = true;
      }
      $empresa->created_at = date("Y-m-d H:i:s");
      $empresa->updated_at = date("Y-m-d H:i:s");
      $sucursales = $params['sucursales'];

      if (!$empresa->save()) {
        Yii::$app->response->statusCode = 400;
        return ["status" => false, "msg" => "Algo salio mal al registrar la empresa", "errors" =>
          $empresa->getErrors()];
      } else {
        $id_empresa = $empresa->getPrimaryKey();
        foreach ($sucursales as $sucursal) {
          $sucursal['id_empresa'] = $id_empresa;
          $sucursal['created_at'] = date("Y-m-d H:i:s");
          $sucursal['updated_at'] = date("Y-m-d H:i:s");
          $modelSucursal = new Sucursal($sucursal);
          if (!$modelSucursal->save()) {
            throw new ServerErrorHttpException("Algo salio mal al registrar sucursales");
          } else {
            NotificationController::createNoti(
              [
                "title" => "Nueva empresa",
                "msg" => $empresa->razon_social . " se ha unido, echale un vistazo a su informacion.",
                "canal" => "web",
                "created_at" => date("Y-m-d H:i:s"),
                "id_empresa" => $empresa->id_empresa
              ]
            );
            UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $empresa->attributes], "empresa", "CREATE");
            return [
              "status" => true, "msg" => "Empresa registrado exitosamente!",
              "empresa" => $empresa->getPrimaryKey()
            ];
          }
        }
      }
    }

    /**
     * @return array
     * Accion que permite actualizar los datos de una empresa
     * * endpoint: empresa/update
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @author Cristhian Mercado
     * @method actionUpdate
     */
    public function actionUpdate($id) {
      $r = null;
      $params = Yii::$app->request->getBodyParams();
      $params['updated_at'] = date("Y-m-d H:i:s");
      if ($empresaModel = Empresa::findOne($id)) {
        if ($empresaModel->load($params, '') && $empresaModel->save()) {
          $r = ["status" => true, "msg" => "Empresa actualizado exitosamente!", "empresa" => $empresaModel->getPrimaryKey()];
        } else {
          throw new ServerErrorHttpException("Algo salio mal al actualizar la empresa");
        }
      } else {
        throw new ServerErrorHttpException("La empresa no existe");
      }
      return $r;
    }

    /**
     * @return array
     * Accion que permite remover una empresa y sus relaciones de forma logica
     * * endpoint: empresa/delete
     * @throws \Throwable
     * @author Cristhian Mercado
     * @method actionDelete
     */
    public function actionDelete($id) {
      $r = null;
      $removed["removed"] = date("Y-m-d H:i:s");
      $empresa = Empresa::findOne($id);
      $sucursales = Sucursal::find()->where(['id_empresa' => $id])->all();
      $offers = Beneficio::find()->where(['id_empresa' => $id])->all();
      $products = Producto::find()->where(['id_empresa' => $id])->all();
      $cashiers = User::find()->where(['removed' => null, 'cashier' => true, 'cajero_de' => $id])->all();
      $provider = User::find()->where(['removed' => null,
                                       'id' => $empresa->id_proveedor])->one();
      if ($provider) {
        if (!($provider->load($removed, '') && $provider->save())) {
          return ["status" => false, "msg" => "Algo salio mal al eliminar responsable!", "error" => $empresa->getErrors()];
        }
      }
      if ($empresa->load($removed, '') && $empresa->save()) {
        $r = ["status" => true, "msg" => "Empresa eliminado exitosamente!"];
      } else {
        $r = ["status" => false, "msg" => "Algo salio mal al eliminar la empresa!", "error" => $empresa->getErrors()];
      }
      foreach ($cashiers as $cashier) {
        if (!($cashier->load($removed, '') && $cashier->save())) {
          return ["status" => false, "msg" => "Algo salio mal al eliminar 
          cajeros!", "error" => $cashier->getErrors()];
        }
      }
      foreach ($sucursales as $sucursal) {
        if (!($sucursal->load($removed, ''))) {
          return ["status" => false, "msg" => "Algo salio mal al eliminar sucursales!", "error" => $sucursal->getErrors()];
        }
      }
      foreach ($offers as $offer) {
        if (!($offer->load($removed, '') && $offer->save())) {
          return ["status" => false, "msg" => "Algo salio mal al eliminar ofertas!", "error" => $offer->getErrors()];
        }
      }
      foreach ($products as $product) {
        if (!($product->load($removed, '') && $product->save())) {
          return ["status" => false, "msg" => "Algo salio mal al eliminar productos!", "error" => $product->getErrors()];
        }
      }
      return $r;
    }

    public function actionListNotVerified() {
      $pending = Empresa::find()->select('id_empresa,razon_social,descripcion,logo,email,rubro,verified,rejected')
        ->where(['removed' => null, 'verified' => false, 'rejected' => false])
        ->all();
      $rejected = Empresa::find()->select('id_empresa,razon_social,descripcion,logo, email,rubro,rejected,verified')
        ->where(['rejected' => true])->all();
      return ["pending" => $pending ?? null, "rejected" => $rejected ?? null];
    }

    /**
     * Accion que permite rechazar la afiliacion de una empresa nueva
     * * endpoint: empresa/reject
     * @return array
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionReject
     */
    public function actionReject() {
      $params = Yii::$app->request->getBodyParams();
      $model = [
        "removed" => date("Y-m-d H:i:s"),
        "rejected" => true,
        "rejection_reason" => $params["rejection_reason"]
      ];
      $removed = ["removed" => date("Y-m-d H:i:s"),];
      $sucursales = Sucursal::find()->where(['id_empresa' => $params['id_empresa']])->all();
      $offers = Beneficio::find()->where(['id_empresa' => $params['id_empresa']])->all();
      $products = Producto::find()->where(['id_empresa' => $params['id_empresa']])->all();

      if ($empresa = Empresa::findOne($params['id_empresa'])) {
        if ($empresa->load($model, '') && $empresa->save()) {
          if ($user = User::findOne($empresa->id_proveedor)) {
            $user->access_token = "access token and his company was removed.";
            if ($user->load($removed, '') && $user->save()) {
              $mail = new AfilitationResponseMail();
              $mail->Response($user, $empresa, true);
              foreach ($sucursales as $sucursal) {
                if (!($sucursal->load($removed, ''))) {
                  return ["status" => false, "msg" => "Algo salio mal al eliminar sucursales!", "error" => $sucursal->getErrors()];
                }
              }
              foreach ($offers as $offer) {
                if (!($offer->load($removed, '') && $offer->save())) {
                  return ["status" => false, "msg" => "Algo salio mal al eliminar ofertas!", "error" => $offer->getErrors()];
                }
              }
              foreach ($products as $product) {
                if (!($product->load($removed, '') && $product->save())) {
                  return ["status" => false, "msg" => "Algo salio mal al eliminar productos!", "error" => $product->getErrors()];
                }
              }
              return ["status" => true, "msg" => "Responsable y empresa removidos exitosamente!"];
            } else {
              throw new ServerErrorHttpException("Algo salio mal al remover al responsable.");
            }
          }
        } else {
          throw new ServerErrorHttpException("Algo salio mal rechazar la empresa");
        }
      } else {
        throw new ServerErrorHttpException("La empresa no existe");
      }
    }

    /**
     * Accion que permite aprobar la afiliacion de una empresa nueva
     * * endpoint: empresa/approve
     * @return array
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionReject
     */
    public function actionApprove() {
      $params = Yii::$app->request->getBodyParams();
      if ($empresa = Empresa::findOne($params['id_empresa'])) {
        $user = User::findOne($empresa->id_proveedor);
        if ($empresa->rejected === true) {
          $user->access_token = UtilController::generateToken();
          $user->load(["removed" => null], '');
          $user->save();
        }
        if ($empresa->load($params, '') && $empresa->save()) {
          $mail = new AfilitationResponseMail();
          $mail->Response($user, $empresa);
          NotificationController::createNoti(
            [
              "title" => "Nueva empresa",
              "msg" => $empresa->razon_social . " se ha unido para ofrecerte beneficios espectaculares",
              "canal" => "movil",
              "created_at" => date("Y-m-d H:i:s"),
              "id_empresa" => $empresa->id_empresa
            ]
          );
          NotificationController::createNoti(
            [
              "title" => "Empresa aprobada",
              "msg" => $empresa->razon_social . " ah sido aprobado para pertenecer al sistema de beneficios",
              "canal" => "web",
              "created_at" => date("Y-m-d H:i:s"),
              "id_empresa" => $empresa->id_empresa
            ]
          );
          return ["status" => true, "msg" => "Empresa aprobado exitosamente!"];
        } else {
          throw new ServerErrorHttpException("Algo salio mal al aprobar la empresa");
        }
      } else {
        throw new ServerErrorHttpException("La empresa no existe");
      }
    }

    /**
     * Accion que permite reconsiderar(aprobar) la afilicacion de una empresa
     * anteriormente rechazada
     * * endpoint: empresa/reconsider
     * @return array
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     * @author Cristhian Mercado
     * @method actionReject
     */
    public function actionReconsider() {
      $params = Yii::$app->request->getBodyParams();
      $id = $params['id_empresa'];
      $model = [
        "removed" => null,
        "rejected" => false,
        "verified" => true
      ];
      $removed = ["removed" => null];
      $sucursales = Sucursal::find()->where(['id_empresa' => $id])->all();
      $offers = Beneficio::find()->where(['id_empresa' => $id])->all();
      $products = Producto::find()->where(['id_empresa' => $id])->all();

      if ($empresa = Empresa::findOne($id)) {
        if ($empresa->load($model, '') && $empresa->save()) {
          if ($user = User::findOne($empresa->id_proveedor)) {
          $user->access_token = UtilController::generateToken();
            if ($user->load($removed, '') && $user->save()) {
              $mail = new AfilitationResponseMail();
              $mail->Response($user, $empresa, true);
              foreach ($sucursales as $sucursal) {
                if (!($sucursal->load($removed, ''))) {
                  return ["status" => false, "msg" => "Algo salio mal al reestablecer sucursales!", "error" => $sucursal->getErrors()];
                }
              }
              foreach ($offers as $offer) {
                if (!($offer->load($removed, '') && $offer->save())) {
                  return ["status" => false, "msg" => "Algo salio mal al reestablecer ofertas!", "error" => $offer->getErrors()];
                }
              }
              foreach ($products as $product) {
                if (!($product->load($removed, '') && $product->save())) {
                  return ["status" => false, "msg" => "Algo salio mal al reestablecer productos!", "error" => $product->getErrors()];
                }
              }
              return ["status" => true, "msg" => "Responsable y empresa reestablecidos exitosamente!"];
            } else {
              throw new ServerErrorHttpException("Algo salio mal al reestablecer al responsable.");
            }
          }
        } else {
          throw new ServerErrorHttpException("Algo salio mal reconsiderar la empresa");
        }
      } else {
        throw new ServerErrorHttpException("La empresa no existe");
      }
    }
  }

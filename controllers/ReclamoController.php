<?php

  namespace app\controllers;

  use app\models\Beneficio;
  use app\models\Empresa;
  use app\models\Producto;
  use app\models\Reclamo;
  use Yii;
  use yii\base\ExitException;
  use yii\base\InvalidConfigException;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;

  class ReclamoController extends Controller {
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
        'except' => ['options', 'index'],
      ];

      $behaviors['access'] = [
        'class' => \mdm\admin\components\AccessControl::className(),
      ];

      $behaviors['verbs'] = [
        'class' => VerbFilter::className(),
        'actions' => [
          'create' => ["post"],
          'list' => ["get"]
        ],
      ];
      return $behaviors;
    }

    /**
     * @param JSON $body los datos de los usuarios;
     * @throws InvalidConfigException
     * Funcion que crea un reclamo realizado por un estudiante
     * * Endpoint: /reclamo/create
     * @author Yurguen Pariente
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->getBodyParams();
      $existe = Reclamo::find()
        ->where([
          "id_beneficio" => $params["id_beneficio"],
          "id_user" => $params["id_user"]
        ])
        ->one();
      if ($existe) {
        return [
          'status' => false,
          'msg' => 'Usted ya ha realizado un reporte sobre este beneficio'
        ];
      }
      $params["created_at"] = date("Y-m-d H:i:s");
      $new = new Reclamo($params);

      if ($oferta = Beneficio::findOne($params['id_beneficio'])) {
//        return $oferta;
        $oferta->image = "";
        $productos = null;
        if (!is_null($oferta->productos['productos'])) {
          foreach ($oferta->productos['productos'] as $dp) {
            $producto = Producto::find()->where(['id_producto' => $dp])->one();
            if ($producto) {
              $productos[] = [
                'id_product' => $producto->id_producto,
                'name' => $producto->nombre,
              ];
            }
          }
        }

        $oferta->productos = $productos;
        $new->detalle_oferta = $oferta;
        if ($empresa = Empresa::findOne($oferta->id_empresa)) {
          $new->detalle_empresa = $empresa;
        }
      };

      if ($new->save()) {
        return [
          'status' => true,
          'reclamo' => $new
        ];
      } else {
        return [
          'status' => false,
          "erro" => $new->getErrors(),
          'msg' => 'Ocurrio un error, por favor inténtelo nuevamente.'
        ];
      }
    }

    /**
     * Accion que permite listar los reclamos realizados por estudiantes,
     * recibe parametros para los filtros.
     * endpoint: reclamo/list
     * @param  $search
     * @param  $type
     * @return array
     * @author Cristhian Mercado
     * @method actionList
     */
    public function actionList($search = 'All', $type = 'All') {
      $typeWhere = $type !== 'All' ? ['tipo_reclamo' => $type] : '';
      if ($search === 'All') {
        $reclamos = Reclamo::find()
          ->where($typeWhere)
//            ->where(["id_beneficio" => $id_beneficio])
          ->all();
      } else {
        $reclamos = Reclamo::find()->where($typeWhere)
          ->andFilterWhere([
            'or',
            ['ilike', 'reclamo.tipo_reclamo', $search],
            ['ilike', 'reclamo.descripcion', $search],
          ])
          ->all();
      }
      $complaints = null;
      foreach ($reclamos as $rec) {
        $reclamo = new Reclamo($rec);
        $student = [
          'names' => $reclamo->user->nombres,
          'last_names' => $reclamo->user->apellidos,
          'picture' => $reclamo->user->picture,
          'email' => $reclamo->user->email
        ];

        $companie = new Empresa($reclamo->detalle_empresa);
        $offer = new Beneficio($reclamo->detalle_oferta);

        $complaints [] = [
          'id' => $reclamo->id_reclamo,
          'description' => $reclamo->descripcion,
          'type' => $reclamo->tipo_reclamo,
          'date' => $reclamo->created_at,
          'companie' => [
            "name" => $companie->razon_social,
            //            "logo" => $companie->logo,
            "phone" => $companie->telefono,
            "email" => $companie->email,
            "rubro" => $companie->rubro
          ],
          "offer" => [
            "title" => $offer->titulo,
            "discount" => $this->getDiscountLabel($offer),
            "status" => $offer->status,
            //            "image" => $offer->image,
            "start_date" => $offer->fecha_inicio,
            "end_date" => $offer->fecha_fin,
            "description" => $offer->condiciones,
            "products" => $offer->productos
          ],
          'student' => $student,
        ];
      }
      return $complaints;
    }

    /**
     * Funcion que customiza el label de descuento
     * @param Beneficio $offer
     * @return string|void|null
     * @author Cristhian Mercado
     * @method getDiscountLabel
     */
    protected function getDiscountLabel(Beneficio $offer) {
      if ($offer->tipo_descuento === 'Descripcion') {
        return $offer->dto_descripcion;
      } else if ($offer->tipo_descuento === 'Monetario') {
        return "Bs. " . $offer->dto_monetario;
      } else if ($offer->tipo_descuento === 'Porcentual') {
        return $offer->dto_porcentaje . "%";
      }
    }
  }

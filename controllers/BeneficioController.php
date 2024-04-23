<?php

namespace app\controllers;

use app\models\Codigo;
use app\models\Empresa;
use app\models\Producto;
use app\models\Sucursal;
use Yii;
use app\models\Beneficio;
use app\models\CodigoPregenerado;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class BeneficioController extends Controller
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
      'except' => ['options'],
    ];

    $behaviors['access'] = [
      'class' => \mdm\admin\components\AccessControl::className(),
    ];

    $behaviors['verbs'] = [
      'class' => VerbFilter::className(),
      'actions' => [
        'beneficios' => ["get"],
        'solo' => ["post"],
        'desc' => ["post"],
        'filtros' => ["get"],
        'crear-beneficio' => ["post"],
        'lista' => ["post"],
        'detalle-oferta' => ["post"],
        'create' => ["post"],
        "enviar-notificacion" => ["post"],
        "beneficios-paginacion" => ["get"],
        "busqueda" => ["get"],
        "filter-group"
      ],
    ];
    return $behaviors;
  }

  /**
   * Funcion que obtiene uno o mas beneficios
   * * Endpoint: /beneficio/beneficios
   * @param string $id El id del beneficio;
   * @author Cristhian Mercado - Yurguen Pariente
   * @method actionBeneficios
   */
  public function actionBeneficios($id = null)
  {
    $respuesta = [];
    if (!is_null($id)) {
      $beneficios = Beneficio::find()
        ->where(["id_beneficio" => $id, "status" => "VIGENTE", "removed" => null])
        ->all();
      $sucursales = null;
      foreach ($beneficios as $b) {
        if (is_null($b->sucursales_disp["ids"])) {
          $sucursales = $b->empresa->sucursals;
        } else {
          $ids_suc = $b->sucursales_disp["ids"];
          $sucursales = Sucursal::find()
            ->where(["id_sucursal" => $ids_suc])
            ->all();
        }
        $desc = UtilController::getDiscount($b);
        if (isset($b->productos["productos"])) {
          $ids = $b->productos["productos"];
          $productos2 = Producto::find()
            ->select(["nombre", "tipo", "precio", "descripcion"])
            ->andWhere(["id_producto" => $ids])
            ->all();
        } else {
          $productos2 = [];
        }

        $respuesta[] = [
          "id_beneficio" => $b->id_beneficio,
          "titulo" => $b->titulo,
          "condiciones" => $b->condiciones,
          'descuento' => $desc,
          "tipo_descuento" => $b->tipo_descuento,
          "image" => $b->image,
          "fecha_fin" => $b->fecha_fin,
          "empresa" => [
            "id_empresa" => $b->empresa->id_empresa,
            "razon_social" => $b->empresa->razon_social,
            "sucursales" => $sucursales,
            "facebook" => $b->empresa->facebook,
            "instagram" => $b->empresa->instagram,
            "sitio" => $b->empresa->sitio_web,
            "email" => $b->empresa->email
          ],
          "productos" => $productos2,
          "frequency_redeem" => $b->frequency_redeem
        ];
      }
    } else {

      $beneficios = Beneficio::find()
        ->select(["id_beneficio", "titulo", "tipo_descuento", "image", "dto_monetario", "dto_porcentaje", "dto_descripcion", "status"])
        ->where(["status" => "VIGENTE", "removed" => null])
        ->all();
      foreach ($beneficios as $b) {
        $desc = UtilController::getDiscount($b);
        $respuesta[] = [
          "id_beneficio" => $b->id_beneficio,
          "titulo" => $b->titulo,
          "tipo_descuento" => $b->tipo_descuento,
          "image" => $b->image,
          "descuento" => $desc
        ];
      }
    }
    return $respuesta;
  }

  /**
   * @throws Exception
   */
  public function actionFiltros($filtro, $categoria, $inicial)
  {
    $respuesta = [];
    switch ($filtro) {
      case 'menor':
        $respuesta = $this->tipoDescuento(true, $categoria, $inicial);
        break;
      case 'mayor':
        $respuesta = $this->tipoDescuento(false, $categoria, $inicial);
        break;
      case 'monetario':
        $respuesta = $this->solo(true, $categoria, $inicial);
        break;
      case 'porcentual':
        $respuesta = $this->solo(false, $categoria, $inicial);
        break;
      case 'descriptivo':
        $respuesta = $this->Desc($inicial, $categoria);
      default:
        # code...
        break;
    }
    $arreglo = UtilController::getBeneficios($respuesta);
    return $arreglo;
  }

  public function actionBeneficiosPaginacion($inicial = 0, $categoria = null)
  {
    $connection = \Yii::$app->getDb();
    if ($categoria !== 'todo') {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL and e.rubro = :categoria ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5,
        ':categoria' => $categoria
      ];
    } else {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5
      ];
    }
    $query = $connection->createCommand($consulta . "limit :limit offset :inicial", $datos);
    $datosRes = $query->queryAll();

    $conteo = count($datosRes);
    if ($conteo > 0) {
      foreach ($datosRes as $b) {
        $desc = UtilController::verificarDescuento($b["dto_monetario"], $b["dto_porcentaje"], $b["dto_descripcion"]);
        $respuesta[] = [
          "id_beneficio" => $b["id_beneficio"],
          "titulo" => $b["titulo"],
          "tipo_descuento" => $b["tipo_descuento"],
          "image" => $b["image"],
          "descuento" => $desc
        ];
      }
    } else {
      $respuesta = [];
    }
    return $respuesta;
  }

  /**
   * Funcion que parsea las categorias que llegan de un array de categorias para que puedan ser añadidas a un filtro
   * @param Array $array Array de categorias;
   * @author Yurguen Pariente
   * @method actionBeneficios
   */
  private function obtenerCategorias($array = [])
  {
    $respuesta = "";
    $length = count($array);
    if ($length === 1) {
      $respuesta = "and e.rubro = '" . $array[0] . "'";
    } else {
      for ($i = 0; $i < $length; $i++) {
        if ($i + 1 === $length) {
          $respuesta .= "e.rubro = '" . $array[$i] . "' )";
        } else if ($i === 0) {
          $respuesta .= "and ( e.rubro = '" . $array[$i] . "' or ";
        } else {
          $respuesta .= "e.rubro = '" . $array[$i] . "' or ";
        }
      }
    }
    return $respuesta;
  }

  /**
   * Funcion que filtra los beneficios de diferentes formas (categorias, orden, tipos, busquedas)
   * * Endpoint: /beneficio/filter-group
   * @param JSON $params Los parametros que llegan desde el body de la peticion;
   * @author Yurguen Pariente
   * @method actionFilterGroup
   */
  public function actionFilterGroup()
  {
    $request = Yii::$app->request;
    $params = $request->getBodyParams();
    $connection = \Yii::$app->getDb();
    $categorias = $this->obtenerCategorias($params["categories"]);
    if ($params["argument"]) {
      $busqueda = "and (b.titulo ILIKE '%" . $params["argument"] . "%' or e.razon_social ILIKE '%" . $params["argument"] . "%')";
    } else {
      $busqueda = "";
    }
    $typeParam = $request->getBodyParam("type", ""); // filtro de  tipo
    if ($typeParam !== "") {
      $type = " and b.tipo_descuento = '" . $typeParam . "'";
    } else {
      $type = "";
    }
    $filterParam = $request->getBodyParam("filter", ""); // filtro de orden
    $filter = "";
    if ($filterParam !== "") {
      if ($filterParam === "reciente") {
        $filter = " b.fecha_inicio ASC";
      } else if ($filterParam === "expirar") {
        $filter = " b.fecha_fin ASC";
      } else if ($typeParam === "Porcentual") {
        if ($filterParam === "mayor") {
          $filter = " b.dto_porcentaje DESC";
        } else {
          $filter = " b.dto_porcentaje ASC";
        }
      } else if ($typeParam === "Monetario") {
        if ($filterParam === "mayor") {
          $filter = " b.dto_monetario DESC";
        } else {
          $filter = " b.dto_monetario ASC";
        }
      } else if ($typeParam === "") {
        if ($filterParam === "mayor") {
          $filter = " b.dto_monetario DESC, b.dto_porcentaje DESC";
        } else {
          $filter = " b.dto_monetario ASC, b.dto_porcentaje ASC";
        }
      }
    }
    $filter = $filter === "" ? "" : " order by" . $filter . ", b.titulo";
    $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL " . $categorias . $busqueda . $type . $filter . " limit 10 offset " . $params["offset"];
    $query = $connection->createCommand($consulta);
    $data = $query->queryAll();
    $arreglo = UtilController::getBeneficios($data);
    return $arreglo;
  }

  /**
   * @throws Exception
   */

  public function Desc($inicial, $categoria)
  {
    $connection = \Yii::$app->getDb();
    if ($categoria !== 'todo') {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL and e.rubro = :categoria ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5,
        ':categoria' => $categoria
      ];
    } else {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5
      ];
    }
    $query1 = $connection->createCommand($consulta . "and b.tipo_descuento='Descripcion' limit :limit offset :inicial", $datos);
    $beneficios = $query1->queryAll();
    if (count($beneficios) > 0) {
      return $beneficios;
    } else {
      return [];
    }
  }


  public function actionBusqueda($argumento, $categoria)
  {
    $connection = \Yii::$app->getDb();
    if ($categoria !== "todo") {
      $consulta = "select b.*, e.razon_social from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL and e.rubro = :categoria ";
      $datos = [
        ':categoria' => $categoria
      ];
    } else {
      $consulta = "select b.*, e.razon_social from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL ";
      $datos = [];
    }
    $query = $connection->createCommand($consulta, $datos);
    $beneficios = $query->queryAll();
    $response = [];
    foreach ($beneficios as $b) {
      if (stripos($b["titulo"], $argumento) !== false) {
        $response[] = $b;
      } else if (stripos($b["razon_social"], $argumento) !== false) {
        $response[] = $b;
      }
    }

    $arreglo = UtilController::getBeneficios($response);

    return $arreglo;
  }

  /**
   * @throws \yii\db\Exception
   */
  public function tipoDescuento($menor, $categoria, $inicial)
  {
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $connection = \Yii::$app->getDb();
    if ($categoria !== "todo") {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL and e.rubro = :categoria ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5,
        ':categoria' => $categoria
      ];
    } else {
      $consulta = "select b.* from beneficio b, empresa e where b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL ";
      $datos = [
        ':inicial' => $inicial,
        ':limit' => 5
      ];
    }
    $respuesta = [];
    if ($menor === true) {
      $query1 = $connection->createCommand($consulta . "and b.tipo_descuento='Monetario' order by b.dto_monetario ASC limit :limit offset :inicial", $datos);
      $beneficios = $query1->queryAll();

      $query2 = $connection->createCommand($consulta . "and b.tipo_descuento='Porcentual' order by b.dto_porcentaje ASC limit :limit offset :inicial", $datos);
      $beneficios2 = $query2->queryAll();
    } else {
      // ->all();
      $query1 = $connection->createCommand($consulta . "and b.tipo_descuento='Monetario' order by b.dto_monetario DESC limit :limit offset :inicial", $datos);
      $beneficios = $query1->queryAll();

      $query2 = $connection->createCommand($consulta . "and b.tipo_descuento='Porcentual' order by b.dto_porcentaje DESC limit :limit offset :inicial", $datos);
      $beneficios2 = $query2->queryAll();
    }
    $query3 = $connection->createCommand($consulta . "and b.tipo_descuento='Descripcion' limit :limit offset :inicial", $datos);
    $descriptivos = $query3->queryAll();
    return array_merge($beneficios, $beneficios2, $descriptivos);
  }

  /**
   * Funcion que obtiene ofertas segun los filtros aplicados (search,
   * status,idc)
   * @param $search
   * @param $status
   * @param $idc
   * @return array|null
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method getOffers
   */
  protected function getOffers($search, $status, $idc, $rubro)
  {
    $searchWhere = [
      'or',
      ['ilike', 'beneficio.titulo', $search],
      ['ilike', 'beneficio.condiciones', $search],

    ];
    $statusWhere = $status !== 'All' ? ['status' => $status] : '';
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $offers = null;
    if (isset($roles['ADM']) || isset($roles['SADM'])) {
      if ($search === 'All' && $idc === 'All') {
        $offers = Beneficio::find()->where(['removed' => null])
          ->andWhere($statusWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($search !== 'All' && $idc !== 'All') {
        $offers = Beneficio::find()->where(['removed' => null, 'id_empresa' => $idc, 'status' => $status])
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($search !== 'All' && $idc === 'All') {
        $offers = Beneficio::find()->where(['removed' => null])->andWhere($statusWhere)
          ->andFilterWhere($searchWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      } else if ($idc !== 'All') {
        $offers = Beneficio::find()->where(['removed' => null, 'id_empresa' => $idc])
          ->andWhere($statusWhere)->orderBy(['updated_at' => SORT_DESC])->all();
      }
    } else if (isset($roles['PRV'])) {
      $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
      if ($empresa) {
        if ($search === 'All') {
          $offers = Beneficio::find()
            ->where(['removed' => null, 'id_empresa' => $empresa->id_empresa])
            ->andWhere($statusWhere)
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();
        } else {
          $offers = Beneficio::find()
            ->where(['removed' => null, 'id_empresa' => $empresa->id_empresa])
            ->andWhere($statusWhere)
            ->andFilterWhere($searchWhere)
            ->orderBy(['updated_at' => SORT_DESC])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();
        }
      } else {
        return $offers;
      }
    }
    return $offers;
  }

  protected function getOffers2($search, $status, $idc, $rubro)
  {
    $searchWhere = [
      'or',
      ['ilike', 'beneficio.titulo', $search],
      ['ilike', 'beneficio.condiciones', $search],
    ];
    $statusWhere = $status !== 'All' ? ['status' => $status] : [];
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $offers = null;
    $query = Beneficio::find()->alias('b')->joinWith('empresa e'); // Ensure proper alias and relation names

    // Apply basic conditions
    $query->where(['b.removed' => null])->andWhere($statusWhere);

    // Filter by IDC
    if ($idc !== 'All') {
      $query->andWhere(['b.id_empresa' => $idc]);
    }

    // Apply search condition
    if ($search !== 'All') {
      $query->andFilterWhere($searchWhere);
    }

    // Apply rubro condition if specified
    if ($rubro !== 'All') {
      $query->andWhere(['e.rubro' => $rubro]);
    }

    // Order and get results
    $offers = $query->orderBy(['b.updated_at' => SORT_DESC])->all();

    // For roles such as PRV, filter offers specific to the user's empresa
    if (isset($roles['PRV'])) {
      $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
      if ($empresa) {
        $query->andWhere(['b.id_empresa' => $empresa->id_empresa]);
      } else {
        return null; // No offers if no empresa found
      }
    }

    return $offers;
  }

  /**
   * Accion que lista ofertas segun los filtros aplicados (search,
   * status,idc) y ejecutados en la funcion getOffers()
   * * Endpoint: /beneficio/list
   * @param $search
   * @param $idc
   * @param $status
   * @return array
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionList
   */
  public function actionList($search = 'All', $idc = 'All', $status = 'All', $rubro = 'All')
  {
    $response = null;
    $offers = $this->getOffers($search, $status, $idc, $rubro);
    if ($offers != null) {
      foreach ($offers as $offer) {
        $descuento = UtilController::getDiscount($offer);
        $ids_sucursales = $offer->sucursales_disp['ids'] ?? null;
        $sucursales = null;
        if (!is_null($ids_sucursales)) {
          for ($i = 0; $i < count($ids_sucursales); $i++) {
            $id = $ids_sucursales[$i];
            $sucursal = Sucursal::findOne($id);
            $sucursales[] = [
              "id_branch" => $sucursal->id_sucursal,
              "name" => $sucursal->nombre,
              "address" => $sucursal->direccion,
            ];
          }
        }
        $productos = null;
        if (!is_null($offer->productos['productos'])) {
          //            return ['id' => $offer];
          foreach ($offer->productos['productos'] as $dp) {
            $producto = Producto::find()->where(['id_producto' => $dp])->one();
            //              return $producto;
            if ($producto) {
              $productos[] = [
                'id_product' => $producto->id_producto,
                'name' => $producto->nombre,
              ];
            }
          }
        }
        $codes = CodigoPregenerado::find()
          ->select(['COUNT(*) AS total_codes', 'SUM(CASE WHEN status=true THEN 1 ELSE 0 END) AS rest_codes'])
          ->where(['id_beneficio' => $offer->id_beneficio])
          ->asArray()->groupBy('id_beneficio')->one();
        $codesPre = $offer->cod_pregenerado && !is_null($codes) ? $codes : [
          "total_codes" => 0,
          "rest_codes" => 0,
        ];

        $codesRedeemed = Codigo::find()->where(['id_beneficio' =>
        $offer->id_beneficio, "status" => true])->all();
        $response[] = [
          "id_offer" => $offer->id_beneficio,
          "stock" => $offer->stock,
          "title" => $offer->titulo,
          "discount_type" => $offer->tipo_descuento,
          "discount" => $descuento,
          "conditions" => $offer->condiciones,
          "start_date" => $offer->fecha_inicio,
          "end_date" => $offer->fecha_fin,
          "status" => $offer->status,
          "companie" => [
            "id_empresa" => $offer->empresa->id_empresa,
            "razon_social" => $offer->empresa->razon_social,
            "logo" => $offer->empresa->logo,
            "rubro" => $offer->empresa->rubro
          ],
          "branch_offices" => $sucursales ?? null,
          "products" => $productos,
          "image" => $offer->image,
          "frequency_redeem" => $offer->frequency_redeem,
          "cant_redeemed" => count($codesRedeemed),
          "cod_pregenerado" => $offer->cod_pregenerado,
          "total_codes" => $codesPre["total_codes"],
          "rest_codes" => $codesPre["rest_codes"],
        ];
      }
    }
    return $response;
  }

  /**
   * Accion que registra una oferta, recibe una JSON body con los datos.
   * * Endpoint: /beneficio/create
   * @throws ServerErrorHttpException
   * @throws InvalidConfigException
   * @author Cristhian Mercado
   * @method actionCreate
   */
  public function actionCreate()
  {
    $params = Yii::$app->getRequest()->getBodyParams();
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    if (!isset($roles['ADM']) && !isset($roles['SADM'])) {
      $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
      $params["id_empresa"] = $empresa->id_empresa;
    }
    $td = $params['tipo_descuento'];
    $data = [
      "titulo" => $params['titulo'],
      "id_empresa" => $params['id_empresa'],
      "fecha_inicio" => $params['fecha_inicio'],
      "fecha_fin" => $params['fecha_fin'],
      "tipo_descuento" => $params['tipo_descuento'],
      "condiciones" => $params['condiciones'],
      "sucursales_disp" => $params['sucursales_disp'],
      "productos" => $params['productos'],
      "image" => $params["image"],
      'frequency_redeem' => $params['frequency_redeem'],
      'cod_pregenerado' => $params['cod_pregenerado'],
      'stock' => $params['stock']
    ];

    if ($td === 'Porcentual') {
      $data['dto_porcentaje'] = $params["descuento"];
    } else if ($td === "Monetario") {
      $data['dto_monetario'] = $params["descuento"];
    } else if ($td === 'Descripcion') {
      $data['dto_descripcion'] = $params["descuento"];
    }

    $beneficio = new Beneficio($data);
    $beneficio->created_at = date("Y-m-d H:i:s");
    $beneficio->updated_at = date("Y-m-d H:i:s");
    if ($beneficio->fecha_fin < date("Y-m-d")) {
      $beneficio->status = "EXPIRADO";
    }
    if (!$beneficio->save()) {
      Yii::$app->response->statusCode = 400;
      $r = ["status" => false, "msg" => "Algo salio mal, intentelo de nuevo"];
    } else {
      $r = ["status" => true, "msg" => "Oferta creado exitosamente!"];
    }
    return $r;
  }
  /* 
  private function createCodigoPregenerado($codes, $id_beneficio)
  {
    if ($codes !== '') {
      $data = explode("\n", $codes);
      $codes = array_reduce($data, function ($carry, $row) {
        if ($row) {
          $cells = explode(",", $row);
          if (count($cells) > 0 && $cells[0]) $carry[] = $cells[0];
        }
        return $carry;
      }, []);
      foreach ($codes as $code) {
        $model = new CodigoPregenerado();
        $model->codigo = $code;
        $model->id_beneficio = $id_beneficio;
        $model->save();
      }
    }
  } */

  /**
   * Accion que actualiza la informacion de una oferta, recibe una JSON body
   * con los datos
   * * Endpoint: /beneficio/update
   * @param string $id identificador de la oferta a actualizar.
   * @throws ServerErrorHttpException
   * @throws InvalidConfigException
   * @author Cristhian Mercado
   * @method actionCreate
   */
  /*   public function actionUpdate($id)
  {
    $params = Yii::$app->request->getBodyParams();
    if (isset($params['tipo_descuento'])) {
      $td = $params['tipo_descuento'];
      if ($td === 'Porcentual') {
        $params['dto_porcentaje'] = $params["descuento"];
      } else if ($td === "Monetario") {
        $params['dto_monetario'] = $params["descuento"];
      } else if ($td === 'Descripcion') {
        $params['dto_descripcion'] = $params["descuento"];
      }
    }

    if (isset($params["fecha_fin"]) && $params["fecha_fin"] <= date("Y-m-d H:i:s")) {
      $params['status'] = "EXPIRADO";
    } else {
      $params['status'] = "VIGENTE";
    }
    $params['updated_at'] = date("Y-m-d H:i:s");
    if ($beneficio = Beneficio::findOne($id)) {

      if ($beneficio->load($params, '') && $beneficio->save()) {

        if ($beneficio->cod_pregenerado) {
          $codes = Yii::$app->getRequest()->getBodyParam('codes', '');
          $this->createCodigoPregenerado($codes, $beneficio->id_beneficio);
        }

        return ["status" => true, "msg" => "Oferta actualizado exitosamente!"];
      } else {
        throw new ServerErrorHttpException("Algo salio mal al actualizar la oferta");
      }
    } else {
      throw new ServerErrorHttpException("No existe la oferta");
    }
  } */
  public function actionUpdate($id)
  {
    $params = Yii::$app->request->getBodyParams();

    if (isset($params['tipo_descuento'])) {
      $td = $params['tipo_descuento'];
      switch ($td) {
        case 'Porcentual':
          $params['dto_porcentaje'] = $params['descuento'];
          break;
        case 'Monetario':
          $params['dto_monetario'] = $params['descuento'];
          break;
        case 'Descripcion':
          $params['dto_descripcion'] = $params['descuento'];
          break;
      }
    }
    unset($params['descuento']);
    unset($params['codes']);

    if (isset($params['fecha_fin']) && $params['fecha_fin'] <= date('Y-m-d H:i:s')) {
      $params['status'] = 'EXPIRADO';
    } else {
      $params['status'] = 'VIGENTE';
    }

    $params['updated_at'] = date('Y-m-d H:i:s');

    $transaction = Yii::$app->db->beginTransaction();

    try {
      $updatedRows = Beneficio::updateAll(
        $params,
        ['id_beneficio' => $id]
      );

      if ($updatedRows > 0) {
        $transaction->commit();
        return ['status' => true, 'msg' => 'Oferta actualizada exitosamente!'];
      } else {
        throw new ServerErrorHttpException('Algo salió mal al actualizar la oferta');
      }
    } catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }


  /**
   * Accion que elimina de forma logica una oferta
   * con los datos
   * * Endpoint: /beneficio/update
   * @param string $id identificador de la oferta a eliminar.
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionCreate
   */
  /*   public function actionDelete($id)
  {
    $removed["removed"] = date("Y-m-d H:i:s");
    if ($beneficio = Beneficio::findOne($id)) {
      if ($beneficio->load($removed, '') && $beneficio->save()) {
        return ["status" => true, "msg" => "Oferta eliminado exitosamente!"];
      } else {
        throw new ServerErrorHttpException("Algo salio mal al remover la oferta");
      }
    } else {
      throw new ServerErrorHttpException("No existe la oferta");
    }
  } */

  public function actionDelete($id)
  {
    $removedTimestamp = date("Y-m-d H:i:s");

    // Actualiza la oferta utilizando updateAll
    $numUpdated = Beneficio::updateAll(['removed' => $removedTimestamp], ['id_beneficio' => $id]);

    if ($numUpdated > 0) {
      return ["status" => true, "msg" => "Oferta eliminada exitosamente!"];
    } else {
      throw new ServerErrorHttpException("Algo salió mal al eliminar la oferta");
    }
  }


  /**
   * Funcion que obtiene los 10 beneficios mas recientes
   * * Endpoint: beneficio/get-recent
   * @param JSON $params Parametros que se encuentran en el body de la peticion;
   * @throws Exception
   * @method actionGetRecent
   */
  public function actionGetRecent()
  {
    return Beneficio::find()
      ->where(['>=', 'fecha_fin', date('Y-m-d') . ' 00:00:00'])
      ->andWhere('removed IS NULL')
      ->orderBy('fecha_inicio DESC')->limit(10)->all();
  }

  public function actionSold($id)
  {
    // Encuentra el beneficio por su ID
    $beneficio = Beneficio::findOne($id);

    // Lanza una excepción si el beneficio no se encuentra
    if (!$beneficio) {
      throw new NotFoundHttpException("No existe la oferta");
    }
    if ($beneficio->status == 'AGOTADO') {
      $updatedRows = Beneficio::updateAll(['status' => 'VIGENTE'], ['id_beneficio' => $id]);
    } else {
      $updatedRows = Beneficio::updateAll(['status' => 'AGOTADO'], ['id_beneficio' => $id]);
    }

    if ($updatedRows > 0) {
      return ["status" => true, "msg" => "Oferta actualizado exitosamente!"];
    } else {
      throw new ServerErrorHttpException("Algo salió mal al actualizar la oferta");
    }
  }
}

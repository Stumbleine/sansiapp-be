<?php

  namespace app\controllers;

  use app\models\Empresa;
  use app\models\Visualizacion;
  use Yii;
  use yii\base\InvalidConfigException;
  use yii\db\DataReader;
  use yii\db\Exception;
  use yii\filters\auth\HttpBearerAuth;
  use yii\filters\VerbFilter;

  class AnalitycsController extends \yii\web\Controller {
    public function init() {
      Yii::warning(getallheaders());
      parent::init();
    }

    /**
     * @throws \yii\base\ExitException
     * @throws \yii\web\BadRequestHttpException
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

    /**
     * @inheritDoc
     */

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
          'create' => ['post'],
          'get-top' => ["get"]
        ],
      ];
      return $behaviors;
    }

    /**
     * @param JSON $params Parametros que se encuentran en el body de la peticion;
     *
     * @throws InvalidConfigException
     * Funcion que crea las analiticas desde la aplicacion movil
     * * Endpoint: /analitycs/create
     * @author Yurguen Pariente
     * @method actionCreate
     */
    public function actionCreate() {
      $params = Yii::$app->request->getBodyParams();
      $analisiNuevo = new Visualizacion($params);
      $analisiNuevo->save();
    }

    /**
     * Funcion que obtiene el top de beneficios segun sus visualizaciones/analiticas
     * * Endpoint: analitycs/get-top
     * @param JSON $params Parametros que se encuentran en el body de la peticion;
     * @throws Exception
     * @author Yurguen Pariente
     * @method actionGetTop
     */
    public function actionGetTop() {
      $connection = \Yii::$app->getDb();
      $query = $connection->createCommand(
        "select v.id_beneficio, b.titulo, b.image, count(*) from visualizacion v, beneficio b, empresa e  where v.id_beneficio = b.id_beneficio and b.id_empresa = e.id_empresa and b.status = 'VIGENTE' and b.removed IS NULL and e.verified and e.removed IS NULL group by v.id_beneficio, b.titulo, b.image having  count(*)>=1 order by count(*) desc limit 7");
      return $query->queryAll();
    }

    /**
     * Accion que lista las ofertas mas visualizadas y muestra la
     * cantidad de de veces canjeados
     * * Endpoint: /analitycs/offers-view
     * @return array|DataReader|null
     * @throws Exception
     * @author Cristhian Mercado
     * @method actionOffersViews
     */
    public function actionOffersViews() {
      $redeemed = null;
      $vistas = null;
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      if (isset($roles['PRV'])) {
        $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
        $vistas = $this->getVisits('PRV', $empresa);
        $redeemed = $this->getRedeemed('PRV', $empresa);
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $vistas = $this->getVisits('ADM');
        $redeemed = $this->getRedeemed('ADM');
      }
      foreach ($vistas as &$vista) {
        $vista['count_redeemed'] = 0;
        foreach ($redeemed as $rd) {
          if ($vista['id_beneficio'] === $rd['id_beneficio']) {
            $vista['count_redeemed'] = $rd['count'];
          }
        }
      }
      return $vistas;
    }

    /**
     * Metodo que se conecta a la BD y extrae datos con una Query, sobre
     * ofertas mas visualizadas.
     * @param string $rol
     * @param Empresa|null $empresa
     * @return array|DataReader
     * @throws Exception
     * @author Cristhian Mercado
     * @method getVisits
     */
    public function getVisits($rol = "PRV", Empresa $empresa = null) {
      $connection = \Yii::$app->getDb();
      if ($rol === 'PRV') {
        $query = $connection->createCommand("select b.id_beneficio, b.titulo,b.image,b.status, count(*) 
        from  visualizacion v, beneficio b
        where v.id_beneficio  = b.id_beneficio and b.id_empresa = " . $empresa->id_empresa . " 
        group by b.id_beneficio, b.titulo, b.image,b.status having count(*)>=1 order by count(*) desc limit 10");
      } else if ($rol === 'ADM') {
        $query = $connection->createCommand("select v.id_beneficio, b.titulo,b.image,b.status ,e.razon_social, count(*) 
        from visualizacion v,beneficio b,empresa e 
        where v.id_beneficio  = b.id_beneficio and b.id_empresa = e.id_empresa and b.removed is null and e.removed is null and e.verified
        group by v.id_beneficio, b.titulo, b.image,b.status ,e.razon_social having  count(*)>=1 order by count(*) desc limit 10");
      }
      return $query->queryAll();
    }

    /**
     * Metodo que se conecta a la BD y extrae datos con una Query, sobre
     * ofertas mas canjeadas.
     * @param string $rol
     * @param Empresa|null $empresa
     * @return array|DataReader
     * @throws Exception
     * @author Cristhian Mercado
     * @method getRedeemed
     */
    public function getRedeemed(string $rol = "PRV", Empresa $empresa = null) {
      $connection = \Yii::$app->getDb();
      $query = "";
      if ($rol === 'PRV') {
        $query = $connection->createCommand("select c.id_beneficio ,e.razon_social, count(*) from codigo c ,beneficio b,empresa e  
        where  c.status  and c.id_beneficio  = b.id_beneficio and b.id_empresa = e.id_empresa 
        and e.id_empresa=" . $empresa->id_empresa . " and b.removed is null and e.removed is null and e.verified
        group by c.id_beneficio ,e.razon_social having  count(*)>=1 order by count(*) desc limit 10");
      } else if ($rol === 'ADM') {
        $query = $connection->createCommand("select c.id_beneficio ,e.razon_social, count(*) from codigo c ,beneficio b,empresa e  
        where  c.status  and c.id_beneficio  = b.id_beneficio 
        and b.id_empresa = e.id_empresa  and b.removed is null and e.removed is null and e.verified
        group by c.id_beneficio ,e.razon_social having  count(*)>=1 order by count(*) desc limit 10");
      }
      return $query->queryAll();
    }

    /**
     * Accion que genera el conjunto de datos para el grafico estadistico de
     * las ofertas mas visualizadas, recibe parametros que definen un rango
     * de fechas.
     * * endpoint: analitycs/offers-views-chart
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method actionOffersViewsChart
     */
    public function actionOffersViewsChart($start_d, $start_m, $end_date) {
      $chart = null;
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      if (isset($roles['PRV'])) {
        $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
        $chart = $this->getOVChart($start_d, $start_m, $end_date,
          'PRV', $empresa);
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $chart = $this->getOVChart($start_d, $start_m, $end_date, 'ADM');
      }
      return $this->responseChart($chart);
    }

    /**
     * Metodo que se conecta a la BD y extrae datos con una Query, sobre
     * ofertas mas visualizadas para un grafico estadistico.
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @param string $rol
     * @param Empresa|null $empresa
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getOVChart
     */
    public function getOVChart($start_d, $start_m, $end_date, string $rol = "PRV", Empresa $empresa = null) {
      $connection = \Yii::$app->getDb();
      $queryDay = "";
      $queryMonth = "";
      if ($rol === "PRV") {
        $queryMonth = $connection->createCommand(
          "select count(v.id) as total, TO_CHAR(v.created , 'YYYY-MM') as fecha
          from visualizacion v, beneficio b, empresa e  
          where v.id_beneficio  = b.id_beneficio  and b.id_empresa = e.id_empresa  
          and e.id_empresa = " . $empresa->id_empresa . " and (v.created between '" . $start_m . " 00:00:00' and '" . $end_date . " 23:59:59') 
          group by fecha order by fecha asc");
        $queryDay = $connection->createCommand(
          "select count(v.id) as total, TO_CHAR(v.created , 'YYYY-MM-DD') as fecha
          from visualizacion v, beneficio b, empresa e  
          where v.id_beneficio  = b.id_beneficio  and b.id_empresa = e.id_empresa  
          and e.id_empresa = " . $empresa->id_empresa . " and (v.created between '" . $start_d . " 00:00:00' and '" . $end_date . " 23:59:59') 
          group by fecha order by fecha asc");
      } else if ($rol === "ADM") {
        $queryMonth = $connection->createCommand(
          "select TO_CHAR(created , 'YYYY-MM') as fecha, count(created) as total 
          from visualizacion v,beneficio b, empresa e
          where v.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null  
          and (v.created between '" . $start_m . " 00:00:00' and '" .
          $end_date . " 23:59:59') 
          group by fecha order by fecha asc");
        $queryDay = $connection->createCommand(
          "select TO_CHAR(created , 'YYYY-MM-DD') as fecha, count(created) as total 
          from visualizacion v,beneficio b, empresa e
         where v.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null  
          and (v.created between '" . $start_d . " 00:00:00' and '" .
          $end_date . " 23:59:59') 
          group by fecha order by fecha asc");
      }
      $chartMonth = $queryMonth->queryAll();
      $chartDay = $queryDay->queryAll();
      return ["monthly" => $chartMonth, "daily" => $chartDay];
    }

    /**
     * Accion que genera el conjunto de datos para el grafico estadistico de
     * las codigos generados en ofertas y que recibe parametros para definir
     * un rango de fechas.
     * * endpoint: analitycs/generated-chart
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method actionGeneratedChart
     */
    public function actionGeneratedChart($start_d, $start_m, $end_date) {
      $chart = null;
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      if (isset($roles['PRV'])) {
        $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
        $chart = $this->getGeneratedChart($start_d, $start_m, $end_date, 'PRV', $empresa);
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $chart = $this->getGeneratedChart($start_d, $start_m, $end_date, 'ADM');
      }
      return $this->responseChart($chart);
    }

    /**
     * Metodo que se conecta a la BD y extrae datos con una Query, sobre
     * codigo generados para un grafico estadistico.
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @param string $rol
     * @param Empresa|null $empresa
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getGeneratedChart
     */
    public function getGeneratedChart($start_d, $start_m, $end_date, string $rol = "PRV", Empresa $empresa = null) {
      $connection = \Yii::$app->getDb();
      $queryDaily = "";
      $queryMonthly = "";
      if ($rol === "PRV") {
        $queryDaily = $connection->createCommand(
          "select  TO_CHAR(c.created_at , 'YYYY-MM-DD') as fecha, count(c.created_at) as total 
          from codigo c,beneficio b ,empresa e  
          where   c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa 
          and e.id_empresa =  " . $empresa->id_empresa . "  
          and (c.created_at between '" . $start_d . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc");
        $queryMonthly = $connection->createCommand(
          "select  TO_CHAR(c.created_at , 'YYYY-MM') as fecha, count(c.created_at) as total 
          from codigo c,beneficio b ,empresa e  
          where  c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa 
          and e.id_empresa =  " . $empresa->id_empresa . " 
          and (c.created_at between '" . $start_m . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc");

      } else if ($rol === "ADM") {
        $queryDaily = $connection->createCommand(
          "select  TO_CHAR(c.created_at , 'YYYY-MM-DD') as fecha, count(c.created_at) as total 
          from codigo c,beneficio b ,empresa e  
          where  c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null
          and (c.created_at between '" . $start_d . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc ;");
        $queryMonthly = $connection->createCommand(
          "select  TO_CHAR(c.created_at , 'YYYY-MM') as fecha, count(c.created_at) as total 
          from codigo c,beneficio b ,empresa e  
          where c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null 
          and (c.created_at between '" . $start_m . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc ;");
      }
      $daily = $queryDaily->queryAll();
      $monthly = $queryMonthly->queryAll();
      return ["daily" => $daily, "monthly" => $monthly];
    }

    /**
     * Accion que genera el conjunto de datos para el grafico estadistico de
     * las codigos canjeados en ofertas y que recibe parametros para definir
     * un rango de fechas.
     * * endpoint: analitycs/redeemed-chart
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method actionRedeemedChart
     */
    public function actionRedeemedChart($start_d, $start_m, $end_date) {
      $chart = null;
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      if (isset($roles['PRV'])) {
        $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
        $chart = $this->getRedeemedChart($start_d, $start_m, $end_date, 'PRV',
          $empresa);
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $chart = $this->getRedeemedChart($start_d, $start_m, $end_date, 'ADM');
      }
      return $this->responseChart($chart);
    }

    /**
     * Metodo que se conecta a la BD y extrae datos con una Query, sobre
     * codigos canjeados para un grafico estadistico.
     * @param $start_d
     * @param $start_m
     * @param $end_date
     * @param string $rol
     * @param Empresa|null $empresa
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getRedeemedChart
     */
    public function getRedeemedChart($start_d, $start_m, $end_date, string $rol = "PRV", Empresa $empresa = null) {
      $connection = \Yii::$app->getDb();
      $queryDaily = "";
      $queryMonthly = "";
      if ($rol === "PRV") {
        $queryDaily = $connection->createCommand(
          "select  TO_CHAR(c.fecha_consumo , 'YYYY-MM-DD') as fecha, count(c.fecha_consumo) as total 
          from codigo c,beneficio b ,empresa e  
          where c.status=true and c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa 
          and e.id_empresa =  " . $empresa->id_empresa . " 
          and (c.fecha_consumo between '" . $start_d . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc");
        $queryMonthly = $connection->createCommand(
          "select  TO_CHAR(c.fecha_consumo , 'YYYY-MM') as fecha, count(c.fecha_consumo) as total 
          from codigo c,beneficio b ,empresa e  
          where c.status=true and c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa 
          and e.id_empresa =  " . $empresa->id_empresa . " 
          and (c.fecha_consumo between '" . $start_m . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc");
      } else if ($rol === "ADM") {
        $queryDaily = $connection->createCommand(
          "select  TO_CHAR(c.fecha_consumo , 'YYYY-MM-DD') as fecha, count(c.fecha_consumo) as total 
          from codigo c,beneficio b ,empresa e  
          where c.status=true and c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null
          and (c.fecha_consumo between '" . $start_d . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc ;");
        $queryMonthly = $connection->createCommand(
          "select  TO_CHAR(c.fecha_consumo , 'YYYY-MM') as fecha, count(c.fecha_consumo) as total 
          from codigo c,beneficio b ,empresa e  
          where c.status=true and c.id_beneficio = b.id_beneficio and b.id_empresa  = e.id_empresa and e.removed is null
          and (c.fecha_consumo between '" . $start_m . " 00:00:00' and '" .
          $end_date . " 23:59:59')
          group by fecha order by fecha asc ;");
      }
      $daily = $queryDaily->queryAll();
      $monthly = $queryMonthly->queryAll();
      return ["daily" => $daily, "monthly" => $monthly];
    }

    /**
     * Accion que devuelve un resumen del total de ofertas visualizadas,
     * codigos canjeados, y cogidos generados
     * * endpoint: analitycs/summary
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method actionSummary
     */
    public function actionSummary() {
      $total_views = null;
      $total_codes = null;
      $total_redeemed = null;
      $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
      if (isset($roles['PRV'])) {
        $empresa = Empresa::findOne(['id_proveedor' => Yii::$app->user->identity->id]);
        $total_views = count($this->getTotalViews($empresa));
        $total_codes = count($this->getTotalCodes($empresa));
        $total_redeemed = count($this->getTotalRedeemedCodes($empresa));
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        $total_views = count($this->getTotalViews(null, 'ADM'));
        $total_codes = count($this->getTotalCodes(null, 'ADM'));
        $total_redeemed = count($this->getTotalRedeemedCodes(null, 'ADM'));
      }
      return ["total_views" => $total_views, "total_codes" => $total_codes, "total_redeemed" => $total_redeemed];
    }

    /**
     * Metodo que se conecta a la BD y devuelve el total de ofertas
     * visualizadas
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getTotalViews
     */
    public function getTotalViews(Empresa $empresa = null, $rol = 'PRV') {
      $id = $rol === 'PRV' ? $empresa->id_empresa : "e.id_empresa";
      $connection = \Yii::$app->getDb();
      $query = $connection->createCommand("select v.id from visualizacion v, beneficio b, empresa e where v.id_beneficio = b.id_beneficio 
        and b.id_empresa = " . $id . " and b.removed is null and e.verified and e.removed IS NULL group by v.id");
      return $query->queryAll();
    }

    /**
     * Metodo que se conecta a la BD y devuelve el total de codigos generados
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getTotalCodes
     */
    public function getTotalCodes(Empresa $empresa = null, $rol = "PRV") {
      $id = $rol === 'PRV' ? $empresa->id_empresa : "e.id_empresa";
      $connection = \Yii::$app->getDb();
      $query = $connection->createCommand("select c.codigo from codigo c , beneficio b, empresa e where c.id_beneficio = b.id_beneficio 
        and b.id_empresa = " . $id . " and e.verified and e.removed IS NULL group by c.codigo");
      return $query->queryAll();
    }

    /**
     * Metodo que se conecta a la BD y devuelve el total de codigos canjeados
     * @return array
     * @throws Exception
     * @author Cristhian Mercado
     * @method getTotalRedeemedCodes
     */
    public function getTotalRedeemedCodes(Empresa $empresa = null, $rol = "PRV") {
      $id = $rol === 'PRV' ? $empresa->id_empresa : "e.id_empresa";
      $connection = \Yii::$app->getDb();
      $query = $connection->createCommand("select c.codigo from codigo c , beneficio b, empresa e where c.status and c.id_beneficio = b.id_beneficio 
        and b.id_empresa = " . $id . " and e.verified and e.removed IS NULL group by c.codigo");
      return $query->queryAll();
    }

    /*metodos auxiliares*/
    /**
     * Metodo auxiliar que ordena los datos de una chart
     * @return array
     * @author Cristhian Mercado
     * @method responseChart
     */
    public function responseChart($chart) {
      $data = null;
      $labels = null;
      $dataDaily = null;
      $labelsDaily = null;
      foreach ($chart['monthly'] as $c) {
        $data [] = $c["total"];
        $labels [] = $this->labelMonth($c["fecha"]);
      }
      foreach ($chart['daily'] as $c) {
        $dataDaily [] = $c["total"];
        $labelsDaily [] = $c["fecha"];
      }
      $monthly = [
        "data" => $data,
        "labels" => $labels
      ];
      $daily = [
        "data" => $dataDaily,
        "labels" => $labelsDaily
      ];
      return ["monthly" => $monthly, "daily" => $daily];
    }

    /**
     * Metodo auxiliar que cambia los labels de una chart de numeros a meses
     * literales.
     * @author Cristhian Mercado
     * @method responseChart
     */
    public function labelMonth($fecha) {
      $month = substr($fecha, -2);
      $yyyy = substr($fecha, 0, 4);
      if ($month === "01") {
        return "Enero " . $yyyy;
      } else if ($month === "02") {
        return "Febrero " . $yyyy;
      } else if ($month === "03") {
        return "Marzo " . $yyyy;
      } else if ($month === "04") {
        return "Abril " . $yyyy;
      } else if ($month === "05") {
        return "Mayo " . $yyyy;
      } else if ($month === "06") {
        return "Junio " . $yyyy;
      } else if ($month === "07") {
        return "Julio " . $yyyy;
      } else if ($month === "08") {
        return "Agosto " . $yyyy;
      } else if ($month === "09") {
        return "Septiembre " . $yyyy;
      } else if ($month === "10") {
        return "Octubre " . $yyyy;
      } else if ($month === "11") {
        return "Noviembre " . $yyyy;
      } else if ($month === "12") {
        return "Diciembre " . $yyyy;
      }
    }
  }

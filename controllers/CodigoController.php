<?php

namespace app\controllers;

use Yii;
use app\models\Beneficio;
use app\models\Codigo;
use app\models\CodigoPregenerado;
use app\models\User;
use yii\base\InvalidConfigException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\ServerErrorHttpException;

class CodigoController extends \yii\web\Controller
{
  public function init()
  {
    Yii::warning(getallheaders());
    parent::init();
  }

  /**
   * @throws \yii\base\ExitException
   * @throws \yii\web\BadRequestHttpException
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

  /**
   * @inheritDoc
   */

  public function behaviors()
  {
    $behaviors = parent::behaviors();

    $behaviors['authenticator'] = [
      'class' => HttpBearerAuth::class,
    ];

    $behaviors['access'] = [
      'class' => \mdm\admin\components\AccessControl::className(),
    ];

    $behaviors['verbs'] = [
      'class' => VerbFilter::className(),
      'actions' => [
        'verificar-codigo' => ['post'],
        'generar-codigo' => ['post'],
        'verify-code' => ['post']
      ],
    ];


    // // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)

    return $behaviors;
  }

  /**
   * Funcion que verifica el codigo de canje
   * @param JSON $params Los parametros que llegan desde el body de la peticion;
   * @throws InvalidConfigException
   * * Endpoint: /codigo/verificar-codigo
   * @author Yurguen Pariente
   * @method actionVerificarCodigo
   */
  public function actionVerificarCodigo()
  {
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $params = Yii::$app->getRequest()->getBodyParams();
    $consumo = Codigo::find()
      ->where([
        "id_beneficio" => $params["id_beneficio"],
        "id_user" => $params["id_user"]
      ])
      ->one();
    if ($consumo) {
      $response = [
        "status" => true,
        "codigo" => $consumo
      ];
    } else {
      $response = [
        "status" => false
      ];
    }
    return $response;
  }

  /**
   * Funcion que crea un codigo de canje
   * @param string $id_user Id del usuario al que se asociara el codigo
   * @param string $id_beneficio Id del beneficio al que se asociara el codigo
   * @param boolean $cod_pregenerado true si usa códigos pregenerados
   * @author Yurguen Pariente
   * @method createCod
   */
  private function createCod($id_user, $id_beneficio, $cod_pregenerado)
  {
    //$token = (string)$this->generateJwt();
    $fecha = date('Y-m-d H:i:s');
    //$codigo = substr($token, 280, 280);
    //HACER HASH DEL CODIGO
    $time = time();

    $codPre = null;
    if ($cod_pregenerado) {
      $codPre = CodigoPregenerado::findOne(['id_beneficio' => $id_beneficio, 'status' =>  false]);
      if (is_null($codPre)) {
        return [
          "status" => false,
          "codigo" => [
            "codigo" => "No quedan códigos"
          ]
        ];
      } else {
        $cod = $codPre->codigo;
        $codPre->status = true;
      }
    } else {
      $cod = substr(hash("sha256", $time), 0, 6);
    }

    $body = [
      "codigo" => $cod,
      "id_user" => $id_user,
      "id_beneficio" => $id_beneficio,
      "created_at" => $fecha,
      "status" => false
    ];
    $model = new Codigo($body);

    $transaction = Yii::$app->db->beginTransaction();
    try {
      if ($model->save() && (is_null($codPre) || ($codPre && $codPre->save()))) {
        $transaction->commit();
        $response = [
          "status" => true,
          "codigo" => $model
        ];
      } else {
        $transaction->rollBack();
        $response = [
          "status" => false,
          "message" => "Algo salio mal intentelo mas tarde",
          "errores" => $model->getErrors()
        ];
      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }

    return $response;
  }

  /**
   * @param JSON $params Los parametros que llegan desde el body de la peticion;
   * @throws InvalidConfigException
   * Funcion que genera el codigo de canje segun el tipo de canje del beneficio
   * * Endpoint: /codigo/generar-codigo
   * @author Yurguen Pariente
   * @method actionGenerarCodigo
   */
  public function actionGenerarCodigo()
  {
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    $params = Yii::$app->request->getBodyParams();
    $beneficioAc = Beneficio::find()
      ->select(["frequency_redeem", "cod_pregenerado"])
      ->where(["id_beneficio" => $params["id_beneficio"]])
      ->one();
    $consumo = Codigo::find()
      ->where([
        "id_beneficio" => $params["id_beneficio"],
        "id_user" => $params["id_user"],
      ])
      ->one();
    if ($consumo) {
      switch ($beneficioAc["frequency_redeem"]) {
        case 'unlimited':
          $consumoUltimo = Codigo::find()
            ->where([
              "id_beneficio" => $params["id_beneficio"],
              "id_user" => $params["id_user"],
              "status" => false
            ])
            ->one();
          if ($consumoUltimo) {
            return [
              "status" => false,
              "codigo" => [
                "codigo" => $consumoUltimo->codigo
              ]
            ];
          } else {
            return $this->createCod($params["id_user"], $params["id_beneficio"], $beneficioAc["cod_pregenerado"]);
          }
          break;
        case 'one':
          if ($consumo->status === false) {

            return [
              "status" => true,
              "codigo" => $consumo
            ];
          } else {
            return [
              "status" => false,
              "codigo" => [
                "codigo" => "Usted ya ha canjeado esta oferta"
              ]
            ];
          }
        default:
          return [
            "status" => false,
            "codigo" => [
              "codigo" => "Oferta sin canje"
            ]
            // "coso" => $beneficioAc["frequency_redeem"]
          ];
          break;
      }
    } else if ($beneficioAc["frequency_redeem"] === "no-redeem") {
      return [
        "status" => false,
        "codigo" => [
          "codigo" => "Oferta sin canje"
        ]
        //"coso" => $beneficioAc["frequency_redeem"]
      ];
    } else {
      return $this->createCod($params["id_user"], $params["id_beneficio"], $beneficioAc["cod_pregenerado"]);
    }
  }

  /**
   * Accion que permite a canjear codigos de ofertas generados por los
   * estudiantes, funcionalidad del cajero
   * * endpoint: codigo/redeem
   * @throws InvalidConfigException
   * @author Cristhian Mercado
   * @method actionRedeem
   */
  public function actionRedeem()
  {
    $params = Yii::$app->request->getBodyParams();
    $codigo = Codigo::findOne($params['code']);
    //      $companie = Empresa::findOne(codigo->id)
    $user = User::findOne(Yii::$app->user->identity->id);

    if ($codigo && $codigo->beneficio->empresa->id_proveedor === $user->id) {

      if ($codigo->status === true) {
        $userEst = User::find()->where(["id" => $codigo->id_user])
          ->select(["nombres", "apellidos"])->one();
        $beneficio = Beneficio::find()->where(["id_beneficio" => $codigo->id_beneficio])
          ->select(["titulo"])->one();
        return ["redeemed" => "El codigo ya fue canjeado anteriormente", "student" => $userEst, "offer" => $beneficio];
      }
      if ($this->redeemCode($codigo, $user->id)) {
        return $this->constructResponse($codigo);
      } else {
        Yii::$app->response->statusCode = 400;
        return ["status" => false, "msg" => "No se pudo canjear el codigo, vuelva a intentarlo"];
      }
    } else {
      Yii::$app->response->statusCode = 400;
      return ["status" => false, "msg" => "El codigo no existe"];
    }
  }

  /**
   * Metodo que cambia el estado del codigo a true, indica que se canjeo
   * @author Cristhian Mercado
   * @method redeemCode
   */
  protected function redeemCode(Codigo $code, $id)
  {
    $params['fecha_consumo'] = date("Y-m-d H:i:s");
    $params['verificado_por'] = $id;
    $params['status'] = true;
    if ($code->load($params, '') && $code->save()) {
      $beneficio = Beneficio::findOne($code->id_beneficio);
      if ($beneficio && $beneficio->stock > 0) {
        $updatedRows = $beneficio->updateCounters(['stock' => -1]);
        if ($updatedRows > 0) {
          if ($beneficio->stock == 0) {
            $this->updateBeneficioStatus($beneficio->id);
          }
          return true;
        } else {
          throw new ServerErrorHttpException('Error al actualizar el stock del beneficio.');
        }
      } else {
        return true;
      }
    } else {
      Yii::$app->response->statusCode = 400;
      return ["status" => false, "msg" => "Algo salio mal al canjear el codigo",];
    }
  }

  protected function updateBeneficioStatus($beneficioId)
  {
    Yii::$app->db->createCommand()
      ->update('beneficio', ['status' => 'AGOTADO'], ['id' => $beneficioId])
      ->execute();
  }

  /**
   * Metodo que devuelve del detalle del canje.
   * @author Cristhian Mercado
   * @method constructResponse
   */
  protected function constructResponse(Codigo $codigo)
  {
    $userEst = User::find()->where(["id" => $codigo->id_user])
      ->select(["id", "nombres", "apellidos", "email", "picture", "carreras"])->one();
    $beneficio = Beneficio::find()->where(["id_beneficio" => $codigo->id_beneficio])->one();
    $discount = UtilController::getDiscount($beneficio);
    $offer = [
      "id_offer" => $beneficio->id_beneficio,
      "title" => $beneficio->titulo,
      "discount" => $discount,
      "image" => $beneficio->image,
      "type_discount" => $beneficio->tipo_descuento,
      "stock" => $beneficio->stock
    ];
    return ["student" => $userEst, "offer" => $offer, "code" => $codigo];
  }
}

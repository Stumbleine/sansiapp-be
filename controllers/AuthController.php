<?php

namespace app\controllers;

use app\models\ForgotPasswordMail;
use app\models\Notifications;
use app\models\User;
use app\models\Vigente;
use app\models\WelcomeMail;
use DateTime;
use Yii;
use yii\base\Exception;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Creates a custom cron schedule.
 * @param array $schedules list of schedules
 * @example class-wcdpue-admin.php add_filter( 'wcdpue_custom_cron_schedule', 300 ); Creates a custom cron
 * @since    2.0.0
 */
class AuthController extends Controller
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
    Yii::$app->response->format = Response::FORMAT_JSON;
    if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
      Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');
      Yii::$app->end();
    }
    $this->enableCsrfValidation = false;
    return parent::beforeAction($action);
  }

  /**
   * Accion que permite a los proveedores, administradores iniciar sesion
   * en la aplicacion web
   * * endpoint: auth/login
   * @return array
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionLogin
   */
  public function actionLogin()
  {
    $r = null;
    $params = Yii::$app->getRequest()->getBodyParams();
    $user = User::find()->where(['email' => $params['email'], 'removed' => null])->one();
    if ($user && Yii::$app->getSecurity()->validatePassword($params['password'], $user->password_hash)) {
      $r = [
        "msg" => "Inicio de sesion exitoso",
        "token" => $user->access_token,
      ];
    } else {
      Yii::$app->response->statusCode = 500;
      $r = ["status" => $this->response->statusCode, "msg" => "Credenciales invalidas, vuelva a intentarlo."];
    }
    return $r;
  }

  /**
   * Accion que permite a usuarios de la aplicacion web registrarse en el
   * sistema
   * * endpoint: auth/register
   * @return array
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionRegister
   */
  public function actionRegister()
  {
    $r = null;
    $params = Yii::$app->getRequest()->getBodyParams();
    $user = User::findOne(['email' => $params['email']]);
    if (!$user) {
      $userCreated = $this->RegistroUsuario($params, $params['rol']);
      if ($userCreated) {
        $r = ["status" => true, "msg" => "Registro existoso",];
      } else {
        throw new ServerErrorHttpException("No se pudo registrar, intente de nuevo");
      }
    } else {
      throw new HttpException(409, "El correo electronico ya esta en uso, use una diferente.");
    }
    return $r;
  }

  /**
   * @param JSON $params Parametros que se encuentran en el body de la peticion;
   * @throws ServerErrorHttpException|Exception
   * Funcion que inicia sesion a los usuarios de la aplicacion movil
   * * Endpoint: /auth/login-movil
   * @throws InvalidConfigException
   * @author Yurguen Pariente
   * @method actionLoginMovil
   */
  public function actionLoginMovil()
  {
    $params = Yii::$app->request->getBodyParams();
    $usuario = User::find()->where(["email" => $params["email"]])->one();
    $cod = explode("@", $params["email"]);
    if ($cod[1] !== "est.umss.edu") {
      throw new HttpException(403, "No se pudo iniciar sesion");
    }
    // if (!is_null($usuario) && $usuario["removed"] === null && Yii::$app->getSecurity()->validatePassword($params["password"], $usuario->password_hash)) {
    if (!is_null($usuario)) {
      $removed = !is_null($usuario["removed"]); // true si esta removido, false en caso contrario
      $removed = $removed ? $this->unremoved($usuario) : $removed; // Verifica en servicio si el usuario esta removido
      if (!$removed && Yii::$app->getSecurity()->validatePassword($params["password"], $usuario->password_hash)) {
        $res = [
          "status" => true,
          "msg" => "Login exitoso",
          "token" => $usuario->access_token,
          "usuario" => [
            "id" => $usuario["id"],
            "nombres" => $usuario["nombres"],
            "apellidos" => $usuario["apellidos"],
            "ci" => $usuario["ci"],
            "picture" => $usuario["picture"],
            "codigo_sis" => $usuario["codigo_sis"],
            "carreras" => $usuario["carreras"]
          ]
        ];
        $usuario->load(['sesion_status' => 'online'], '');
        $usuario->save();
      }
    } else {
      if (is_null($usuario)) {
        $estUmss = UtilController::getEst($cod[0]);
        if (isset($estUmss->response->nombres)) {
          $estUmss->response->email = $estUmss->response->codsis . "@est.umss.edu";
          $estUmss->response->picture = "";
          $body = [
            "nombres" => $estUmss->response->nombres,
            "apellidos" => $estUmss->response->apellidos,
            "ci" => $estUmss->response->ci,
            "picture" => "",
            "codigo_sis" => $estUmss->response->codsis,
            "email" => $estUmss->response->email,
            "carreras" => $estUmss->response->carrera
          ];
          $userRegistered = $this->RegistroUsuario($body, "estudiante");
          if ($userRegistered) {
            $userRegistered->load(['sesion_status' => 'online'], '');
            $userRegistered->save();
            $res = [
              "status" => true,
              "msg" => "Inicio de sesion existoso",
              "token" => $userRegistered["access_token"],
              "usuario" => [
                "nombres" => $userRegistered["nombres"],
                "apellidos" => $userRegistered["apellidos"],
                "picture" => $userRegistered["picture"],
                "ci" => $userRegistered["ci"],
                "codigo_sis" => $userRegistered["codigo_sis"],
                "carreras" => $userRegistered["carreras"]
              ]
            ];
          } else {
            throw new HttpException(403, "No se pudo iniciar sesion");
          }
        } else {
          throw new HttpException(402, "No se pudo iniciar sesion");
        }
      } else {
        throw new HttpException(403, "No se pudo iniciar sesion");
      }
    }
    return $res;
  }

  /**
   * Verifica si el usuario es valido en servicio,
   * si es valido quita el removido del usuario, en caso contrario se mantiene removido
   * @param ActiveRecord $usuario Usuario a verificar si esta removido
   */
  private function unremoved($usuario)
  {
    $removed = true;
    $validity = UtilController::getEst($usuario["codigo_sis"]); // Verificar la validez en el servicio
    if ($validity->error === null) { // si no hay error en la verificación de validez
      $vigencia = Vigente::findOne($usuario["codigo_sis"]); // Obtener el registro de vigencia del usuario
      $vigencia["date_verifiqued"] = date("Y-m-d 00:00:00");
      $vigencia["validate"] = true;
      if ($vigencia->save()) { // Actualizar registro de vigencia a verificado
        $usuario["removed"] = null;
        if ($usuario->save()) { // Quitar removido del usuario
          $removed = false;
        }
      }
    }
    return $removed;
  }

  /**
   * @throws InvalidConfigException
   * @throws \Exception
   */

  protected function asignarRol($id, $rols)
  {
    $auth = Yii::$app->authManager;
    $rol = $auth->getRoles();
    if ($auth->assign($rol[$rols], $id)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Funcion que cierra sesion a los usuarios de la aplicacion movil
   * * Endpoint: /auth/cerrar-sesion
   * @param JSON $params Parametros que se encuentran en el body de la peticion;
   * @throws InvalidConfigException
   * @author Yurguen Pariente
   * @method actionCerrarSesion
   */
  public function actionCerrarSesion()
  {
    $params = Yii::$app->request->getBodyParams();
    $usuario = User::find()
      ->where(["codigo_sis" => $params["codigo_sis"]])
      ->one();
    if ($usuario) {
      $usuario["token_notificacion"] = null;
      if ($usuario->save()) {
        return [
          "status" => true,
          "msg" => "Cambio de token de notificacion"
        ];
      }
    }
    return [
      "status" => false,
      "msg" => "Error en el cambio de token"
    ];
  }

  /**
   * Funcion que inicia sesion por Google a los usuarios de la aplicacion movil
   * @param JSON $params Parametros que se encuentran en el body de la peticion;
   * @throws InvalidConfigException
   * Endpoint: /auth/login-google
   * @throws Exception
   * @author Yurguen Pariente
   * @method actionLoginGoogle
   */
  public function actionLoginGoogle()
  {
    Yii::$app->response->format = Response::FORMAT_JSON;
    $params = Yii::$app->request->getBodyParams();
    $usuario = User::find()
      ->where(["email" => $params["email"]])
      ->one();
    $cod = explode("@", $params["email"]);
    if ($cod[1] !== "est.umss.edu") {
      throw new HttpException(403, "No se pudo iniciar sesion con google");
    }
    if ($usuario) {
      $removed = !is_null($usuario["removed"]); // true si esta removido, false en caso contrario
      $removed = $removed ? $this->unremoved($usuario) : $removed; // Verifica en servicio si el usuario esta removido
      // if($usuario["removed"] !== null){
      if ($removed) { // Si el usuario esta removido
        throw new HttpException(403, "No se pudo iniciar sesion con google");
      } else {
        Yii::$app->response->statusCode = 200;

        $response = [
          "status" => true,
          "msg" => "Inicio de sesion exitoso",
          "token" => $usuario->access_token,
          "usuario" => [
            "id" => $usuario["id"],
            "nombres" => $usuario["nombres"],
            "apellidos" => $usuario["apellidos"],
            "ci" => $usuario["ci"],
            "picture" => $usuario["picture"],
            "codigo_sis" => $usuario["codigo_sis"],
            "carreras" => $usuario["carreras"]
          ]
        ];
        $usuario->load(['sesion_status' => 'online'], '');
        $usuario->save();
      }
    } else {
      $estUmss = UtilController::getEst($cod[0]);
      if (isset($estUmss->response->nombres)) {
        $estUmss->response->email = $estUmss->response->codsis . "@est.umss.edu";
        $estUmss->response->picture = "";
        $body = [
          "nombres" => $estUmss->response->nombres,
          "apellidos" => $estUmss->response->apellidos,
          "ci" => $estUmss->response->ci,
          "picture" => "",
          "codigo_sis" => $estUmss->response->codsis,
          "email" => $estUmss->response->email,
          "carreras" => $estUmss->response->carrera
        ];
        $userRegistered = $this->RegistroUsuario($body, "estudiante");
        if ($userRegistered) {
          $userRegistered->load(['sesion_status' => 'online'], '');
          $userRegistered->save();
          $response = [
            "status" => true,
            "msg" => "Inicio de sesion existoso",
            "token" => $userRegistered->access_token,
            "usuario" => [
              "nombres" => $userRegistered["nombres"],
              "apellidos" => $userRegistered["apellidos"],
              "picture" => $userRegistered["picture"],
              "ci" => $userRegistered["ci"],
              "codigo_sis" => $userRegistered["codigo_sis"],
              "carreras" => $userRegistered["carreras"]
            ]
          ];
        } else {
          throw new HttpException(403, "No se pudo iniciar sesion con google");
        }
      } else {
        throw new HttpException(403, "No se pudo iniciar sesion con google");
      }
    }
    return $response;
  }

  /**
   * Funcion que registra segun el tipo de rol que tiene un usuario
   * @param string $rol Rol del usuario a registrar
   * @throws Exception
   * @author Cristhian Mercado - Yurguen Pariente
   * @method RegistroUsuario
   */
  protected function RegistroUsuario($body, $rol)
  {
    $time = date("Y-m-d H:i:s");
    $code = Yii::$app->getSecurity()->generatePasswordHash($time);
    $data = [
      "username" => $body["email"],
      "email" => $body["email"],
      "nombres" => ucwords(strtolower($body["nombres"])),
      "apellidos" => ucwords(strtolower($body["apellidos"])),
      "picture" => $body["picture"],
      "access_token" => $code,
      "created_at" => $time,
      "updated_at" => $time,
      "status" => 10,
    ];
    if (isset($body['password'])) {
      $pwd = Yii::$app->getSecurity()->generatePasswordHash($body['password']);
      $data['password_hash'] = $pwd;
    }

    if ($rol === "estudiante") {
      $data["codigo_sis"] = $body["codigo_sis"];
      $data["ci"] = $body["ci"];
      $data["picture"] = UtilController::getPhoto($body["codigo_sis"]);
      $data["password_hash"] = Yii::$app->getSecurity()->generatePasswordHash($body["ci"]);
      $data["carreras"] = ucwords(strtolower($body["carreras"]));
    }
    $usuarioNuevo = new User($data);
    $usuarioNuevo->tag_rol = $rol === "proveedor" ? "PRV" : "EST";
    $rolAsignado = false;
    if ($usuarioNuevo->save()) {
      switch ($rol) {
        case 'estudiante':
          $rolAsignado = $this->asignarRol($usuarioNuevo->id, "EST");
          break;
        case 'proveedor':
          $rolAsignado = $this->asignarRol($usuarioNuevo->id, "PRV");
          break;
        default:
          break;
      }
      if ($rolAsignado === true) {
        $mail = new WelcomeMail();
        if ($rol === 'proveedor') {
          $mail->Welcome($usuarioNuevo);
        }
        return $usuarioNuevo;
      } else {
        return null;
      }
    } else {
      throw new ServerErrorHttpException("No se pudo iniciar sesion con google");
    }
  }

  /**
   * Accion que permite a usuarios de la aplicacion web cambiar su contraseña
   * de cuenta
   * * endpoint: auth/forgot-password
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionForgotPassword
   */
  public function actionForgotPassword()
  {
    $params = Yii::$app->request->getBodyParams();
    $user = User::find()->where(['removed' => null, 'email' => $params['email']])->one();
    $code = Yii::$app->getSecurity()->generatePasswordHash(date("Y-m-d H:i:s"));
    $pwdProvisional = substr($code, 5, 13);
    $pwdProv_hash = Yii::$app->getSecurity()->generatePasswordHash($pwdProvisional);
    if ($user) {
      if ($user->load(['password_hash' => $pwdProv_hash], '') && $user->save()) {
        $mail = new ForgotPasswordMail();
        if ($mail->ForgotPassword($user, $pwdProvisional)) {
          return ["status" => true, "msg" => 'Se ha eviado la nueva contraseña', "pwd" => $pwdProvisional];
        }
      } else {
        throw new ServerErrorHttpException("Algo salio mal");
      }
    } else {
      throw new ServerErrorHttpException("No existe el usuario");
    }
  }

  public function actionForgotPasswordMovil()
  {
    $params = Yii::$app->request->getBodyParams();
    $user = User::find()->where(['removed' => null, 'email' => $params['email'], "tag_rol" => "EST"])->one();
    $code = Yii::$app->getSecurity()->generatePasswordHash(date("Y-m-d H:i:s"));
    $pwdProvisional = substr($code, 5, 13);
    $pwdProv_hash = Yii::$app->getSecurity()->generatePasswordHash($pwdProvisional);
    if ($user) {
      if ($user->load(['password_hash' => $pwdProv_hash], '') && $user->save()) {
        $mail = new ForgotPasswordMail();
        if ($mail->ForgotPassword($user, $pwdProvisional)) {
          return ["status" => true, "msg" => 'Se ha eviado la nueva contraseña', "pwd" => $pwdProvisional];
        }
      } else {
        throw new ServerErrorHttpException("Algo salio mal");
      }
    } else {
      throw new ServerErrorHttpException("No existe el usuario");
    }
  }

  /**
   * @throws Exception
   */
  protected function generarToken($usuario)
  {
    $time = date("Y-m-d H:i:s");
    $code = Yii::$app->getSecurity()->generatePasswordHash($time);
    $usuario["access_token"] = $code;
    if ($usuario->save()) {
      return $code;
    } else {
      return null;
    }
  }

  /**
   * Accion que permite a usuarios de la aplicacion web inicar sesion con
   * una cuenta de google existente
   * * endpoint: auth/auth-google
   * @throws Exception
   * @throws InvalidConfigException
   * @author Cristhian Mercado
   * @method actionAuthGoogle
   */
  public function actionAuthGoogle()
  {
    $params = Yii::$app->request->getBodyParams();
    $user = User::findOne(['email' => $params['email']]);

    if ($user && is_null($user->removed)) {
      $user->load(['sesion_status' => 'online'], '');
      $user->save();
      return [
        "message" => "Inicio de sesion exitoso",
        "token" => $user->access_token,
      ];
    } else if ($user && !is_null($user['removed'])) {
      Yii::$app->response->statusCode = 500;
      return [
        "msg" => "Su cuenta fue removida del sistema, contacte con un administrador",
      ];
      //        throw new ServerErrorHttpException("Su cuenta fue betada del sistema, contacte con un administrador");
    } else {
      $userRegistered = $this->RegistroUsuario($params, "proveedor");
      if ($userRegistered) {
        $userRegistered->load(['sesion_status' => 'online'], '');
        $userRegistered->save();
        return [
          "message" => "Inicio de sesion exitoso",
          "token" => $userRegistered->access_token,
        ];
      } else {
        throw new ServerErrorHttpException("No se pudo iniciar sesion con google");
      }
    }
  }
}

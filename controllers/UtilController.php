<?php

  namespace app\controllers;

  use app\models\Beneficio;
  use app\models\Log;
  use app\models\User;
  use Exception;
  use yii\console\Controller;
  use Yii;


  class UtilController extends Controller {
    /**
     *
     * @throws Exception
     */

    public static function assignRole($id, $rolName) {
      $auth = Yii::$app->authManager;
      $rol = $auth->getRoles();
      if ($auth->assign($rol[$rolName], $id)) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * Funcion que genera contraseñas para cuentas.
     * @return false|string
     * @author Cristhian Mercado
     * @method generatePassword
     */
    public static function generatePassword() {
      $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
      return substr(str_shuffle($chars), 0, 8);
    }

    /**
     * Funcion que genera tokens de acceso
     * @return string
     * @throws \yii\base\Exception
     * @author Cristhian Mercado
     * @method generateToken
     */
    public static function generateToken() {
      $time = date("Y-m-d H:i:s");
      return Yii::$app->getSecurity()->generatePasswordHash($time);
    }

    /**
     * Funcion que permite determinar que tipo de descuento tiene una oferta.
     * @return string
     * @author Cristhian Mercado
     * @method getDiscount
     */
    public static function getDiscount(Beneficio $offer) {
      $discount = null;
      if ($offer->tipo_descuento === 'Monetario') {
        $discount = $offer->dto_monetario;
      } else if ($offer->tipo_descuento === 'Porcentual') {
        $discount = $offer->dto_porcentaje;
      } else if ($offer->tipo_descuento === 'Descripcion') {
        $discount = $offer->dto_descripcion;
      }
      return $discount;
    }

    /**
     * Funcion que verfica el tipo de descuento de un beneficio
     * @param string $monetario Descuento monetario
     * @param string $porcentual Descuento porcentual
     * @param string $descriptivo Descuento descriptivo
     * @author Yurguen Pariente
     * @method verificarDescuento
     */
    public static function verificarDescuento($monetario, $porcentual, $descriptivo) {
      if ($monetario) {
        return $monetario;
      }
      if ($porcentual) {
        return $porcentual;
      }
      if ($descriptivo) {
        return $descriptivo;
      }
    }


    /**
     * Funcion que parsea los beneficios
     * @param Array $beneficios Los beneficios a parsear
     * @author Yurguen Pariente
     * @method getBeneficios
     */
    public static function getBeneficios($beneficios) {
      $arreglo = null;
      foreach ($beneficios as $b) {
        $desc = UtilController::verificarDescuento($b["dto_monetario"], $b["dto_porcentaje"], $b["dto_descripcion"]);
        $arreglo[] = [
          "id_beneficio" => $b["id_beneficio"],
          "titulo" => $b["titulo"],
          "descuento" => $desc,
          "tipo_descuento" => $b["tipo_descuento"],
          "image" => $b["image"]
        ];
      }
      if (count($beneficios) > 0) {
        return $arreglo;
      } else {
        return [];
      }
    }

    /**
     * Funcion que devuelve el label de descuento personalizado.
     * @return string
     * @author Cristhian Mercado
     * @method getDiscountLabel
     */
    public static function getDiscountLabel(Beneficio $offer) {
      if ($offer->tipo_descuento === 'Descripcion') {
        return $offer->dto_descripcion;
      } else if ($offer->tipo_descuento === 'Monetario') {
        return "Bs. " . $offer->dto_monetario;
      } else if ($offer->tipo_descuento === 'Porcentual') {
        return $offer->dto_porcentaje . " %";
      }
    }

    /**
     * Funcion obtiene el estudiante realizando una peticion a la base de datos de San Simon
     * @param string $cod_sis Codigo sis del estudiante
     * @author Yurguen Pariente
     * @method getEst
     */
    public static function getEst($cod_sis) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'http://167.157.60.25/v1/beneficios/validez/' . $cod_sis);
      $headers = array(
        'Authorization: Basic ' . base64_encode("b_estudiantiles:eHB7K75PtwgX")
      );
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch);
      $json = json_decode($response);
      if ($json->error === null) {
        $body = [
          "validate" => true,
          "date_verifiqued" => date("Y-m-d"),
          "codigo_sis" => $cod_sis
        ];
        VigenteController::create($body);
      }
      return $json;
    }

    /**
     * Funcion obtiene la foto del estudiante realizando una peticion a la base de datos de San Simon
     * @param string $cod_sis Codigo sis del estudiante
     * @author Yurguen Pariente
     * @method getPhoto
     */
    public static function getPhoto($cod_sis) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'http://formsis.umss.edu.bo/api/fotos/' . $cod_sis);
      $headers = array(
        'Authorization: Bearer ba525e83d324ff846e8c9e27cf253f79c5c07872306f28f2e414d2f328ad60ba33e7cb88f2fb03275b948b436b881778d6fb65f4ab70c7fc875c703e5a0c26030f9ed1c90c2bb0093129053a07fad69100548c272370f9d4e655836bfee3da5e34724412'
      );
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch);
      $json = json_decode($response);

      return "http://formsis.umss.edu.bo/uploads/estudiante/" . $json->foto;
    }

    /**
     * Funcion obtiene el horario del estudiante realizando una peticion a la base de datos de San Simon
     * @param string $cod_sis Codigo sis del estudiante
     * @author Yurguen Pariente
     * @method getHorario
     */
    public static function getHorario($cod_sis) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'http://167.157.60.25/api/horarios/' . $cod_sis);
      $headers = array(
        'Authorization: Basic ' . base64_encode("b_estudiantiles:eHB7K75PtwgX")
      );
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch);
      $json = json_decode($response, true);
      return UtilController::parse($json);
      // return UtilController::parseHorario($json);
    }

    /**
     * Funcion que parsea el horario de clases de un estudiante
     * Reemplazo del parser parseHorario
     * @param Array $horario Horario de clases a parsear
     * @method parseHorario
     */
    private static function parse($horarios) {
      $hours = ['06:45', '08:15', '09:45', '11:15', '12:45', '14:15', '15:45', '17:15', '18:45', '20:15'];
      $newHorarios = ['LU' => [], 'MA' => [], 'MI' => [], 'JU' => [], 'VI' => [], 'SA' => []];
      // Agrupar por dia
      foreach ($horarios as $horario) {
        if (in_array($horario['hora'], $hours)) { // Solo obtener el periodo inicial de los 2 periodos por clase
          $newHorarios[$horario['dia']][] = $horario;
        }
      }
      // Ordenar por hora
      foreach ($newHorarios as $key => $horario) {
        usort($horario, function ($a, $b) { return strcmp($a['hora'], $b['hora']); });
        $newHorarios[$key] = $horario;
      }
      // Armar estructura final de horarios
      $finalHorarios = [
        ['dia' => 'Lunes', 'horario' => $newHorarios['LU']],
        ['dia' => 'Martes', 'horario' => $newHorarios['MA']],
        ['dia' => 'Miercoles', 'horario' => $newHorarios['MI']],
        ['dia' => 'Jueves', 'horario' => $newHorarios['JU']],
        ['dia' => 'Viernes', 'horario' => $newHorarios['VI']],
        ['dia' => 'Sábado', 'horario' => $newHorarios['SA']],
      ];
      return $finalHorarios;
    }

    /**
     * Funcion que parsea el horario de clases de un estudiante
     * @param Array $horario Horario de clases a parsear
     * @author Yurguen Pariente
     * @method parseHorario
     */
    private static function parseHorario($horario) {
      $dias = ["LU", "MA", "MI", "JU", "VI", "SA"];
      $aux = [];
      $horario = UtilController::orderBy($horario, "hora", "asc");
      foreach ($dias as $dia) {
        $resp = [
          "dia" => $dia,
          "horario" => []
        ];
        for ($i = 0; $i < count($horario); $i++) {
          $rep = UtilController::searchRep($resp["horario"], $horario[$i]->materia);
          if ($horario[$i]->dia === $dia && $rep === false) {
            $horario[$i]->descPlan = ucwords(strtolower($horario[$i]->descPlan));
            $horario[$i]->descMateria = ucwords(strtolower($horario[$i]->descMateria));
            array_push($resp["horario"], $horario[$i]);
          }
        }
        $resp["dia"] = UtilController::parseDias($resp["dia"]);
        array_push($aux, $resp);
      }
      return $aux;
    }

    /**
     * Funcion obtiene el dia segun lo que llega en el horario de clases
     * @param string $dia Dia que esta en el horario
     * @author Yurguen Pariente
     * @method parseDias
     */
    static function parseDias($dia) {
      switch ($dia) {
        case 'LU':
          return "Lunes";
          break;
        case 'MA':
          return "Martes";
          break;
        case 'MI':
          return "Miercoles";
          break;
        case 'JU':
          return "Jueves";
          break;
        case 'VI':
          return "Viernes";
          break;
        case 'SA':
          return "Sábado";
          break;
        default:
          # code...
          break;
      }
    }

    static function searchRep($array, $valor) {
      $res = false;
      for ($i = 0; $i < count($array); $i++) {

        if ($array[$i]->materia === $valor) {
          $res = true;
          $i = count($array);
        }
      }
      return $res;
    }

    static function orderBy($items, $attr, $order) {
      $sortedItems = [];
      foreach ($items as $item) {
        $key = is_object($item) ? $item->{$attr} : $item[$attr];
        $sortedItems[$key] = $item;
      }
      if ($order === 'desc') {
        krsort($sortedItems);
      } else {
        ksort($sortedItems);
      }

      return array_values($sortedItems);
    }

    static function generatedLog($data, $data_type, $action_type) {
      $user = Yii::$app->user->identity;
      if (!is_null($user)) {
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $roles = array_keys($roles);
        $dataUser = [
          "id" => $user->id,
          "nombres" => $user->nombres,
          "apellidos" => $user->apellidos,
          "email" => $user->email,
          "roles" => $roles,
          "codigo_sis" => $user->codigo_sis,
          "ci" => $user->ci,
          "sesion_status" => $user->sesion_status,
        ];
        $dataLog = [
          "user" => $dataUser,
          "data" => $data,
          "created_at" => date("Y-m-d H:i:s"),
          "data_type" => $data_type,
          "action_type" => $action_type,
        ];
        $log = new Log();
        $log->load($dataLog, '');
  
        if ($log->save()) {
          self::registerLog($data, $user->email, $log->id);
          return $log;
        }
      }
    }

    /**
     * Registra el log de beneficios en el Sistema de Log's
     * @param object|array $data 
     * @param string $user Identificador del usuario
     * @param int $logId 
     */
    private static function registerLog($data, $user, $logId)
    {
      $isOk = false;
      $data = is_array($data) ? $data : $data->attributes;
      $request = Yii::$app->request;
      $params = Yii::$app->params;
      $url = $params['sisLogUrl'].'api/logs';
      $token = self::loginSisLog();

      if (!is_null($token)) {
        $hostInfo = $request->getHostInfo();
        $protocol = $hostInfo ? explode(':', $hostInfo)[0] : '';

        $crl = curl_init($url);
        $post_data = json_encode([
          "ipAdress" => $request->getUserIP(),
          "dateQuery" => date('Y-m-d H:i:s'),
          "action" => $request->getMethod(),
          "task" => $request->getPathInfo(),
          "url" => $request->getAbsoluteUrl(),
          "payload" => json_encode($data),
          "httpProtocol" => $protocol,
          "status" => Yii::$app->response->getStatusCode(),
          "transactionUserName" => $user
        ]);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLINFO_HEADER_OUT, true);
        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($crl, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $token,
          'Content-Type: application/json'
        ]);
        $res = curl_exec($crl);
        if ($res) {
          $isOk = json_decode($res)->isOk;
        }
      }
      if (is_null($token) || !$isOk) {
        self::registerFailOnLog('Fallo al registrar log, IdLog: [' . $logId . ']');
      }

      return $isOk;
    }

    /**
     * Iniciar sesión en el sistema de Log's para obtener el access token
     */
    private static function loginSisLog()
    {
      $params = Yii::$app->params;
      $credentials = ['username' => $params['sisLogUsr'], 'password' => $params['sisLogPwd']];
      $url = $params['sisLogUrl'].'api/auth/login';

      $crl = curl_init($url);
      $post_data = json_encode($credentials);
      curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($crl, CURLINFO_HEADER_OUT, true);
      curl_setopt($crl, CURLOPT_POST, true);
      curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
      curl_setopt($crl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      $res = curl_exec($crl);
      $token = null;
      if ($res) {
        $res = json_decode($res);
        if (is_object($res)) {
          $token = $res->accessToken;
        }
      }
      curl_close($crl);

      return $token;
    }

    /**
     * Inserta un nuevo registro de log en el archivo local
     * en la ruta pathLogs
     */
    private static function registerFailOnLog($fallo)
    {
      $pathLogs = Yii::$app->params['pathLogs'];
      if (file_exists($pathLogs)) {
        $fp = fopen($pathLogs, 'a');
        $data = '[' . date('Y-m-d H:i:s') . '] --> ' . $fallo  . PHP_EOL;
        fwrite($fp, $data);
        fclose($fp);
      }
    }

    static function getEmailConstants(){
      return [
        "appWebDomain" => Yii::$app->params["appWebDomain"],
        "logoUrl" => Yii::$app->params["logoUrl"],
        "appEmail" => Yii::$app->params["appEmail"],
        'phone' => Yii::$app->params["phone"],
        ];
    }
  }

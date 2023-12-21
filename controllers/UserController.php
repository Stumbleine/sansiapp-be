<?php

namespace app\controllers;

use app\models\AuthItem;
use app\models\Empresa;
use app\models\Horario;
use app\models\User;
use app\models\Vigente;
use app\models\WelcomeMail;
use Yii;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBearerAuth;
use Exception;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class UserController extends Controller
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
        'index' => ['get'],
        'list' => ['get'],
        'create' => ['post'],
        'update' => ['post', 'put'],
        'delete' => ['delete'],
        'change-password' => ['post'],
        'validate-student' => ['post'],
        'horario' => ['get']
      ]
    ];
    $behaviors['access'] = [
      'class' => \mdm\admin\components\AccessControl::className(),
    ];
    return $behaviors;
  }

  protected function getPermisos($id = null)
  {
    $array = Yii::$app->authManager->getPermissionsByUser($id);
    $permisos = null;
    foreach ($array as $p) {
      if (!str_contains($p->name, '/')) {
        $permisos[] = $p->name;
      }
    }
    return $permisos;
  }

  protected function getRoles($id = null)
  {
    $roles = null;
    $roleArray = Yii::$app->authManager->getRolesByUser($id);
    foreach ($roleArray as $role) {
      $rl = AuthItem::find()->where(['name' => $role->name])->select(['name', 'label', 'isadmin'])->one();
      $roles[] = $rl;
    }
    return $roles;
  }

  /**
   * Accion que devuelve la informacion de la cuenta del usuario
   * endpoint: /user
   * @return array
   * @author Cristhian Mercado
   * @method actionIndex
   */
  public function actionIndex()
  {
    $r = null;
    $user = Yii::$app->user->identity;
    $user = User::findOne($user->id);
    $roles = $this->getRoles($user->id);
    $permisos = $this->getPermisos($user->id);
    $empresa = Empresa::find()->where(['id_proveedor' => $user->id])->one();
    return [
      "id" => $user->id,
      "nombres" => $user->nombres,
      "apellidos" => $user->apellidos,
      "email" => $user->email,
      "picture" => $user->picture,
      "roles" => $roles,
      "permisos" => $permisos,
      "companie" => $empresa->id_empresa ?? null,
      "companieVerified" => $empresa->verified ?? null,
      "cajero_de" => $user->cajero_de
    ];
  }

  /**
   * Accion que lista los usuarios registrados en el sistema
   * endpoint: /user/list
   * @param $search
   * @param $rol
   * @param $sesion
   * @param $pag
   * @return array
   * @author Cristhian Mercado
   * @method actionList
   */
  public function actionList($search = 'All', $rol = 'All', $sesion =
  'All',                     $pag = 0)
  {
    $limit = 10;
    $r = null;
    $users = [];
    $sesionCondition = $sesion === 'All' ? '' : ['sesion_status' => $sesion, 'removed' => null];
    $count = $limit;
    if ($search === 'All' && $rol === 'All') {
      $count = User::find()->where($sesionCondition)->andWhere(['removed' => null])
        ->select([new Expression('COUNT(id) as total')])
        ->scalar();
      $users = User::find()->where($sesionCondition)->andWhere(['removed' => null])
        ->limit($limit)->offset($pag * $limit)->orderBy(['updated_at' => SORT_DESC])->all();
    } else if ($search !== 'All' && $rol !== 'All') {
      $count = User::find()->where($sesionCondition)
        ->andWhere(['removed' => null, 'tag_rol' => $rol])->andFilterWhere([
          'or',
          ['ilike', 'user.nombres', $search],
          ['ilike', 'user.apellidos', $search],
          ['ilike', 'user.email', $search],
        ])
        ->select([new Expression('COUNT(id) as total')])
        ->scalar();
      $users = User::find()->where($sesionCondition)
        ->andWhere(['removed' => null, 'tag_rol' => $rol])->andFilterWhere([
          'or',
          ['ilike', 'user.nombres', $search],
          ['ilike', 'user.apellidos', $search],
          ['ilike', 'user.email', $search],
        ])
        ->limit($limit)->offset($pag * $limit)->orderBy(['updated_at' => SORT_DESC])->all();
    } else if ($rol !== 'All') {
      $count = User::find()->where($sesionCondition)
        ->andWhere(['removed' => null, 'tag_rol' => $rol])
        ->select([new Expression('COUNT(id) as total')])
        ->scalar();
      $users = User::find()->where($sesionCondition)
        ->andWhere(['removed' => null, 'tag_rol' => $rol])
        ->limit($limit)->offset($pag * $limit)->orderBy(['updated_at' => SORT_DESC])->all();
    } else if ($search !== 'All') {
      $count = User::find()->where($sesionCondition)->andWhere(['removed' => null])->andFilterWhere([
        'or',
        ['ilike', 'user.nombres', $search],
        ['ilike', 'user.apellidos', $search],
        ['ilike', 'user.email', $search],
      ])
        ->select([new Expression('COUNT(id) as total')])
        ->scalar();
      $users = User::find()->where($sesionCondition)->andWhere(['removed' => null])
        ->andFilterWhere([
          'or',
          ['ilike', 'user.nombres', $search],
          ['ilike', 'user.apellidos', $search],
          ['ilike', 'user.email', $search],
        ])->limit($limit)->offset($pag * $limit)->orderBy(['updated_at' => SORT_DESC])
        ->all();
    }
    $r = [];
    foreach ($users as $user) {
      $roles = $this->getRoles($user['id']);
      $empresa = Empresa::find()->where(['id_proveedor' => $user['id']])->one();
      $r[] = [
        "id" => $user['id'],
        "nombres" => $user['nombres'],
        "apellidos" => $user['apellidos'],
        "email" => $user['email'],
        "picture" => $user['picture'],
        "created_at" => $user['created_at'],
        "sesion_status" => $user['sesion_status'],
        "roles" => $roles,
        "empresa" => $empresa['razon_social'] ?? null
      ];
    }
    return ["users" => $r, "total" => $count];
  }

  /**
   * Funcion que devuelves usuarios segun el rol especificado.
   * @param $role_name
   * @return array
   * @throws \yii\db\Exception
   * @author Cristhian Mercado
   * @method getUsersByRole
   */
  protected function getUsersByRole($role_name)
  {
    $connection = \Yii::$app->db;
    $connection->open();
    $command = $connection->createCommand(
      "SELECT * FROM auth_assignment INNER JOIN \"user\" u  ON auth_assignment.user_id = u.id WHERE auth_assignment.item_name = '" . $role_name . "'AND u.removed is null;"
    );
    $users = $command->queryAll();
    $connection->close();
    return $users;
  }

  /**
   * Accion que registra un usuario, recibe un objeto JSON con los datos
   * endpoint: /user/create
   * @return array
   * @throws InvalidConfigException
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionCreate
   */
  public function actionCreate()
  {
    $r = null;
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $params = Yii::$app->request->getBodyParams();
    $verify = User::findOne(["email" => $params['email']]);
    if ($verify) {
      throw new ServerErrorHttpException("El correo ya esta en uso");
    }
    if ($params['rol'] !== 'PRV' && $params['rol'] !== 'ADM') {
      throw new ServerErrorHttpException("No es posible crear un usuario con este rol");
    }
    $pwd = UtilController::generatePassword();
    $model = [
      "nombres" => $params["nombres"],
      "apellidos" => $params["apellidos"],
      "email" => $params["email"],
      "username" => $params["email"],
      "picture" => $params["picture"] ?? null,
      "password_hash" => Yii::$app->security->generatePasswordHash($pwd),
      "access_token" => UtilController::generateToken(),
      "created_at" => date("Y-m-d H:i:s"),
      "updated_at" => date("Y-m-d H:i:s"),
    ];
    $user = new User($model);

    if (isset($roles['ADM']) && $params['rol'] === 'PRV') {
      $r = $this->registerUser($user, 'PRV', $pwd);
    } else if (isset($roles['SADM'])) {
      $r = $this->registerUser($user, $params['rol'], $pwd);
    } else {
      throw new UnauthorizedHttpException("No es posible crear el usuario.");
    }
    return $r;
  }

  /**
   * Funcion que registra un usuario
   * @param User $user
   * @param $rol
   * @param $pwd
   * @return array
   * @throws ServerErrorHttpException
   * @throws Exception
   * @author Cristhian Mercado
   * @method registerUser
   */
  private function registerUser(User $user, $rol, $pwd)
  {
    $user->tag_rol = $rol;
    if ($user->save()) {
      $assignRole = UtilController::assignRole($user->id, $rol);
      if ($assignRole) {
        $mail = new WelcomeMail();
        if ($rol === 'ADM') {
          $mail->WelcomeAdmin($user, $pwd);
        } else {
          $mail->Welcome($user, true, $pwd);
        }
        UtilController::generatedLog(['datoAnterior' => null, 'datoNuevo' => $user->attributes], "usuario", "CREATE");
        $r = ["status" => true, "msg" => "Registro de usuario existoso"];
      } else {
        throw new ServerErrorHttpException("No se asigno roles al usuario");
      }
    } else {
      throw new ServerErrorHttpException("Algo salio mal, vuelva a intentarlo");
    }
    return $r;
  }

  /**
   * Accion que permite acualizar los datos de un usuario, recibe un objeto
   * JSON con los datos
   * endpoint: /user/update
   * @param null $id
   * @throws InvalidConfigException
   * @throws ServerErrorHttpException
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionUpdate
   */
  public function actionUpdate($id = null)
  {
    $r = null;
    $params = Yii::$app->request->getBodyParams();
    $params['updated_at'] = date("Y-m-d H:i:s");
    $user = User::findOne(Yii::$app->user->identity->id);
    $roles = Yii::$app->authManager->getRolesByUser($user->id);

    if (!is_null($id) && (isset($roles['ADM']) || isset($roles['SADM']))) {
      if ($model = User::findOne(['id' => $id, 'removed' => null])) {
        if ($model->load($params, '') && $model->save()) {
          $r = ["status" => false, "msg" => "Se actualizo el usuario",];
        } else {
          throw new ServerErrorHttpException("Algo salio mal, vuelva a intentarlo");
        }
      } else {
        throw new ServerErrorHttpException("El usuario no existe");
      }
    } else {
      if ($user && $user->load($params, '') && $user->save()) {
        $r = ["status" => false, "msg" => "Se actualizo la cuenta",];
      } else {
        throw new ServerErrorHttpException("Algo salio mal, vuelva a intentarlo");
      }
    }
    return $r;
  }

  /**
   * Accion que elimina los datos de un usuario
   * endpoint: /user/delete
   * @throws \Throwable
   * @author Cristhian Mercado
   * @method actionDelete
   */
  public function actionDelete($id)
  {
    $r = null;
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $removed["removed"] = date("Y-m-d H:i:s");
    if ($model = User::findOne(['id' => $id, 'removed' => null])) {
      $model->access_token = "access token was removed.";
      $roles2 = Yii::$app->authManager->getRolesByUser($model->id);
      if (isset($roles['PRV'])) {
        if (
          isset($roles2['CJRO']) && $model->load($removed, '') &&
          $model->save()
        ) {
          $r = ["status" => false, "msg" => "Se elimino el usuario",];
        } else {
          throw new ServerErrorHttpException("Hubo un error al tratar de eliminar el usuario");
        }
      } else if (isset($roles['ADM']) || isset($roles['SADM'])) {
        if ($model->load($removed, '') && $model->save()) {
          $r = ["status" => false, "msg" => "Se elimino el usuario",];
        } else {
          throw new ServerErrorHttpException("Hubo un error al tratar de eliminar el usuario");
        }
      }
    } else {
      throw new ServerErrorHttpException("No existe el usuario");
    }
    return $r;
  }

  public function actionListRoles()
  {
    $rols = null;
    $roles = Yii::$app->authManager->getRoles();
    foreach ($roles as $role) {
      $rols[] = AuthItem::find()->where(['name' => $role->name])->select(['name', 'label', 'isadmin'])->one();
    }
    return $rols;
  }

  public function actionListPermissions()
  {
    $permissions = Yii::$app->authManager->getPermissions();
    $permisos = null;
    foreach ($permissions as $p) {
      if (!str_contains($p->name, '/')) {
        $permisos[] = $p->name;
      }
    }
    return $permisos;
  }

  /**
   * Accion que cambia la contrasela del usuario por una nueva, recibe un
   * objeto JSON con los datos.
   * enpoint: /user/change-password
   * @throws InvalidConfigException
   * @throws \yii\base\Exception
   * @author Cristhian Mercado
   * @method actionChangePassword
   */
  public function actionChangePassword()
  {
    $user = User::findOne(Yii::$app->user->identity->id);
    $params = Yii::$app->request->getBodyParams();
    if (Yii::$app->getSecurity()->validatePassword($params['password'], $user->password_hash)) {
      $newPassword = Yii::$app->getSecurity()->generatePasswordHash($params['new_password']);
      if ($user->load(['password_hash' => $newPassword], '') && $user->save()) {
        return ["message" => 'Contraseña actualizada exitosamente!'];
      } else {
        throw new ServerErrorHttpException("Algo salio mal, vuelva a intentarlo");
      }
    } else {
      throw new ServerErrorHttpException("Las credenciales son incorrectas");
    }
  }

  /**
   * Accion que cierra la sesion del usuario y cambia su estado a offline
   * enpoint: /user/logout
   * @throws \yii\base\Exception
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionLogout
   */
  public function actionLogout()
  {
    $user = User::findOne(Yii::$app->user->identity->id);
    if ($user && $user->load(['sesion_status' => 'offline'], '') && $user->save()) {
      return ["stats" => true, "message" => "Cerrar sesion"];
    } else {
      throw new ServerErrorHttpException("Algo salio mal");
    }
  }

  /**
   * Accion que lista usuarios de tipo cajero unicamente (funcion del
   * proveedor)
   * enpoint: /user/list-cashiers
   * @throws \yii\base\Exception
   * @throws ServerErrorHttpException
   * @author Cristhian Mercado
   * @method actionListCashiers
   */
  public function actionListCashiers()
  {
    $cashiers = null;
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $empresa = Empresa::findOne(["id_proveedor" => Yii::$app->user->identity->id]);
    if (isset($roles['PRV'])) {
      $cashiers = User::find()->where([
        'removed' => null, "cashier" => true,
        "cajero_de" => $empresa->id_empresa
      ])
        ->select('picture,nombres,apellidos,email,id')
        ->all();
    } else {
      throw new ServerErrorHttpException("No se encontraron cajeros");
    }
    return $cashiers;
  }

  /**
   *  Accion que permite registrar usuarios de tipo cajero unicamente (funcion
   * del proveedor)
   * enpoint: /user/add-cashier
   * @throws InvalidConfigException
   * @throws \yii\base\Exception
   * @throws ServerErrorHttpException
   * @throws Exception
   * @author Cristhian Mercado
   * @method actionAddCashier
   */
  public function actionAddCashier()
  {
    $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->identity->id);
    $params = Yii::$app->request->getBodyParams();
    $verify = User::findOne(["email" => $params['email']]);
    if ($verify) {
      if (!is_null($verify->removed)) {
        $verify->load($params, '');
        $verify->removed = null;
        if ($verify->save()) {
          return ["status" => true, "msg" => "Cuenta de cajero reestablecida"];
        } else {
          throw new ServerErrorHttpException("No se puedo crear el cajero");
        }
      } else {
        throw new HttpException(409, "El correo electronico ya esta en uso, use una diferente.");
      }
    }
    $pwd = UtilController::generatePassword();
    $model = [
      "nombres" => $params["nombres"],
      "apellidos" => $params["apellidos"],
      "email" => $params["email"],
      "username" => $params["email"],
      "password_hash" => Yii::$app->security->generatePasswordHash($pwd),
      "access_token" => UtilController::generateToken(),
      "created_at" => date("Y-m-d H:i:s"),
      "cajero_de" => $params['cajero_de'],
      "cashier" => true,
      "tag_rol" => "CJRO"
    ];
    $user = new User($model);
    if ($user->save()) {
      $assignRole = UtilController::assignRole($user->id, 'CJRO');
      if ($assignRole) {
        $mail = new WelcomeMail();
        $mail->WelcomeCashier($user, $pwd);
        return ["status" => true, "msg" => "Registro de cajero existoso"];
      } else {
        throw new ServerErrorHttpException("No se asigno roles al usuario");
      }
    } else {
      throw new ServerErrorHttpException("El usuario no se creo");
    }
  }

  /**
   * @param JSON $body los datos de los usuarios;
   * @throws InvalidConfigException|HttpException
   * Funcion que verifica que el estudiante aun siga siendo estudiante de la universidad
   * * Endpoint: /user/validate-student
   * @author Yurguen Pariente
   * @method actionValidateStudent
   */
  public function actionValidateStudent()
  {
    $today = date("Y-m-d 00:00:00");
    $params = Yii::$app->request->getBodyParams();
    $student = Vigente::find()
      ->where(["codigo_sis" => $params["codigo_sis"]])
      ->one();
    if (!is_null($student)) { // Existe registro de vigencia del estudiante
      if ($student["date_verifiqued"] === $today) { // Verificado hoy?
        if ($student["validate"] === true) { // Valido?
          return ["status" => true];
        } else { // Si no es valido verifica en el servicio
          $validate = $this->getEstVigente($student, $params, $today);
          if ($validate === true) {
            return ["status" => true];
          } else {
            $e = User::find()->where(["codigo_sis" => $params["codigo_sis"]])->one();
            if ($e) {
              $e["removed"] = date("Y-m-d H:i:s");
              $e->save();
            }
            throw new HttpException(403, "El estudiante no es valido");
          }
        }
      } else { // Si no se a verificado hoy, se verifica en el servicio
        $validate = $this->getEstVigente($student, $params, $today);
        if ($validate === true) {
          return ["status" => true];
        } else {
          throw new HttpException(403, "El estudiante no es valido");
        }
      }
    } else { // Si no existe registro de vigencia del estudiante
      throw new HttpException(403, "El estudiante no es valido");
    }
  }

  /**
   * Funcion que valida la vigencia de los estudiantes 
   * @param Object $student estudiante
   * @param Object $params Parametros con datos del estudiante ha validar
   * @param Date $today Fecha del día de hoy
   * @throws ServerErrorHttpException
   * @author Yurguen Pariente
   * @method getEstVigente
   */
  private function getEstVigente($student, $params, $today)
  {
    $est = UtilController::getEst($params["codigo_sis"]);
    $e = User::find()->where(["codigo_sis" => $params["codigo_sis"]])->one();
    if ($est->error === null) {
      $student["date_verifiqued"] = $today;
      $student["validate"] = true;
      $student->save();
      if ($e) {
        $e["removed"] = null;
        $e->save();
      }
      return true;
    } else {
      $student["validate"] = false;
      $student->save();
      if ($e) {
        $e["removed"] = date("Y-m-d H:i:s");
        $e->save();
      }
      return false;
    }
  }
  /**
   * Funcion que devuelve el horario de clases de un estudiante
   * * Endpoint: /user/horario
   * @param string $cod_sis Codigo sis del estudiante
   * @throws ServerErrorHttpException
   * @author Yurguen Pariente
   * @method actionHorario
   */
  public function actionHorario($cod_sis)
  {
    $miHorario = Horario::find()->where(["codigo_sis" => $cod_sis])->one();
    $today = date("Y-m-d 00:00:00");
    if (!$miHorario) {
      $horario = UtilController::getHorario($cod_sis);
      if ($horario) {
        $body = [
          "codigo_sis" => $cod_sis,
          "horario" => $horario,
          "update_at" => $today
        ];
        $nuevo = new Horario($body);
        if ($nuevo->save()) {
          return $nuevo["horario"];
        } else {
          throw new ServerErrorHttpException("Error al obtener el horario");
        }
      } else {
        throw new ServerErrorHttpException("Error al obtener el horario");
      }
    } else {
      if ($miHorario["update_at"] === $today) {
        return $miHorario["horario"];
      } else {
        $horario = UtilController::getHorario($cod_sis);
        if ($miHorario->load(["horario" => $horario, "update_at" => $today], '') && $miHorario->save()) {
          return $horario;
        } else {
          throw new ServerErrorHttpException("Error al obtener el horario");
        }
      }
    }
  }

  public static function getUsersMovil()
  {
    $users = User::find()
      ->where(["removed" => null])
      ->andWhere(['not', ['codigo_sis' => null]])
      ->all();
    return $users;
  }
}

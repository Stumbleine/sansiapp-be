<?php
  /**
   * @link http://www.yiiframework.com/
   * @copyright Copyright (c) 2008 Yii Software LLC
   * @license http://www.yiiframework.com/license/
   */

  namespace app\commands;

  use app\models\Beneficio;
use app\models\Notifications;
use yii\console\Controller;
  use yii\console\ExitCode;

  /**
   * This command echoes the first argument that you have entered.
   *
   * This command is provided as an example for you to learn how to create console commands.
   *
   * @author Qiang Xue <qiang.xue@gmail.com>
   * @since 2.0
   */
  class HelloController extends Controller {
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex($message = 'hello world') {
      echo $message . "\n";

      return ExitCode::OK;
    }

    public function actionExpirarOferta() {
      $darBaja = Beneficio::find()
        ->select('id_beneficio, fecha_fin, titulo, fecha_inicio, tipo_descuento, sucursales_disp ')
        ->where(['removed' => null])
        ->all();


      foreach ($darBaja as $b) {
        echo $b->fecha_fin;
        if ($b->fecha_fin < date("Y-m-d")) {

          $b->status = 'EXPIRADO';
          $b->updated_at = date("Y-m-d H:i:s");
          if (!$b->save()) {
            echo "No se actualizo";
            print_r($b->getErrors());
          }
        }
      }
      //print_r($darBaja);

    }

    public function actionEliminarNotificaciones(){
      $time = date("Y-m-d 00:00:00");
      $eliminar = Notifications::find()
      ->all();

      foreach($eliminar as $e){
        if($e->created_at < $time){
          $e->delete();
        }
      }
    }
  }

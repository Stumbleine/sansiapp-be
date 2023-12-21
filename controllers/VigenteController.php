<?php

namespace app\controllers;

use app\models\Vigente;

class VigenteController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }


    public static function create($params){
        $nuevo = new Vigente($params);
        $nuevo->save();
    }
}

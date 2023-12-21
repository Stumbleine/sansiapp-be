<?php

  namespace app\controllers;

  use app\models\Beneficio;
  use app\models\Producto;
  use Yii;
  use app\models\Empresa;
  use yii\base\ExitException;
  use yii\web\BadRequestHttpException;
  use yii\web\Controller;
  use yii\web\NotFoundHttpException;

  class PublicController extends Controller {
    /**
     * @inheritDoc
     * @throws BadRequestHttpException|ExitException
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
     * Accion que permite listar las empresas asociadas para mostrarlos en la
     * pagina principal
     * endpoint: public/logo-companies
     * @return array
     * @author Cristhian Mercado
     * @method actionLogoCompanies
     */
    public function actionLogoCompanies() {
      return Empresa::find()->where(['removed' => null, 'verified' => true])
        ->select('logo')->all();
    }
  }

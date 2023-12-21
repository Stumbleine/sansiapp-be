<?php

use app\controllers\BeneficioController;
use app\models\User;

class BeneficioControllerTest extends \PHPUnit\Framework\TestCase
{

    public function testActionCreate()
    {
        // Setup, Arrange (preparar)
        $controller =  new BeneficioController("beneficio", Yii::$app);
        $postData = [
            'titulo' => 'Sample Title',
            'fecha_inicio' => '2023-01-01',
            'fecha_fin' => '2023-12-31',
            'tipo_descuento' => 'Porcentual',
        ];
        Yii::$app->request->setMethod('POST');
        Yii::$app->request->setBodyParams($postData);
        Yii::$app->user->identity = Yii::$app->getUser()->login(User::findIdentity(1));

        // Act (actuar, ejecutar)
        $result = $controller->actionCreate();
        
        // Assert (comparar)
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('msg', $result);
        if ($result['status']) {
            $this->assertEquals('Oferta creada exitosamente!', $result['msg']);
        } else {
            $this->assertEquals('Algo salió mal, inténtelo de nuevo', $result['msg']);
        }
    }
}

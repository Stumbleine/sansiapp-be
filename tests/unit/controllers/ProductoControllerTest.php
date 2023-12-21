<?php
// tests/unit/controllers/ProductoControllerTest.php
namespace tests\unit\controllers;

use app\controllers\ProductoController;
use app\models\Producto;
use app\models\User;
use Yii;
use yii\web\ServerErrorHttpException;

class ProductoControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testActionList()
    {
        // Create an instance of the controller
        $controller = new ProductoController('producto', Yii::$app);

        // Call the actionList method with test data
        $result = $controller->actionList('All', 'All');

        // Assert that the result is an array or null
        $this->assertNull($result); // You might need to adjust this based on your logic

        // For more specific assertions, you can check the structure of the result
        // Example: Assert that the result is an array with specific keys
        if ($result !== null) {
            $this->assertArrayHasKey('id_product', $result[0]);
            $this->assertArrayHasKey('id_companie', $result[0]);
            $this->assertArrayHasKey('image', $result[0]);
            $this->assertArrayHasKey('name', $result[0]);
            $this->assertArrayHasKey('description', $result[0]);
            $this->assertArrayHasKey('type', $result[0]);
            $this->assertArrayHasKey('companie', $result[0]);
            $this->assertArrayHasKey('price', $result[0]);
        }
    }

    // You can add more test methods for edge cases and variations

    public function testActionCreate()
    {
        // Create an instance of the controller
        $controller = new ProductoController('producto', Yii::$app->getModule("producto"));

        // Set up a mock request with sample post data
        $postData = [
            'attribute1' => 'value1',
            'attribute2' => 'value2',
            // ... include other attributes as needed
        ];
        Yii::$app->request->setMethod('POST');
        Yii::$app->request->setBodyParams($postData);

        // Mock the user identity (for example, an admin user)
        Yii::$app->user->identity = Yii::$app->getUser()->login(User::findIdentity(1));

        // Call the actionCreate method
        $result = $controller->actionCreate();

        // Assert that the result is as expected
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('msg', $result);

        if ($result['status']) {
            // Assert that the product was created successfully
            $this->assertEquals('Producto creado exitosamente!', $result['msg']);
        } else {
            // Assert that there was an error in creating the product
            $this->assertEquals('Algo salió mal al crear el producto', $result['msg']);
            $this->assertArrayHasKey('error', $result);
        }
    }
}

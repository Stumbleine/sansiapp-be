<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "producto".
 *
 * @property int $id_producto
 * @property int $id_empresa
 * @property string $nombre
 * @property string|null $image
 * @property string $tipo
 * @property float|null $precio
 * @property string|null $descripcion
 * @property string|null $removed
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Empresa $empresa
 */
class Producto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'producto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_empresa', 'nombre', 'tipo'], 'required'],
            [['id_empresa'], 'default', 'value' => null],
            [['id_empresa'], 'integer'],
            [['nombre', 'image', 'tipo', 'descripcion'], 'string'],
            [['precio'], 'number'],
            [['removed', 'created_at', 'updated_at'], 'safe'],
            [['id_empresa'], 'exist', 'skipOnError' => true, 'targetClass' => Empresa::className(), 'targetAttribute' => ['id_empresa' => 'id_empresa']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_producto' => 'Id Producto',
            'id_empresa' => 'Id Empresa',
            'nombre' => 'Nombre',
            'image' => 'Image',
            'tipo' => 'Tipo',
            'precio' => 'Precio',
            'descripcion' => 'Descripcion',
            'removed' => 'Removed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Create
            // UtilController::generatedLog($changedAttributes, 'producto', 'CREATE');
        } else {
            // Update y Delete
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'producto', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Gets query for [[Empresa]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(Empresa::className(), ['id_empresa' => 'id_empresa']);
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "sucursal".
 *
 * @property int $id_sucursal
 * @property string|null $nombre
 * @property string $direccion
 * @property string $latitud
 * @property string $longitud
 * @property int $id_empresa
 * @property string|null $removed
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Empresa $empresa
 */
class Sucursal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sucursal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'direccion', 'latitud', 'longitud'], 'string'],
            [['direccion', 'latitud', 'longitud', 'id_empresa'], 'required'],
            [['id_empresa'], 'default', 'value' => null],
            [['id_empresa'], 'integer'],
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
            'id_sucursal' => 'Id Sucursal',
            'nombre' => 'Nombre',
            'direccion' => 'Direccion',
            'latitud' => 'Latitud',
            'longitud' => 'Longitud',
            'id_empresa' => 'Id Empresa',
            'removed' => 'Removed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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

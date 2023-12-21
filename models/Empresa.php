<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "empresa".
 *
 * @property int $id_empresa
 * @property string $razon_social
 * @property string|null $nit
 * @property string $rubro
 * @property string $telefono
 * @property string|null $facebook
 * @property string|null $instagram
 * @property string|null $logo
 * @property string|null $sitio_web
 * @property string|null $email
 * @property int $id_proveedor
 * @property string|null $descripcion
 * @property string|null $removed
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property bool $verified
 * @property bool $rejected
 * @property string|null $rejection_reason
 *
 * @property Beneficio[] $beneficios
 * @property Producto[] $productos
 * @property User $proveedor
 * @property Rubro $rubro0
 * @property Sucursal[] $sucursals
 */
class Empresa extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'empresa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['razon_social', 'rubro', 'telefono', 'id_proveedor'], 'required'],
            [['razon_social', 'nit', 'rubro', 'telefono', 'facebook', 'instagram', 'logo', 'sitio_web', 'email', 'descripcion', 'rejection_reason'], 'string'],
            [['id_proveedor'], 'default', 'value' => null],
            [['id_proveedor'], 'integer'],
            [['removed', 'created_at', 'updated_at'], 'safe'],
            [['verified', 'rejected'], 'boolean'],
            [['rubro'], 'exist', 'skipOnError' => true, 'targetClass' => Rubro::className(), 'targetAttribute' => ['rubro' => 'nombre']],
            [['id_proveedor'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['id_proveedor' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_empresa' => 'Id Empresa',
            'razon_social' => 'Razon Social',
            'nit' => 'Nit',
            'rubro' => 'Rubro',
            'telefono' => 'Telefono',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'logo' => 'Logo',
            'sitio_web' => 'Sitio Web',
            'email' => 'Email',
            'id_proveedor' => 'Id Proveedor',
            'descripcion' => 'Descripcion',
            'removed' => 'Removed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            'rejection_reason' => 'Rejection Reason',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Create
            // UtilController::generatedLog($changedAttributes, 'empresa', 'CREATE');
        } else {
            // Update y Delete
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'empresa', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Gets query for [[Beneficios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBeneficios()
    {
        return $this->hasMany(Beneficio::className(), ['id_empresa' => 'id_empresa']);
    }

    /**
     * Gets query for [[Productos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::className(), ['id_empresa' => 'id_empresa']);
    }

    /**
     * Gets query for [[Proveedor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProveedor()
    {
        return $this->hasOne(User::className(), ['id' => 'id_proveedor']);
    }

    /**
     * Gets query for [[Rubro0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRubro0()
    {
        return $this->hasOne(Rubro::className(), ['nombre' => 'rubro']);
    }

    /**
     * Gets query for [[Sucursals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSucursals()
    {
        return $this->hasMany(Sucursal::className(), ['id_empresa' => 'id_empresa']);
    }
}

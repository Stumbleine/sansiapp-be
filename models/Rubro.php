<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "rubro".
 *
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $icono
 * @property string|null $updated_at
 * @property string|null $created_at
 *
 * @property Empresa[] $empresas
 */
class Rubro extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rubro';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'icono'], 'required'],
            [['nombre', 'descripcion', 'icono', 'created_at'], 'string'],
            [['updated_at'], 'safe'],
            [['nombre'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nombre' => 'Nombre',
            'descripcion' => 'Descripcion',
            'icono' => 'Icono',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Create
            // UtilController::generatedLog($changedAttributes, 'rubro', 'CREATE');
        } else {
            // Update
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'rubro', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        parent::afterDelete();
        UtilController::generatedLog(['datoAnterior' => $this->attributes, 'datoNuevo' => null], 'rubro', 'DELETE');
    }

    /**
     * Gets query for [[Empresas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresas()
    {
        return $this->hasMany(Empresa::class, ['rubro' => 'nombre']);
    }
}

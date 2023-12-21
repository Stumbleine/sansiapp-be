<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "codigo_pregenerado".
 *
 * @property int $id
 * @property string $codigo
 * @property int $id_beneficio
 * @property bool $status
 *
 * @property Beneficio $beneficio
 */
class CodigoPregenerado extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'codigo_pregenerado';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigo', 'id_beneficio'], 'required'],
            [['codigo'], 'string'],
            [['codigo'], 'unique'],
            [['id_beneficio'], 'default', 'value' => null],
            [['id_beneficio'], 'integer'],
            [['status'], 'boolean'],
            [['codigo'], 'unique'],
            [['id_beneficio'], 'exist', 'skipOnError' => true, 'targetClass' => Beneficio::class, 'targetAttribute' => ['id_beneficio' => 'id_beneficio']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'codigo' => 'Codigo',
            'id_beneficio' => 'Id Beneficio',
            'status' => 'Status',
        ];
    }

    /**
     * Gets query for [[Beneficio]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBeneficio()
    {
        return $this->hasOne(Beneficio::class, ['id_beneficio' => 'id_beneficio']);
    }
}

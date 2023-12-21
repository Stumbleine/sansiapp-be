<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "visualizacion".
 *
 * @property int $id
 * @property int $id_usuario
 * @property int|null $id_beneficio
 * @property string $created
 *
 * @property Beneficio $beneficio
 */
class Visualizacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'visualizacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_usuario', 'created'], 'required'],
            [['id_usuario', 'id_beneficio'], 'default', 'value' => null],
            [['id_usuario', 'id_beneficio'], 'integer'],
            [['created'], 'safe'],
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
            'id_usuario' => 'Id Usuario',
            'id_beneficio' => 'Id Beneficio',
            'created' => 'Created',
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

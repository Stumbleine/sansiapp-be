<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "horario".
 *
 * @property string $codigo_sis
 * @property string $horario
 * @property string $update_at
 */
class Horario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'horario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigo_sis', 'horario', 'update_at'], 'required'],
            [['codigo_sis'], 'string'],
            [['horario', 'update_at'], 'safe'],
            [['codigo_sis'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'codigo_sis' => 'Codigo Sis',
            'horario' => 'Horario',
            'update_at' => 'Update At',
        ];
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "vigente".
 *
 * @property string $date_verifiqued
 * @property string $codigo_sis
 * @property bool $validate
 */
class Vigente extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vigente';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date_verifiqued', 'codigo_sis', 'validate'], 'required'],
            [['date_verifiqued'], 'safe'],
            [['codigo_sis'], 'string'],
            [['validate'], 'boolean'],
            [['codigo_sis'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'date_verifiqued' => 'Date Verifiqued',
            'codigo_sis' => 'Codigo Sis',
            'validate' => 'Validate',
        ];
    }
}

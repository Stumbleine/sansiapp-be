<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "log".
 *
 * @property int $id
 * @property string $data_type
 * @property string $data
 * @property string $created_at
 * @property string $user
 * @property string|null $action_type
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data_type', 'data', 'created_at', 'user'], 'required'],
            [['data_type', 'action_type'], 'string'],
            [['data', 'created_at', 'user'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'data_type' => 'Data Type',
            'data' => 'Data',
            'created_at' => 'Created At',
            'user' => 'User',
            'action_type' => 'Action Type',
        ];
    }
}

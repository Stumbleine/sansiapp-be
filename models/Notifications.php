<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $msg
 * @property string $canal
 * @property string|null $type_item
 * @property string|null $created_at
 * @property string|null $emmit_at
 * @property int|null $id_empresa
 * @property int|null $id_beneficio
 */
class Notifications extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'msg', 'canal', 'type_item'], 'string'],
            [['canal'], 'required'],
            [['created_at', 'emmit_at'], 'safe'],
            [['id_empresa', 'id_beneficio'], 'default', 'value' => null],
            [['id_empresa', 'id_beneficio'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'msg' => 'Msg',
            'canal' => 'Canal',
            'type_item' => 'Type Item',
            'created_at' => 'Created At',
            'emmit_at' => 'Emmit At',
            'id_empresa' => 'Id Empresa',
            'id_beneficio' => 'Id Beneficio',
        ];
    }
}

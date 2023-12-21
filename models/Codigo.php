<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "codigo".
 *
 * @property bool $status
 * @property string $codigo
 * @property int $id_user
 * @property int $id_beneficio
 * @property string|null $fecha_consumo
 * @property string $created_at
 * @property bool|null $expired
 * @property int|null $verificado_por
 *
 * @property Beneficio $beneficio
 * @property User $user
 */
class Codigo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'codigo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'expired'], 'boolean'],
            [['codigo', 'id_user', 'id_beneficio', 'created_at'], 'required'],
            [['codigo'], 'string'],
            [['id_user', 'id_beneficio', 'verificado_por'], 'default', 'value' => null],
            [['id_user', 'id_beneficio', 'verificado_por'], 'integer'],
            [['fecha_consumo', 'created_at'], 'safe'],
            [['codigo'], 'unique'],
            [['id_beneficio'], 'exist', 'skipOnError' => true, 'targetClass' => Beneficio::class, 'targetAttribute' => ['id_beneficio' => 'id_beneficio']],
            [['id_user'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['id_user' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'status' => 'Status',
            'codigo' => 'Codigo',
            'id_user' => 'Id User',
            'id_beneficio' => 'Id Beneficio',
            'fecha_consumo' => 'Fecha Consumo',
            'created_at' => 'Created At',
            'expired' => 'Expired',
            'verificado_por' => 'Verificado Por',
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

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'id_user']);
    }
}

<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "reclamo".
 *
 * @property int $id_reclamo
 * @property string $descripcion
 * @property int $id_user
 * @property int $id_beneficio
 * @property string $tipo_reclamo
 * @property string|null $created_at
 * @property string|null $detalle_oferta
 * @property string|null $detalle_empresa
 *
 * @property Beneficio $beneficio
 * @property User $user
 */
class Reclamo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reclamo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'id_user', 'id_beneficio', 'tipo_reclamo'], 'required'],
            [['descripcion', 'tipo_reclamo'], 'string'],
            [['id_user', 'id_beneficio'], 'default', 'value' => null],
            [['id_user', 'id_beneficio'], 'integer'],
            [['created_at', 'detalle_oferta', 'detalle_empresa'], 'safe'],
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
            'id_reclamo' => 'Id Reclamo',
            'descripcion' => 'Descripcion',
            'id_user' => 'Id User',
            'id_beneficio' => 'Id Beneficio',
            'tipo_reclamo' => 'Tipo Reclamo',
            'created_at' => 'Created At',
            'detalle_oferta' => 'Detalle Oferta',
            'detalle_empresa' => 'Detalle Empresa',
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

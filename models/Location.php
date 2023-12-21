<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "location".
 *
 * @property string $name
 * @property string $lat
 * @property string $lng
 * @property string $type
 * @property int $id
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $removed
 * @property string|null $description
 */
class Location extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'location';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'lat', 'lng', 'type'], 'required'],
            [['name', 'lat', 'lng', 'type', 'description'], 'string'],
            [['created_at', 'updated_at', 'removed'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Name',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'type' => 'Type',
            'id' => 'ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'removed' => 'Removed',
            'description' => 'Description',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Create
            // UtilController::generatedLog($changedAttributes, 'location', 'CREATE');
        } else {
            // Update y Delete
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'location', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }
}

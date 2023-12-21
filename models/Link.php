<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "link".
 *
 * @property int $id
 * @property string $title
 * @property string $url
 * @property int $priority
 * @property string|null $image
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $removed
 * @property string|null $description
 */
class Link extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'url', 'priority'], 'required'],
            [['title', 'url', 'image', 'description'], 'string'],
            [['priority'], 'default', 'value' => null],
            [['priority'], 'integer'],
            [['created_at', 'updated_at', 'removed'], 'safe'],
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
            'url' => 'Url',
            'priority' => 'Priority',
            'image' => 'Image',
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
            // UtilController::generatedLog($changedAttributes, 'link', 'CREATE');
        } else {
            // Update y Delete
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'link', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }
}

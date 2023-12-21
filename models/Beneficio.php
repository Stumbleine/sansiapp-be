<?php

namespace app\models;

use app\controllers\UtilController;
use Yii;

/**
 * This is the model class for table "beneficio".
 *
 * @property int $id_beneficio
 * @property int $id_empresa
 * @property string $titulo
 * @property string|null $image
 * @property string $fecha_inicio
 * @property string $fecha_fin
 * @property string $tipo_descuento
 * @property float|null $dto_porcentaje
 * @property float|null $dto_monetario
 * @property string|null $dto_descripcion
 * @property string|null $condiciones
 * @property string|null $productos
 * @property string|null $sucursales_disp
 * @property string|null $removed
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $frequency_redeem
 * @property string|null $status
 * @property bool $cod_pregenerado Códigos pregenerados
 * @property int $stock
 *
 * @property CodigoPregenerado[] $codigoPregenerados
 * @property Codigo[] $codigos
 * @property Empresa $empresa
 * @property Reclamo[] $reclamos
 * @property Visualizacion[] $visualizacions
 */
class Beneficio extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'beneficio';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['titulo', 'fecha_inicio', 'fecha_fin', 'tipo_descuento', 'stock'], 'required'],
            [['titulo', 'image', 'tipo_descuento', 'dto_descripcion', 'condiciones', 'frequency_redeem', 'status',], 'string'],
            [['fecha_inicio', 'fecha_fin', 'productos', 'sucursales_disp', 'removed', 'created_at', 'updated_at'], 'safe'],
            [['dto_porcentaje', 'dto_monetario', 'stock'], 'number'],
            [['cod_pregenerado'], 'boolean'],
            [['id_empresa'], 'exist', 'skipOnError' => true, 'targetClass' => Empresa::class, 'targetAttribute' => ['id_empresa' => 'id_empresa']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_beneficio' => 'Id Beneficio',
            'id_empresa' => 'Id Empresa',
            'titulo' => 'Titulo',
            'image' => 'Image',
            'fecha_inicio' => 'Fecha Inicio',
            'fecha_fin' => 'Fecha Fin',
            'tipo_descuento' => 'Tipo Descuento',
            'dto_porcentaje' => 'Dto Porcentaje',
            'dto_monetario' => 'Dto Monetario',
            'dto_descripcion' => 'Dto Descripcion',
            'condiciones' => 'Condiciones',
            'productos' => 'Productos',
            'sucursales_disp' => 'Sucursales Disp',
            'removed' => 'Removed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'frequency_redeem' => 'Frequency Redeem',
            'status' => 'Status',
            'cod_pregenerado' => 'Cod Pregenerado',
            'stock' => 'Stock'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // Create
            // UtilController::generatedLog($changedAttributes, 'beneficio', 'CREATE');
        } else {
            // Update y Delete
            $datoNuevo = array_intersect_key((array)$this->attributes, (array)$changedAttributes);
            $datoAnterior = array_merge((array)$this->attributes, (array)$changedAttributes);
            UtilController::generatedLog([
                'datoAnterior' => $datoAnterior,
                'datoNuevo' => $datoNuevo,
            ], 'beneficio', 'UPDATE');
        }
        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Gets query for [[CodigoPregenerados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCodigoPregenerados()
    {
        return $this->hasMany(CodigoPregenerado::class, ['id_beneficio' => 'id_beneficio']);
    }

    /**
     * Gets query for [[Codigos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCodigos()
    {
        return $this->hasMany(Codigo::class, ['id_beneficio' => 'id_beneficio']);
    }

    /**
     * Gets query for [[Empresa]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmpresa()
    {
        return $this->hasOne(Empresa::class, ['id_empresa' => 'id_empresa']);
    }

    /**
     * Gets query for [[Reclamos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReclamos()
    {
        return $this->hasMany(Reclamo::class, ['id_beneficio' => 'id_beneficio']);
    }

    /**
     * Gets query for [[Visualizacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVisualizaciones()
    {
        return $this->hasMany(Visualizacion::class, ['id_beneficio' => 'id_beneficio']);
    }
}

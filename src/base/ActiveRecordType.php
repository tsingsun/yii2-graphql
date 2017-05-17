<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2016/11/22
 * Time: 下午3:56
 */

namespace yii\graphql\base;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use yii\db\ActiveRecord;
use yii\db\ColumnSchema;

/**
 * for activeRecord transfer to ObjectType
 * @package yii\graphql\base
 */
class ActiveRecordType
{
    /**
     * @var ActiveRecord
     */
    protected $model;

    /**
     * Registered type name.
     *
     * @var string
     */
    protected $name;

    /**
     * table fields.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * @var array for activeRecord description
     */
    protected $descriptions = [];

    /**
     * Create new instance of ActiveRecord type.
     *
     * @param ActiveRecord $model
     * @param string $name
     */
    public function __construct(ActiveRecord $model, $name = '')
    {
        $this->name         = $name;
        $this->model        = $model;
    }

    /**
     * Transform  model to graphql type.
     *
     * @return ObjectType
     */
    public function toType()
    {
        $name = $this->getName();
        $this->schemaFields();

        if (method_exists($this->model, 'graphqlFields')) {
            $this->modelDefinedFields($this->model->graphqlFields());
        }

        if (method_exists($this->model, $this->getTypeMethod())) {
            $method = $this->getTypeMethod();
            $this->modelDefinedFields($this->model->{$method}());
        }

        return new ObjectType([
            'name'        => $name,
            'description' => $this->getDescription(),
            'fields'      => $this->fields,
        ]);
    }

    /**
     * Convert ActiveRecord instance defined fields method named 'graphqlFields' .
     *
     * @param array $fields
     */
    public function modelDefinedFields($fields)
    {
        foreach($fields as $key=>$field){
            $data = [];
            $data['type'] = $field['type'];
            $data['description'] = isset($field['description']) ? $field['description'] : null;

            if (isset($field['resolve'])) {
                $data['resolve'] = $field['resolve'];
            } elseif ($method = $this->getModelResolve($key)) {
                $data['resolve'] = $method;
            }

            $this->addField($key, $data);
        }
    }

    /**
     * Create fields for type.
     *
     * @return void
     */
    protected function schemaFields()
    {
        $schema = $this->model->getTableSchema();
        $columns = $schema->columns;

        foreach($columns as $column){
            $this->generateField($column);
        }
    }

    /**
     * Generate type field from schema.
     *
     * @param  ColumnSchema $column
     * @return void
     */
    protected function generateField($column)
    {
        $field = [];
        $field['type'] = $this->resolveTypeByColumn($column);
        $field['description'] = isset($this->descriptions[$column->name]) ? $this->descriptions[$column->name] : $column->comment;

        if ($column->isPrimaryKey) {
            $field['description'] = $field['description'] ?: 'Primary id of type.';
        }

        if ($method = $this->getModelResolve($column->name)) {
            $field['resolve'] = $method;
        }

        $this->addField($column->name, $field);
    }

    /**
     * Resolve field type by column info.
     *
     * @param ColumnSchema $column
     * @return \GraphQL\Type\Definition\Type
     */
    protected function resolveTypeByColumn($column)
    {
        $type = Type::string();
        $type->name = $this->getName().'_String';
        $colType = $column->phpType;

        if ($column->isPrimaryKey) {
            $type = Type::id();
            $type->name = $this->getName().'_ID';
        } elseif ($colType === 'integer') {
            $type = Type::int();
            $type->name = $this->getName().'_Int';
        } elseif ($colType === 'float' || $colType === 'decimal' || $colType === 'double') {
            $type = Type::float();
            $type->name = $this->getName().'_Float';
        } elseif ($colType === 'boolean') {
            $type = Type::boolean();
            $type->name = $this->getName().'_Boolean';
        }
        return $type;
    }

    /**
     * Add field to collection.
     *
     * @param string $name
     * @param array $field
     */
    protected function addField($name, $field)
    {
        $this->fields[$name] = $field;
    }

    /**
     * Check if model has resolve function.
     *
     * @param  string  $key
     * @return array|null
     */
    protected function getModelResolve($key)
    {
        $method = 'resolve' . ucfirst($key) . 'Field';

        if (method_exists($this->model, $method)) {
            return array($this->model, $method);
        }

        return null;
    }

    /**
     * Get name for type.
     *
     * @return string
     */
    protected function getName()
    {
        if ($this->name) {
//            $value = ucwords(str_replace(['-', '_'], ' ', $value));
            return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $this->name)));
        }

        return lcfirst($this->model->formName());
    }

    /**
     * Get description of type.
     *
     * @return string
     */
    protected function getDescription()
    {
        return $this->model->className();
    }

    /**
     * Get method name for type.
     *
     * @return string
     */
    protected function getTypeMethod()
    {
        return 'graphql'.$this->getName().'Fields';
    }
}
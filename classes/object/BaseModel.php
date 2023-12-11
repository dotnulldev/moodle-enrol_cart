<?php

/**
 * @package    enrol_cart
 * @author     MohammadReza PourMohammad <dotnulldev@gmail.com>
 * @copyright  2023 MohammadReza PourMohammad {@link https://dotnull.dev}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_cart\object;

use Exception;

class BaseModel extends BaseObject
{
    /**
     * @var array dynamic attribute values (name => value).
     */
    private array $_attributes = [];

    /**
     * Constructor.
     * @param array $attributes the attributes (name-value pairs, or names) being defined.
     * @param array $config the configuration array to be applied to this object.
     */
    public function __construct(array $attributes = [], array $config = [])
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                $this->_attributes[$value] = null;
            } else {
                $this->_attributes[$name] = $value;
            }
        }
        parent::__construct($config);
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @return mixed property value
     * @throws Exception
     * @see getAttribute()
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        if ($this->hasAttribute($name)) {
            return null;
        }
        return parent::__get($name);
    }

    /**
     * PHP setter magic method.
     * @param string $name property name
     * @param mixed $value property value
     * @throws Exception
     */
    public function __set(string $name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Checks if a property value is null.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset(string $name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (Exception $t) {
            return false;
        }
    }

    /**
     * Sets a component property to be null.
     * @param string $name the property name or the event name
     */
    public function __unset(string $name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->_attributes[$name]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canGetProperty(string $name, bool $checkVars = true): bool
    {
        if (parent::canGetProperty($name, $checkVars)) {
            return true;
        }
        try {
            return $this->hasAttribute($name);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function canSetProperty(string $name, bool $checkVars = true): bool
    {
        if (parent::canSetProperty($name, $checkVars)) {
            return true;
        }
        try {
            return $this->hasAttribute($name);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Defines an attribute.
     * @param string $name the attribute name.
     * @param mixed $value the attribute value.
     */
    public function defineAttribute(string $name, $value = null)
    {
        $this->_attributes[$name] = $value;
    }

    /**
     * Returns the list of attribute names.
     *
     * @return string[] list of attribute names.
     */
    public function attributes(): array
    {
        return array_keys($this->_attributes);
    }

    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return bool whether the model has an attribute with the specified name.
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->_attributes[$name]) || in_array($name, $this->attributes(), true);
    }

    /**
     * Returns the named attribute value.
     * @param string $name the attribute name
     * @return mixed the attribute value. `null` if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute(string $name)
    {
        return $this->_attributes[$name] ?? null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @throws Exception if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute(string $name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->_attributes[$name] = $value;
        } else {
            throw new Exception(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }

    /**
     * Returns attribute values.
     * @param array|null $names list of attributes whose value needs to be returned.
     * @return array attribute values (name => value).
     */
    public function getAttributes(array $names = null): array
    {
        $values = [];
        if ($names === null) {
            $names = $this->attributes();
        }
        foreach ($names as $name) {
            $values[$name] = $this->$name;
        }
        return $values;
    }

    /**
     * Sets the attribute values in a massive way.
     * @param array $values attribute values (name => value) to be assigned to the model.
     * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
     */
    public function setAttributes(array $values)
    {
        $attributes = array_flip($this->attributes());
        foreach ($values as $name => $value) {
            if (isset($attributes[$name])) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Creates a model instance.
     *
     * @param array $row row data to be populated into the record.
     * @return static the newly created model
     */
    public static function instantiate(array $row): BaseModel
    {
        return new static();
    }

    /**
     * Populates an active record object using a row of data from the database.
     *
     * @param BaseModel $record the record to be populated.
     * @param array $row attribute values (name => value)
     */
    public static function populateRecord(BaseModel $record, array $row)
    {
        $columns = array_flip($record->attributes());
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $record->_attributes[$name] = $value;
            } elseif ($record->canSetProperty($name)) {
                $record->$name = $value;
            }
        }
    }

    /**
     * Converts found rows into model instances.
     *
     * @param array $rows
     * @return array|static[]
     */
    protected static function createModels(array $rows): array
    {
        $models = [];
        /* @var $class BaseModel */
        $class = get_called_class();
        foreach ($rows as $row) {
            $row = (array) $row;
            $model = $class::instantiate($row);
            $modelClass = get_class($model);
            $modelClass::populateRecord($model, $row);
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Converts the raw query results.
     *
     * @param array $rows the raw query result from database
     * @return array the converted query result
     */
    public static function populate(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }
        $models = static::createModels($rows);
        foreach ($models as $model) {
            $model->afterFind();
        }
        return $models;
    }

    /**
     * Converts the raw query results.
     *
     * @param object|array $row the raw query result from database
     * @return static the converted query result
     */
    public static function populateOne($row)
    {
        $models = static::createModels([$row]);
        $model = $models[0];
        $model->afterFind();
        return $model;
    }

    /**
     * This method is called when the Model object is created and populated with the query result.
     * @return void
     */
    public function afterFind()
    {
    }
}

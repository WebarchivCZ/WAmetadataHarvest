<?php

namespace Model;

use Nette,
    Access,
    TexyWrapper;

abstract class Base extends Simple implements Access\Provider {

    /**
     * Model key name
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * Use to setup your model after construction
     */
    protected function setup()
    {
        parent::setup();
        $tableName = ltrim(preg_replace('/^[^_]*module_/', '_', strtr(strtolower(get_class($this)), '\\', '_')), '_');
        $this->tableName = $this->manager->getTablePrefix() . $tableName;
        $this->id = str_replace('%table%', $tableName, $this->manager->getIdFormat());
    }

    public function setTable($name)
    {
        $this->tableName = $name;
    }

    /**
     * @return Nette\Database\Table\Selection
     */
    public function getTable()
    {
        return $this->connection->table($this->tableName);
    }

    public function getAll()
    {
        return $this->getTable();
    }

    private $cache = array();

    public function getById($id)
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        return $this->cache[$id] = $this->table->where($this->id, $id)->limit(1)->fetch();
    }

    public function getByIds($ids)
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) return array();

        return $this->table->where(array(
                $this->id => $ids,
            ))->fetchPairs($this->id);
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Returns model identified by name
     *
     * @param string $name
     * @return Model\Base
     */
    protected function getModel($name)
    {
        trigger_error("Using model is deprecated please use model('$name') or table('$name')", E_USER_NOTICE);
        return $this->model($name);
    }

    /**
     * Get model table
     * @param string $name
     * @return Nette\Database\Table\Selection
     */
    public function table($name)
    {
        return $this->manager->table($name, $this);
    }


    const
        OUTPUT_TRANSFORM_EXPAND = 'expand',
        OUTPUT_TRANSFORM_DATETIME = 'datetime',
        OUTPUT_TRANSFORM_BOOL = 'bool',
        OUTPUT_TRANSFORM_SET = 'enum';

    const
        OUTPUT_FORMAT_FULL = 'full',
        OUTPUT_FORMAT_SHORT = 'short',
        OUTPUT_FORMAT_VALUE = 'value';

    const
        OUTPUT_FILTER_MASK_DELETE = '___output_filter_mask_delete';

    protected $output = array();

    protected $privacy = array(
        /**
         * default => public
         * field => [ role, <field>_id ( <field>_id == <user>->id ) ]
         */
    );

    protected $outputFormatValueKey;

    public function output($resource, $format = NULL)
    {
        if ($format === NULL) {
            $format = $this->getOutputDefaultFormat();
        }

        $output = $this->getOutput();

        /**
         * OUTPUT_FORMAT_VALUE expects only string with key
         */
        if ($format === self::OUTPUT_FORMAT_VALUE) {
            if (is_string($this->outputFormatValueKey)) {
                return $resource->{$this->outputFormatValueKey};
            } else {
                throw new Exception('OUTPUT_FORMAT_VALUE expects string ' . $this->outputFormatValueKey . ' given');
            }
        }

        if (!is_object($resource)) $exit();
        $result = $resource->toArray();
        $filter = $this->getOutputFilter($format);
        if (is_callable($filter)) {
            return $filter($resource, $format);
        }
        if (is_string($filter) && $filter !== self::OUTPUT_FILTER_NONE) {
            throw new Exception("Unsupported string value '$filter' for format '$format'.");
        }
        if (is_array($filter) && isset($filter[self::OUTPUT_FILTER_MASK_DELETE])) {
            unset($filter[self::OUTPUT_FILTER_MASK_DELETE]);
            $filter = array_diff(array_keys($result), $filter);
        }
        foreach ($resource as $key => $value) {
            if ($filter !== self::OUTPUT_FILTER_NONE && !in_array($key, $filter, TRUE)) {
                unset($result[$key]);
                continue;
            }
            if (isset($this->privacy[$key])) {
                if (!$this->is($resource, $this->privacy[$key])) {
                    unset($result[$key]);
                    continue;
                }
            }
            if (!isset($output[$key])) {
                continue;
            }
            $options = $output[$key];
            if (is_string($options)) {
                $options = array('transform' => $options);
            }
            switch ($options['transform']) {
                case self::OUTPUT_TRANSFORM_EXPAND:
                    unset($result[$key]);
                    $key = substr($key, 0, -3);
                    if ($value === NULL) {
                        $result[$key] = NULL;
                        break;
                    }
                    $result[$key] = $this->model(isset($options['model']) ? $options['model'] : $key)->output($resource->$key, isset($options['format']) ? $options['format'] : NULL);
                    break;
                case self::OUTPUT_TRANSFORM_BOOL:
                    $result[$key] = (bool) $result[$key];
                    break;
                case self::OUTPUT_TRANSFORM_DATETIME:
                    $value = $result[$key];
                    if ($value instanceof Nette\DateTime) {
                        $value = $value->format(Nette\DateTime::W3C);
                    } elseif ($value !== NULL) {
                        throw new Exception("Transformation of '" . get_class($value) . "' to datetime is not supported.");
                    }
                    $result[$key] = $value;
                    break;
                case self::OUTPUT_TRANSFORM_SET:
                    $result[$key] = explode(',', $result[$key]);
                    break;
                default:
                    throw new Exception("Unknown transform type '$options[transform]'.");
            }
        }
        return $result;
    }

    protected function getOutput()
    {
        return $this->output;
    }

    const OUTPUT_FILTER_NONE = '*';

    protected $outputFilter = array();

    protected $outputDefaultFilter = self::OUTPUT_FILTER_NONE;

    protected function getOutputFilter($format)
    {
        if (isset($this->outputFilter[$format])) {
            return $this->outputFilter[$format];
        } else {
            return $this->outputDefaultFilter;
        }
    }

    protected $outputDefaultFormat = self::OUTPUT_FORMAT_FULL;

    protected function getOutputDefaultFormat()
    {
        return $this->outputDefaultFormat;
    }

    public function getSupportedActions($resource = NULL)
    {
        return Action::SUPPORT_ALL;
    }

    public function create($data)
    {
        return $this->table->insert($data);
    }

    public function replace($resource, $data)
    {
        $id = $resource->{$this->id};
        $resource->delete();
        $data[$this->id] = $id;
        return $this->table->insert($data);
    }

    public function update($resource, $data)
    {
        return $resource->update($data);
    }

    public function delete($resource)
    {
        return $resource->delete();
    }


    const
        FILTER_BOOL = 'bool',
        FILTER_INT = 'int',
        FILTER_ENUM = 'enum',
        FILTER_ENUM_TABLE = '!enum',
        FILTER_DATETIME = 'datetime',
        FILTER_SET = 'set',
        FILTER_TRIM = 'trim',
        FILTER_WS_REMOVE = 'ws-remove',
        FILTER_ID = 'id';

    const
        FILTER_KEY_TYPE = 'filter';

    protected $filter = array();

    public function filter($data)
    {
        foreach ($data as $key => $value) {
            if (!isset($this->filter[$key])) {
                continue;
            }
            $filter = $this->filter[$key];
            if (!is_array($filter)) {
                $filter = array(self::FILTER_KEY_TYPE => $filter);
            }
            switch ($filter[self::FILTER_KEY_TYPE]) {
                case self::FILTER_ID:
                    $data[$key] = trim($data[$key]) ?: null;
                    break;
                case self::FILTER_WS_REMOVE:
                    $data[$key] = preg_replace('/\s+/u', '', $data[$key]);
                    break;
                case self::FILTER_SET:
                    if (is_array($value)) {
                        $data[$key] = implode(',', $value);
                    }
                    break;
                case self::FILTER_BOOL:
                    if (is_string($value) && strtolower($value) == 'false') {
                        $value = FALSE;
                    } // 'true' will translate to TRUE implicitly
                    $data[$key] = (bool) $value;
                    break;
                case self::FILTER_INT:
                    $data[$key] = (int) $value;
                    break;
                case self::FILTER_DATETIME:
                    if (is_int($value)) {
                        $value = '@' . $value;
                    }
                    try {
                        $data[$key] = new Nette\DateTime($value);
                    } catch (\Exception $e) {
                        throw new Exception("Cannot convert value '$value' to datetime for '$key'.");
                    }
                    break;
                case self::FILTER_ENUM:
                    if (!is_array($data[$key], $filter['values'], TRUE)) {
                        throw new Exception("Unsupported value '$value' for '$key'.");
                    }
                    break;
                case self::FILTER_ENUM_TABLE:
                    unset($data[$key]);
                    $item = $this->model($filter['model'])->{'getBy' . ucfirst($filter['value'])}($value);
                    if (!$item) {
                        throw new Exception("Unsupported value '$value' for '$key'.");
                    }
                    $data[$key . '_id'] = $item->id;
                    break;
                case self::FILTER_TRIM:
                    $data[$key] = trim($data[$key]);
                    break;
                default:
                    throw new Exception("Unsupported filter '{$filter[self::FILTER_KEY_TYPE]}'");
            }
        }
        return $data;
    }

    const
        VALIDATE_REQUIRED = 'required',
        VALIDATE_NOT_EMPTY = 'not-empty',
        VALIDATE_GREATER_THAN = 'greater-than',
        VALIDATE_GREATER_THAN_OR_EQUAL = 'greater-than-or-equal',
        VALIDATE_LESS_THAN = 'lesser-than',
        VALIDATE_LESS_THAN_OR_EQUAL = 'lesser-than-or-equal',
        VALIDATE_ENUM = 'enum',
        VALIDATION_CONDITION = 'condition';

    public function validate($action, $data)
    {
        $debug = 0;
        $current = NULL;
        $skip = FALSE;
        $equal = FALSE;
        foreach ($this->getValidationRules($action) as $key => $validator) {
            if (!is_int($key)) {
                $current = $key;
                // trim($key); // idiotic I know but simplest way to include multiple check for same key (conditions)
                $skip = FALSE;
            } elseif ($current == NULL) {
                throw new Exception("First key in validate array must be key name '$key' given.");
            }
            if ($skip) {
                continue;
            }
            if (is_array($validator)) {
                $options = $validator;
                $validator = array_shift($options);
            } else {
                $options = NULL;
            }
            if (!in_array($validator, array(self::VALIDATE_REQUIRED, self::VALIDATE_NOT_EMPTY, self::VALIDATION_CONDITION), TRUE) && !isset($data[$current])) {
                $skip = TRUE;
                continue;
            }
            switch ($validator) {
                case self::VALIDATION_CONDITION:
                    $next = array_shift($options);
                    if (!isset($data[$next]) || $options && $data[$next] !== array_shift($options)) {
                        $skip = TRUE;
                        continue;
                    }
                    break;

                case self::VALIDATE_REQUIRED:
                case self::VALIDATE_NOT_EMPTY:
                    if (!isset($data[$current])) {
                        throw new Exception("Parameter '$current' is required.");
                    }
                    if ($validator === self::VALIDATE_NOT_EMPTY && $data[$current] === '') {
                        throw new Exception("Parameter '$current' must not be empty.");
                    }
                    break;

                case self::VALIDATE_ENUM:
                    if (!in_array($data[$current], $options, TRUE)) {
                        $last = array_pop($options);
                        throw new Exception("Parameter '$current' must be one of: " . ($options ? implode(', ', $options) . " or " : '') . "$last.");
                    }
                    break;

                case self::VALIDATE_GREATER_THAN_OR_EQUAL:
                    $equal = TRUE;
                case self::VALIDATE_GREATER_THAN:
                    $next = array_shift($options);
                    if (!isset($data[$next])) {
                        throw new Exception("Parameter '$next' is missing. Cannot compare with '$current'.");
                    }
                    $value = $data[$current];
                    $compare = $data[$next];
                    if (!$equal && $value <= $compare || $equal && $value < $compare) {
                        throw new Exception("Parameter '$current' must be greater than" . ($equal ? " or equal" : '') . " '$next'.");
                    }
                    break;
                case self::VALIDATE_LESS_THAN_OR_EQUAL:
                    $equal = TRUE;
                case self::VALIDATE_LESS_THAN:
                    $next = array_shift($options);
                    if (!isset($data[$next])) {
                        throw new Exception("Parameter '$next' is missing. Cannot compare with '$current'.");
                    }
                    $value = $data[$current];
                    $compare = $data[$next];
                    if (!$equal && $value >= $compare || $equal && $value > $compare) {
                        throw new Exception("Parameter '$current' must be less than" . ($equal ? " or equal" : '') . " '$next'.");
                    }
                    break;

                default:
                    throw new Exception("Unsupported validator '$validator'.");
            }
        }
    }

    protected $validate = array();

    protected function getValidationRules($action)
    {
        return isset($this->validate[$action]) ? $this->validate[$action] : array();
    }

    protected $defaults = array();

    public function defaults($action)
    {
        return isset($this->defaults[$action]) ? $this->defaults[$action] : array();
    }

    const
        PROCESS_USER = 'user',
        PROCESS_WEBALIZE = 'webalize',
        PROCESS_TEXY = 'texy';

    protected $process = array();

    public function process($action, $data)
    {
        foreach ($this->getProcessRules() as $key => $rule) {
            if (is_array($rule)) {
                $options = $rule;
                $rule = array_shift($options);
                if ($options) {
                    $source = array_shift($options);
                    if (!isset($data[$source])) {
                        continue;
                    }
                }
            } else {
                $options = NULL;
            }
            switch ($rule) {
                case self::PROCESS_USER:
                    if (Action::CREATE === $action || Action::REPLACE === $action) {
                        $data[$key] = $this->user->getId();
                    }
                    break;
                case self::PROCESS_WEBALIZE:
                    $data[$key] = Nette\Utils\Strings::webalize($data[$source]);
                    break;
                case self::PROCESS_TEXY:
                    $texy = new TexyWrapper;
                    $data[$key] = $texy->processUserInput($data[$source]);
                    break;
            }
        }
        return $data;
    }

    protected function getProcessRules()
    {
        return $this->process;
    }

    public function getResourceType()
    {
        return $this->model('resource.type')->getByName(strtr(get_class($this), '\\', '.'));
    }

    public function __call($function, $parameters)
    {
        if (preg_match('/^(?P<action>has|get|is|add|remove)(?P<name>[A-Z].*)$/', $function, $matches)) {
            $action = $matches['action'];
            $name = $matches['name'];
            switch ($action) {
                case 'has':
                    return $parameters[0]->{lcfirst($name)} !== NULL;
                case 'get':
                    if (substr($name, -1) != 's') {
                        break;
                    }
                    $name = substr($name, 0, -1);
                    if (!defined('static::GROUP_' . strtoupper($name))) {
                        break;
                    }
                    return $this->model('user.resource.group')->ids($this->getResourceType(), reset($parameters), lcfirst($name));
                case 'add':
                case 'remove':
                    if (!defined('static::GROUP_' . strtoupper($name))) {
                        break;
                    }
                    static $map = array(
                        'get' => 'ids',
                        'is' => 'contains',
                        'add' => 'add',
                        'remove' => 'remove',
                    );
                    return $this->model('user.resource.group')->{$map[$action]}($this->getResourceType(), array_shift($parameters), array_shift($parameters), lcfirst($name));
            }
        }
        return parent::__call($function, $parameters);
    }

    private $action;

    public function getAction()
    {
        if ($this->action === NULL) {
            $this->action = new Action($this, $this->user);
        }
        return $this->action;
    }

    public function is(/* $resource, $groups[]|$group[, $group ..], $user = NULL */)
    {
        $parameters = func_get_args();
        $resource = array_shift($parameters);
        $user = NULL;
        if (is_object(end($parameters))) {
            $user = array_pop($parameters);
        }
        $user = $this->getUser($user);
        if (!$user) {
            return FALSE;
        }

        $first = reset($parameters);
        if (is_array($first)) {
            $groups = $first;
        } else {
            $groups = $parameters;
        }

        foreach ($groups as $group) {
            if (isset($resource->{$group . '_id'})) {
                return $resource->{$group . '_id'} == $user->getPrimary();
            }
            if ($this->{'is' . $group}($resource, $user)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Wrapper for retrieving current user or passed user parameter record
     */
    protected function getUser($user = NULL)
    {
        if (NULL === $user) {
            $user = $this->user;
        }
        if ($user instanceof \Nette\Database\Table\ActiveRow) {
            return $user;
        }
        if (is_scalar($user)) {
            return $this->model('user')->getById($user->getId());
        }
        if ($user instanceof \Nette\Security\User) {
            return $this->model('user')->getById($user->getId());
        }
        throw new Exception('Unsupported parameter for ' . __FUNCTION__ . '.');
    }

    /**
     * Determine if action is supported taking into account context etc.
     *
     * @param Model\Action::* $action
     * @param Nette\Database\Table\ActiveRow $resource
     * @return bool
     */
    public function isSupported($action, $resource = NULL)
    {
        return $action != Action::REPLACE;
    }

}

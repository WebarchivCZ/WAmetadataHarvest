<?php

namespace Model;

use Nette;

class Manager
{

    private $connection;

    private $context;

    private $user;

	public function __construct(Nette\DI\Container $context, Nette\Database\Connection $connection, Nette\Security\User $user)
	{
        $this->connection = $connection;
        $this->context = $context;
        $this->user = $user;
	}

    private $cache = array();
	public function model($name, $context = NULL)
	{
        $name = strtolower(strtr($name, array('_' => '.', '\\' => '.')));
        $class = $context !== NULL && $name[0] === '.' ? get_class($context) : '';

        if (isset($this->cache[$class . $name])) {
            $cached = $this->cache[$class . $name];
            if (is_string($cached)) {
                return $this->model($cached);
            } else {
                return $cached;
            }
        }

        if ($name[0] == '.') {
            if ($context === NULL) {
                throw new Exception("Dot syntax must be used with $context.");
            }
            if ($name[1] == '.') {
                $slugs = explode('.', strtr($class, '\\', '.'));
                array_pop($slugs);
                if (strlen($name) > 2) {
                    array_push(substr($name, 2));
                }
                $this->cache[$class . $name] = implode('.', $slugs);
            } else {
                $this->cache[$class . $name] = strtr($class, '\\', '.') . $name;
            }
            return $this->model($this->cache[$class . $name]);
        }

        if ($name) {
            $class = strtr($name, '.', '_');
        } else {
            $class = strtr($class, '.', '_');
        }
        if ($model = $this->context->getService('model_' . $class)) {
            return $this->cache[$name] = $model;
        } else {
            throw new Exception("Model '$name' ($class) cannot be found");
        }
    }

	public function table($name, $source = NULL)
	{
        $model = $this->model($name, $source);
        return $model->getTable();
	}

    private $tablePrefix = '';

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    private $idFormat = 'id';

    public function getIdFormat()
    {
        return $this->idFormat;
    }

    /**
     * Id format supports substitution
     *  - %table% for table name
     */
    public function setIdFormat($format)
    {
        $this->idFormat = $format;
    }

}
<?php

namespace Model;

abstract class LocaleEnum extends Base {

    /**
     * Model value name
     * @var string
     */
    protected $value = 'value';

    /**
     * Default locale
     * @var string
     */
    protected $defaultLocale = 'enUS';
    protected $localeKeys;
    protected function _construct()
    {
        parent::_construct();
        $locales = $this->getLocales();
        $this->defaultLocale = reset($locales);
        $localeKeys = array();
        foreach ($locales as $locale) {
            $localeKeys[$locale] = 'value_' . $locale;
        }
        $this->localeKeys = $localeKeys;
        $this->value .= '_' . $this->defaultLocale;
    }
    public function getId($value)
    {
        $row = $this->table->select($this->id)->where(implode(' = ? OR ', $this->localeKeys) . ' = ?', array_fill(0, count($this->localeKeys), $value))->limit(1)->fetch();
        return $row ? $row->id : FALSE;
    }

    public function getByValue($value)
    {
        return $this->table->select($this->id)->where($this->value, $value)->limit(1)->fetch();
    }

    public function getValueId($value)
    {
        $id = $this->getId($value);
        if (!$id) {
            $item = $this->table->insert(array_fill_keys(
                $this->localeKeys, $value
            ));
            $id = $item->id;
        }
        return $id;
    }

    public function getById($id, $locale = null, $fallback = null)
    {
        $row = parent::getById($id);
        return $row ? $this->_getValue($row, $locale, $fallback) : FALSE;
    }

    public function getByIds($ids, $locale = null, $fallback = null)
    {
        $rows = parent::getByIds($ids);
        $result = array();
        foreach ($rows as $id => $row) {
            $result[$id] = $this->_getValue($row, $locale, $fallback);
        }
        return $result;
    }
    protected function _getValue($row, $locale = null, $fallback = null)
    {
        if (null === $fallback) {
            $fallback = $this->defaultLocale;
        }
        if (null === $locale) {
            $locale = $this->defaultLocale;
        }
        if ($row->{'value_' . $locale}) {
            return $row->{'value_' . $locale};
        } else {
            return $row->{'value_' . $fallback};
        }
    }

    public function getAll($locale = null, $fallback = null)
    {
        $all = array();
        foreach ($this->table->fetchPairs($this->id) as $id => $row) {
            $all[$id] = $this->_getValue($row, $locale, $fallback);
        }
        return $all;
    }

    public function getByValues($values)
    {
        $result = array();
        foreach ($values as $value) {
            $result[$this->getValueId($value)] = $value;
        }
        return $result;
    }

    public abstract function getLocales();

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }
    public function getLocaleKeys()
    {
        return $this->localeKeys;
    }

}

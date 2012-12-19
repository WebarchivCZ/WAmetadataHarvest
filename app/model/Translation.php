<?php

class Translation implements Nette\Localization\ITranslator {

    private $tableName = 'translation';

    private $tablePrefix = '';

    private $connection;

    private $cacheStorage;

    public function __construct(Nette\Database\Connection $connection, Nette\Caching\IStorage $cacheStorage)
    {
        $this->connection = $connection;
        $this->cacheStorage = $cacheStorage;
    }


    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    private $locale;
    public function setLocale($locale)
    {
        if ($locale !== $this->locale) {
            $this->locale = $locale;
            $this->loadTranslation($locale);
        }
    }

    private $translations;
    private function loadTranslation($locale)
    {
        $this->translations = $this->fetchTranslation();return;
        $cache = new Nette\Caching\Cache($this->cacheStorage, 'translation');
        if (!isset($cache[$locale])) {
            $cache->save($locale, callback($this, 'fetchTranslation'));
        }
        $this->translations = $cache->load($locale);
    }
    public function fetchTranslation()
    {
        $translations = array();
        $rows = $this->getTable()->where('locale', $this->locale);
        foreach ($rows as $row) {
            $translations[$row->key][(int) $row->count] = $row->value;
        }
        return $translations;
    }

    public function translate($message, $count = NULL)
    {
        if (NULL === $message || is_scalar($message) && !is_string($message) || ctype_digit($message)) {
            return $message;
        }

        if (!isset($this->translations[$message][0])) {
            $this->translations[$message][0] = $message;
            $this->getTable()->insert(array(
                'key' => $message,
                'value' => $message,
                'count' => 0,
                'locale' => $this->locale,
            ));
            $cache = new Nette\Caching\Cache($this->cacheStorage, 'translation');
            unset($cache[$this->locale]);
        }
        $localeCount = $this->getLocaleCount($count);
        if (!isset($this->translations[$message][(int) $count]) && $count == 1) {
            $count = 2;
        }
        $translation = isset($this->translations[$message][(int) $count]) ? $this->translations[$message][(int) $count] : $this->translations[$message][0];
        if (func_num_args() > 1) {
            $parameters = func_get_args();
            array_shift($parameters);
            if (NULL !== $parameters[0]) {
                $translation = vsprintf($translation, $parameters);
            }
        }
        return $translation;
    }

    private function getLocaleCount($number)
    {
        $result = 0;
        if (is_numeric($number)) {
            switch ($this->locale) {
                case 'cs_CZ':
                    if ($number == 1) {
                        $result = 0;
                    } elseif (1 < $number && $number < 5) {
                        $result = 1;
                    } else {
                        $result = 2;
                    }
                    break;
                default:
                    $result = $number == 1 ? 0 : 1;
            }
        }
        return $result;
    }

    private function getTable()
    {
        return $this->connection->table($this->tablePrefix . $this->tableName);
    }

}

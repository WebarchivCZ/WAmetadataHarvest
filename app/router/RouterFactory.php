<?php

use Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory
{

	private $defaultLocale;

	private $availableLocales;

	private $localeTranslations;

	private $secured;

	public function __construct($locale, $secured = FALSE)
	{
		$this->secured = $secured;

		$this->defaultLocale = $locale['default'];
		$this->availableLocales = $locale['available'];
		$this->localeTranslations = $locale['translations'];
	}

	/**
	 * @return Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$flags = $this->secured ? Route::SECURED : 0;

		$router = new RouteList();
		$router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY | $flags);

		Route::$styles['#raw'] = array(
			Route::FILTER_OUT => null,
			Route::PATTERN => '.*?',
		);
		$router[] = new Route('[<locale [a-z]{2}(-[a-z]{2})?>/]~/<path #raw>', array(
			'presenter' => 'Harvest:Browse',
			'action' => 'default',
			'locale' => array(
				Route::VALUE => $this->defaultLocale,
				Route::FILTER_IN => array($this, 'filterInLocale'),
				Route::FILTER_OUT => array($this, 'filterOutLocale')
			)
		));

		$router[] = new Route('[<locale [a-z]{2}(-[a-z]{2})?>/]<presenter>[/<action>][/<id>]', array(
			'presenter' => 'Homepage',
			'action' => 'default',
			'locale' => array(
				Route::VALUE => $this->defaultLocale,
				Route::FILTER_IN => array($this, 'filterInLocale'),
				Route::FILTER_OUT => array($this, 'filterOutLocale')
			)
		), $flags);
		return $router;
	}

	public function filterInLocale($locale)
	{
		return array_search($locale, $this->localeTranslations, TRUE) ?: (array_search($locale, $this->availableLocales, TRUE) !== FALSE ? $locale : $this->defaultLocale);
	}

	public function filterOutLocale($locale)
	{
		return isset($this->localeTranslations[$locale]) ? $this->localeTranslations[$locale] : $locale;
	}

}

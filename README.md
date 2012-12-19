WAmetadataHarvest
=================

Project focused on harvesting metadata from Heritrix logs/archives and WA-admin tool.


##Programátorská dokumentace
Veškerý kód je v PHP nebo v konfiguraci formátu neon. Všude, kde je to bylo časově možné jsem používal příkladné postupy a dependency injection.
Třídy až na pár vyjímek jsou jednoduché a drží se kontraktu deklarovaných rozhraním.

###HarvestModule\XmlGenerator
Tato třída generuje výsledné XML za použití definice šablony v konfiguraci a zdrojů, které jsou převážně také deklarovány v konfiguraci. Používá dva specifické zdroje: sklizeň (harvest) nad kterou generování provádí a hodnoty formuláre (form), pokud jsou zadané. Zbytek zdrojů je v definovaný v konfiguraci. Iterator je pseoudo zdroj, který se hodí při generování dětí pomocí tabulkového zdroje.
Možné nastavení jednotlivých zdrojů mají idiomatické názvy a jejich vychozí hodnoty jsou definovány při volání setOptions.

###Použité moduly a knihovky
V zdroji je sice composer.json ale projekt používá upravené kódy zejména web-resourcemanager a webarchive. Zbytek knihoven by měl být identický se stažením přes composer.

###TAR Archívy
Kvulí nevhodné konfiguraci serveru a specifickým podmínkám přístupu k velkým datovým souborům sem napsal vlastní implementaci čtenáře TAR archívů. Měla by být kompatibilní s USTAR formátem. Pro rychlejší eliminaci chyb jsem kód zveřejnil na https://github.com/mishak87/archive-tar

###WebArchive
Čtenář hlaviček formátů WARC a ARC. Implementoval jsem v PHP i přesto, že jsem měl
fungující implementaci pomocí dostupných knihoven v python. Kvůli časové tísní a konfiguraci
produkčního serveru jsem se rozhodl implementovat základní fci v PHP. Implementace je podle
specifikací WARC i ARC se základní detekcí špatných archívů.

##Administrátorská dokumentace

####Podporované prostředí
Aplikace byla testována na systémech windows i linux. Pro běh potřebuje PHP 5.3.8 a vyšší, MySQL databázi, webový server prakticky jakýkoli s podporou PHP nebo proxy na PHP v CGI. Dostatečné množství dostupné paměti, alespoň 128MB.
Aplikace podporuje pěkné url - mod_rewrite u Apache.
Aplikace nepotřebuje pro své fungování Python nebo knihovny pro práci s TAR archívy. Vše je implementováno v aplikaci v PHP.

###Předpoklady
Administrátor by měl znát konfigurační formát www.ne-on.org a chápat základy Nette a jeho
konfigurace.

###Instalace
Aplikace se nainstaluje nakopírováním do libovolné složky. Kořenová složka pro webový server je www. Ostatní složky nesmí být dostupné prostřednictvím webového serveru. PHP musí mít právo zapisovat ve složkách temp/cache, log a www/assets. Veškerá specifická nastavení pro instanci aplikace patří do app/config/config.local.neon. Zejména jde o nastavení přístupu k databázím. Aplikace potřebuje dvě databáze - vlastní databázi pro ukládání informací o sklizních a uživatelích a přístup k databázi programu Wadmin pro získávání informací o “semíncích” sklizní.
Skript pro vytvoření tabulek je v db.sql. Po jeho importu bude v aplikaci dostupný administrátorský účet admin s heslem 123456.
Pokud aplikace bude běžet na nezašifrovaném protokolu je třeba explicitně do /app/config/config.local.neon přidat do sekce parameters hodnotu secured: false

###Údržba
Aplikace je bezúdržbová, nicméně uchovává informace o zpracovaných archívech a souborech pokud by některé soubory soubory byly pro aplikaci “neviditelné” zejména zdroje pro vygenerování XML. Je možné jako administrátor vymazat celou mezipamět bez ručního zásahu na serveru. Tato volba je v administraci aplikace v sekci Sklizně.


###Uživatelé
Jsou rozlišeny dva typy uživatelů: obyčejní a administrátoři. Administrátoři mohou manipulovat se všemi uživateli, jen se nemohou smazat. Je jim poskytnuta základní funkce pro správu uživatelů: vytváření, editaci a mazání.
Ve výchozím nastavení není povolena obnova ztraceného hesla a registrace nových uživatelů. Obě tyto volby lze nastavit v konfiguraci (app/config/config.local.neon) pod cestou parameters > user > registration > enabled: True a parameters > user > account-recovery > enabled: True.
Obnovení ztraceného hesla vyžaduje ještě nastavení odesilatele emailu.
Uživatelé si v nastavení mohou sami měnit heslo a jejich email.


###Sklizně
Lze definovat více kořenových adresářů se sklizněmi. Konfiguruje se v sekci parameters > harvest > directories: jako jméno: adresář.

###Detekce sklizní
Detekce jestli aktuální adresář je sklizní probíhá automaticky. Konfigurace parametrů pro detekci je v HarvestModule/harvest.defaults.neon, harvest.arc.neon, harvest.warc.neon. Jedná se o sekci sources důležité hodnoty jsou priority, depth, filename. Priority určuje hodnotu konfigurace, používá se pro vybírání mezi různýmí konfiguracemi (zatím jen arc a warc). Depth značí hloubku do jaké se má soubor hledat 0 znamená jen v aktuálním adresáři. Hloubka se nebere se v potaz při prohledávání obsahu TAR archívů, porovnává se jen maska souboru. Ve filename je definována maska datového souboru, může obsahovat více variant (definuje se jako pole).
Skoré konfigurace se počítá vynásobením priority počtem souborů pro každý zdroj, pokud zdroj podporuje jen jeden soubor počítá se jen jeden pro ten zdroj.

###Sběr do mezipaměti
Při zobrazení výpisu adresáře se sklizní se může objevit ukazatel průběhu. Znamená to, že
aplikace sbírá informace do mezipaměti o webových archívech sklizně. Sběr do mezipaměti
značně zkrátí čekání na první generování XML.


###Generování XML
Společně s detekcí i generování XML je plně konfigurovatelné. Samotné XML se skládá ze
zdrojů a definice v konfiguraci. Základní šablona je v /HarvestModule/harvest.defaults.neon v
sekci xml včetně dokumentace.
Některé parametry lze upravit ve formuláři. Cesty k výchozím hodnotám jednotlivých vstupů
formuláře jsou pod sekcí form. Hodnoty se získávají z vygenerovaného XML pomocí XPath.
V případě že není možné XML ukládat do adresáře se sklizní je zde nastavení parameters > harvest > output. Aplikace pak do něj bude ukládat pod stejnou cestou jako při procházení vygenerované XML.


###Překlady
Aplikace podporuje překlady. Všechny řetězce jsou v tabulce translation. Jde o mírně přes sto záznamů.

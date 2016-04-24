<?php
/**
 * index file, starting the CMS
 */
ob_start(); //buforowanie wyjścia

require_once 'config/defines.php';
require_once(CORE.'kernel.php');

$_APPS=array();

//ładowanie potrzebnych danych
try {
    $kernel = new kernel(true, true, false);
    $kernel->setResponseCode(200);
    $kernel->loadApp('CMS_settings', $_APPS);
$kernel->loadTheme('html', $_APPS['CMS_settings']->theme);
$kernel->loadRequiredApps($_APPS);
} catch (Exception $e) {
    echo $e->getMessage();
    die('Wystąpił niespodziewany błąd');
}

//dodawanie do wynikowej strony skryptów i arkuszy styli zawsze ładowanych niezależnie od szablonu
if (file_exists(THEMES . $_APPS['CMS_settings']->theme . '/jquery-ui.css'))
    $kernel->view->addStylesheet($kernel->host() . THEMES . $_APPS['CMS_settings']->theme . '/jquery-ui.css');
else
    $kernel->view->addStylesheet($kernel->host() . MODULES . 'jquery-ui-1.11.4/jquery-ui.min.css');

//$kernel->view->addScript(null, $kernel->host() . MODULES . 'jquery-2.1.4.min.js');
$kernel->view->addScript(null, $kernel->host() . MODULES . 'jquery-1.11.3.min.js');
$kernel->view->addScript(null, $kernel->host() . MODULES . 'jquery-ui-1.11.4/jquery-ui.min.js');

if (isset($_GET['errorPage']) && is_integer($_GET['errorPage']) && $_GET['errorPage'] != 404) {
    $kernel->setResponseCode($_GET['errorPage']);
    } elseif ($kernel->rewrite->search && !is_null($kernel->rewrite->app)) {
    $kernel->setResponseCode(200);
        if($kernel->loadApp($kernel->rewrite->app, $_APPS)){
            if(!is_null($kernel->rewrite->action)){
                if(method_exists($_APPS[$kernel->rewrite->app], $kernel->rewrite->action.'Action')){
                    $_APPS[$kernel->rewrite->app]->{$kernel->rewrite->action.'Action'}($kernel->rewrite->vars);
                } else {
                    $kernel->setResponseCode(404);
		}
            } elseif (method_exists($_APPS[$kernel->rewrite->app], 'indexAction')) {
                $_APPS[$kernel->rewrite->app]->indexAction($kernel->rewrite->vars);
            } else {
                $kernel->setResponseCode(404);
	    }
        } else {
            $kernel->setResponseCode(404);
	}
} else {
    if (($app = $kernel->loadHomeApp($_APPS)) !== false) {
	if (method_exists($_APPS[$app], 'indexAction')) {
	    $_APPS[$app]->indexAction($kernel->rewrite->vars);
	} else {
	    $kernel->setResponseCode(404);
	}
    }
}

unset($_APPS);
unset($kernel);

ob_flush(); //wypisanie wyjścia

if (isset($_GET['debug'])) {
    echo ((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000) . ' ms';
}
?>

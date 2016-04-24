<?php
/**
 * Defines for CMS
 */

/**
 * config dir
 */
define('CONFIG', str_replace('defines.php', '', __FILE__));
/**
 * MyCMS version
 */
define('CMS_VERSION', '2.0.0');
/**
 * themes include dir
 */
define('THEMES', '_themes/');
/**
 * modules include dir
 */
define('MODULES', '_include/modules/');
/**
 * apps include dir
 */
define('APPS', '_include/apps/');
/**
 * languages files include dir
 */
define('LANG', '_include/lang/');
/**
 * kernel files and needed libs include dir
 */
define('CORE', '_core/');
/**
 * type of access to site admin or client
 */
define('TYPE','client');
?>

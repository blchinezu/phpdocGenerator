<?php

/**
 * Configuration. Loaded in all files.
 *
 * @author   Gabriel Ionescu
 * @package  phpdocGenerator
 * @link     https://github.com/blchinezu/phpdocGenerator
 * 
 */


// PHP DOCUMENTOR
define('PHPDOC_BINARY', '/usr/bin/phpdoc');


// PATHS
define('RESOURCES_PATH',    'resources/');
define('TRANSLATIONS_PATH', RESOURCES_PATH.'translations.ini');
define('IMAGES_PATH',       RESOURCES_PATH.'img/');
define('CSS_PATH',          RESOURCES_PATH.'css/');
define('JS_PATH',           RESOURCES_PATH.'js/');

define('CLASSES_PATH', 'classes/');

define('DOCS_PATH',     'docs');
define('DOCS_PATH_WEB', 'http://brucelee.duckdns.org/phpDocumentor/generator/docs/');
define('LOG_PATH',      'log/');


// FILE EXPLORER
define('FILE_EXPLORER_CHROOT', '/var/www/brucelee.duckdns.org');


// SQL
define('SQL_HOST',             'localhost');
define('SQL_USER',             'brucelee');
define('SQL_PASS',             '');
define('SQL_DB',               'brucelee_duckdns_org');
define('SQL_PROJECTS_TABLE',   SQL_DB.'.phpDocumentorProjects');


// Load non standard ini files
function loadNonStandardIni($path) {
    $tmp = explode("\n", str_replace("\r", "", file_get_contents($path)));
    $i18n = array();
    foreach( $tmp AS $line ) {

        if( empty($line) )
            continue;

        $line = explode(' = ', $line, 2);
        if( !isset($line[1]) )
            continue;

        $i18n[ $line[0] ] = $line[1];
    }
    return $i18n;
}

// Translation function
function i18n($text) { return isset($GLOBALS['i18n'][$text]) ? $GLOBALS['i18n'][$text] : $text; }

// Configure autoloader
function defines__autoload($class){
    if( file_exists(CLASSES_PATH.'class.'.$class.'.php') )
        require_once(CLASSES_PATH.'class.'.$class.'.php');
    elseif( 'class.'.$class.'.php' )
        require_once('class.'.$class.'.php');
    else
        die('autoloader error: Couldn\' find class file for "'.$class.'"');
}
spl_autoload_register('defines__autoload');


// Load translation strings
$i18n = loadNonStandardIni(TRANSLATIONS_PATH);

?>
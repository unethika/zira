<?php
    define('COMMONPATH', str_replace("\\", "/", realpath(dirname(__FILE__) . '/..') . '/GridCommon/'));
    define("SIMIAN_INSTALLER", TRUE);

    define("NEED_PHP_VERSION", "5.3");
    define("MYSQL_VERSION", "5.1");
    define("INSTALLER_PROJECT", 'Simian Grid');
    
    $requiredMysqlVersion = "5.0";
    $requiredModules = array(
        'curl',
        'hash',
        'bcmath'
    );
    
    $defaultDB['user'] = 'root';
    $defaultDB['host'] = '127.0.0.1';
    $defaultDB['db'] = 'Simian';
    $defaultDB['password'] = '';

    $dbCheckTables = array( );
    $dbSchemas = array();
    $dbFixtures = array();
    
    $writableDirectories = array('logs', 'maps', 'textures');
    
    $configOptions['user_service']['name'] = "User Server";
    $configOptions['user_service']['description'] = "The URL of the User Server";
    $configOptions['user_service']['default'] = "http://localhost/Grid/";
    $configOptions['user_service']['string'] = "@@USER_SERVICE@@";
    $configOptions['user_service']['file'] = "config/config.php";
    
    $configOptions['grid_service']['name'] = "Grid Server";
    $configOptions['grid_service']['description'] = "The URL of the Grid Server";
    $configOptions['grid_service']['default'] = "http://localhost/Grid/";
    $configOptions['grid_service']['string'] = "@@GRID_SERVICE@@";
    $configOptions['grid_service']['file'] = "config/config.php";
    
    $configOptions['asset_service']['name'] = "Asset Server";
    $configOptions['asset_service']['description'] = "The URL of the Asset Server";
    $configOptions['asset_service']['default'] = "http://localhost/Grid/?id=";
    $configOptions['asset_service']['string'] = "@@ASSET_SERVICE@@";
    $configOptions['asset_service']['file'] = "config/config.php";
    
    $configOptions['inventory_service']['name'] = "Inventory Server";
    $configOptions['inventory_service']['description'] = "The URL of the Inventory Server";
    $configOptions['inventory_service']['default'] = "http://localhost/Grid/";
    $configOptions['inventory_service']['string'] = "@@INVENTORY_SERVICE@@";
    $configOptions['inventory_service']['file'] = "config/config.php";
    
    $configOptions['map_service']['name'] = "Map Server";
    $configOptions['map_service']['description'] = "The URL of the Map Server";
    $configOptions['map_service']['default'] = "http://localhost/Grid/";
    $configOptions['map_service']['string'] = "@@MAP_SERVICE@@";
    $configOptions['map_service']['file'] = "config/config.php";

    require COMMONPATH . 'Installer/install.php';

?>

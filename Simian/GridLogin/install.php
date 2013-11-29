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
    
    $writableDirectories = array('logs');
    
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
    
    $configOptions['motd']['name'] = "MOTD";
    $configOptions['motd']['description'] = "Message which is displayed to users at login";
    $configOptions['motd']['default'] = "Welcome to SimianGrid !";
    $configOptions['motd']['string'] = "@@MOTD@@";
    $configOptions['motd']['file'] = "config/config.php";

    $configOptions['loginuri']['name'] = "Login URI";
    $configOptions['loginuri']['description'] = "Login URL for the grid";
    $configOptions['loginuri']['default'] = "http://localhost/GridLogin/";
    $configOptions['loginuri']['string'] = "@@LOGINURI@@";
    $configOptions['loginuri']['file'] = "get_grid_info";

    $configOptions['gridname']['name'] = "Grid Name";
    $configOptions['gridname']['description'] = "Full name of the grid";
    $configOptions['gridname']['default'] = "OpenSim Simian Grid";
    $configOptions['gridname']['string'] = "@@GRIDNAME@@";
    $configOptions['gridname']['file'] = "get_grid_info";

    $configOptions['gridnick']['name'] = "Grid Nickname";
    $configOptions['gridnick']['description'] = "Short name of the grid";
    $configOptions['gridnick']['default'] = "simgrid";
    $configOptions['gridnick']['string'] = "@@GRIDNICKNAME@@";
    $configOptions['gridnick']['file'] = "get_grid_info";

    $configOptions['frontend']['name'] = "Grid Frontend";
    $configOptions['frontend']['description'] = "URL for information about the grid";
    $configOptions['frontend']['default'] = "http://localhost/";
    $configOptions['frontend']['string'] = "@@GRIDFRONTEND@@";
    $configOptions['frontend']['file'] = "get_grid_info";

    require COMMONPATH . 'Installer/install.php';

?>

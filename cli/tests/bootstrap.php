<?php
// PHPUnit bootstrap file
define('WHMCS_CAC_ROOT', dirname(__DIR__));
require_once WHMCS_CAC_ROOT . '/cli/src/Whmcs/LocalApiClient.php';
require_once WHMCS_CAC_ROOT . '/cli/src/Adapter/AdapterInterface.php';
require_once WHMCS_CAC_ROOT . '/cli/src/Adapter/Result.php';
require_once WHMCS_CAC_ROOT . '/cli/src/Adapter/SettingsAdapter.php';
require_once WHMCS_CAC_ROOT . '/cli/src/Adapter/GatewayAdapter.php';

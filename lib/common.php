<?php
// base
$path_base = dirname(__FILE__);

//----------------------------------------------------------------------------
/// load mustache module
//----------------------------------------------------------------------------
if(is_file($path_base.'/Mustache/Autoloader.php'))
{
    require $path_base.'/Mustache/Autoloader.php';
    Mustache_Autoloader::register();
}
else
{
    echo 'Missing Module: Mustache';
    exit;
}

//----------------------------------------------------------------------------
/// include rester module
//----------------------------------------------------------------------------
require $path_base . '/ResterFrontend.class.php';
$rester = new ResterFrontend();

try
{
    $rester->init();

}
catch (Exception $e)
{
    $rester->error($e->getMessage());
}

$rester->run();

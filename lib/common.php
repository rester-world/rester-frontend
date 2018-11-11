<?php
// base
$path_base = dirname(__FILE__);

/// ============================================================================
/// include data module
/// ============================================================================
require $path_base.'/data.class.php';

/// ============================================================================
/// load mustache module
/// ============================================================================
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
$mustache = new Mustache_Engine;

try
{
/// ============================================================================
/// include pages
/// ============================================================================
    $pages = array();
    foreach (glob($path_base.'/../html/rester-pages/*.html') as $filename)
    {
        $data = $mustache->render(file_get_contents($filename),cfg());
        $pages[basename($filename,'.html')] = $data;
    }
    data::Set($pages,data::inc);

/// ============================================================================
/// include and echo contents
/// ============================================================================
    $contents = file_get_contents(cfg(data::path));
    echo $mustache->render($contents,cfg());
}
catch (Exception $e)
{
    echo $e->getMessage();
}

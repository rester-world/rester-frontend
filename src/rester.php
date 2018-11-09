<?php
// base
$path_base = dirname(__FILE__);

///
/// load mustache module
///
if(is_file($path_base.'/../lib/Mustache/Autoloader.php'))
{
    require $path_base.'/../lib/Mustache/Autoloader.php';
    Mustache_Autoloader::register();
}
else
{
    echo 'You must include the mustache-php module.';
    exit;
}

///
/// Check path
/// Add default index.html
///
$path = $_GET['rester-front'];
if(substr($path,-1)=='/' || $path=='')
{
    $path.='index.html';
}

///
/// Check file
/// Not find include 404.html
///
if(!is_file($path))
{
    echo file_get_contents($path_base.'/404.html');
    exit;
}


$m = new Mustache_Engine;
$contents = file_get_contents($path);

///
/// include config files
///
$cfg = array();
foreach (glob($path_base.'/../cfg/*.ini') as $filename)
{
    $_cfg = parse_ini_file($filename,true);
    if($_cfg) $cfg = array_merge($cfg,$_cfg);
}

///
/// include data
///
$data = array();
$data['cfg'] = $cfg;
$data['page'] = $cfg[$path];

if($cfg['rester-api']['host'] && $cfg['rester-api']['port'])
{
// TODO api 서버에서 페이지 결과 받아와서 merge
}

///
/// include pages
///
foreach (glob($path_base.'/rester-pages/*.html') as $filename)
{
    $data['rester-pages'][basename($filename,'.html')] = $m->render(file_get_contents($filename),$data);
}

echo $m->render($contents,$data);


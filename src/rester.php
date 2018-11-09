<?php
// base url
$path_base = dirname(__FILE__);

// load mustache
if(is_file($path_base.'/Mustache/Autoloader.php'))
{
    require $path_base.'/Mustache/Autoloader.php';
    Mustache_Autoloader::register();
}
else
{
    echo 'You must include the mustache-php module.<br>Place the mustache in the root folder.<br>You can download the mustache here. <a href="https://github.com/bobthecow/mustache.php" target="_blank">https://github.com/bobthecow/mustache.php</a>';
    exit;
}

$path = $_GET['rester-front'];
if(substr($path,-1)=='/' || $path=='')
{
    $path.='index.html';
}

if(is_file($path))
{
    $m = new Mustache_Engine;

    $contents = file_get_contents($path);

    $cfg = parse_ini_file('../cfg/rester.ini',true);
    if($cfg[$path]) $data = array_merge($cfg['common'],$cfg[$path]);
    else $data = $cfg['common'];

    // TODO api 서버에서 페이지 결과 받아와서 merge

    foreach (glob($path_base.'/rester-pages/*.html') as $filename)
    {
        $data['rester-pages'][basename($filename,'.html')] = $m->render(file_get_contents($filename),$data);
    }



    echo $m->render($contents,$data);
}
else
{
    echo file_get_contents($path_base.'/404.html');
}


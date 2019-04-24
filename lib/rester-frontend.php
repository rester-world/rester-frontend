<?php
//----------------------------------------------------------------------------
/// Define
//----------------------------------------------------------------------------
define('__INC__','rester-inc');
define('__SKINS__','rester-skins');
define('__QUERY_PATH__','rester-front');
define('__CONFIG_REQUEST__','request');
define('__CONFIG_REQUEST_COMMON__','common');
define('__CONFIG_REQUEST_DEFAULT__','default');
define('__CONFIG_REQUEST_PAGES__','request-pages');
define('__CONFIG_REQUEST_PARAM__','request-param');

define('__DATA_URI__','request-uri');
define('__DATA_CONFIG__','cfg');
define('__DATA_RESPONSE__','res');
define('__DATA_COMMON__','common');
define('__DATA_PAGE__','page');

//----------------------------------------------------------------------------
/// Define function
//----------------------------------------------------------------------------
/**
 * @param string $file
 *
 * @return string
 */
function path_html($file='') { return dirname(__FILE__).'/../src/'.$file; }

/**
 * @param string $file
 *
 * @return string
 */
function path_html_inc($file='') { return dirname(__FILE__).'/../src/'.__INC__.'/'.$file; }

/**
 * @param string $file
 *
 * @return string
 */
function path_html_skins($file='') { return dirname(__FILE__).'/../src/'.__SKINS__.'/'.$file; }

/**
 * @param string $file
 *
 * @return string
 */
function path_cfg($file='') { return dirname(__FILE__).'/../cfg/'.$file; }

/**
 * @param array $cfg
 * @param string $name
 *
 * @return string
 */
function api_uri($cfg, $name='')
{
    $module_proc = '';
    if($cfg[__CONFIG_REQUEST__][$name]) $module_proc = $cfg[__CONFIG_REQUEST__][$name];
    if($cfg[__CONFIG_REQUEST_PAGES__][$name]) $module_proc = $cfg[__CONFIG_REQUEST_PAGES__][$name];
    if(substr($module_proc,0,1)=='/') $module_proc = substr($module_proc, 1);

    $uri = null;
    if($module_proc)
    {
        $uri = implode('/', [
            $cfg[__CONFIG_REQUEST__]['host'].':'.$cfg[__CONFIG_REQUEST__]['port'],
            $cfg[__CONFIG_REQUEST__]['prefix'],
            $module_proc
        ]);
    }
    return $uri;
}

/**
 * @param array $cfg
 * @param string $uri
 * @param array  $body
 *
 * @return bool|mixed
 * @throws Exception
 */
function request($cfg, $uri, $body)
{
    if($cfg[__CONFIG_REQUEST_PARAM__])
    {
        $body = array_merge($body,$cfg[__CONFIG_REQUEST_PARAM__]);
    }

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_URL => $uri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($body),
    ));

    $response_body = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //echo curl_error($ch);
    //var_dump(curl_getinfo($ch));
    curl_close($ch);

    if($response_code != 200)
    {
        //$this->error_msg = array_pop(json_decode($this->response_body,true));
        return false;
    }
    $res = json_decode($response_body,true);
    return $res;
}

//----------------------------------------------------------------------------
/// load mustache module
//----------------------------------------------------------------------------
if(is_file(dirname(__FILE__).'/Mustache/Autoloader.php'))
{
    require dirname(__FILE__).'/Mustache/Autoloader.php';
    Mustache_Autoloader::register();
}
else
{
    echo 'Missing Module: Mustache';
    exit;
}
$mustache = new Mustache_Engine;

$cfg = [];
$path = '';
$data = [];

// include config files (*.ini)
foreach (glob(path_cfg('*.ini')) as $filename)
{
    $_cfg = parse_ini_file($filename,true);
    if($_cfg) $cfg = array_merge($cfg,$_cfg);
}
$data[__DATA_CONFIG__] = $cfg;

try
{
    // Check path
    // Add default index.html
    $path = $_GET[__QUERY_PATH__];
    if(substr($path,-1)=='/' || $path=='')
    {
        $path.='index.html';
    }

    // 스킨폴더 접근금지
    if( strpos($path,__SKINS__.'/')!==false || strpos($path,__INC__.'/')!==false )
    {
        throw new Exception("예약된 폴더에 접근할 수 없습니다.");
    }

    // 파일검사
    if( !is_file(path_html($path)))
    {
        throw new Exception("파일을 찾을 수 없습니다.({$path})");
    }
    $data[__DATA_URI__] = $path;

    //----------------------------------------------------------------------------------------------
    /// include page data
    //----------------------------------------------------------------------------------------------
    // common
    if($api = api_uri($cfg, __CONFIG_REQUEST_COMMON__))
    {
        $res = request($cfg, $api, ['path'=>$path,'query'=>$_GET]);

        if(is_array($res)) $data[__DATA_RESPONSE__] = $res;
        if(!is_array($res) || !$res['success'])
        {
            throw new Exception("{$api} API 호출에 실패 하였습니다. ");
        }

        // 로그인 체크
        if($res['retCode']=='01') throw new Exception("로그인이 필요합니다.",'01');
        $data[__DATA_COMMON__] = $res['data'];
    }

    // page
    $api_uri = api_uri($cfg, __CONFIG_REQUEST_DEFAULT__);
    if(isset($cfg[__CONFIG_REQUEST_PAGES__]))
    {
        foreach($cfg[__CONFIG_REQUEST_PAGES__] as $_path=>$_url)
        {
            if(strpos($path,$_path)===0) $api_uri = api_uri($cfg, $_path);
        }
    }

    if($api_uri)
    {
        // Api 호출
        $res = request($cfg, $api_uri,['path'=>$path,'query'=>$_GET]);

        if(is_array($res)) $data[__DATA_RESPONSE__] = $res;
        if(!is_array($res) || !$res['success'])
        {
            throw new Exception("{$api} API 호출에 실패 하였습니다. ");
        }

        // 로그인 체크
        if($res['retCode']=='01') throw new Exception("로그인이 필요합니다.",'01');
        $data[__DATA_COMMON__] = $res['data'];

        if(is_array($res['data']))
        {
            foreach($res['data'] as $k=> $v)
            {
                $contents = $v;

                // 스킨 데이터가 있을 경우 스킨을 파싱함
                if(isset($v['rester-skin']))
                {
                    $filename = path_html_skins($v['rester-skin-name'].'.html');
                    if(!is_file($filename))
                        throw new Exception("skin 파일을 찾을 수 없습니다.: {$v['rester-skin-name']}.html");

                    $contents = $mustache->render(file_get_contents($filename),$v['rester-skin-contents']);
                }
                if(isset($v['listable']) && $v['listable']) $data['pages']['list'][] = $contents;
                $data['pages'][$k] = $contents; // 연관배열
            }
        }
    }

}
catch (Exception $e)
{
    $path = 'error.html';
    $data['error-msg'] = $e->getMessage();
}

// include inc folder
$data[__INC__] = [];
foreach (glob(path_html_inc('*.html')) as $filename)
{
    $__data = $mustache->render(file_get_contents($filename),$data);
    $data[__INC__][basename($filename,'.html')] = $__data;
}

// Rendering
$contents = file_get_contents(path_html($path));
echo $mustache->render($contents,$data);

<?php
//-------------------------------------------------------------------------------
/// set php.ini
//-------------------------------------------------------------------------------
set_time_limit(0);
ini_set("session.use_trans_sid", 0); // PHPSESSID 를 자동으로 넘기지 않음
ini_set("url_rewriter.tags","");     // 링크에 PHPSESSID 가 따라다니는것을 무력화
ini_set("default_socket_timeout",500);

ini_set("memory_limit", "1000M");     // 메모리 용량 설정.
ini_set("post_max_size","1000M");
ini_set("upload_max_filesize","1000M");

//-------------------------------------------------------------------------------
/// Set the global variables [_POST / _GET / _COOKIE]
/// initial a post and a get variables.
/// if not support short global variables, will be available.
//-------------------------------------------------------------------------------
if (isset($HTTP_POST_VARS) && !isset($_POST))
{
    $_POST   = &$HTTP_POST_VARS;
    $_GET    = &$HTTP_GET_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_ENV    = &$HTTP_ENV_VARS;
    $_FILES  = &$HTTP_POST_FILES;
    if (!isset($_SESSION))
        $_SESSION = &$HTTP_SESSION_VARS;
}

// force to set register globals off
// http://kldp.org/node/90787
if(ini_get('register_globals'))
{
    foreach($_GET as $key => $value) { unset($$key); }
    foreach($_POST as $key => $value) { unset($$key); }
    foreach($_COOKIE as $key => $value) { unset($$key); }
}

function stripslashes_deep($value)
{
    $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    return $value;
}

//-------------------------------------------------------------------------------
/// if get magic quotes gpc is on, set off
/// set magic_quotes_gpc off
//-------------------------------------------------------------------------------
if (get_magic_quotes_gpc())
{

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

//-------------------------------------------------------------------------------
/// add slashes
//-------------------------------------------------------------------------------
//if(is_array($_POST)) array_walk_recursive($_POST, function(&$item){ $item = addslashes($item); });
//if(is_array($_GET)) array_walk_recursive($_GET, function(&$item){ $item = addslashes($item); });
//if(is_array($_COOKIE)) array_walk_recursive($_COOKIE, function(&$item){ $item = addslashes($item); });

session_start();

//----------------------------------------------------------------------------
/// Define
//----------------------------------------------------------------------------
define('__INC__','rester-inc');
define('__SKINS__','rester-skins');
define('__QUERY_PATH__','rester-front');
define('__CONFIG_REQUEST__','request');
define('__CONFIG_REQUEST_COMMON__','common');
define('__CONFIG_REQUEST_DEFAULT__','default');
define('__CONFIG_REQUEST_LOGIN__','login');
define('__CONFIG_REQUEST_LOGOUT__','logout');
define('__CONFIG_REQUEST_JOIN__','join');
define('__CONFIG_REQUEST_PAGES__','request-pages');
define('__CONFIG_REQUEST_PARAM__','request-param');

define('__DATA_URI__','request-uri');
define('__DATA_CONFIG__','cfg');
define('__DATA_RESPONSE__','res');
define('__DATA_COMMON__','common');
define('__DATA_PAGE__','page');

define('__SESSION_TOKEN__','token');

define('__REQUEST__','rester-request');

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
function get_api_uri($cfg, $name='')
{
    $module_proc = '';
    if($cfg[__CONFIG_REQUEST__][$name]) $module_proc = $cfg[__CONFIG_REQUEST__][$name];
    if($cfg[__CONFIG_REQUEST_PAGES__][$name]) $module_proc = $cfg[__CONFIG_REQUEST_PAGES__][$name];
    if(substr($module_proc,0,1)=='/') $module_proc = substr($module_proc, 1);

    $uri = null;
    if($module_proc && $module_proc!='no-data')
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
 * @return string
 */
function access_ip()
{
    // Check allows ip address
    // Check ip from share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $access_ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    //to check ip is pass from proxy
    else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $access_ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $access_ip=$_SERVER['REMOTE_ADDR'];
    }
    return $access_ip;
}

/**
 * https://stackoverflow.com/questions/3772096/posting-multidimensional-array-with-php-and-curl
 * @param       $arrays
 * @param array $new
 * @param null  $prefix
 */
function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

    if ( is_object( $arrays ) ) {
        $arrays = get_object_vars( $arrays );
    }

    foreach ( $arrays AS $key => $value ) {
        $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
        if ( is_array( $value ) OR is_object( $value )  ) {
            http_build_query_for_curl( $value, $new, $k );
        } else {
            $new[$k] = $value;
        }
    }
}

/**
 * @param array  $cfg
 * @param string $uri
 * @param array  $body
 * @param bool   $files
 *
 * @return bool|mixed
 */
function request($cfg, $uri, $body, $files=false)
{
    if($cfg[__CONFIG_REQUEST_PARAM__])
    {
        $body = array_merge($body,$cfg[__CONFIG_REQUEST_PARAM__]);
    }
    if($_SESSION[__SESSION_TOKEN__])
    {
        $body = array_merge($body,[__SESSION_TOKEN__=>$_SESSION[__SESSION_TOKEN__]]);
    }

    // 2차배열 이상 처리
    $post_body = null;
    http_build_query_for_curl($body, $post_body);

    if($files)
    {
        $post_body = array_merge($post_body,$files); // 1차원 배열만 전달됨
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
        CURLOPT_POSTFIELDS => $post_body,
    ));

    $response_body = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //echo curl_error($ch);
    //var_dump(curl_getinfo($ch));
    curl_close($ch);
//    var_dump($response_body);
//    exit;

    if($response_code != 200)
    {
        //$this->error_msg = array_pop(json_decode($this->response_body,true));
        return false;
    }
    $res = json_decode($response_body,true);
    return $res;
}

/**
 * @param array $cfg
 *
 * @return bool|string
 * @throws Exception
 */
function rester_request($cfg)
{
    $uri = false;
    if($_POST[__REQUEST__])
    {
        $name = $_POST[__REQUEST__];
        $uri = get_api_uri($cfg, $name);
        if(!$uri) throw new Exception("Not found Request Name");
    }
    return $uri;
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
    $data['GET'] = $_GET;

    // Check path
    // Add default index.html
    $path = $_GET[__QUERY_PATH__];
    if(substr($path,-1)=='/' || $path=='')
    {
        $path.='index.html';
    }

    // logout
    // 로그하웃 하면 메인화면으로 돌아감
    if($path=='logout.html')
    {
        if($api = get_api_uri($cfg, __CONFIG_REQUEST_LOGOUT__))
        {
            $res = request($cfg, $api, []);
            if(!is_array($res) || !$res['success'])
            {
                throw new Exception("{$api} API 호출에 실패 하였습니다. ");
            }
            unset($_SESSION[__SESSION_TOKEN__]);
            header("Location: /");
            exit;
        }
        else
        {
            throw new Exception("로그아웃 API 설정이 없습니다.");
        }
    }

    // API 호출이 있는지 검사
    // 주로 업데이트/삭제/입력 등의 처리
    if($rester_api = rester_request($cfg))
    {
        // 첨부파일 추가
        // 배열 형태의 파일도 가능하도록
        $files = false;
        foreach ($_FILES as $fname=>$FILE)
        {
            // 단일 파일일 경우
            if(!is_array($FILE['name']) && $FILE['name'])
            {
                if(isset($FILE) && $FILE['error'] == UPLOAD_ERR_OK)
                {
                    $files[$fname] = new CURLFile($FILE['tmp_name'], $FILE['type'], $FILE['name']);
                }
            }
            // 배열 파일일 경우
            // 1차배열까지만 허용
            else
            {
                foreach($FILE['name'] as $idx => $name)
                {
                    $type = $FILE['type'][$idx];
                    $tmp_name = $FILE['tmp_name'][$idx];
                    $error = $FILE['error'][$idx];
                    $files[$fname."[{$idx}]"] = curl_file_create($tmp_name, $type, $name);
                }
            }
        }
        $res = request($cfg, $rester_api, $_POST, $files);

        if($res['success'])
        {
            // 세션저장
            if($res['session'])
            {
                foreach($res['session'] as $k=>$v)
                {
                    $_SESSION[$k] = $v;
                }
            }
            header("Content-type: application/json; charset=UTF-8");
            echo json_encode($res);
        }
        else
        {
            header("Content-type: application/json; charset=UTF-8");
            echo json_encode($res);
        }
        exit;
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
    if($api = get_api_uri($cfg, __CONFIG_REQUEST_COMMON__))
    {
        $res = request($cfg, $api, [
            'ip'=>access_ip(),
            'agent'=>$_SERVER['HTTP_USER_AGENT'],
            'referer'=>$_SERVER['HTTP_REFERER'],
            'path'=>$path,
            'query'=>$_GET
        ]);
        if(is_array($res)) $data[__DATA_RESPONSE__] = $res;

        // 로그인 체크
        if(!$res['success'] && $res['retCode']=='01')
        {
            throw new Exception("로그인이 필요합니다.",'01');
        }

        if(!is_array($res) || !$res['success'])
        {
            throw new Exception("{$api} API 호출에 실패 하였습니다. ");
        }
        $data[__DATA_COMMON__] = $res['data'];
    }

    // page
    $api_uri = get_api_uri($cfg, __CONFIG_REQUEST_DEFAULT__);
    if(isset($cfg[__CONFIG_REQUEST_PAGES__]))
    {
        foreach($cfg[__CONFIG_REQUEST_PAGES__] as $_path=>$_url)
        {
            if(strpos($path,$_path)===0) $api_uri = get_api_uri($cfg, $_path);
        }
    }

    if($api_uri)
    {
        // Api 호출
        $res = request($cfg, $api_uri,['path'=>$path,'query'=>$_GET]);
        if(is_array($res)) $data[__DATA_RESPONSE__] = $res;

        if(!$res['success'] && $res['retCode']=='01')
        {
            throw new Exception("로그인이 필요합니다.",'01');
        }

        if(!is_array($res) || !$res['success'])
        {
            throw new Exception("{$api_uri} API 호출에 실패 하였습니다. ");
        }
        $data[__DATA_RESPONSE__] = $res['data'];


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

    // common 키워드 제거
    if($data[__DATA_COMMON__])
    {
        $common = $data[__DATA_COMMON__];
        unset($data[__DATA_COMMON__]);
        $data = array_merge($common, $data);
    }

}
catch (Exception $e)
{
    if($e->getCode()=='01')
    {
        $path = 'login.html';
    }
    else
    {
        $path = 'error.html';
    }
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

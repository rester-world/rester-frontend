<?php

/**
 * Class rester
 */
class ResterFrontend
{
    const request_uri       = 'request-uri';
    const request_path      = 'rester-front';

    const inc           = 'rester-inc';
    const skins         = 'rester-skins';

    const config        = 'cfg';

    const request = 'request';
    const request_common = 'common';
    const request_default = 'default';

    const request_pages = 'request-pages';

    const request_param = 'request-param';

    const data_response = 'res';
    const data_common = 'common';
    const data_page = 'page';

    private $data;	// rester config data
    private $html;  // result html
    private $mustache = null;

    /**
     * @param string $file
     *
     * @return string
     */
    private function path_html($file='')
    {
        return dirname(__FILE__).'/../src/'.$file;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function path_html_inc($file='')
    {
        return dirname(__FILE__).'/../src/rester-inc/'.$file;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function path_html_skins($file='')
    {
        return dirname(__FILE__).'/../src/rester-skins/'.$file;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function path_cfg($file='')
    {
        return dirname(__FILE__).'/../cfg/'.$file;
    }

    /**
     * ResterFrontend constructor.
     */
    public function __construct()
    {
        $this->data = [];
        $this->html = '';
        $this->mustache = new Mustache_Engine;
    }

    /**
     * Read and render rester-inc folder
     */
    protected function resterInc()
    {
        $this->data[self::inc] = [];
        foreach (glob($this->path_html_inc('*.html')) as $filename)
        {
            $data = $this->mustache->render(file_get_contents($filename),$this->data);
            $this->data[self::inc][basename($filename,'.html')] = $data;
        }
    }

    /**
     * @throws Exception
     */
    public function init()
    {
        // include config files (*.ini)
        $cfg = array();
        foreach (glob($this->path_cfg('*.ini')) as $filename)
        {
            $_cfg = parse_ini_file($filename,true);
            if($_cfg) $cfg = array_merge($cfg,$_cfg);
        }
        $this->data[self::config] = $cfg;

        //----------------------------------------------------------------------------------------------
        /// Check path
        /// Add default index.html
        //----------------------------------------------------------------------------------------------
        $path = $_GET[self::request_path];
        if(substr($path,-1)=='/' || $path=='')
        {
            $path.='index.html';
        }
        $this->data[self::request_uri] = $path;

        // 스킨폴더 접근금지
        if( strpos($path,self::skins.'/')!==false || strpos($path,self::inc.'/')!==false )
        {
            throw new Exception("예약된 폴더에 접근할 수 없습니다.");
        }

        // 파일검사
        if( !is_file($this->path_html($path)))
        {
            throw new Exception("파일을 찾을 수 없습니다.({$path})");
        }

        //----------------------------------------------------------------------------------------------
        /// include page data
        //----------------------------------------------------------------------------------------------
        // common
        if($api = $cfg[self::request][self::request_common])
        {
            $res = $this->resterApi($api,array('path'=>$path,'query'=>$_GET));
            $this->data[self::data_common] = $res['data'];
        }

        // page
        $api_url = $cfg[self::request][self::request_default];
        if(isset($cfg[self::request_pages]))
        {
            foreach($cfg[self::request_pages] as $_path=>$_url)
            {
                if(strpos($path,$_path)===0) $api_url = $_url;
            }
        }

        if($api_url)
        {
            // Api 호출
            $res = $this->resterApi($api_url,['path'=>$path,'query'=>$_GET]);

            // check login
//        if(self::$data['common']['auth-required'])
//        {
//            $path = 'login.html';
//            if(!is_file(dirname(__FILE__).'/../src/'.$path)) $path = 'rester-error/404.html';
//            self::$data[self::path] = $path;
//        }

            if(is_array($res['rester']))
            {
                foreach($res['rester'] as $k=> $v)
                {
                    $contents = $v;

                    // 스킨 데이터가 있을 경우 스킨을 파싱함
                    if(isset($v['rester-skin']))
                    {
                        $filename = $this->path_html_skins($v['rester-skin-name'].'.html');
                        if(!is_file($filename))
                            throw new Exception("skin 파일을 찾을 수 없습니다.: {$v['rester-skin-name']}.html");

                        $contents = $this->mustache->render(file_get_contents($filename),$v['rester-skin-contents']);
                    }
                    if(isset($v['listable']) && $v['listable']) $this->data['pages']['list'][] = $contents;
                    $this->data['pages'][$k] = $contents; // 연관배열
                }
            }
        }

        // rester-inc folder
        $this->resterInc();

        // Generate last html
        $contents = file_get_contents($this->path_html($path));
        $this->html = $this->mustache->render($contents,$this->data);
    }

    /**
     * @param string $msg
     */
    public function error($msg)
    {
        $this->resterInc();
        $this->data['error-msg'] = $msg;
        $contents = file_get_contents($this->path_html('error.html'));
        $this->html = $this->mustache->render($contents,$this->data);
    }

    /**
     * echo html
     */
    public function run()
    {
        echo $this->html;
    }

    /**
     * @param string $request_url
     * @param array  $body
     *
     * @return bool|mixed
     * @throws Exception
     */
    private function resterApi($request_url, $body)
    {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $request_url,
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

        $this->data[self::data_response] = $res;
        if(!is_array($res) || !$res['success'])
        {
            throw new Exception("{$request_url} API 호출에 실패 하였습니다. ");
        }
        return $res;
    }
}

<?php

/**
 * Class data
 */
class data
{
    private static $data;	// data

    const config = 'cfg';
    const inc = 'rester-pages';
    const path = 'path';
    const path_key = 'rester-front';
    const api_module = 'front';
    const api_proc = 'page';

    private static function ResterApi($request_url, $body)
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
            //CURLOPT_HTTPHEADER => $this->request_headers,
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
        return json_decode($response_body,true);
    }

    /**
     * @throws Exception
     */
    private static function init()
    {
        self::$data = [];

        // include config files
        $cfg = array();
        foreach (glob(dirname(__FILE__).'/../cfg/*.ini') as $filename)
        {
            $_cfg = parse_ini_file($filename,true);
            if($_cfg) $cfg = array_merge($cfg,$_cfg);
        }
        self::$data[self::config] = $cfg;

        ///
        /// Check path
        /// Add default index.html
        ///
        $path = $_GET[self::path_key];
        if(substr($path,-1)=='/' || $path=='')
        {
            $path.='index.html';
        }
        if(!is_file(dirname(__FILE__).'/../html/'.$path))
        {
            $path = '404.html';
        }
        self::$data[self::path] = $path;

        // include page data
        $api = $cfg['rester-api'];
        if($api['host'] && $api['port'])
        {
            $api_url = implode('/',array(
                $api['host'].':'.$api['port'],
                'v'.$api['version'],
                $api['module'],
                $api['proc']
            ));

            $res = self::ResterApi($api_url,array('path'=>$path));
            if(is_array($res))
            {
                if($res['success'])
                {
                    self::$data['pages'] = $res['data'];
                }
                else
                {
                    throw new Exception("API 호출에 실패 : ".implode(' ',$res['msg']));
                }
            }
            else
            {
                throw new Exception("API 호출에 실패 하였습니다. ");
            }
        }

        // parsing skin data
    }

    /**
     * @param string|array $data
     * @param string       $section
     * @param string       $key
     *
     * @throws Exception
     */
    public static function Set($data, $section, $key='')
    {
        if(!isset(self::$data)) self::init();
        if($key)
        {
            if(!isset(self::$data[$section])) self::$data[$section] = [];
            self::$data[$section][$key] = $data;
        }
        else
        {
            self::$data[$section] = $data;
        }
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return array
     * @throws Exception
     */
    public static function Get($section='', $key='')
    {
        if(!isset(self::$data)) self::init();
        if($section==='') return self::$data;
        if($section && $key) return self::$data[$section][$key];
        return self::$data[$section];
    }
}

/**
 * @param string $section
 * @param string $key
 *
 * @return array
 * @throws Exception
 */
function cfg($section='', $key='')
{
    return data::Get($section,$key);
}

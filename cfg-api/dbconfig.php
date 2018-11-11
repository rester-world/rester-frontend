<?php if(!defined('__RESTER__')) exit;
$db = array();

// 기본 데이터베이스 서버
$db['default'] = array(
    'type' => 'mysql',
    'host'=>'score-zone.czoa8ecmg9dk.ap-northeast-2.rds.amazonaws.com',
    'user'=>'scorezone',
    'password'=>'scorezone1128',
    'database' =>'scorezone',
);

$db['reader'] = array(
    'type' => 'mysql',
    'host'=>'score-zone-ap-northeast-2c.czoa8ecmg9dk.ap-northeast-2.rds.amazonaws.com',
    'user'=>'scorezone',
    'password'=>'scorezone1128',
    'database' =>'scorezone',
);

return $db;

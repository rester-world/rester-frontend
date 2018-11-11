<?php if (!defined('__RESTER__')) exit;

$body = cfg::Get('response_body_skel');
$body['success'] = true;

$path = rester::param('path');

// 페이지 내용 불러오기

$body['data'] = array(
    'welcome'=>'Welcome to the RESTer world!',
    'current'=>$path
);

// 게시판 등 기능이 들어가는 페이지가 있을경우 다른 프로시저 호출 기능을 넣는다.

echo json_encode($body);

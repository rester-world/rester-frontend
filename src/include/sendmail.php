<?php

$id = 'help@posicreactive.com';
$pw = 'helpBe@smart09';

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

//Create a new PHPMailer instance
$mail = new PHPMailer;

//Tell PHPMailer to use SMTP
$mail->isSMTP();
$mail->CharSet = 'UTF-8';

//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug = 0;

//Ask for HTML-friendly debug output
$mail->Debugoutput = 'html';

//Set the hostname of the mail server
$mail->Host = 'smtp.gmail.com';

//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = 587;

//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'tls';

//Whether to use SMTP authentication
$mail->SMTPAuth = true;

//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = $id;

//Password to use for SMTP authentication
$mail->Password = $pw;

//Set who the message is to be sent from
$mail->setFrom($this->from, $this->from_name);

//Set an alternative reply-to address
$mail->addReplyTo($this->from, $this->from_name);

//Set who the message is to be sent to
$mail->addAddress($this->to, $this->to_name);

//Set the subject line
$mail->Subject = $this->subject;

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
$mail->msgHTML($contents);

//Replace the plain text body with one created manually
//$mail->AltBody = 'This is a plain-text message body';

//Attach an image file
//$mail->addAttachment('images/phpmailer_mini.png');
//$mail->AddAttachment('files/invoice-user-1234.pdf', 'invoice.pdf'); // attach files/invoice-user-1234.pdf, and rename it to invoice.pdf

//send the message, check for errors
if (!$mail->send()) {
    return "Mailer Error: " . $mail->ErrorInfo;
}

return true;

$body = cfg::Get('response_body_skel');
$body['success'] = false;

$email = rester::param('email');

try
{
    $key = VerifyEmail::Add($email);

    // 이메일 인증
    $mailer = new Mailer();
    $mailer->subject("스코어존 회원가입 인증키");
    $mailer->from("help@posicreactive.com","스코어존");
    $mailer->to($email,'스코어존회원');
    //$mailer->to('kevinpark@webace.co.kr',$nick);

    $logo = dirname(__FILE__).'/img/logo.png';

    $contents = '<div style="width: 500px; margin: 30px auto; border: 1px solid #eee; border-radius: 5px; padding: 20px; text-align: center; ">
        <div style="border-bottom : 1px solid #eee; padding-bottom : 20px;">
            <img src="'.$logo.'" />
        </div>
        <p style="margin: 20px 0px; ">
            스코어존 회원가입 인증키 <br/><br/>
            아래 6자리 숫자를 입력하세요.
        </p>
        <b>'.$key.'</b>

        <p style="margin-top : 100px;  text-align: left; color: #999; font-size: 11px; line-height: 1.5;">
            ※ 본 메일은 스코어존 회원님께 보내는 공지성 메일입니다.<br/>
            ※ 발신전용으로 회신되지 않으니 궁금하신 점은 <a href="http://score.zone/cs/inquiry">고객센터</a>를 통해 문의하여 주시기 바랍니다.
        </p>
    </div>';

    $mailer->send($contents);

    $body['success'] = true;
    $body['msg'] = "인증메일 발송성공 ({$email})";
    $body['data'] = array();
}
catch (Exception $e)
{
    $body['msg'] = $e->getMessage();
}

echo json_encode($body);

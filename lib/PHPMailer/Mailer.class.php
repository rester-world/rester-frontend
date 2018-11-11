<?php
require dirname(__FILE__).'/SMTP.class.php';
require dirname(__FILE__).'/PHPMailer.class.php';

/**
 * Class Mailer
 */
class Mailer
{
    protected $from;
    protected $from_name;
    protected $to;
    protected $to_name;
    protected $subject;
    protected $contents;
    protected $attachment;

    /**
     * Mailer constructor.
     */
    public function __construct()
    {
        $this->attachment = array();
    }

    /**
     * @param string $email
     * @param string $name
     */
    public function from($email,$name='')
    {
        $this->from = $email;
        $this->from_name = $name;
    }

    /**
     * @param string $email
     * @param string $name
     */
    public function to($email,$name='')
    {
        $this->to = $email;
        $this->to_name = $name;
    }

    /**
     * @param string $data
     */
    public function subject($data)
    {
        $this->subject = $data;
    }

    /**
     * @param string $data
     */
    public function contents($data)
    {
        $this->contents = $data;
    }

    /**
     * @param string $path
     * @param string $alias
     */
    public function addAttachment($path, $alias)
    {
        $this->attachment[] = array(
            'path'=>$path,
            'alias'=>$alias
        );
    }

    /**
     * @param $contents
     *
     * @return bool|string
     * @throws phpmailerException
     * @throws Exception
     */
    public function send($contents)
    {
        $cfg = cfg('sendmail');

        //SMTP needs accurate times, and the PHP time zone MUST be set
        //This should be done in your php.ini, but this is how to do it if you don't have access to that
        date_default_timezone_set($cfg['timezone']);

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
        $mail->Host = $cfg['host'];

        //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $mail->Port = $cfg['port'];

        //Set the encryption system to use - ssl (deprecated) or tls
        $mail->SMTPSecure = $cfg['smtp_secure'];

        //Whether to use SMTP authentication
        $mail->SMTPAuth = true;

        //Username to use for SMTP authentication - use full email address for gmail
        $mail->Username = $cfg['username'];

        //Password to use for SMTP authentication
        $mail->Password = $cfg['password'];

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

        // Attach an image file
        foreach ($this->attachment as $file)
        {
            $mail->AddAttachment($file['path'], $file['alias']);
        }
        //$mail->addAttachment('images/phpmailer_mini.png');
        //$mail->AddAttachment('files/invoice-user-1234.pdf', 'invoice.pdf'); // attach files/invoice-user-1234.pdf, and rename it to invoice.pdf

        //send the message, check for errors
        if (!$mail->send()) {
            return "Mailer Error: " . $mail->ErrorInfo;
        }
        return true;
    }
}

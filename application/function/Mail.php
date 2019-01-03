<?php
require_once ROOT_FW_PATH . 'application/function/includes/PHPMailer/Exception.php';
require_once ROOT_FW_PATH . 'application/function/includes/PHPMailer/OAuth.php';
require_once ROOT_FW_PATH . 'application/function/includes/PHPMailer/PHPMailer.php';
require_once ROOT_FW_PATH . 'application/function/includes/PHPMailer/POP3.php';
require_once ROOT_FW_PATH . 'application/function/includes/PHPMailer/SMTP.php';
require_once ROOT_FW_PATH . 'config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class Mail
{
    protected $mail;
    protected static $instance;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        $this->init();
    }

    public function init()
    {
        //关闭调试模式
        $this->mail->SMTPDebug = 0;
        //使用SMTP协议
        $this->mail->isSMTP();
        //允许SMTP认证
        $this->mail->SMTPAuth = true;
        //启用TLS加密
        $this->mail->SMTPSecure = 'tls';
        //邮件服务器地址
        $this->mail->Host = MAIL_HOST;
        //用户名
        $this->mail->Username = MAIL_USERNAME;
        //密码
        $this->mail->Password = MAIL_PASSWORD;
        //端口
        $this->mail->Port = MAIL_PORT;
    }

    public static function instance()
    {
        if(! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 发送邮件
     * @param string $toEmail 接收人email
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param string $senderName 发件人名称
     * @param string $charset   字符集
     * @return bool
     */
    public static function send($toEmail, $title, $content, $senderName='', $charset='UTF-8')
    {
        $instance = self::instance();
        try {
            $instance->mail->setFrom(MAIL_USERNAME, $senderName);
            $instance->mail->addBCC($toEmail);

            $instance->mail->Subject = $title;
            $instance->mail->Body    = $content;
            $instance->mail->AltBody = strip_tags($content);
            $instance->mail->CharSet = $charset;

            return !! $instance->mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

}
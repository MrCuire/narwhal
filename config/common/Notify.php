<?php

trait Notify
{


    /**
     * 成功返回信息
     * @param string $data
     * @param string $msg
     * @return null
     */
    protected function succ($data='', $msg = '操作成功')
    {
        if(defined('HAS_BEGIN_TRANSACTION') && HAS_BEGIN_TRANSACTION === true) {
            /** @var cls_mysql $db */
            $db = $GLOBALS['db'];
            @$db->pdo->commit();
        }
         return cls_output::out("E000000", $msg, $data);
    }

    /**
     * 失败返回信息
     * @param $code
     * @param $msg
     * @param array $data
     * @return null
     */
    protected function err($code, $msg, $data = array())
    {
        if(defined('HAS_BEGIN_TRANSACTION') && HAS_BEGIN_TRANSACTION === true) {
            /** @var cls_mysql $db */
            $db = $GLOBALS['db'];
            @$db->pdo->rollBack();
        }
         return cls_output::out($code, $msg, $data);
    }

    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param string $charset   字符集
     * @return bool             是否发送成功
     */
    protected function sendMail($to, $title, $content, $charset='UTF-8')
    {
        require_once ROOT_FW_PATH . '/application/function/Mail.php';
        $senderName = '时间小柜';

        return Mail::send($to, $title, $content, $senderName, $charset);
    }

    /**
     * 发送短信验证码
     * @param int $phone    手机号码
     * @param int $code     短信验证码
     * @return bool
     */
    protected function sendSMS($phone, $code)
    {
        return sendSMSALI($phone, compact(['code']));
    }




}
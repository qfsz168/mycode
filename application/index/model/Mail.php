<?php
namespace app\api\model;

class Mail
{
	/**
	 * 发送邮件
	 * @author 王崇全
	 * @param string $toMail
	 * @param string $subject
	 * @param string $body
	 * @param string $contentType
	 * @param bool   $isEncrypt
	 * @return void
	 */
	public static function EmailSend($toMail, $subject, $body, $contentType = 'text/html', $isEncrypt = false)
	{
		$smtp = Config::GetSMTP();
		if (is_null($smtp))
		{
			E(\EC::GET_CONFIG_ERROR, "SMTP配置获取失败");
		}

		if ($isEncrypt)
		{
			$smtp['pwd'] = \PhpCrypt::PHP_Encrypt($smtp['pwd']);
		}

		$transport = \Swift_SmtpTransport::newInstance($smtp['host'], $smtp['port'], $smtp['security']);
		$transport->setUsername($smtp['username']);
		$transport->setPassword($smtp['pwd']);

		try
		{
			$mailer = \Swift_Mailer::newInstance($transport);

			$message = \Swift_Message::newInstance();
			$message->setFrom(array($smtp['frommail'] => $smtp['fromuser']));
			$message->setTo(array($toMail));

			$message->setSubject($subject);
			$message->setBody($body, $contentType, $smtp['charset']);

			$mailer->send($message);
		}
		catch (\Swift_SwiftException $e)
		{
			E(\EC::MAIL_ERROR, '邮件发送失败: '.$e->getMessage());
		}
	}
}
<?php

/**
 * Created by PhpStorm.
 * User: hacfin
 * Date: 2017/6/1
 * Time: 11:12
 */
class Email
{
	protected $_from = null;

	/**
	 * Email constructor.
	 * @param string $host     smtp主机
	 * @param int    $port     smtp端口
	 * @param string $passWord 发件邮箱密码
	 * @param string $from     发件人邮箱地址
	 * @param string $fromName 发件人别称
	 * @param bool   $ssl      是否ssl传输
	 * @throws Exception
	 */
	function __construct($host, $port, $passWord, $from, $fromName = null, $ssl = true)
	{
		//		$from = 'wangchongquan@hacfin.com';
		//		$host = 'smtp.exmail.qq.com';
		//		$port = 465;

		if (!isset($host) || !isset($port) || !isset($from) || !isset($passWord))
		{
			throw new Exception('smtp主机和端口、发件邮箱、邮箱密码 均不能为空');
		}

		if (is_null($fromName))
		{
			$fromName = $from;
		}

		$this->_from      = [$from => $fromName];
		$this->_transport = Swift_SmtpTransport::newInstance($host, $port, $ssl ? "ssl" : null)
			->setUsername($from)
			->setPassword($passWord)
			->setTimeout(5);
	}

	/**
	 * sendMail
	 * @author 王崇全
	 * @date
	 * @param string $subject    邮件主题
	 * @param string $body       邮件内容
	 * @param array $to         收件人邮箱列表 [收件人邮箱1,收件人邮箱2,...] 或 [收件人邮箱1=>收件人名称1,收件人邮箱2=>收件人名称2,...]
	 * @param null   $attachFile 附件文件路径（本地或URL）
	 * @param null   $attachName 附件名称
	 * @return void
	 * @throws Exception
	 */
	public function sendMail($subject, $body, $to, $attachFile = null, $attachName = null)
	{
		//		$attachFile = '/mnt/volume1/files/2017/6/1/11/30e7b93b-a444-8a78-518d-af9bd71c37d2_thumb/200';
		//		$to         = ['qfsz168@163.com'=>"王崇全",'591572471@qq.com'];
		//		$subject    = "测试邮件";
		//		$body       = '这是一封测试邮件';

		$message = Swift_Message::newInstance()
			->setFrom($this->_from)
			->setTo($to)
			->setSubject($subject)
			->setBody($body, 'text/html', 'utf-8');

		if (isset($attachFile))
		{
			$attach = Swift_Attachment::fromPath($attachFile);
			if (isset($attachName))
			{
				$attach->setFilename($attachName);
			}

			$message->attach($attach);
		}

		try
		{
			$mailer = Swift_Mailer::newInstance($this->_transport);
			$mailer->send($message);
		}
		catch (\Swift_SwiftException $e)
		{
			throw new Exception('发送失败（'.$e->getMessage().'）');
		}
	}
}


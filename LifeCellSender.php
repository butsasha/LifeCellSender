<?php

/**
 * LifeCell SMS sender
 * Author: But Oleksandr
 */
class LifeCellSender implements ISender
{
	/**
	 * USAGE:
	 *
	 * Send single:
	 * $msg = (new LifeCellSender('login','password','alfaName'))->sendSingle('My message test','mobileNumber');
	 * var_dump($msg->sendMessages());
	 *
	 * Send multiple
	 * $msg = (new LifeCellSender('login','password','alfaName'))->sendSingle('My message test','mobileNumber');
	 * $msg->sendSingle('My message test','0973105044'); //SecondMessage
	 * var_dump($msg->sendMessages());
	 *
	 * Output will be XML doc for single receiver:
	 * <?xml version="1.0" encoding="UTF-8"?>
	 * <root>
	 * <message>
	 * <service id="bulk" source="alfaname"/>
	 * <to>mobileNumber</to>
	 * <body content-type="text/plain">My message test</body>
	 * </message>
	 * </root>
	 *
	 * Output will be XML doc for multiple receivers:
	 * <?xml version="1.0" encoding="UTF-8"?>
	 * <root>
	 * <message>
	 * <service id="bulk" source="alfaname"/>
	 * <to>mobileNumber</to>
	 * <body content-type="text/plain">My message test</body>
	 * </message>
	 * <message>
	 * <service id="bulk" source="alfaname"/>
	 * <to>mobileNumber</to>
	 * <body content-type="text/plain">My message test</body>
	 * </message>
	 * </root>
	 */

	/**
	 * Url of LifeCell API
	 * @var string
	 */
	private $url = 'https://api.life.com.ua/ip2sms/';
	/**
	 * Login of LifeCell
	 * @var string
	 */
	protected $login;
	/**
	 * Password of LifeCell
	 * @var string
	 */
	protected $password;
	/**
	 * Alfaname of SMS API senders
	 * @var string
	 */
	protected $sender;
	/**
	 * Array of messages
	 * @var array
	 */
	private $messages = [];
	/**
	 * cURL
	 * @var resource
	 */
	protected $curl = null;

	/**
	 * LifeCellSender constructor.
	 * @param $login of LifeCell panel
	 * @param $password of LifeCell panel
	 * @param string $sender Sender alfaname like NashNet
	 */
	public function __construct($login, $password, $sender = 'NashNet')
	{
		$this->login = $login;
		$this->password = $password;
		$this->sender = $sender;
		$this->initCURL();
	}

	/**
	 * Initialize cURL
	 *
	 * @return void
	 */
	protected function initCURL()
	{
		$this->curl = curl_init($this->url);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($this->curl, CURLOPT_CAINFO, '/etc/nginx/ssl/dhparam.pem'); // FIXME: SET SSL CERT!
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // TODO: Lifecell required want SLL cert for using his API
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_POST, true);
	}

	/**
	 * Send for single receiver
	 * @param $text string
	 * @param $to string mobile phone number
	 *
	 * @return object LifeCellSender
	 */
	public function sendSingle($text, $to)
	{
		$this->messages[] = [
			'text' => $text,
			'to' => $to
		];
		return $this;
	}

	/**
	 * Send messages
	 *
	 * @return bool true if each sms is sent successfully otherwise false
	 */
	public function sendMessages()
	{
		$data = $this->processMessages();
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
				'Authorization: Basic ' . base64_encode($this->login . ":" . $this->password), //TODO: Testing with headers instead CURLOPT_USERPASSWD
				'Content-Type: text/xml'
			]
		);
		curl_setopt($this->curl, CURLOPT_USERPWD, $this->login . ":" . $this->password);
		$result = curl_exec($this->curl);

		if (false === $result) {
			// FIXME: Handle curl errors
			// throw new SMSNetworkException(curl_errno($this->curl)curl_error($this->curl)));
			return false;
		}

		return $this->processResult($result);
	}


	/**
	 * Created new one XML object
	 *
	 * @return \SimpleXMLElement
	 */
	protected function createXML()
	{
		$xmlObj = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
		return $xmlObj;
	}

	/**
	 * @return string XML after converting from object
	 */
	protected function processMessages()
	{
		$xmlObj = $this->createXML();
		// Add messages
		foreach ($this->messages as $msg) {
			$message = $xmlObj->addChild('message');
			$service = $message->addChild('service');
			$service->addAttribute('id', (count($this->messages) > 1 ? 'bulk' : 'single'));
			// $service->addAttribute('validity', '+2 hour');
			$service->addAttribute('source', $this->sender);

			// We can support time of delayed send as integer Unixtime
			// $time = isset($msg['time']) ? date('D, d M Y H:i:s e', $msg['time']) : '';
			// $service->addAttribute('start', $time);
			if (!is_array($msg['to'])) {
				$msg['to'] = [$msg['to']];
			}

			// Add recipients
			foreach ($msg['to'] as $phone) {
				$phone = preg_replace('/\D/', '', $phone);
				switch (mb_strlen($phone)) {
					case 7:
						$phone = '+38044' . $phone;
						break;
					case 9:
						$phone = '+380' . $phone;
						break;
					case 10:
						$phone = '+38' . $phone;
						break;
					case 11:
						$phone = '+3' . $phone;
						break;
					case 12:
						$phone = '+' . $phone;
						break;
				}
				$message->addChild('to', $phone);
			}
			$message->addChild('body', $msg['text'])->addAttribute('content-type', 'text/plain');
		}
		return $xmlObj->asXML();
	}

	/**
	 * @param string $result
	 *
	 * @return boolean
	 */
	protected function processResult($result)
	{
		$respXml = new \SimpleXMLIterator($result);
		if ($respXml->error) {
			// FIXME: Handle errors
			// throw new SMSFatalException($respXml->error);
			echo (string)$respXml->error . "\n";

			return false;
		}
		$errors = [];
		for ($respXml->rewind(); $respXml->valid(); $respXml->next()) {
			$res = (string)$respXml->current();
			if ('send' !== $res) {
				$errors[(int)$respXml->current()->attributes()->number_sms] = $res;
				continue;
			}
			// TODO: Use list of sent SMSs?
		}
		if ($errors) {
			// FIXME: Handle errors
			// throw new SMSNotSentException($errors);
			print_r($errors);

			return false;
		}

		return true;
	}
}

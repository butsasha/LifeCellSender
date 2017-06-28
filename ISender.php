<?php

/**
 * SMS sender interface
 * Author: But Oleksandr
 */

namespace Library\SMSApi;

interface ISender
{
	/**
	 * Send for single receiver
	 * @param $text string
	 * @param $to string mobile phone number
	 * @return bool true if sms is sent successfully otherwise false
	 */
	function sendSingle($text, $to);

	/**
	 * Send for multiple receivers
	 * @return bool true if each sms is sent successfully otherwise false
	 */
	function sendMessages();
}

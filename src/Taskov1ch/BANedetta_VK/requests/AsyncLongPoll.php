<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use Taskov1ch\BANedetta_VK\VkPosts;

class AsyncLongPoll extends AsyncTask
{

	public function __construct(
		private string $key,
		private string $server,
		private int $ts
	) {
	}

	public function onRun(): void
	{
		$ch = curl_init("{$this->server}?act=a_check&key={$this->key}&ts={$this->ts}&wait=25&mode=2&version=3");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$this->setResult(curl_exec($ch));
	}

	public function onCompletion(): void
	{
		VkPosts::getInstance()->handleLongpollRespone(json_decode($this->getResult(), true));
	}
}

<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use Taskov1ch\BANedetta_VK\VkPosts;
use VK\Client\VKApiClient;

class AsyncGetLongpollServer extends AsyncTask
{

	public function __construct(
		private string $token,
		private int $groupId
	) {
	}

	public function onRun(): void
	{
		$client = new VKApiClient();
		$response = $client->groups()->getLongPollServer($this->token, [
			"group_id" => $this->groupId
		]);
		$this->setResult($response);
	}

	public function onCompletion(): void
	{
		$data = $this->getResult();
		VkPosts::getInstance()->updateLongpollData($data);
	}
}

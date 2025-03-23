<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use VK\Client\VKApiClient;

class AsyncSettingLongpoll extends AsyncTask
{

	public function __construct(
		private string $token,
		private int $groupId
	) {
	}

	public function onRun(): void
	{
		$client = new VKApiClient();
		$response = $client->groups()->setLongPollSettings($this->token, [
			"group_id" => $this->groupId,
			"wall_reply_new" => 1
		]);
		$this->setResult($response);
	}
}

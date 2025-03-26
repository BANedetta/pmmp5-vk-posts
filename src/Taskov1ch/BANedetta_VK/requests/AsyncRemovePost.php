<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use VK\Client\VKApiClient;

class AsyncRemovePost extends AsyncTask
{

	public function __construct(
		private string $token,
		private int $groupId,
		private int $postId
	) {
	}

	public function onRun(): void
	{
		$client = new VKApiClient();

		$response = $client->wall()->delete($this->token, [
			"owner_id" => $this->groupId,
			"post_id" => $this->postId
		]);

		$this->setResult($response);
	}
}

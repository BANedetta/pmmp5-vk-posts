<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use Taskov1ch\BANedetta_VK\VkPosts;
use VK\Client\VKApiClient;

class AsyncCreatePost extends AsyncTask
{

	public function __construct(
		private string $token,
		private int $groupId,
		private string $postContent,
		private string $attachments,
		private string $banned,
		private string $data
	) {
	}

	public function onRun(): void
	{
		$client = new VKApiClient();

		$response = $client->wall()->post($this->token, [
			"owner_id" => $this->groupId,
			"from_group" => 1,
			"message" => $this->postContent,
			"attachments" => json_decode($this->attachments)
		]);

		$this->setResult($response);
	}

	public function onCompletion(): void
	{
		$data = $this->getResult();
		VkPosts::getInstance()->getDatabase()->add($data["post_id"], $this->banned, $this->data);
	}
}

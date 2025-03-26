<?php

namespace Taskov1ch\BANedetta_VK\requests;

use pocketmine\scheduler\AsyncTask;
use VK\Client\VKApiClient;

class AsyncEditPost extends AsyncTask
{

	public function __construct(
		private string $token,
		private int $groupId,
		private int $postId,
		private string $postContent,
		private string $attachments
	) {
	}

	public function onRun(): void
	{
		$client = new VKApiClient();

		$response = $client->wall()->edit($this->token, [
			"owner_id" => $this->groupId,
			"post_id" => $this->postId,
			"message" => $this->postContent,
			"attachments" => json_decode($this->attachments)
		]);
	}
}

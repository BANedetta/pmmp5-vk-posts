<?php

namespace Taskov1ch\BANedetta_VK\requests;

use Taskov1ch\BANedetta_VK\VkPosts;

class AsyncRequests
{

	public function __construct(
		private VkPosts $main,
		private string $token,
		private int $groupId
	) {
	}

	public function createPost(string $postContent, array $attachments, string $banned, array $data): void
	{
		$attachments = json_encode($attachments);
		$data = json_encode($data);

		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncCreatePost($this->token, -$this->groupId, $postContent, $attachments, $banned, $data)
		);
	}

	public function editPost(int $postId, string $postContent, array $attachments): void
	{
		$attachments = json_encode($attachments);

		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncEditPost(
				$this->token,
				-$this->groupId,
				$postId,
				$postContent,
				$attachments
			)
		);
	}

	public function removePost(int $postId): void
	{
		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncRemovePost(
				$this->token,
				-$this->groupId,
				$postId
			)
		);
	}

	public function settingLongpoll(): void
	{
		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncSettingLongpoll(
				$this->token,
				$this->groupId
			)
		);
	}

	public function getLongpollServer(): void
	{
		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncGetLongpollServer(
				$this->token,
				$this->groupId
			)
		);
	}

	public function longpoll(array $data): void
	{
		$this->main->getServer()->getAsyncPool()->submitTask(
			new AsyncLongPoll(...$data)
		);
	}
}

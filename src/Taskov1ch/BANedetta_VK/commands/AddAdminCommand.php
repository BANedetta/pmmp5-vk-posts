<?php

namespace Taskov1ch\BANedetta_VK\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use Taskov1ch\BANedetta_VK\VkPosts;

class AddAdminCommand extends Command implements PluginOwned
{

	public function __construct(
		private VkPosts $main,
		string $name, string $description, string $permission
	) {
		parent::__construct($name, $description);
		$this->setPermission($permission);
	}

	public function getOwningPlugin(): VkPosts
	{
		return $this->main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		$translator = $this->main->getTranslator();

		if (count($args) != 1 || !is_numeric($args[0])) {
			$messgae = $translator->translate($sender, "add_admin.usage");
			$sender->sendMessage($messgae);
			return;
		}

		$id = (int) $args[0];

		if ($this->main->isAdmin($id)) {
			$messgae = $translator->translate($sender, "add_admin.already_admin");
			$sender->sendMessage($messgae);
			return;
		}

		$this->main->addAdmin($id);

		$message = $translator->translate($sender, "add_admin.success");
		$sender->sendMessage($message);
	}
}
<?php

namespace Taskov1ch\BANedetta_VK\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use Taskov1ch\BANedetta_VK\VkPosts;

class AdminsListCommand extends Command implements PluginOwned
{

	public function __construct(
		private VkPosts $main,
		string $name,
		string $description,
		string $permission
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
		$admins = $this->main->getAdmins();

		$message = $translator->translate(null, "admins_list") . implode(", ", $admins);
		$sender->sendMessage($message);
	}
}

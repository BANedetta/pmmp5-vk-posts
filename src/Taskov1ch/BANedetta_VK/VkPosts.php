<?php

namespace Taskov1ch\BANedetta_VK;

use IvanCraft623\languages\Language;
use IvanCraft623\languages\Translator;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\posts\PostPlugin;
use Taskov1ch\BANedetta_VK\commands\AddAdminCommand;
use Taskov1ch\BANedetta_VK\commands\RemoveAdminCommand;
use Taskov1ch\BANedetta_VK\providers\libasynql;
use Taskov1ch\BANedetta_VK\requests\AsyncRequests;

class VkPosts extends PostPlugin
{
	use SingletonTrait;

	private Translator $translator;
	private libasynql $db;
	private AsyncRequests $requests;
	private array $posts;
	private array $admins;

	private array $longpollData = ["key" => "", "server" => "", "ts" => -1];

	public function onEnable(): void
	{
		self::setInstance($this);
		BANedetta::getInstance()->getPostsManager()->registerPostPlugin($this);
	}

	public function onDisable(): void
	{
		$admins = new Config($this->getDataFolder() . "admins.yml");
		$admins->setAll($this->admins);
		$admins->save();
	}

	public function onRegistered(): void
	{
		$this->saveResources();
		$this->loadTranslations();

		$this->db = new libasynql($this->getBansManager()->getDataBase());

		$config = $this->getConfig();
		$this->requests = new AsyncRequests(
			$this,
			$config->get("access_token"),
			$config->get("group_id")
		);

		foreach (["admins", "posts"] as $configName) {
			$this->saveResource("{$configName}.yml");
			$this->{$configName} = (new Config($this->getDataFolder() . "$configName.yml"))->getAll();
		}

		$this->requests->getLongpollServer();
	}

	private function saveResources(): void
	{
		$dirs = ["", "languages"];
		$resourceFolder = $this->getResourceFolder();

		foreach ($dirs as $dir) {
			$files = glob(Path::join($resourceFolder, $dir, "*.yml"));

			foreach ($files as $file) {
				$relativePath = str_replace($resourceFolder, "", $file);
				$this->saveResource($relativePath);
			}
		}
	}

	private function loadTranslations(): void
	{
		$defaultLang = $this->getConfig()->get("default_language");
		$files = glob(Path::join($this->getDataFolder(), "languages", "*.yml"));
		$this->translator = new Translator($this);

		foreach ($files as $file) {
			$langName = basename($file, ".yml");
			$lang = new Language(
				$langName,
				(new Config($file))->getAll()
			);

			$this->translator->registerLanguage($lang);

			if ($langName === $defaultLang) {
				$this->translator->setDefaultLanguage($lang);
			}
		}
	}

	private function registerCommands(): void
	{
		$map = $this->getServer()->getCommandMap();

		$map->registerAll("BANedetta_VK", [
			new AddAdminCommand($this, "vaa", "Add admin command", "banedetta.vk.add_admin"),
			new RemoveAdminCommand($this, "vaa", "Remove admin command", "banedetta.vk.remove_admin")
		]);
	}

	public function getDatabase(): libasynql
	{
		return $this->db;
	}

	public function getDatabaseQueriesMap(): array
	{
		return ["mysql" => "database/mysql.sql", "sqlite" => "database/sqlite.sql"];
	}

	public function getTranslator(): Translator
	{
		return $this->translator;
	}

	public function isAdmin(int $id): bool
	{
		return in_array($id, $this->admins);
	}

	public function addAdmin(int $id): void
	{
		if (!$this->isAdmin($id)) {
			$this->admins[] = $id;
		}
	}

	public function removeAdmin(int $id): void
	{
		if ($this->isAdmin($id)) {
			unset($this->admins[array_search($id, $this->admins)]);
		}
	}

	public function createPost(string $banned, string $by, string $reason, int $timeLimit): void
	{
		$postContent = str_replace(
			["{banned}", "{by}", "{reason}", "{time}"],
			[strtolower($banned), strtolower($by), $reason, date("H:i:s d:m:Y", time() + $timeLimit)],
			$this->posts["waiting"]["post"]
		);

		$this->requests->createPost($postContent, $this->posts["waiting"]["attachments"], $banned, compact("by", "reason"));
	}

	public function removePost(string $banned): void
	{
		$this->db->getDataByBanned($banned)->onCompletion(
			function (array $data) {
				if (!empty($data)) {
					$this->requests->removePost($data["post_id"]);
					$this->db->removePostByBanned($data["banned"]);
				}
			},
			fn () => null
		);
	}

	public function updateLongpollData(array $data): void
	{
		$this->longpollData = $data;
		$this->requests->longpoll($this->longpollData);
	}

	public function handleLongpollRespone(array $response): void
	{
		if (isset($response["failed"])) {
			$this->longpollData["ts"] = $response["failed"] === 1 ? (int) $response["ts"] : $this->requests->getLongpollServer();
			return;
		}

		if (isset($response["ts"])) {
			$this->longpollData["ts"] = (int) $response["ts"];

			array_map(
				fn ($update) => $this->db->getDataById($update["object"]["post_id"])->onCompletion(
					fn (array $data) => empty($data) ?: match ($update["object"]["text"]) {
						"+" => $this->getBansManager()->confirm($data["banned"]),
						"-" => $this->getBansManager()->notConfirm($data["banned"])
					},
					fn () => null
				),
				array_filter($response["updates"], fn ($update) => $update["type"] === "wall_reply_new" && in_array($update["object"]["from_id"], $this->admins) && in_array($update["object"]["text"], ["+", "-"]))
			);

			$this->requests->longpoll($this->longpollData);
		}
	}

	private function updatePost(string $banned, string $type): void
	{
		$this->db->getDataByBanned($banned)->onCompletion(
			function (array $data) use ($type) {
				if (!empty($data)) {
					$banData = json_decode($data["data"], true);

					$this->requests->editPost(
						$data["post_id"],
						str_replace(
							["{banned}", "{by}", "{reason}"],
							[$data["banned"], $banData["by"], $banData["reason"]],
							$this->posts[$type]["post"]
						),
						$this->posts[$type]["attachments"]
					);

					$this->db->removePostByBanned($data["banned"]);
				}
			},
			fn () => null
		);
	}

	public function confirmed(string $banned): void
	{
		$this->updatePost($banned, "confirmed");
	}

	public function notConfirmed(string $banned): void
	{
		$this->updatePost($banned, "not_confirmed");
	}
}

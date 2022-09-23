<?php

namespace Terrains\command;

use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use Terrains\Loader;
use Terrains\util\MessageReplacer;

class TerrainCommand extends Command
{

    public function __construct(
        private Loader $plugin
    )
    {
        parent::__construct("terrain", "Terrain commands", null, ["island", "is"]);
        $plugin->getServer()->getCommandMap()->register($this->getName(), $this);
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;

        $manager = $this->getPlugin()->getTerrainManager();

        if (!isset($args[0])) {
            if(empty($manager->getConfig($sender)->get('island'))) {
                $manager->create($sender);
            } else {
                $worldName = $manager->getConfig($sender)->get('island')['mapId'];

                if($sender->getWorld()->getFolderName() == $worldName) {
                    $sender->sendMessage(MessageReplacer::replace("island.its.already"));
                }
            }

            $manager->join($sender);
            return;
        }

        switch ($args[0]) {
            case "upgrade":
            case "improve":
                if(empty($manager->getConfig($sender)->getAll()['island'])) {
                    $sender->sendMessage(MessageReplacer::replace("island.no.have"));
                    return;
                }

                if(!isset($manager->localData[$sender->getName()])) {
                    $sender->sendMessage(MessageReplacer::replace("island.must.to.be"));
                    return;
                }

                $size = $manager->getIsland($sender)["size"];

                if($size != 250) {
                    $config = (int) Loader::getInstance()->getConfig()->get("prices")[$size + 50 . "x" . $size + 50];

                    if (EconomyAPI::getInstance()->myMoney($sender) < $config) {
                        $sender->sendMessage(MessageReplacer::replace("island.no_money_upgrade"));
                        return;
                    }

                    EconomyAPI::getInstance()->reduceMoney($sender, $config, true);
                    $sender->sendMessage(
                        MessageReplacer::replace(
                            "island.upgrade.success",
                            [$size + 50, $config])
                    );

                    $manager->upgrade($sender);
                } else {
                    $sender->sendMessage(MessageReplacer::replace("island.full.upgrade"));
                }
                break;
            case "help":
                $sender->sendMessage(MessageReplacer::replace("island.help.command"));
                break;
        }
    }
}
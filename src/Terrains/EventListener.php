<?php

namespace Terrains;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use Terrains\util\MessageReplacer;

class EventListener implements Listener
{
    public function __construct(
        private Loader $plugin
    )
    {
    }

    /**
     * @return Loader
     */
    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $manager = $this->getPlugin()->getTerrainManager();

        $manager->saveData($player);
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $manager = $this->getPlugin()->getTerrainManager();

        if(!$manager->isValidPosition($player, $block->getPosition())) {
            $player->sendMessage(MessageReplacer::replace("island.border_limit"));
            $event->cancel();
            return;
        }

        $event->uncancel();
    }
}
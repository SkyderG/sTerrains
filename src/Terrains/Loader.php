<?php

namespace Terrains;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use Terrains\command\TerrainCommand;
use Terrains\manager\TerrainManager;

class Loader extends PluginBase
{
    use SingletonTrait;

    private TerrainManager $terrainManager;

    protected function onEnable(): void
    {
        self::setInstance($this);

        $this->terrainManager = new TerrainManager($this);
        new TerrainCommand($this);
    }

    protected function onDisable(): void
    {
        $this->terrainManager->saveAllData();
    }

    /**
     * @return TerrainManager
     */
    public function getTerrainManager(): TerrainManager
    {
        return $this->terrainManager;
    }
}
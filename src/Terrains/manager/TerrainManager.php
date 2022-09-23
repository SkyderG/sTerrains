<?php

namespace Terrains\manager;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;
use Terrains\Loader;
use Terrains\util\MessageReplacer;
use Terrains\util\WorldUtilites;

class TerrainManager
{
    public array $localData = [];

    public function __construct(
        private Loader $plugin
    )
    {
        @mkdir($this->plugin->getDataFolder()."players/");
    }

    /**
     * @return Loader
     */
    private function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function getConfig(Player|string $player): Config
    {
        if($player instanceof Player) $player = $player->getName();

        $folder = $this->getPlugin()->getDataFolder();
        return new Config($folder . "players/" . $player . ".json", Config::JSON);
    }

    public function getIsland(Player|string $player): ?array
    {
        if($player instanceof Player) $player = $player->getName();

        return $this->localData[$player] ?? null;
    }

    public function upgrade(Player|string $player)
    {
        if(!$this->getIsland($player)) return;
        if($player instanceof Player) $player = $player->getName();

        $this->localData[$player]['size'] += 50;
    }

    public function create(Player $player): void
    {
        $server = $player->getServer();
        $worldManager = $server->getWorldManager();

        $data = $this->getConfig($player);
        $config = $data->getAll();

        $uuid = Uuid::getFactory()->uuid4();

        WorldUtilites::duplicateWorld($this->getPlugin()->getConfig()->get("map.to.clone"), $uuid);

        $world = $worldManager->getWorldByName($uuid);
        $spawn = $world->getSpawnLocation();

        $config['island'] = [
            "mapId" => $uuid,
            "size" => 100,
            "spawn" => [
                "x" => $spawn->getX(),
                "y" => $spawn->getY(),
                "z" => $spawn->getZ()
            ]
        ];

        $data->setAll($config);
        $data->save();

        $player->teleport($spawn);
        $player->sendMessage(MessageReplacer::replace("island.generated"));

        $this->sendData($player);
    }

    public function sendData(Player $player): void
    {
        $data = $this->getConfig($player);
        $config = $data->getAll();

        $this->localData[$player->getName()] = $config['island'];
    }

    public function saveData(Player $player)
    {
        if(!isset($this->localData[$player->getName()])) return;

        $config = $this->getConfig($player);
        $config->setAll(['island' => $this->localData[$player->getName()]]);
        $config->save();
    }

    public function saveAllData(): void
    {
        if(empty($this->localData)) return;

        foreach ($this->localData as $username => $data) {
            $config = $this->getConfig($username);
            $config->setAll(['island' => $data]);
            $config->save();
        }
    }

    public function join(Player $player): void
    {
        $server = $player->getServer();
        $worldManager = $server->getWorldManager();

        $this->sendData($player);
        $island = $this->getIsland($player);

        $world = $worldManager->getWorldByName($island['mapId']);

        if(!$world instanceof World) {
            $worldManager->loadWorld($island['mapId']);
            $world = $worldManager->getWorldByName($island['mapId']);
        }

        $spawn = $world->getSafeSpawn(new Vector3($island['spawn']['x'], $island['spawn']['y'], $island['spawn']['z']));

        $player->teleport($spawn);
    }

    public function isValidPosition(Player $player, Position $position): bool
    {
        if (!isset($this->localData[$player->getName()])) return false;
        $data = $this->localData[$player->getName()];

        $server = $player->getServer();
        $worldManager = $server->getWorldManager();

        $border = $this->getAxisAligned($player);

        if ($border->isVectorInside($position->asVector3()))
            return true;

        return false;
    }

    public function getAxisAligned(Player $player): ?AxisAlignedBB
    {
        if (!isset($this->localData[$player->getName()])) return null;
        $data = $this->localData[$player->getName()];
        $size = $data["size"];
        $spawn = $data["spawn"];

        $center = new Vector3($spawn["x"], $spawn["y"], $spawn["z"]);
        $xMin = $center->x - ($size / 2);
        $xMax = $center->x + ($size / 2);

        $yMin = ($center->y - ($size / 2)) - 5;
        $yMin = $yMin < 0 ? 0 : $yMin;
        $yMax = ($center->y + ($size / 2)) - 5;
        $yMax = $yMax > 256 ? 256 : $yMax;

        $zMin = $center->z - ($size / 2);
        $zMax = $center->z + ($size / 2);

        return new AxisAlignedBB($xMin, $yMin, $zMin, $xMax, $yMax, $zMax);
    }
}
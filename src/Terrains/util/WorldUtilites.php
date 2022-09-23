<?php

namespace Terrains\util;

use FilesystemIterator;
use pocketmine\Server;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Terrains\Loader;
use SplFileInfo;
use ZipArchive;

class WorldUtilites
{
    public static function getServer(): Server
    {
        return Server::getInstance();
    }

    public static function backupWorld(string $worldName)
    {
        $path = Loader::getInstance()->getDataFolder();

        $server = self::getServer();
        $serverPath = $server->getDataPath();

        @mkdir($path . "/backups");

        $zip = new ZipArchive();
        $zip->open($path . "/backups/" . $worldName . ".zip", ZipArchive::CREATE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($serverPath . "worlds/" . $worldName)
        );

        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, str_replace("\\", "/",
                    ltrim(
                        substr($file,
                            strlen($serverPath . "worlds/" . $worldName)
                        ))));
            }
        }

        $zip->close();

        //$server->getWorldManager()->loadWorld($worldName, true);
        $server->getLogger()->notice("§eWorld " . $worldName . " had its contents compressed.");
    }

    public static function renameWorld(string $oldName, string $newName)
    {
        $server = self::getServer();
        $worldManager = $server->getWorldManager();

        $worldManager->unloadWorld($worldManager->getWorldByName($oldName));

        $fromPath = $server->getDataPath() . "/worlds/" . $oldName;
        $toPath = $server->getDataPath() . "/worlds/" . $newName;

        @rename($fromPath, $toPath);

        $worldManager->loadWorld($newName);
        $newWorld = $worldManager->getWorldByName($newName);
        if (!$newWorld instanceof World)
            return;

        $worldData = $newWorld->getProvider()->getWorldData();
        if (!$worldData instanceof BaseNbtWorldData)
            return;

        $worldData->getCompoundTag()->setString("LevelName", $newName);

        $worldManager->unloadWorld($newWorld);
        $worldManager->loadWorld($newName);
    }

    public static function duplicateWorld(string $worldName, string $newName): void
    {
        $server = self::getServer();
        $worldManager = $server->getWorldManager();

        if ($worldManager->isWorldLoaded($worldName)) {
            $worldManager->getWorldByName($worldName)->save(false);
        }

        @mkdir($server->getDataPath() . "/worlds/" . $newName);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($server->getDataPath() . "/worlds/$worldName",
                FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST);

        /** @var SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if ($filePath = $fileInfo->getRealPath()) {
                if ($fileInfo->isFile())
                    copy($filePath, str_replace($worldName, $newName, $filePath));
                else
                    @mkdir(str_replace($worldName, $newName, $filePath));
            }
        }

        $worldManager->loadWorld($newName);
        $newWorld = $worldManager->getWorldByName($newName);

        if (!$newWorld instanceof World)
            return;

        $worldData = $newWorld->getProvider()->getWorldData();
        if (!$worldData instanceof BaseNbtWorldData)
            return;

        $worldData->getCompoundTag()->setString("LevelName", $newName);

        //$worldManager->unloadWorld($newWorld);
        $worldManager->loadWorld($newName);
        $newWorld->setAutoSave(true);
    }

    public static function unzipMapAndDuplicate(string $worldName, string $newName)
    {
        $zip = new ZipArchive();
        $src = Loader::getInstance()->getDataFolder() . "backups/";
        $dir = self::getServer()->getDataPath();

        if (!is_file($src . $worldName . ".zip"))
            return;

        $zip->open($src . $worldName . ".zip");
        $zip->extractTo($dir . "/worlds/");

        # not finished
        self::duplicateWorld($worldName, $newName);

        $zip->close();
    }
}
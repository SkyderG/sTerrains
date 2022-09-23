<?php

namespace Terrains\util;

use Terrains\Loader;

class MessageReplacer
{

    public static function replace(string $message, array $data = []): array|string
    {
        $message = Loader::getInstance()->getConfig()->getAll()["messages"][$message];
        $message = str_replace("{n}", PHP_EOL, $message);

        if (count($data) > 0) {
            for ($i = 0; $i < count($data); $i++) {
                $message = str_replace("{" . $i . "}", $data[$i], $message);
            }
        }
        return $message;
    }
}
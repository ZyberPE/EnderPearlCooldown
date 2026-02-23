<?php

namespace PearlCooldown;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;

class Main extends PluginBase implements Listener {

    private array $cooldowns = [];
    private int $cooldownTime;
    private string $cooldownMessage;

    public function onEnable(): void {
        $this->saveDefaultConfig();

        $this->cooldownTime = $this->getConfig()->get("cooldown-seconds", 15);
        $this->cooldownMessage = $this->getConfig()->get("cooldown-message");

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPearlUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if(!$item->equals(VanillaItems::ENDER_PEARL(), false, false)){
            return;
        }

        if($player->hasPermission("pearl.cooldown.bypass")){
            return;
        }

        $name = $player->getName();
        $currentTime = time();

        if(isset($this->cooldowns[$name])){
            $remaining = ($this->cooldowns[$name] + $this->cooldownTime) - $currentTime;

            if($remaining > 0){
                $event->cancel();

                $message = str_replace("{TIME}", (string)$remaining, $this->cooldownMessage);
                $player->sendMessage($message);
                return;
            }
        }

        $this->cooldowns[$name] = $currentTime;
    }
}

<?php

namespace PearlCooldown;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase implements Listener {

    private array $cooldowns = [];
    private array $tasks = [];

    private int $cooldownTime;
    private string $cooldownMessage;
    private string $messageType;

    public function onEnable(): void {
        $this->saveDefaultConfig();

        $this->cooldownTime = $this->getConfig()->get("cooldown-seconds", 15);
        $this->cooldownMessage = $this->getConfig()->get("cooldown-message");
        $this->messageType = strtolower($this->getConfig()->get("message-type", "chat"));

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
                $this->sendCooldownMessage($player, $remaining);
                return;
            }
        }

        $this->cooldowns[$name] = $currentTime;
        $this->startActionBarCountdown($player);
    }

    private function sendCooldownMessage(Player $player, int $time): void {
        $message = str_replace("{TIME}", (string)$time, $this->cooldownMessage);

        if($this->messageType === "actionbar"){
            $player->sendActionBarMessage($message);
        } else {
            $player->sendMessage($message);
        }
    }

    private function startActionBarCountdown(Player $player): void {
        if($this->messageType !== "actionbar") return;

        $name = $player->getName();

        if(isset($this->tasks[$name])){
            $this->tasks[$name]->cancel();
        }

        $this->tasks[$name] = $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function() use ($player, $name): void {

                if(!$player->isOnline()){
                    $this->tasks[$name]->cancel();
                    return;
                }

                $remaining = ($this->cooldowns[$name] + $this->cooldownTime) - time();

                if($remaining <= 0){
                    $player->sendActionBarMessage("§aEnder Pearl Ready!");
                    $this->tasks[$name]->cancel();
                    unset($this->tasks[$name]);
                    return;
                }

                $message = str_replace("{TIME}", (string)$remaining, $this->cooldownMessage);
                $player->sendActionBarMessage($message);

            }),
            20
        );
    }
}

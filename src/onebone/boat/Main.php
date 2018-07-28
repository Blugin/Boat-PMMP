<?php

namespace onebone\boat;

use onebone\boat\entity\Boat as BoatEntity;
use onebone\boat\item\Boat as BoatItem;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\{
	Item, ItemFactory
};
use pocketmine\network\mcpe\protocol\{
	InteractPacket, MovePlayerPacket, SetEntityLinkPacket
};
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{
	private $riding = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		ItemFactory::registerItem(new BoatItem(), true);
		Item::addCreativeItem(new BoatItem());
		$this->getServer()->getCraftingManager()->registerRecipe(
			new ShapelessRecipe(
				[
					Item::get(Item::WOODEN_PLANK, null, 5),
					Item::get(Item::WOODEN_SHOVEL, null, 1)
				],
				[Item::get(333, 0, 1)])
		);

		Entity::registerEntity(BoatEntity::class, true);
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		if(isset($this->riding[$event->getPlayer()->getName()])){
			unset($this->riding[$event->getPlayer()->getName()]);
		}
	}

	public function onPacketReceived(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet instanceof InteractPacket){
			$boat = $player->getLevel()->getEntity($packet->target);
			if($boat instanceof BoatEntity){
				if($packet->action === 1){
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = $player->getId();
					$pk->type = 2;

					$this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = 0;
					$pk->type = 2;
					$player->dataPacket($pk);

					$this->riding[$player->getName()] = $packet->target;
				}elseif($packet->action === 3){
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = $player->getId();
					$pk->type = 3;

					$this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = 0;
					$pk->type = 3;
					$player->dataPacket($pk);

					if(isset($this->riding[$event->getPlayer()->getName()])){
						unset($this->riding[$event->getPlayer()->getName()]);
					}
				}
			}
		}elseif($packet instanceof MovePlayerPacket){
			if(isset($this->riding[$player->getName()])){
				$boat = $player->getLevel()->getEntity($this->riding[$player->getName()]);
				if($boat instanceof BoatEntity){
					$boat->x = $packet->x;
					$boat->y = $packet->y;
					$boat->z = $packet->z;
				}
			}
		}
	}
}

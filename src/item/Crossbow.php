<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootCrossbowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Arrow as ArrowItem;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\CrossbowLoadingEndSound;
use pocketmine\world\sound\CrossbowLoadingStartSound;
use pocketmine\world\sound\CrossbowShootSound;
use function cos;
use function deg2rad;
use function sin;

final class Crossbow extends Tool implements Releasable{

	public function onClickAir(Player $player, Vector3 $directionVector) : ItemUseResult{
		$arrow = VanillaItems::ARROW();
		$quickCharge = $this->getEnchantmentLevel(VanillaEnchantments::QUICK_CHARGE());
		$multishot = $this->getEnchantmentLevel(VanillaEnchantments::MULTISHOT());
		$location = $player->getLocation();
		if(!$this->isCharged()){
			if($player->hasFiniteResources() and !($player->getInventory()->contains($arrow) or $player->getOffHandInventory()->contains($arrow))){
				return ItemUseResult::FAIL();
			}
			$player->getWorld()->addSound($location, new CrossbowLoadingStartSound($quickCharge > 0));
		}else{
			$item = Item::nbtDeserialize($this->getNamedTag()->getCompoundTag("chargedItem"));
			$this->setCharged(null);
			if($item instanceof ArrowItem){
				$entity = new ArrowEntity(Location::fromObject(
					$player->getEyePos(),
					$player->getWorld(),
					($location->yaw > 180 ? 360 : 0) - $location->yaw,
					-$location->pitch
				), $player, true);
				if($multishot > 0){
					$location = clone $location;
					$location->yaw -= 10;

					for($i = 0; $i < 3; $i++){
						$arrow = new ArrowEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), ($location->yaw > 180 ? 360 : 0) - $location->yaw, -$location->pitch), $player, true);

						if($player->isCreative(true) or $i !== 1){
							$arrow->setPickupMode(ArrowEntity::PICKUP_CREATIVE);
						}

						$y = -sin(deg2rad($location->pitch));
						$xz = cos(deg2rad($location->pitch));
						$x = -$xz * sin(deg2rad($location->yaw));
						$z = $xz * cos(deg2rad($location->yaw));

						$directionVector = (new Vector3($x, $y, $z))->normalize();

						$arrow->setMotion($directionVector->multiply(7));

						$arrow->spawnToAll();

						$location->yaw += 10;
					}
					if($player->hasFiniteResources()){
						$this->applyDamage($multishot ? 3 : 1);
					}

					$location->getWorld()->addSound($location, new CrossbowShootSound());

					return ItemUseResult::SUCCESS();
				}
				$entity->setMotion($directionVector);

				$ev = new EntityShootCrossbowEvent($player, $this, $entity, 7);
				$ev->call();

				$entity = $ev->getProjectile();

				if($ev->isCancelled()){
					$entity->flagForDespawn();
					return ItemUseResult::FAIL();
				}

				$entity->setMotion($entity->getMotion()->multiply($ev->getForce()));

				if($entity instanceof Projectile){
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					if($projectileEv->isCancelled()){
						$ev->getProjectile()->flagForDespawn();
						return ItemUseResult::FAIL();
					}

					$ev->getProjectile()->spawnToAll();
					$location->getWorld()->addSound($location, new CrossbowShootSound());
				}else{
					$entity->spawnToAll();
				}

				if($player->hasFiniteResources()){
					$this->applyDamage($multishot ? 3 : 1);
				}
			}else{
				return ItemUseResult::SUCCESS();
			}
		}
		return ItemUseResult::NONE();
	}

	public function onReleaseUsing(Player $player) : ItemUseResult{
		$arrow = VanillaItems::ARROW();
		$quickCharge = $this->getEnchantmentLevel(VanillaEnchantments::QUICK_CHARGE());
		$time = $player->getItemUseDuration();
		if($time >= 24 - $quickCharge * 5){
			if($player->hasFiniteResources()){
				if($player->getInventory()->contains($arrow)){
					$player->getInventory()->removeItem($arrow);
				}elseif($player->getOffHandInventory()->contains($arrow)){
					$player->getOffHandInventory()->removeItem($arrow);
				}
			}
			$this->setCharged($arrow);
			$player->getWorld()->addSound($player->getLocation(), new CrossbowLoadingEndSound($quickCharge > 0));
			return ItemUseResult::SUCCESS();
		}
		return ItemUseResult::FAIL();
	}

	public function getMaxDurability() : int{
		return 464;
	}

	public function isCharged() : bool{
		return $this->getNamedTag()->getCompoundTag("chargedItem") !== null;
	}

	public function setCharged(?Item $item) : void{
		if($item === null){
			$this->getNamedTag()->removeTag("chargedItem");
		}else{
			$this->getNamedTag()->setTag("chargedItem", $item->nbtSerialize(-1));
		}
	}
}
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

namespace pocketmine\world\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

final class CrossbowLoadingStartSound implements Sound{

	protected bool $quickCharge = false;

	public function __construct(bool $quickCharge = false){ $this->quickCharge = $quickCharge; }

	public function encode(?Vector3 $pos) : array{
		return [LevelSoundEventPacket::create($this->quickCharge ? LevelSoundEventPacket::SOUND_CROSSBOW_QUICK_CHARGE_START : LevelSoundEventPacket::SOUND_CROSSBOW_LOADING_START, $pos)];
	}
}
<?php
declare(strict_types=1);

namespace alvin0319\OnixMine;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use function array_rand;
use function array_values;
use function intval;
use function mt_rand;
use function shuffle;
use function time;

class Mine{
	/** @var Position */
	protected Position $pos;
	/** @var Block|null */
	protected ?Block $block = null;
	/** @var bool */
	protected bool $refilled = true;
	/** @var int */
	protected int $lastAccessTime = -1;
	/** @var int */
	protected int $refillTime = 0;

	public function __construct(Position $pos){
		$this->pos = $pos;
		$this->block = $pos->getWorld()->getBlock($pos->floor());
	}

	public function getPosition() : Position{
		return $this->pos;
	}

	/**
	 * @param Position $pos
	 *
	 * @deprecated
	 */
	public function setPosition(Position $pos) : void{
		$this->pos = $pos;
	}

	public function getBlock() : ?Block{
		return $this->block;
	}

	public function setBlock(?Block $block) : void{
		$this->block = $block;
	}

	public function getRandomBlock() : Block{
		$blockIds = [
			BlockLegacyIds::STONE,
			BlockLegacyIds::COAL_ORE,
			BlockLegacyIds::STONE,
			BlockLegacyIds::STONE,
			BlockLegacyIds::STONE
		];
		if(mt_rand(0, 100) >= 60){
			$blockIds[] = BlockLegacyIds::IRON_ORE;
		}
		if(mt_rand(0, 100) >= 70){
			$blockIds[] = BlockLegacyIds::GOLD_ORE;
		}
		if(mt_rand(0, 100) >= 85){
			$blockIds[] = BlockLegacyIds::DIAMOND_ORE;
			$blockIds[] = BlockLegacyIds::EMERALD_ORE;
		}
		shuffle($blockIds);
		$blockIds = array_values($blockIds);
		return BlockFactory::getInstance()->get(intval($blockIds[array_rand($blockIds)]), 0);
	}

	public function break(Player $player) : void{
		if(!$this->refilled)
			return;
		$this->setAccessTime(time());
		OnixMine::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function() : void{
			$this->pos->getWorld()->setBlock($this->pos, BlockFactory::getInstance()->get(BlockLegacyIds::BEDROCK, 0));
		}));
		$this->setRefilled(false);
	}

	public function setAccessTime(int $time) : void{
		$this->lastAccessTime = $time;
	}

	public function getLastAccessTime() : int{
		return $this->lastAccessTime;
	}

	public function refill() : void{
		$this->pos->getWorld()->setBlock($this->pos, $b = $this->getRandomBlock());
		$this->setBlock($b);
		$this->setRefilled(true);
	}

	public function isReFilled() : bool{
		return $this->refilled;
	}

	public function setRefilled(bool $refilled) : void{
		$this->refilled = $refilled;
	}

	public function check() : void{
		if($this->lastAccessTime !== -1 && !$this->refilled){
			if(time() - $this->lastAccessTime >= $this->getTime()){
				$this->refill();
			}
		}
	}

	public function getTime() : int{
		if($this->block === null){
			return 10;
		}
		switch($this->block->getId()){
			case BlockLegacyIds::STONE:
			case BlockLegacyIds::COAL_ORE:
				return 5;
			case BlockLegacyIds::IRON_ORE:
				return 10;
			case BlockLegacyIds::GOLD_ORE:
				return 15;
			case BlockLegacyIds::LAPIS_ORE:
			case BlockLegacyIds::REDSTONE_ORE:
				return 17;
			case BlockLegacyIds::DIAMOND_ORE:
				return 30;
			case BlockLegacyIds::EMERALD_ORE:
				return 60;
			default:
				return 0;
		}
	}
}

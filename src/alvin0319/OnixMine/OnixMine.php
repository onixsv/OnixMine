<?php
declare(strict_types=1);

namespace alvin0319\OnixMine;

use alvin0319\Jewelry\Jewelry;
use alvin0319\LevelAPI\LevelAPI;
use alvin0319\OnixMine\task\MineTask;
use OnixUtils\OnixUtils;
use pocketmine\block\BlockLegacyIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use function array_values;
use function in_array;
use function mt_rand;

class OnixMine extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var Mine[] */
	protected $mines = [];

	protected $protectMode = false;

	public function onLoad() : void{
		self::setInstance($this);
	}

	protected function onEnable() : void{
		$this->getScheduler()->scheduleRepeatingTask(new MineTask(), 20);

		$c = new PluginCommand("마인관리", $this, $this);
		$c->setPermission("onixmine.command");
		$c->setDescription("마인 월드의 리젠을 활성화/비활성화 할지 결정합니다.");
		$this->getServer()->getCommandMap()->register("mine", $c);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	protected function onDisable() : void{
		foreach($this->getMines() as $mine)
			$mine->refill();
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @handleCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($player->getWorld()->getFolderName() === "mine"){
			$ores = [
				BlockLegacyIds::STONE,
				BlockLegacyIds::COAL_ORE,
				BlockLegacyIds::IRON_ORE,
				BlockLegacyIds::GOLD_ORE,
				BlockLegacyIds::LAPIS_ORE,
				BlockLegacyIds::REDSTONE_ORE,
				BlockLegacyIds::LIT_REDSTONE_ORE,
				BlockLegacyIds::DIAMOND_ORE,
				BlockLegacyIds::EMERALD_ORE
			];
			if(!$this->protectMode){
				if(in_array($block->getId(), $ores)){
					if($event->isCancelled())
						$event->uncancel();
					if(!isset($this->mines[OnixUtils::posToStr($block->getPosition())])){
						$this->mines[OnixUtils::posToStr($block->getPosition())] = new Mine($block->getPosition());
					}
					$this->mines[OnixUtils::posToStr($block->getPosition())]->break($player);

					LevelAPI::getInstance()->addExp($player, mt_rand(1, 3));

					$player->getInventory()->addItem(...$event->getDrops());

					$event->setDrops([]);

					$rand = mt_rand(0, 20);
					if($rand > 4 && $rand < 7){
						Jewelry::getInstance()->addJewelry($player, $jewelry = Jewelry::getInstance()->getRandomJewelryNon(), mt_rand(1, 3));
						OnixUtils::message($player, "§d{$jewelry}§f 보석을 발견했습니다.");
					}
				}
			}
			$event->setXpDropAmount(0);
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 *
	 * @priority         HIGHEST
	 * @handleCancelled  false
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();

		if($entity instanceof Player){
			if($entity->getWorld()->getFolderName() === "mine"){
				if($event->getCause() === EntityDamageEvent::CAUSE_FALL && !$entity->isCreative()){
					$event->uncancel();
				}
			}
		}
	}

	/**
	 * @return Mine[]
	 */
	public function getMines() : array{
		return array_values($this->mines);
	}

	public function removeMine(Mine $mine){
		unset($this->mines[OnixUtils::posToStr($mine->getPosition())]);
		$this->getLogger()->debug("광산 {$mine->getPosition()} 제거");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$command->testPermission($sender))
			return false;
		$this->protectMode = !$this->protectMode;
		$sender->sendMessage("광산 보호 모드를 " . ($this->protectMode ? "비 보호" : "보호") . "모드로 전환했습니다.");
		return true;
	}
}

<?php
declare(strict_types=1);

namespace alvin0319\OnixMine\task;

use alvin0319\OnixMine\OnixMine;
use pocketmine\scheduler\Task;
use function time;

class MineTask extends Task{

	public function onRun() : void{
		foreach(OnixMine::getInstance()->getMines() as $mine){
			/* 광물이 리젠 가능한 상태인지 체크 */
			$mine->check();
			/* 사용되지 않은 광물 제거 */
			if($mine->getLastAccessTime() !== -1){
				if(time() - $mine->getLastAccessTime() >= 120 && $mine->isReFilled()){// 2분마다 안 쓰는 광물 제거
					OnixMine::getInstance()->removeMine($mine);
				}
			}
		}
	}
}
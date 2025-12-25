<?php

declare(strict_types=1);

namespace xeonch\ClaimAndProtect\manager;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use xeonch\ClaimAndProtect\Main;

class LandManager{

    private string $landDirectory;

    /** @var array<int, array> */
    private array $lands = [];

    public function __construct(){
        $this->landDirectory = Main::getInstance()->getDataFolder() . 'lands/';
        $this->loadAll();
    }

    private function loadAll(): void{
        if(!is_dir($this->landDirectory)){
            @mkdir($this->landDirectory, 0777, true);
        }

        foreach(glob($this->landDirectory . '*.json') as $file){
            $id = (int) basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);
            if(is_array($data)){
                $this->lands[$id] = $data;
            }
        }
    }

    public function saveLand(int $landId, array $landData): void{
        $this->lands[$landId] = $landData;

        file_put_contents(
            $this->landDirectory . $landId . '.json',
            json_encode($landData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    public function deleteLand(int $landId): void{
        unset($this->lands[$landId]);
        @unlink($this->landDirectory . $landId . '.json');
    }

    public function getLand(int $landId): ?array{
        return $this->lands[$landId] ?? null;
    }

    /** @return array<int, array> */
    public function getAllLands(): array{
        return $this->lands;
    }

    public function generateLandId(): int{
        if(count($this->lands) === 0){
            return 1;
        }

        return max(array_keys($this->lands)) + 1;
    }

    public function getLandCountByPlayer(string $playerName): int{
        $count = 0;
        foreach($this->lands as $land){
            if(($land['owner'] ?? '') === $playerName){
                ++$count;
            }
        }
        return $count;
    }

    /** @return array<int, array> */
    public function getLandsByOwner(string $owner): array{
        $result = [];
        foreach($this->lands as $id => $land){
            if(($land['owner'] ?? '') === $owner){
                $result[$id] = $land;
            }
        }
        return $result;
    }

    public function isInArea(Position $pos): bool{
        $x = $pos->getFloorX();
        $z = $pos->getFloorZ();
        $world = $pos->getWorld()->getFolderName();

        foreach($this->lands as $land){
            if($land['world'] !== $world){
                continue;
            }

            if($this->createBB($land)->isVectorInside(new Vector3($x, 64, $z))){
                return true;
            }
        }
        return false;
    }

    /** @return array<int, array> */
    public function getLandsIn(Position $pos): array{
        $result = [];
        $x = $pos->getFloorX();
        $z = $pos->getFloorZ();
        $world = $pos->getWorld()->getFolderName();

        foreach($this->lands as $id => $land){
            if($land['world'] !== $world){
                continue;
            }

            if($this->createBB($land)->isVectorInside(new Vector3($x, 64, $z))){
                $result[$id] = $land;
            }
        }
        return $result;
    }

    public function checkOverlap(
        int $x1,
        int $x2,
        int $z1,
        int $z2,
        World|string $world
    ): bool{
        $worldName = $world instanceof World ? $world->getFolderName() : $world;

        $bb = new AxisAlignedBB(
            min($x1, $x2), 0, min($z1, $z2),
            max($x1, $x2) + 1, World::Y_MAX, max($z1, $z2) + 1
        );

        foreach($this->lands as $land){
            if($land['world'] !== $worldName){
                continue;
            }

            if($bb->intersectsWith($this->createBB($land))){
                return true;
            }
        }
        return false;
    }

    public function teleportToLandCenter(Player $player, int $landId): void{
        $land = $this->getLand($landId);
        if($land === null){
            return;
        }

        $world = Main::getInstance()
            ->getServer()
            ->getWorldManager()
            ->getWorldByName($land['world']);

        if($world === null){
            return;
        }

        [$x1,, $z1] = array_map('intval', explode(',', $land['pos']['first']));
        [$x2,, $z2] = array_map('intval', explode(',', $land['pos']['second']));

        $x = ($x1 + $x2) / 2;
        $z = ($z1 + $z2) / 2;
        $y = $world->getHighestBlockAt((int)$x, (int)$z) + 1;

        $player->teleport(new Position(
            $x + 0.5,
            $y,
            $z + 0.5,
            $world
        ));
    }

    private function createBB(array $land): AxisAlignedBB{
		[$x1,, $z1] = array_map('intval', explode(',', $land['pos']['first']));
		[$x2,, $z2] = array_map('intval', explode(',', $land['pos']['second']));

		return new AxisAlignedBB(
			min($x1, $x2),
			0,
			min($z1, $z2),
			max($x1, $x2) + 1,
			World::Y_MAX,
			max($z1, $z2) + 1
		);
	}
}


<?php

declare(strict_types=1);

namespace xeonch\ClaimAndProtect\utils;

use pocketmine\world\Position;
use pocketmine\world\World;

class Math{

    /**
     * Calculate the area between two positions (XZ only)
     *
     * @param string $pos1 Format: "x,y,z"
     * @param string $pos2 Format: "x,y,z"
     */
    public static function calculateArea(string $pos1, string $pos2): int{
        [$x1,, $z1] = array_map('intval', explode(',', $pos1));
        [$x2,, $z2] = array_map('intval', explode(',', $pos2));

        $length = abs($x2 - $x1) + 1;
        $width  = abs($z2 - $z1) + 1;

        return $length * $width;
    }

    /**
     * Convert Position object to string (x,y,z,world)
     */
    public static function convertPosToString(Position $pos): string{
        return
            $pos->getFloorX() . ',' .
            $pos->getFloorY() . ',' .
            $pos->getFloorZ() . ',' .
            $pos->getWorld()->getFolderName();
    }

    /**
     * Convert string (x,y,z,world) to Position
     */
    public static function convertStringToPos(string $posString, World $world): ?Position{
        $parts = explode(',', $posString);
        if(count($parts) !== 4){
            return null;
        }

        [$x, $y, $z, $worldName] = $parts;

        if($world->getFolderName() !== $worldName){
            return null;
        }

        return new Position(
            (int) $x,
            (int) $y,
            (int) $z,
            $world
        );
    }

    /**
     * Apply discount percentage (1–100) and return final price
     *
     * Example:
     * price=100, discount=20 → result=80
     */
    public static function applyDiscountWithPercentage(
        int $price,
        int $discountPercentage
    ): int{
        if($discountPercentage < 1){
            $discountPercentage = 1;
        }

        if($discountPercentage > 100){
            $discountPercentage = 100;
        }

        $discount = (int) round($price * ($discountPercentage / 100));
        return $price - $discount;
    }
}

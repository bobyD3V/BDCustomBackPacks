<?php
declare(strict_types=1);

namespace BDCustomBackPacks\BobyDev;

use pocketmine\block\inventory\BlockInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\world\Position;

/**
 * Virtual 27/54-slot backpack container using Altay's native InventoryManager.
 * The plugin supplies a client-only chest block at getHolder() before opening the
 * native BlockInventory window, so no InvMenu dependency is required.
 */
final class BackpackVirtualInventory extends SimpleInventory implements BlockInventory{
    public function __construct(
        int $size,
        private Position $holder
    ){
        if($size !== 27 && $size !== 54){
            throw new \InvalidArgumentException("Backpack inventory size must be 27 or 54");
        }
        parent::__construct($size);
    }

    public function getHolder() : Position{
        return $this->holder;
    }
}

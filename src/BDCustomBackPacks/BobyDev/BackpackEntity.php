<?php
declare(strict_types=1);

namespace BDCustomBackPacks\BobyDev;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use function array_key_exists;

abstract class BackpackEntity extends Entity{
    public const TAG_VARIANT = 'BDCBPVariant';
    public const TAG_NAME = 'BDCBPName';
    public const TAG_ITEMS = 'BDCBPItems';

    protected SimpleInventory $inventory;
    protected string $variantItemId;
    protected string $displayName;

    abstract public static function getNetworkTypeId() : string;
    abstract public static function getVariantItemId() : string;

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.7, 0.7);
    }

    protected function getInitialGravity() : float{
        return 0.08;
    }

    protected function getInitialDragMultiplier() : float{
        return 0.02;
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        $this->variantItemId = static::getVariantItemId();
        $this->displayName = $nbt->getString(self::TAG_NAME, Main::displayNameForItem($this->variantItemId));
        $size = Main::sizeForItem($this->variantItemId);
        $this->inventory = new SimpleInventory($size);
        $items = $nbt->getListTag(self::TAG_ITEMS);
        if($items !== null){
            foreach($items as $tag){
                if(!$tag instanceof CompoundTag){
                    continue;
                }
                $slot = $tag->getByte('Slot', -1);
                if($slot < 0 || $slot >= $size){
                    continue;
                }
                try{
                    $item = Item::nbtDeserialize($tag);
                    if(!$item->isNull()){
                        $this->inventory->setItem($slot, $item);
                    }
                }catch(\Throwable){
                    // Ignore malformed individual stacks rather than losing the entire backpack.
                }
            }
        }
        $this->setNameTag($this->displayName);
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
    }

    public function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setString(self::TAG_VARIANT, $this->variantItemId);
        $nbt->setString(self::TAG_NAME, $this->displayName);
        $items = new ListTag([], 10);
        foreach($this->inventory->getContents() as $slot => $item){
            if($item->isNull()){
                continue;
            }
            $items->push($item->nbtSerialize($slot));
        }
        $nbt->setTag(self::TAG_ITEMS, $items);
        return $nbt;
    }

    public function getInventory() : SimpleInventory{
        return $this->inventory;
    }

    public function getVariantItemIdValue() : string{
        return $this->variantItemId;
    }

    public function getDisplayName() : string{
        return $this->displayName;
    }

    public function setDisplayName(string $name) : void{
        $this->displayName = $name;
        $this->setNameTag($name);
        $this->setNameTagVisible(false);
    }

    public function loadFromBackpackItem(Item $item) : void{
        $size = Main::sizeForItem($this->variantItemId);
        $this->inventory->clearAll();
        $tag = $item->getNamedTag();
        $items = $tag->getListTag(Main::TAG_ITEM_CONTENTS);
        if($items !== null){
            foreach($items as $stackTag){
                if(!$stackTag instanceof CompoundTag){
                    continue;
                }
                $slot = $stackTag->getByte('Slot', -1);
                if($slot < 0 || $slot >= $size){
                    continue;
                }
                try{
                    $stack = Item::nbtDeserialize($stackTag);
                    if(!$stack->isNull()){
                        $this->inventory->setItem($slot, $stack);
                    }
                }catch(\Throwable){
                }
            }
        }
        $customName = $item->getCustomName();
        if($customName !== ''){
            $this->setDisplayName($customName);
        }
    }
}

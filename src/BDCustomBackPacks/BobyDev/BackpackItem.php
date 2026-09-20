<?php
declare(strict_types=1);

namespace BDCustomBackPacks\BobyDev;

use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\VanillaArmorMaterials;

final class BackpackItem extends Armor implements ItemComponents{
    use ItemComponentsTrait;

    private const ICONS = [
        'Backpack' => 'backpack_icon',
        'Blue Backpack' => 'backpack_blue_icon',
        'Green Backpack' => 'backpack_green_icon',
        'Purple Backpack' => 'backpack_purple_icon',
        'Red Backpack' => 'backpack_red_icon',
        'Axolotl Backpack' => 'axolotl_backpack_icon',
        'Blue Axolotl Backpack' => 'axolotl_backpack_blue_icon',
        'Brown Axolotl Backpack' => 'axolotl_backpack_brown_icon',
        'Cyan Axolotl Backpack' => 'axolotl_backpack_cyan_icon',
        'Gold Axolotl Backpack' => 'axolotl_backpack_gold_icon',
        'Big Backpack' => 'big_backpack_icon',
        'Blue Big Backpack' => 'big_backpack_blue_icon',
        'Green Big Backpack' => 'big_backpack_green_icon',
        'Purple Big Backpack' => 'big_backpack_purple_icon',
        'Red Big Backpack' => 'big_backpack_red_icon',
    ];

    public function __construct(ItemIdentifier $identifier, string $name){
        parent::__construct(
            $identifier,
            $name,
            new ArmorTypeInfo(2, 10000, ArmorInventory::SLOT_CHEST, material: VanillaArmorMaterials::LEATHER())
        );
        $icon = self::ICONS[$name] ?? 'backpack_icon';
        // Customies 1.4.0 builds the required icon, armor wearable/protection,
        // durability and other default components inside initComponent().
        // Do not pass raw strings/arrays to addComponent(); it only accepts ItemComponent objects.
        $this->initComponent($icon);
    }
}

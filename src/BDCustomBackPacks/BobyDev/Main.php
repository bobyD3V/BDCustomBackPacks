<?php
declare(strict_types=1);

namespace BDCustomBackPacks\BobyDev;

// BackpackEntityType and its variant classes are intentionally grouped in one file.
// Load that file explicitly because the classloader cannot PSR-4 autoload multiple classes
// from a single BackpackEntities.php file by individual class name.
require_once __DIR__ . "/BackpackEntities.php";

use customiesdevs\customies\entity\CustomiesEntityFactory;
use customiesdevs\customies\item\CustomiesItemFactory;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use customiesdevs\customies\item\CreativeInventoryInfo;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\inventory\Inventory;
use pocketmine\block\VanillaBlocks;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use function copy;
use function file_exists;
use function is_dir;
use function mkdir;

final class Main extends PluginBase implements Listener{
    public const TAG_ITEM_CONTENTS = 'BDCBPItems';
    public const TAG_ITEM_ID = 'BDCBPVariant';
    /** @var array<string,array{entity:string,size:int,name:string,entityClass:class-string<BackpackEntity>}> */
    private static array $variants = [
        'bdcustombackpacks:backpack' => ['entity'=>'bdcustombackpacks:backpack_e','size'=>27,'name'=>'Backpack','entityClass'=>BackpackEntityType::class],
        'bdcustombackpacks:backpack_blue' => ['entity'=>'bdcustombackpacks:backpack_blue_e','size'=>27,'name'=>'Blue Backpack','entityClass'=>BackpackBlueEntityType::class],
        'bdcustombackpacks:backpack_green' => ['entity'=>'bdcustombackpacks:backpack_green_e','size'=>27,'name'=>'Green Backpack','entityClass'=>BackpackGreenEntityType::class],
        'bdcustombackpacks:backpack_purple' => ['entity'=>'bdcustombackpacks:backpack_purple_e','size'=>27,'name'=>'Purple Backpack','entityClass'=>BackpackPurpleEntityType::class],
        'bdcustombackpacks:backpack_red' => ['entity'=>'bdcustombackpacks:backpack_red_e','size'=>27,'name'=>'Red Backpack','entityClass'=>BackpackRedEntityType::class],
        'bdcustombackpacks:axolotl_backpack' => ['entity'=>'bdcustombackpacks:axolotl_backpack_e','size'=>27,'name'=>'Axolotl Backpack','entityClass'=>AxolotlBackpackEntityType::class],
        'bdcustombackpacks:axolotl_backpack_blue' => ['entity'=>'bdcustombackpacks:axolotl_backpack_blue_e','size'=>27,'name'=>'Blue Axolotl Backpack','entityClass'=>AxolotlBackpackBlueEntityType::class],
        'bdcustombackpacks:axolotl_backpack_brown' => ['entity'=>'bdcustombackpacks:axolotl_backpack_brown_e','size'=>27,'name'=>'Brown Axolotl Backpack','entityClass'=>AxolotlBackpackBrownEntityType::class],
        'bdcustombackpacks:axolotl_backpack_cyan' => ['entity'=>'bdcustombackpacks:axolotl_backpack_cyan_e','size'=>27,'name'=>'Cyan Axolotl Backpack','entityClass'=>AxolotlBackpackCyanEntityType::class],
        'bdcustombackpacks:axolotl_backpack_gold' => ['entity'=>'bdcustombackpacks:axolotl_backpack_gold_e','size'=>27,'name'=>'Gold Axolotl Backpack','entityClass'=>AxolotlBackpackGoldEntityType::class],
        'bdcustombackpacks:big_backpack' => ['entity'=>'bdcustombackpacks:big_backpack_e','size'=>54,'name'=>'Big Backpack','entityClass'=>BigBackpackEntityType::class],
        'bdcustombackpacks:big_backpack_blue' => ['entity'=>'bdcustombackpacks:big_backpack_blue_e','size'=>54,'name'=>'Blue Big Backpack','entityClass'=>BigBackpackBlueEntityType::class],
        'bdcustombackpacks:big_backpack_green' => ['entity'=>'bdcustombackpacks:big_backpack_green_e','size'=>54,'name'=>'Green Big Backpack','entityClass'=>BigBackpackGreenEntityType::class],
        'bdcustombackpacks:big_backpack_purple' => ['entity'=>'bdcustombackpacks:big_backpack_purple_e','size'=>54,'name'=>'Purple Big Backpack','entityClass'=>BigBackpackPurpleEntityType::class],
        'bdcustombackpacks:big_backpack_red' => ['entity'=>'bdcustombackpacks:big_backpack_red_e','size'=>54,'name'=>'Red Big Backpack','entityClass'=>BigBackpackRedEntityType::class],
    ];

    /** @var array<int,string> */
    private array $numericItemToVariant = [];

    /** @var array<int,bool> */
    private array $lastSneakingState = [];

    /** @var array<int,float> */
    private array $lastSneakPressAt = [];
    /**
     * @var array<int,array{playerId:int,type:string,entity:?BackpackEntity,item:?Item,variant:string,holder:Position}>
     */
    private array $openWindows = [];

    protected function onEnable() : void{
        $this->registerItems();
        $this->registerEntities();
        $this->registerResourcePack();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->startDoubleSneakWatcher();
        $this->getLogger()->info('BDCustomBackPacks 1.0.0 enabled. Author: BobyDev');
        $this->getLogger()->info('Verified by Altay 5.44.5 / Bedrock 1.26.50. Native virtual chest container; InvMenu removed; HUD override removed; double-sneak backpack shortcut enabled.');
    }

    private function registerItems() : void{
        $factory = CustomiesItemFactory::getInstance();
        foreach(self::$variants as $id => $data){

            // Customies 1.4.x expects a Closure returning the fully-constructed Item.
            // Allocate the runtime type ID through Altay's ItemTypeIds::newId().
            $factory->registerItem(
                static fn() => new BackpackItem(new ItemIdentifier(ItemTypeIds::newId()), $data['name']),
                $id,
                new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_EQUIPMENT, 'bdcustombackpacks')
            );
            $item = $factory->get($id);
            $this->numericItemToVariant[$item->getTypeId()] = $id;
        }
    }

    private function registerEntities() : void{
        $factory = CustomiesEntityFactory::getInstance();
        foreach(self::$variants as $data){
            $factory->registerEntity($data['entityClass'], $data['entity']);
        }
    }

    private function registerResourcePack() : void{
        $packPath = $this->getDataFolder().'BDCustomBackPacks_RP.mcpack';
        if(!is_dir($this->getDataFolder())){
            mkdir($this->getDataFolder(), 0777, true);
        }
        $embedded = __DIR__.'/../../../resources/BDCustomBackPacks_RP.mcpack';
        if(file_exists($embedded)){
            copy($embedded, $packPath);
            try{
                $manager = $this->getServer()->getResourcePackManager();
                $pack = new ZippedResourcePack($packPath);
                $stack = $manager->getResourceStack();
                $existing = $manager->getPackById($pack->getPackId());
                if($existing === null){
                    array_unshift($stack, $pack);
                    $manager->setResourceStack($stack);
                }
            }catch(\Throwable $e){
                $this->getLogger()->warning('Could not register embedded resource pack automatically: '.$e->getMessage());
            }
        }
    }

    public static function sizeForItem(string $id) : int{
        return self::$variants[$id]['size'] ?? 27;
    }

    public static function displayNameForItem(string $id) : string{
        return self::$variants[$id]['name'] ?? 'Backpack';
    }

    public static function entityForItem(string $id) : string{
        return self::$variants[$id]['entity'] ?? self::$variants['bdcustombackpacks:backpack']['entity'];
    }

    public static function variantExists(string $id) : bool{
        return isset(self::$variants[$id]);
    }

    public function getVariantForItem(Item $item) : ?string{
        $tag = $item->getNamedTag();
        $stored = $tag->getString(self::TAG_ITEM_ID, '');
        if($stored !== '' && self::variantExists($stored)){
            return $stored;
        }
        return $this->numericItemToVariant[$item->getTypeId()] ?? null;
    }

    public function makeItem(string $id) : Item{
        $item = CustomiesItemFactory::getInstance()->get($id, 1);
        $tag = $item->getNamedTag()->setString(self::TAG_ITEM_ID, $id);
        $item->setNamedTag($tag);
        $item->setCustomName(self::displayNameForItem($id));
        return $item;
    }

    public function storeEntityContents(Item $item, BackpackEntity $entity) : Item{
        $tag = $item->getNamedTag();
        $tag->setString(self::TAG_ITEM_ID, $entity->getVariantItemIdValue());
        $list = new ListTag([], 10);
        foreach($entity->getInventory()->getContents() as $slot => $stack){
            if(!$stack->isNull()){
                $list->push($stack->nbtSerialize($slot));
            }
        }
        $tag->setTag(self::TAG_ITEM_CONTENTS, $list);
        $item->setNamedTag($tag);
        return $item;
    }

    public function spawnFromItem(Player $player, Item $item, Vector3 $pos) : ?BackpackEntity{
        $id = $this->getVariantForItem($item);
        if($id === null || !self::variantExists($id)){
            return null;
        }
        $class = self::$variants[$id]['entityClass'];
        $nbt = CompoundTag::create()
            ->setString(BackpackEntity::TAG_VARIANT, $id)
            ->setString(BackpackEntity::TAG_NAME, $item->getCustomName() !== '' ? $item->getCustomName() : self::displayNameForItem($id));
        $loc = $player->getLocation();
        $entity = new $class(new \pocketmine\entity\Location($pos->x, $pos->y, $pos->z, $player->getWorld(), $loc->getYaw(), 0.0), $nbt);
        if(!$entity instanceof BackpackEntity){
            return null;
        }
        $entity->loadFromBackpackItem($item);
        $entity->spawnToAll();
        return $entity;
    }

    public function onEntityDamage(EntityDamageEvent $event) : void{
        if($event->getEntity() instanceof BackpackEntity){
            $event->cancel();
        }
    }

    public function onEntityInteract(PlayerEntityInteractEvent $event) : void{
        $entity = $event->getEntity();
        if(!$entity instanceof BackpackEntity){
            return;
        }
        $event->cancel();
        $player = $event->getPlayer();
        if($player->isSneaking()){
            $this->pickupEntity($player, $entity);
            return;
        }
        $this->openEntityMenu($player, $entity);
    }

    public function onBlockInteract(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }
        $item = $event->getItem();
        $id = $this->getVariantForItem($item);
        if($id === null){
            return;
        }
        $event->cancel();
        $block = $event->getBlock();
        $target = $block->getSide($event->getFace());
        $pos = $target->getPosition()->add(0.5, 0.05, 0.5);
        // Prevent placement inside a non-replaceable block.
        if(!$target->isTransparent()){
            $pos = $block->getPosition()->add(0.5, 1.05, 0.5);
        }
        $entity = $this->spawnFromItem($event->getPlayer(), $item, $pos);
        if($entity === null){
            return;
        }
        $item->pop();
        $event->getPlayer()->getInventory()->setItemInHand($item);
        $event->getPlayer()->sendTip('§aPlaced '.$entity->getDisplayName().'.');
    }

    private function pickupEntity(Player $player, BackpackEntity $entity) : void{
        $item = $this->makeItem($entity->getVariantItemIdValue());
        $item->setCustomName($entity->getDisplayName());
        $item = $this->storeEntityContents($item, $entity);
        $inv = $player->getInventory();
        if(!$inv->canAddItem($item)){
            $player->sendTip('§cYour inventory is full.');
            return;
        }
        $inv->addItem($item);
        $entity->flagForDespawn();
        $player->sendTip('§aPicked up '.$entity->getDisplayName().'.');
    }

    private function openEntityMenu(Player $player, BackpackEntity $entity) : void{
        // Only one player may edit a placed backpack at a time.
        foreach($this->openWindows as $context){
            if($context['type'] === 'entity' && $context['entity'] === $entity){
                $player->sendTip('§eThis backpack is already being used.');
                return;
            }
        }

        if($player->getCurrentWindow() !== null){
            $player->removeCurrentWindow();
        }

        $inventory = $this->createNativeBackpackInventory($player, $entity->getInventory()->getSize());
        $inventory->setContents($entity->getInventory()->getContents());
        $holder = $inventory->getHolder();

        $key = spl_object_id($inventory);
        $this->openWindows[$key] = [
            'playerId' => $player->getId(),
            'type' => 'entity',
            'entity' => $entity,
            'item' => null,
            'variant' => $entity->getVariantItemIdValue(),
            'holder' => $holder
        ];

        // Altay sends generic BlockInventory windows with a block position. The client may
        // reject the window if that position is air/another block. Spoof a chest only to this
        // player for the lifetime of the menu; the actual world block is never changed.
        $this->sendVirtualContainerBlock($player, $holder, true);

        if(!$player->setCurrentWindow($inventory)){
            $this->sendVirtualContainerBlock($player, $holder, false);
            unset($this->openWindows[$key]);
        }
    }

    public function onInventoryClose(InventoryCloseEvent $event) : void{
        $inventory = $event->getInventory();
        $key = spl_object_id($inventory);
        $context = $this->openWindows[$key] ?? null;

        if($context === null){
            return;
        }

        unset($this->openWindows[$key]);

        $player = $event->getPlayer();
        if($player->isConnected()){
            $this->sendVirtualContainerBlock($player, $context['holder'], false);
        }

        if($context['type'] === 'entity'){
            $entity = $context['entity'];
            if($entity instanceof BackpackEntity && !$entity->isFlaggedForDespawn() && !$entity->isClosed()){
                $entity->getInventory()->setContents($inventory->getContents());
            }
            return;
        }

        $item = $context['item'];
        if(!$item instanceof Item){
            return;
        }

        $this->writeInventoryToItem($item, $inventory);

        if($player->isConnected()){
            $current = $player->getArmorInventory()->getChestplate();
            if($this->getVariantForItem($current) === $context['variant']){
                $player->getArmorInventory()->setChestplate($item);
            }
        }
    }

    private function createNativeBackpackInventory(Player $player, int $size) : BackpackVirtualInventory{
        // Use a nearby virtual holder position. The world is not changed; a client-only chest
        // block is sent immediately before the native container-open packet.
        $base = $player->getPosition()->floor();
        $minY = $player->getWorld()->getMinY() + 1;
        $y = max($minY, $base->y - 8);
        $position = new Position($base->x, $y, $base->z, $player->getWorld());
        return new BackpackVirtualInventory($size, $position);
    }

    private function sendVirtualContainerBlock(Player $player, Position $position, bool $open) : void{
        if(!$player->isConnected()){
            return;
        }

        $block = $open ? VanillaBlocks::CHEST() : $position->getWorld()->getBlock($position);
        $runtimeId = TypeConverter::getInstance()
            ->getBlockTranslator()
            ->internalIdToNetworkId($block->getStateId());

        $player->getNetworkSession()->sendDataPacket(UpdateBlockPacket::create(
            BlockPosition::fromVector3($position),
            $runtimeId,
            UpdateBlockPacket::FLAG_NETWORK,
            UpdateBlockPacket::DATA_LAYER_NORMAL
        ));
    }

    /**
     * Opens the equipped backpack after two quick sneak-toggle presses.
     * This intentionally avoids touching the vanilla inventory HUD/screen so the
     * player's normal inventory remains completely unchanged.
     */
    private function startDoubleSneakWatcher() : void{
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
            $now = microtime(true);
            foreach($this->getServer()->getOnlinePlayers() as $player){
                $playerId = $player->getId();
                $sneaking = $player->isSneaking();
                $wasSneaking = $this->lastSneakingState[$playerId] ?? false;
                $this->lastSneakingState[$playerId] = $sneaking;

                // Only count the rising edge: false -> true. Holding sneak is not
                // repeatedly counted as additional presses.
                if(!$sneaking || $wasSneaking){
                    continue;
                }

                $previous = $this->lastSneakPressAt[$playerId] ?? 0.0;
                $this->lastSneakPressAt[$playerId] = $now;

                // Two sneak presses within 700 ms.
                if($previous <= 0.0 || ($now - $previous) > 0.70){
                    continue;
                }

                // Reset immediately so a third press cannot retrigger this pair.
                $this->lastSneakPressAt[$playerId] = 0.0;

                if($player->getCurrentWindow() !== null){
                    continue;
                }

                $item = $player->getArmorInventory()->getChestplate();
                $id = $this->getVariantForItem($item);
                if($id === null){
                    continue;
                }

                $this->openPortableMenu($player, $item, $id);
            }
        }), 1);
    }

    public function onQuit(PlayerQuitEvent $event) : void{
        // Altay normally emits InventoryCloseEvent during disconnect. This cleanup
        // only removes stale bookkeeping if a host skips the close callback.
        $playerId = $event->getPlayer()->getId();
        foreach($this->openWindows as $key => $context){
            if($context['playerId'] === $playerId){
                unset($this->openWindows[$key]);
            }
        }
        unset($this->lastSneakingState[$playerId], $this->lastSneakPressAt[$playerId]);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(strtolower($command->getName()) !== 'backpack'){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage('Use this command in-game.');
            return true;
        }
        $sub = strtolower($args[0] ?? 'menu');
        if($sub === 'menu' || $sub === 'list' || $sub === 'bags'){
            $this->openBackpackForm($sender);
            return true;
        }
        if($sub === 'open'){
            $item = $sender->getArmorInventory()->getChestplate();
            $id = $this->getVariantForItem($item);
            if($id === null){
                $sender->sendMessage('§cYou are not wearing a BDCustomBackPacks backpack.');
                return true;
            }
            $this->openPortableMenu($sender, $item, $id);
            return true;
        }
        if($sub === 'give'){
            if(!$sender->hasPermission('bdcustombackpacks.admin')){
                $sender->sendMessage('§cNo permission.');
                return true;
            }
            $id = strtolower($args[1] ?? 'bdcustombackpacks:backpack');
            if(!str_starts_with($id, 'bdcustombackpacks:')){
                $id = 'bdcustombackpacks:'.$id;
            }
            if(!self::variantExists($id)){
                $sender->sendMessage('§cUnknown backpack variant.');
                return true;
            }
            $item = $this->makeItem($id);
            $left = $sender->getInventory()->addItem($item);
            if(count($left) > 0){
                foreach($left as $drop){$sender->getWorld()->dropItem($sender->getPosition(), $drop);}
            }
            $sender->sendMessage('§aGiven §f'.self::displayNameForItem($id).'§a.');
            return true;
        }
        $sender->sendMessage('§e/backpack open §7or §e/backpack give <variant>');
        return true;
    }

    private function openBackpackForm(Player $player) : void{
        $form = new SimpleForm(function(Player $player, $data) : void{
            if($data === null){
                return;
            }

            // FormAPI normally returns the clicked button index. Some FormAPI forks return
            // the button's custom data instead, so support both forms without ever falling
            // back to index 0.
            $ids = array_keys(self::$variants);
            $id = null;
            if(is_int($data) || (is_string($data) && ctype_digit($data))){
                $index = (int) $data;
                $id = $ids[$index] ?? null;
            }elseif(is_string($data) && self::variantExists($data)){
                $id = $data;
            }
            if($id === null){
                return;
            }

            $item = $this->makeItem($id);
            $left = $player->getInventory()->addItem($item);
            if(count($left) > 0){
                foreach($left as $drop){
                    $player->getWorld()->dropItem($player->getPosition(), $drop);
                }
                $player->sendMessage('§eInventory full: backpack dropped at your position.');
            }else{
                $player->sendMessage('§aReceived §f' . self::displayNameForItem($id) . '§a.');
            }
        });

        $form->setTitle('§l§6BDCustomBackPacks §r§7[BobyDev]');
        $form->setContent("§7Select a backpack to receive it.\n§8Backpacks are wearable in the chest slot and store their own contents.");

        foreach(self::$variants as $data){
            // Do not pass the variant ID as FormAPI's fourth argument. That argument is
            // image-data, not a button identifier, and some clients/forks can resolve the
            // response incorrectly when arbitrary text is supplied there.
            $form->addButton(
                '§e' . $data['name'] . "\n§7Storage: §f" . $data['size'] . ' slots'
            );
        }

        $player->sendForm($form);
    }

    private function openPortableMenu(Player $player, Item $item, string $id) : void{
        if($player->getCurrentWindow() !== null){
            $player->removeCurrentWindow();
        }

        $size = self::sizeForItem($id);
        $inventory = $this->createNativeBackpackInventory($player, $size);
        $contents = [];

        $tag = $item->getNamedTag()->getListTag(self::TAG_ITEM_CONTENTS);
        if($tag !== null){
            foreach($tag as $stackTag){
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
                        $contents[$slot] = $stack;
                    }
                }catch(\Throwable){
                }
            }
        }

        $inventory->setContents($contents);

        $holder = $inventory->getHolder();
        $key = spl_object_id($inventory);
        $this->openWindows[$key] = [
            'playerId' => $player->getId(),
            'type' => 'portable',
            'entity' => null,
            'item' => clone $item,
            'variant' => $id,
            'holder' => $holder
        ];

        $this->sendVirtualContainerBlock($player, $holder, true);

        if(!$player->setCurrentWindow($inventory)){
            $this->sendVirtualContainerBlock($player, $holder, false);
            unset($this->openWindows[$key]);
        }
    }

    private function writeInventoryToItem(Item $item, Inventory $inventory) : void{
        $tag = $item->getNamedTag();
        $list = new ListTag([], 10);
        foreach($inventory->getContents() as $slot => $stack){
            if(!$stack->isNull()){$list->push($stack->nbtSerialize($slot));}
        }
        $tag->setTag(self::TAG_ITEM_CONTENTS, $list);
        $item->setNamedTag($tag);
    }
}

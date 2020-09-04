<?php
declare(strict_types=1);

namespace GUIShop;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use GUIShop\Commands\OPCommand;
use GUIShop\Inventory\ShopChestInventory;
use GUIShop\Inventory\DoubleChestInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\block\Block;
use pocketmine\tile\Chest;
use GUIShop\entity\NPCEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use CoinAPI\CoinAPI;
// monster
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;


class GUIShop extends PluginBase
{
  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;

  public static function getInstance(): GUIShop
  {
    return static::$instance;
  }

  public function onLoad()
  {
    self::$instance = $this;
  }

  public function onEnable()
  {
    $this->player = new Config ($this->getDataFolder() . "players.yml", Config::YAML);
    $this->pldb = $this->player->getAll();
    $this->shop = new Config ($this->getDataFolder() . "shops.yml", Config::YAML);
    $this->shopdb = $this->shop->getAll();
    $this->getServer()->getCommandMap()->register('GUIShop', new OPCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    Entity::registerEntity(NPCEntity::class, true);
  }
  public function ShopEntitySpawn($player){
    $pos = explode ( ":", $this->shopdb ["엔피시위치"] );
    $nbt = new CompoundTag ( "", [ "Pos" => new ListTag ( "Pos", [ new DoubleTag ( "", (int) $pos [0] ),new DoubleTag ( "", (int) $pos [1] ),new DoubleTag ( "", (int) $pos [2] ) ] ),"Motion" => new ListTag ( "Motion", [ new DoubleTag ( "", 0 ),new DoubleTag ( "", 0 ),new DoubleTag ( "", 0 ) ] ),"Rotation" => new ListTag ( "Rotation", [ new FloatTag ( "", 0 ),new FloatTag ( "", 0 ) ] ) ] );
    $nbt->setTag(new CompoundTag('Skin', [
      new StringTag('Name', $player->getSkin()->getSkinId()),
      new ByteArrayTag('Data', $player->getSkin()->getSkinData()),
      new ByteArrayTag('CapeData', $player->getSkin()->getCapeData()),
      new StringTag('GeometryName', $player->getSkin()->getGeometryName()),
      new ByteArrayTag('GeometryData', $player->getSkin()->getGeometryData())
    ]));
    $entity = Entity::createEntity("NPCEntity", $this->getServer ()->getLevelByName ( $pos [3] ), $nbt);
    $entity->setNameTag( "상점도우미" );
    $entity->setNameTagAlwaysVisible(true);
    $entity->spawnToAll();
    return true;
  }
  public function SayTaskEvent ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(GUIShop $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->SayManagerUI($this->player);
      }
    }, 10);
  }
  public function SayManagerUI(Player $player)
  {
    $name = $player->getName ();
    $item = $this->pldb [strtolower($name)] ["상점물품"];
    $message = $this->pldb [strtolower($name)] ["상점이름"];
    $coin = $this->shopdb ["구매"] [$message] [$item] ["가격"];
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§6[ §f구매 상점 §6]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7구매하실 갯수를 적어주세요.\n개당 {$coin}-코인"
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345656;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function PayTaskEvent ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(GUIShop $owner, Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->PayManagerUI($this->player);
      }
    }, 10);
  }
  public function PayManagerUI(Player $player)
  {
    $name = $player->getName ();
    $item = $this->pldb [strtolower($name)] ["상점물품"];
    $message = $this->pldb [strtolower($name)] ["상점이름"];
    $coin = $this->shopdb ["판매"] [$message] [$item] ["가격"];
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§6[ §f판매 상점 §6]',
      'content' => [
        [
          'type' => 'input',
          'text' => "§r§7판매하실 갯수를 적어주세요.\n개당 {$coin}-코인"
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345657;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function onOpen($player) {
    $name = $player->getName ();
    $inv = new ShopChestInventory("§6§l[ §f상점목록 §6]");
    $CheckItem = Item::get(90, 0, 1);
    $inv->setItem( 0 , $CheckItem );
    $inv->setItem( 1 , $CheckItem );
    $inv->setItem( 2 , $CheckItem );
    $inv->setItem( 3 , $CheckItem );
    $inv->setItem( 4 , $CheckItem );
    $inv->setItem( 5 , $CheckItem );
    $inv->setItem( 6 , $CheckItem );
    $inv->setItem( 7 , $CheckItem );
    $inv->setItem( 8 , $CheckItem );
    $inv->setItem( 9 , $CheckItem );
    $inv->setItem( 10 , $CheckItem );
    $inv->setItem( 11 , $CheckItem );
    $inv->setItem( 12 , $CheckItem );
    $inv->setItem( 13 , $CheckItem );
    $inv->setItem( 14 , $CheckItem );
    $inv->setItem( 15 , $CheckItem );
    $inv->setItem( 16 , $CheckItem );
    $inv->setItem( 17 , $CheckItem );
    $inv->setItem( 18 , $CheckItem );
    $inv->setItem( 19 , Item::get(54, 0, 1)->setCustomName("§r§f구매상점으로 이동")->setLore([ "§r§7구매상점으로 이동합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 20 , $CheckItem );
    $inv->setItem( 21 , $CheckItem );
    $inv->setItem( 22, Item::get(332, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7상점에서 나갑니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 23 , $CheckItem );
    $inv->setItem( 24 , $CheckItem );
    $inv->setItem( 25 , Item::get(266, 0, 1)->setCustomName("§r§f판매상점으로 이동")->setLore([ "§r§7판매상점으로 이동합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 26 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }
  public function onSayOpen($player) {
    $name = $player->getName ();
    $inv = new ShopChestInventory("§6§l[ §f구매상점 §6]");
    $CheckItem = Item::get(90, 0, 1);
    $inv->setItem( 0 , $CheckItem );
    $inv->setItem( 1 , $CheckItem );
    $inv->setItem( 2 , $CheckItem );
    $inv->setItem( 3 , $CheckItem );
    $inv->setItem( 4 , $CheckItem );
    $inv->setItem( 5 , $CheckItem );
    $inv->setItem( 6 , $CheckItem );
    $inv->setItem( 7 , $CheckItem );
    $inv->setItem( 8 , $CheckItem );
    $inv->setItem( 9 , $CheckItem );
    $inv->setItem( 10 , Item::get(43, 2, 1)->setCustomName("§r§f블럭상점")->setLore([ "§r§7블럭상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 11 , $CheckItem );
    $inv->setItem( 12, Item::get(276, 0, 1)->setCustomName("§r§f도구상점")->setLore([ "§r§7도구상점을 오픈합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 13 , $CheckItem );
    $inv->setItem( 14 , Item::get(296, 0, 1)->setCustomName("§r§f농작물상점")->setLore([ "§r§7농작물상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 15 , $CheckItem );
    $inv->setItem( 16 , Item::get(321, 0, 1)->setCustomName("§r§f기타상점")->setLore([ "§r§7기타상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 17 , $CheckItem );
    $inv->setItem( 18 , $CheckItem );
    $inv->setItem( 19 , $CheckItem );
    $inv->setItem( 20 , $CheckItem );
    $inv->setItem( 21 , $CheckItem );
    $inv->setItem( 22, Item::get(324, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7행동을 취소합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 23 , $CheckItem );
    $inv->setItem( 24 , $CheckItem );
    $inv->setItem( 25 , $CheckItem );
    $inv->setItem( 26 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }
  public function onSellOpen($player) {
    $name = $player->getName ();
    $inv = new ShopChestInventory("§6§l[ §f판매상점 §6]");
    $CheckItem = Item::get(90, 0, 1);
    $inv->setItem( 0 , $CheckItem );
    $inv->setItem( 1 , $CheckItem );
    $inv->setItem( 2 , $CheckItem );
    $inv->setItem( 3 , $CheckItem );
    $inv->setItem( 4 , $CheckItem );
    $inv->setItem( 5 , $CheckItem );
    $inv->setItem( 6 , $CheckItem );
    $inv->setItem( 7 , $CheckItem );
    $inv->setItem( 8 , $CheckItem );
    $inv->setItem( 9 , $CheckItem );
    $inv->setItem( 10 , Item::get(43, 2, 1)->setCustomName("§r§f블럭상점")->setLore([ "§r§7블럭상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 11 , $CheckItem );
    $inv->setItem( 12, Item::get(276, 0, 1)->setCustomName("§r§f도구상점")->setLore([ "§r§7도구상점을 오픈합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 13 , $CheckItem );
    $inv->setItem( 14 , Item::get(296, 0, 1)->setCustomName("§r§f농작물상점")->setLore([ "§r§7농작물상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 15 , $CheckItem );
    $inv->setItem( 16 , Item::get(321, 0, 1)->setCustomName("§r§f기타상점")->setLore([ "§r§7기타상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 17 , $CheckItem );
    $inv->setItem( 18 , $CheckItem );
    $inv->setItem( 19 , $CheckItem );
    $inv->setItem( 20 , $CheckItem );
    $inv->setItem( 21 , $CheckItem );
    $inv->setItem( 22, Item::get(324, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7행동을 취소합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 23 , $CheckItem );
    $inv->setItem( 24 , $CheckItem );
    $inv->setItem( 25 , $CheckItem );
    $inv->setItem( 26 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }
  public function SayShopOpen($player, $message) {
    $name = $player->getName ();
    $inv = new DoubleChestInventory("§6§l[ §f상점 §6]");
    $arr = [];
    $i = 0;
    if (isset($this->shopdb ["구매"])){
      foreach($this->shopdb ["구매"] [$message] as $NPCShop => $v){
        $s = $this->shopdb ["구매"] [$message] [$NPCShop] ["아이템"];
        $money = $this->shopdb ["구매"] [$message] [$NPCShop] ["아이템"];
        $item = Item::jsonDeserialize ($s);
        $item->setCount(1);
        $lore = [];
        $lore [] = "§r§7구매 가격 : " . $money . "\n구매를 진행하려면 인벤토리로 가져가보세요.";
        $item->setLore ($lore);
        $inv->setItem( $i , $item );
        ++$i;
      }
      $inv->sendContents($inv->getViewers());
    }
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }
  public function SellShopOpen($player, $message) {
    $name = $player->getName ();
    $inv = new DoubleChestInventory("§6§l[ §f상점 §6]");
    $arr = [];
    $i = 0;
    if (isset($this->shopdb ["판매"])){
      foreach($this->shopdb ["판매"] [$message] as $NPCShop => $v){
        $s = $this->shopdb ["판매"] [$message] [$NPCShop] ["아이템"];
        $money = $this->shopdb ["판매"] [$message] [$NPCShop] ["가격"];
        $item = Item::jsonDeserialize ($s);
        $item->setCount(1);
        $lore = [];
        $lore [] = "§r§7판매 가격 : " . $money . "\n판매를 진행하려면 인벤토리로 가져가보세요.";
        $item->setLore ($lore);
        $inv->setItem( $i , $item );
        ++$i;
      }
    }
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }

  public function ShopItemsSet($player) {
    $name = $player->getName ();
    $name = $player->getName ();
    $inv = new ShopChestInventory("§6§l[ §f상점 §6]");
    $CheckItem = Item::get(90, 0, 1);
    $inv->setItem( 0 , $CheckItem );
    $inv->setItem( 1 , $CheckItem );
    $inv->setItem( 2 , $CheckItem );
    $inv->setItem( 3 , $CheckItem );
    $inv->setItem( 4 , $CheckItem );
    $inv->setItem( 5 , $CheckItem );
    $inv->setItem( 6 , $CheckItem );
    $inv->setItem( 7 , $CheckItem );
    $inv->setItem( 8 , $CheckItem );
    $inv->setItem( 9 , $CheckItem );
    $inv->setItem( 10 , $CheckItem );
    $inv->setItem( 11 , $CheckItem );
    $inv->setItem( 12 , $CheckItem );
    $inv->setItem( 13 , $CheckItem );
    $inv->setItem( 14 , $CheckItem );
    $inv->setItem( 15 , $CheckItem );
    $inv->setItem( 16 , $CheckItem );
    $inv->setItem( 17 , $CheckItem );
    $inv->setItem( 18 , $CheckItem );
    $inv->setItem( 19 , Item::get(54, 0, 1)->setCustomName("§r§f구매상점")->setLore([ "§r§7구매상점에 물품을 추가합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 20 , $CheckItem );
    $inv->setItem( 21 , $CheckItem );
    $inv->setItem( 22, Item::get(324, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7행동을 취소합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 23 , $CheckItem );
    $inv->setItem( 24 , $CheckItem );
    $inv->setItem( 25 , Item::get(266, 0, 1)->setCustomName("§r§f판매상점")->setLore([ "§r§7판매상점에 물품을 추가합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 26 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }

  public function ShopItemPosSet($player) {
    $name = $player->getName ();
    $name = $player->getName ();
    $inv = new ShopChestInventory("§6§l[ §f상점 §6]");
    $CheckItem = Item::get(90, 0, 1);
    $inv->setItem( 0 , $CheckItem );
    $inv->setItem( 1 , $CheckItem );
    $inv->setItem( 2 , $CheckItem );
    $inv->setItem( 3 , $CheckItem );
    $inv->setItem( 4 , $CheckItem );
    $inv->setItem( 5 , $CheckItem );
    $inv->setItem( 6 , $CheckItem );
    $inv->setItem( 7 , $CheckItem );
    $inv->setItem( 8 , $CheckItem );
    $inv->setItem( 9 , $CheckItem );
    $inv->setItem( 10 , Item::get(43, 2, 1)->setCustomName("§r§f블럭상점")->setLore([ "§r§7블럭상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 11 , $CheckItem );
    $inv->setItem( 12, Item::get(276, 0, 1)->setCustomName("§r§f도구상점")->setLore([ "§r§7도구상점을 오픈합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 13 , $CheckItem );
    $inv->setItem( 14 , Item::get(296, 0, 1)->setCustomName("§r§f농작물상점")->setLore([ "§r§7농작물상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 15 , $CheckItem );
    $inv->setItem( 16 , Item::get(321, 0, 1)->setCustomName("§r§f기타상점")->setLore([ "§r§7기타상점을 오픈합니다.\n인벤토리로 가져가보세요." ]) );
    $inv->setItem( 17 , $CheckItem );
    $inv->setItem( 18 , $CheckItem );
    $inv->setItem( 19 , $CheckItem );
    $inv->setItem( 20 , $CheckItem );
    $inv->setItem( 21 , $CheckItem );
    $inv->setItem( 22, Item::get(324, 0, 1)->setCustomName("§r§f나가기")->setLore([ "§r§7행동을 취소합니다.\n인벤토리로 가져가보세요." ]));
    $inv->setItem( 23 , $CheckItem );
    $inv->setItem( 24 , $CheckItem );
    $inv->setItem( 25 , $CheckItem );
    $inv->setItem( 26 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 10);
  }

  public function onDisable()
  {
    $this->save();
  }
  public function save()
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->shop->setAll($this->shopdb);
    $this->shop->save();
  }
}

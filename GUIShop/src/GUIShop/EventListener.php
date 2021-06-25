<?php
declare(strict_types=1);

namespace GUIShop;

use pocketmine\event\Listener;
use GUIShop\entity\NPCEntity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\Item;
use pocketmine\tile\Chest;
use ShopMoneyAPI\ShopMoneyAPI;
use GUIShop\Inventory\ShopChestInventory;
use GUIShop\Inventory\DoubleChestInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class EventListener implements Listener
{

  protected $plugin;

  public function __construct(GUIShop $plugin)
  {
    $this->plugin = $plugin;
  }
  public function OnJoin (PlayerJoinEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (!isset($this->plugin->pldb [strtolower($name)])){
      $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
      $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
      $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
      $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
      $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
      $this->plugin->pldb [strtolower($name)] ["상점수정"] = "오프";
      $this->plugin->pldb [strtolower($name)] ["상점생성"] = "오프";
      $this->plugin->save ();
    }
  }
  public function onPacketReceive (DataPacketReceiveEvent $event) {
    $packet = $event->getPacket();
    if(! $packet instanceof ContainerClosePacket)
    return;
    $player = $event->getPlayer();
    $inv = $player->getWindow ($packet->windowId);
    if ($inv instanceof DoubleChestInventory) {
      $pk = new ContainerClosePacket();
      $pk->windowId = $player->getWindowId($inv);
      $player->sendDataPacket($pk);
    }
  }
  public function onInvClose(InventoryCloseEvent $event) {
    $player = $event->getPlayer();
    $inv = $event->getInventory();
    if ($inv instanceof DoubleChestInventory) {
      $inv->onClose($player);
      return true;
    }
    if ($inv instanceof ShopChestInventory) {
      $inv->onClose($player);
      return true;
    }
  }
  public function onTransaction(InventoryTransactionEvent $event) {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if ($inv instanceof ShopChestInventory) {
          $slot = $action->getSlot ();
          $item = $inv->getItem ($slot);
          $id = $item->getId ();
          $damage = $item->getDamage ();
          $itemname = $item->getCustomName ();
          $nbt = $item->jsonSerialize ();
          if ( $id == 90 ) {
            $event->setCancelled ();
            return true;
          }
          if ( $id == 54 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "구매";
              $this->plugin->save ();
              $this->plugin->onSayOpen($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점이용"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "구매";
              $this->plugin->save ();
              $this->plugin->onSayOpen($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "구매";
              $this->plugin->save ();
              $this->plugin->ShopItemPosSet ($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
          }
          if ( $id == 324 ) {
            $event->setCancelled ();
            $inv->onClose($player);
            return true;
          }
          if ( $id == 266 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "판매";
              $this->plugin->save ();
              $this->plugin->onSellOpen($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점이용"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "판매";
              $this->plugin->save ();
              $this->plugin->onSellOpen($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "판매";
              $this->plugin->save ();
              $this->plugin->ShopItemPosSet ($player);
              $event->setCancelled ();
              $inv->onClose($player);
              return true;
            }
          }
          if ( $id == 43 && $damage == 2 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "블럭상점";
                $this->plugin->save ();
                $message = "블럭상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "블럭상점";
                $this->plugin->save ();
                $message = "블럭상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["구매"] ["블럭상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["구매"] ["블럭상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["구매"] ["블럭상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["구매"] ["블럭상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["판매"] ["블럭상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["판매"] ["블럭상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["판매"] ["블럭상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["판매"] ["블럭상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점이용"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "블럭상점";
                $this->plugin->save ();
                $message = "블럭상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "블럭상점";
                $this->plugin->save ();
                $message = "블럭상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
          }
          if ( $id == 276 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "도구상점";
                $this->plugin->save ();
                $message = "도구상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "도구상점";
                $this->plugin->save ();
                $message = "도구상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["구매"] ["도구상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["구매"] ["도구상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["구매"] ["도구상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["구매"] ["도구상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["판매"] ["도구상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["판매"] ["도구상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["판매"] ["도구상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["판매"] ["도구상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점이용"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "도구상점";
                $this->plugin->save ();
                $message = "도구상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "도구상점";
                $this->plugin->save ();
                $message = "도구상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
          }
          if ( $id == 296 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "농작물상점";
                $this->plugin->save ();
                $message = "농작물상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "농작물상점";
                $this->plugin->save ();
                $message = "농작물상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["구매"] ["농작물상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["구매"] ["농작물상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["구매"] ["농작물상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["구매"] ["농작물상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["판매"] ["농작물상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["판매"] ["농작물상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["판매"] ["농작물상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["판매"] ["농작물상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "농작물상점";
                $this->plugin->save ();
                $message = "농작물상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "농작물상점";
                $this->plugin->save ();
                $message = "농작물상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
          }
          if ( $id == 321 ) {
            if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "기타상점";
                $this->plugin->save ();
                $message = "기타상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "기타상점";
                $this->plugin->save ();
                $message = "기타상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점생성"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["구매"] ["기타상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["구매"] ["기타상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["구매"] ["기타상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["구매"] ["기타상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $item = $player->getInventory()->getItemInHand();
                $itemname = $item->getName ();
                $this->plugin->shopdb ["판매"] ["기타상점"] [$itemname] ['id'] = $item->getId();
                $this->plugin->shopdb ["판매"] ["기타상점"] [$itemname] ['dmg'] = $item->getDamage();
                $this->plugin->shopdb ["판매"] ["기타상점"] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
                $this->plugin->shopdb ["판매"] ["기타상점"] [$itemname] ["가격"] = 0;
                $this->plugin->save ();
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
            if ($this->plugin->pldb [strtolower($name)] ["상점이용"] == "온"){
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "기타상점";
                $this->plugin->save ();
                $message = "기타상점";
                $this->plugin->SayShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
              if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
                $this->plugin->pldb [strtolower($name)] ["상점이름"] = "기타상점";
                $this->plugin->save ();
                $message = "기타상점";
                $this->plugin->SellShopOpen($player, $message);
                $event->setCancelled ();
                $inv->onClose($player);
                return true;
              }
            }
          }
        }
        if ($inv instanceof DoubleChestInventory) {
          $slot = $action->getSlot ();
          $item = $inv->getItem ($slot);
          $id = $item->getId ();
          $damage = $item->getDamage ();
          $message = $this->plugin->pldb [strtolower($name)] ["상점이름"];
          if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
            if (isset($this->plugin->shopdb ["구매"] [$message])) {
              foreach($this->plugin->shopdb ["구매"] [$message] as $NPCShop => $v){
                $item = Item::jsonDeserialize([
                  'id' => $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['id'],
                  'damage' => $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['dmg'],
                  'count' => (int) $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ["갯수"],
                  'nbt' => base64_decode($this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['nbt'], true)
                ]);
                $shopitemid = $item->getId ();
                $shopitemDamage = $item->getDamage ();
                if ($id == $shopitemid && $damage == $shopitemDamage) {
                  if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
                    $event->setCancelled ();
                    $inv->onClose ($player);
                    $this->plugin->pldb [strtolower($name)] ["상점이름"] = $message;
                    $this->plugin->pldb [strtolower($name)] ["상점물품"] = $NPCShop;
                    $this->plugin->save ();
                    $this->ItemNew ($player);
                    return true;
                  }
                  $event->setCancelled ();
                  $inv->onClose ($player);
                  $this->plugin->pldb [strtolower($name)] ["상점이름"] = $message;
                  $this->plugin->pldb [strtolower($name)] ["상점물품"] = $NPCShop;
                  $this->plugin->save ();
                }
              }
              $this->plugin->SayTaskEvent ($player);
              return true;
            }
          }
          if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
            if (isset($this->plugin->shopdb ["판매"] [$message])) {
              foreach($this->plugin->shopdb ["판매"] [$message] as $NPCShop => $v){
                $item = Item::jsonDeserialize([
                  'id' => $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['id'],
                  'damage' => $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['dmg'],
                  'count' => (int) $this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ["갯수"],
                  'nbt' => base64_decode($this->plugin->shopdb ["구매"] ["기타상점"] [$NPCShop] ['nbt'], true)
                ]);
                $shopitemid = $item->getId ();
                $shopitemDamage = $item->getDamage ();
                if ($id == $shopitemid && $damage == $shopitemDamage) {
                  if ($this->plugin->pldb [strtolower($name)] ["상점수정"] == "온"){
                    $event->setCancelled ();
                    $inv->onClose ($player);
                    $this->plugin->pldb [strtolower($name)] ["상점이름"] = $message;
                    $this->plugin->pldb [strtolower($name)] ["상점물품"] = $NPCShop;
                    $this->plugin->save ();
                    $this->ItemNew ($player);
                    return true;
                  }
                  $event->setCancelled ();
                  $inv->onClose ($player);
                  $this->plugin->pldb [strtolower($name)] ["상점이름"] = $message;
                  $this->plugin->pldb [strtolower($name)] ["상점물품"] = $NPCShop;
                  $this->plugin->save ();
                }
              }
              $this->plugin->PayTaskEvent ($player);
              return true;
            }
          }
        }
      }
    }
  }
  public function onPacket(DataPacketReceiveEvent $event)
  {
    $packet = $event->getPacket();
    $player = $event->getPlayer();
    $x = $player->getX ();
    $y = $player->getY ();
    $z = $player->getZ ();
    $level = $player->getLevel ()->getFolderName ();
    $name = $player->getName();
    $tag = "§l§6[ §f안내 §6] ";
    if ($packet instanceof ModalFormResponsePacket) {
      $id = $packet->formId;
      $data = json_decode($packet->formData, true);
      if ($id === 345654) {
        if ($data === 0) {
          if (! $player->isOp()) {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
          $this->plugin->shopdb ["엔피시위치"] = $x . ":" . $y . ":" . $z . ":" . $level;
          $this->plugin->save ();
          $player->sendMessage ($tag . "엔피시 위치를 자신의 위치로 설정했습니다.");
          return true;
        }
        if ($data === 1) {
          if (! $player->isOp()) {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
          if (isset ($this->plugin->shopdb ["엔피시위치"])){
            $this->plugin->ShopEntitySpawn ($player);
            $player->sendMessage( $tag . '상점 엔피시를 소환했습니다.');
            return true;
          } else {
            $player->sendMessage( $tag . '상점 엔피시 위치를 먼저 선택해주세요.');
            return true;
          }
        }
        if ($data === 2) {
          if (! $player->isOp()) {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
          $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
          $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점수정"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점생성"] = "온";
          $this->plugin->save ();
          $this->plugin->ShopItemsSet ($player);
          return true;
        }
        if ($data === 3) {
          if (! $player->isOp()) {
            $player->sendMessage($tag . "권한이 없습니다.");
            return true;
          }
          $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
          $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점수정"] = "온";
          $this->plugin->pldb [strtolower($name)] ["상점생성"] = "오프";
          $this->plugin->save ();
          $this->plugin->ShopItemsSet ($player);
          return true;
        }
      }
      if ($id === 345655) {
        if (!isset($data[1])) {
          $player->sendMessage( $tag . '빈칸을 채워주세요.');
          return;
        }
        if (! is_numeric ($data[1])) {
          $player->sendMessage ( $tag . "숫자를 이용 해야됩니다. " );
          return true;
        }
        if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "구매"){
          $player->sendMessage( $tag . '수정완료.');
          $itemname = $this->plugin->pldb [strtolower($name)] ["상점물품"];
          $itemxyz = $this->plugin->pldb [strtolower($name)] ["상점이름"];
          $item = Item::jsonDeserialize([
            'id' => $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['id'],
            'damage' => $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['dmg'],
            'count' => (int) 1,
            'nbt' => base64_decode($this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['nbt'], true)
          ]);

          $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['id'] = $item->getId();
          $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['dmg'] = $item->getDamage();
          $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
          $this->plugin->shopdb ["구매"] [$itemxyz] [$itemname] ["가격"] = $data[2];
          $this->plugin->save ();
          return true;
        }
        if ($this->plugin->pldb [strtolower($name)] ["상점정보"] == "판매"){
          $player->sendMessage( $tag . '수정완료.');
          $itemname = $this->plugin->pldb [strtolower($name)] ["상점물품"];
          $itemxyz = $this->plugin->pldb [strtolower($name)] ["상점이름"];
          $item = Item::jsonDeserialize([
            'id' => $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['id'],
            'damage' => $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['dmg'],
            'count' => (int) 1,
            'nbt' => base64_decode($this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['nbt'], true)
          ]);

          $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['id'] = $item->getId();
          $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['dmg'] = $item->getDamage();
          $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ['nbt'] = $item->hasCompoundTag() ? base64_encode($item->getCompoundTag()) : '';
          $this->plugin->shopdb ["판매"] [$itemxyz] [$itemname] ["가격"] = $data[2];
          $this->plugin->save ();
          return true;
        }
      }
      if ($id === 345656) {
        if (!isset($data[0])) {
          $player->sendMessage( $tag . '빈칸을 채워주세요.');
          return;
        }
        if (! is_numeric ($data[0])) {
          $player->sendMessage ( $tag . "숫자를 이용 해야됩니다. " );
          return true;
        }
        $this->plugin->pldb [strtolower($name)] ["상점갯수"] = (int)$data[0];
        $this->plugin->save ();
        $this->SayCoin ($player);
        return true;
      }
      if ($id === 345657) {
        if (!isset($data[0])) {
          $player->sendMessage( $tag . '빈칸을 채워주세요.');
          return;
        }
        if (! is_numeric ($data[0])) {
          $player->sendMessage ( $tag . "숫자를 이용 해야됩니다. " );
          return true;
        }
        $this->plugin->pldb [strtolower($name)] ["상점갯수"] = (int)$data[0];
        $this->plugin->save ();
        $this->PayCoin ($player);
        return true;
      }
      if ($id === 345658) {
        if ($data === 0) {
          $shopname = $this->plugin->pldb [strtolower($name)] ["상점이름"];
          $shoppos = $this->plugin->pldb [strtolower($name)] ["상점물품"];
          $money = $this->plugin->shopdb ["구매"] [$shopname] [$shoppos] ["가격"];
          if ($money == 0){
            $player->sendMessage ($tag . "해당 물품은 이용이 막혀있는 물품입니다.");
            $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
            $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
            $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
            $this->plugin->save ();
            return true;
          }
          $count = $this->plugin->pldb [strtolower($name)] ["상점갯수"];
          $item = Item::jsonDeserialize([
            'id' => $this->plugin->shopdb ["구매"] [$shopname] [$shoppos] ['id'],
            'damage' => $this->plugin->shopdb ["구매"] [$shopname] [$shoppos] ['dmg'],
            'count' => (int) $count,
            'nbt' => base64_decode($this->plugin->shopdb ["구매"] [$shopname] [$shoppos] ['nbt'], true)
          ]);
          $moneys = (int)$coin*(int)$count;
          if ($player->getInventory()->canAddItem($item)) {
            if (ShopMoneyAPI::getInstance ()->getMoney ($player) >= $money*$count){
              $player->sendMessage ($tag . "정상적으로 물품을 구매했습니다.\n구매에 사용된 코인 : {$moneys}");
              ShopMoneyAPI::getInstance ()->sellMoney ($player,$moneys);
              $player->getInventory()->addItem($item);
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
              $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
              $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
              $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
              $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
              $this->plugin->save ();
              return true;
            } else {
              $player->sendMessage ($tag . "코인이 부족해서 구매가 취소되었습니다.");
              $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
              $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
              $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
              $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
              $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
              $this->plugin->save ();
              return true;
            }
          } else {
            $player->sendMessage ($tag . "인벤토리의 공간이 부족하여 구매가 취소되었습니다.");
            $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
            $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
            $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
            $this->plugin->save ();
            return true;
          }
        }
        if ($data === 0) {
          $player->sendMessage ($tag . "구매를 취소했습니다.");
          $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
          $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
          $this->plugin->save ();
        }
      }
      if ($id === 345659) {
        if ($data === 0) {
          $shopname = $this->plugin->pldb [strtolower($name)] ["상점이름"];
          $shoppos = $this->plugin->pldb [strtolower($name)] ["상점물품"];
          $money = $this->plugin->shopdb ["판매"] [$shopname] [$shoppos] ["가격"];
          if ($money == 0){
            $player->sendMessage ($tag . "해당 물품은 이용이 막혀있는 물품입니다.");
            $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
            $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
            $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
            $this->plugin->save ();
            return true;
          }
          $count = $this->plugin->pldb [strtolower($name)] ["상점갯수"];
          $item = Item::jsonDeserialize([
            'id' => $this->plugin->shopdb ["판매"] [$shopname] [$shoppos] ['id'],
            'damage' => $this->plugin->shopdb ["판매"] [$shopname] [$shoppos] ['dmg'],
            'count' => (int) $count,
            'nbt' => base64_decode($this->plugin->shopdb ["판매"] [$shopname] [$shoppos] ['nbt'], true)
          ]);
          $moneys = (int)$money*(int)$count;
          if ($player->getInventory ()->contains ( $item )) {
            $player->sendMessage ($tag . "정상적으로 물품을 판매했습니다.\n판매하고 얻은 코인 : {$moneys}");
            $player->getInventory ()->removeItem ( $item );
            ShopMoneyAPI::getInstance ()->addMoney ($player,$moneys);
            $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
            $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
            $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
            $this->plugin->save ();
            return true;
          } else {
            $player->sendMessage ($tag . "가지고 있는 양보다 많이 팔수없습니다..");
            $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
            $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
            $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
            $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
            $this->plugin->save ();
            return true;
          }
        }
        if ($data === 0) {
          $player->sendMessage ($tag . "판매를 취소했습니다.");
          $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
          $this->plugin->pldb [strtolower($name)] ["상점이용"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
          $this->plugin->save ();
        }
      }
    } else if($packet instanceof InventoryTransactionPacket){
      $trData = $packet->trData;
      if($trData instanceof UseItemOnEntityTransactionData){
        $entity = $player->level->getEntity($trData->getEntityRuntimeId());
        if($entity instanceof NPCEntity){
          $this->plugin->pldb [strtolower($name)] ["상점정보"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점물품"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점갯수"] = 0;
          $this->plugin->pldb [strtolower($name)] ["상점이용"] = "온";
          $this->plugin->pldb [strtolower($name)] ["상점이름"] = "없음";
          $this->plugin->pldb [strtolower($name)] ["상점수정"] = "오프";
          $this->plugin->pldb [strtolower($name)] ["상점생성"] = "오프";
          $this->plugin->save ();
          $this->plugin->onOpen ($player);
        }
      }
    }
  }
  public function ItemNew(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§l§6[ §f상점설정 §6]',
      'content' => [
        [
          'type' => 'input',
          'text' => '§l§6[ §f물품가격 §6]'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345655;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function SayCoin(Player $player)
  {
    $name = $player->getName ();
    $shopname = $this->plugin->pldb [strtolower($name)] ["상점이름"];
    $shoppos = $this->plugin->pldb [strtolower($name)] ["상점물품"];
    $coin = $this->plugin->shopdb ["구매"] [$shopname] [$shoppos] ["가격"];
    $count = $this->plugin->pldb [strtolower($name)] ["상점갯수"];
    $money = (int)$coin*(int)$count;
    $encode = [
      'type' => 'form',
      'title' => '§l§6[ §f구매 상점 §6]',
      'content' => "§r§7당신이 구매할 아이템 갯수 : {$count}\n총 가격 : {$money}",
      'buttons' => [
        [
          'text' => '§l§6[ §f구매하기 §6]'
        ],
        [
          'text' => '§l§6[ §f취소하기 §6]'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345658;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function PayCoin(Player $player)
  {
    $name = $player->getName ();
    $shopname = $this->plugin->pldb [strtolower($name)] ["상점물품"];
    $shoppos = $this->plugin->pldb [strtolower($name)] ["상점물품"];
    $coin = $this->plugin->shopdb ["판매"] [$shopname] [$shoppos] ["가격"];
    $count = $this->plugin->pldb [strtolower($name)] ["상점갯수"];
    $money = (int)$coin*(int)$count;
    $encode = [
      'type' => 'form',
      'title' => '§l§6[ §f판매 상점 §6]',
      'content' => "§r§7당신이 판매할 아이템 갯수 : {$count}\n총 가격 : {$money}",
      'buttons' => [
        [
          'text' => '§l§6[ §f판매하기 §6]'
        ],
        [
          'text' => '§l§6[ §f취소하기 §6]'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345659;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function onHit(EntityDamageEvent $event)
  {
    $entity = $event->getEntity();
    if ($entity instanceof NPCEntity) {
      $event->setCancelled(true);
    }
  }
  public function onDeath(EntityDeathEvent $event){
    $npc = $event->getEntity();
    if($npc instanceof NPCEntity){
      $event->setDrops([]);
    }
  }
}

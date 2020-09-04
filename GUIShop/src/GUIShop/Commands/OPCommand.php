<?php
declare(strict_types=1);

namespace GUIShop\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use GUIShop\GUIShop;

class OPCommand extends Command
{

  protected $plugin;

  public function __construct(GUIShop $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('상점설정', '상점설정 명령어.', '/상점설정');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $encode = [
      'type' => 'form',
      'title' => '§l§6[ §f안내 §6]',
      'content' => '§r§7버튼을 눌러주세요.',
      'buttons' => [
        [
          'text' => '§l§6[ §f엔피시 위치설정 §6]'
        ],
        [
          'text' => '§l§6[ §f엔피시 소환 §6]'
        ],
        [
          'text' => '§l§6[ §f물품 설정 §6]'
        ],
        [
          'text' => '§l§6[ §f물품 가격수정 §6]'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 345654;
    $packet->formData = json_encode($encode);
    $sender->sendDataPacket($packet);
  }
}

<?php
namespace Consent;

#Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;

#Event
use pocketmine\event\Event;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;

#Command
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;

#Utils
use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Config;

#etc
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Main extends PluginBase implements Listener{
  private $plugin = "Consent",
          $version = "v1.0.1";

	function onEnable(){
    $this->getLogger()->info(Color::GREEN.$this->plugin." ".$this->version." が読み込まれました。");

    $dir = $this->getDataFolder();

    $file1 = "agrees.yml";
    $file2 = "config.yml";

    if(!file_exists($dir)){
      mkdir($dir);
    }

    if(!file_exists($dir.$file1)){
      file_put_contents($dir.$file1, $this->getResource($file1));
    }

    if(!file_exists($dir.$file2)){
      file_put_contents($dir.$file2, $this->getResource($file2));
    }

    $this->agrs_file = new Config($dir.$file1, Config::YAML);
    $this->agrs = $this->agrs_file->getAll();

    $this->config_file = new Config($dir.$file2, Config::YAML);
    $this->config = $this->config_file->getAll();

    foreach ($this->agrs as $an) {
      $this->flags[$an] = 1;//フラグ1
    }

    $this->getServer()->getPluginManager()->registerEvents($this,$this);
	}

  function onJoin(PlayerJoinEvent $e){
    $p = $e->getPlayer();
    $n = $p->getName();
    //
    if (empty($this->flags[$n])) {
      $this->flags[$n] = 0;
      $p->sendPopup("§bチャット欄のサーバールールをお読み下さい。");
      $m = implode("\n", $this->config);
      $p->sendMessage($m."\n§fよろしければ、§b/agr §fと打ってください。");
    }
  }

  function onCommand(CommandSender $s, Command $cmd, $label, array $args){
    if($label === "agr"){
      if($s instanceOf Player){//送信したのがプレイヤーなら
        $n = $s->getName();
        if ($this->flags[$n] === 1) {
          $s->sendMessage("§b既にあなたはサーバールールに同意しています！");
        }else {
          $this->flags[$n] = 1;//フラグ1
          $this->agrs[] = $n;
          $this->agrs_file->setAll($this->agrs);
          $this->agrs_file->save();

          $s->sendMessage("§bサーバールールに同意していただき、ありがとうございました。硬直を解除しました。");
        }
        return true;
      }
    }
  }

  function onMove(PlayerMoveEvent $e){
    $p = $e->getPlayer();
    $n = $p->getName();
    //
    if ($this->flags[$n] !== 1) {
      $p->sendPopup("§bチャット欄のサーバールールをお読み下さい。");
      $e->setCancelled();
    }
  }

  function onDisable(){
    $this->agrs_file->setAll($this->agrs);
    $this->agrs_file->save();

    $this->getLogger()->info(Color::RED.$this->plugin." が無効化されました。");
  }

  function playerCommand(PlayerCommandPreprocessEvent $e){
    $p = $e->getPlayer();
    $n = $p->getName();
    $m = $e->getMessage();
    //
    if (stristr($m, "/agr")){
      return true;
    }elseif ($this->flags[$n] !== 1) {
      return $e->setCancelled();
    }
  }
}

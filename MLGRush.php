<?php

namespace JavaPluginFX;

//Base
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
//Utils
use pocketmine\utils\TextFormat as Color;
use pocketmine\utils\Config;
//EventListener
use pocketmine\event\Listener;
//PlayerEvents
use pocketmine\Player;
use pocketmine\event\player\PlayerHungerChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerMoveEvent;
//ItemUndBlock
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
//BlockEvents
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
//EntityEvents
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Effect;
//Level
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
//Sounds
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\GhastSound;
//Commands
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
//Tile
use pocketmine\tile\Sign;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
//Nbt
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
//Inventar
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\Inventory;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\level\particle\HeartParticle;

class MLGRush extends PluginBase implements Listener {
	
	public $prefix = Color::WHITE . "[" . Color::RED . "MLG" . Color::WHITE . "Rush" . Color::WHITE . "] ";
	public $arenaname = "";
	public $mode = 0;
	public $players = 0;
	
	public $pc1 = 0;
	public $pc2 = 0;
	
	public function onEnable() {
    	
	    if (is_dir($this->getDataFolder()) !== true) {
        	
            mkdir($this->getDataFolder());
            
        }
        
        if (is_dir("/home/flamelabs/Test/MLGRush") !== true) {
			
             mkdir("/home/flamelabs/Test/MLGRush");
            
        }
        
        if (is_dir("/home/flamelabs/Test/MLGRush/players") !== true) {
			
             mkdir("/home/flamelabs/Test/MLGRush/players");
            
        }
    	
        if(is_dir($this->getDataFolder() . "/maps") !== true) {
        
            mkdir($this->getDataFolder() . "/maps");
            
        }

        $this->saveDefaultConfig();
        $this->reloadConfig();

        $config = $this->getConfig();
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new PlayerSender($this), 10);
        $this->getScheduler()->scheduleRepeatingTask(new ResetMap($this), 5);
        $this->getLogger()->info($this->prefix . Color::GREEN . "wurde aktiviert!");
        $this->getLogger()->info($this->prefix . Color::AQUA . "Programmiert von" . Color::GREEN . "JavaPluginFX!");
        
    }
    
    public function copymap($src, $dst) {
    
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
        	
            if (($file != '.') && ($file != '..')) {
            	
                if (is_dir($src . '/' . $file)) {
                	
                    $this->copymap($src . '/' . $file, $dst . '/' . $file);
                    
                } else {
                	
                    copy($src . '/' . $file, $dst . '/' . $file);
                    
                }
                
            }
            
        }
        
        closedir($dir);
        
    }

    public function deleteDirectory($dirPath) {
    
        if (is_dir($dirPath)) {
        	
            $objects = scandir($dirPath);
            foreach ($objects as $object) {
            	
                if ($object != "." && $object != "..") {
                	
                    if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                    	
                        $this->deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                        
                    } else {
                    	
                        unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                        
                    }
                    
                }
                
            }
            
            reset($objects);
            rmdir($dirPath);
            
        }
        
    }
    
    public function onLogin(PlayerLoginEvent $event) {
    
        $player = $event->getPlayer();
        if (!is_file("/home/flamelabs/Test/MLGRush/players/" . $player->getName() . ".yml")) {
        
            $playerfile = new Config("/home/flamelabs/Test/MLGRush/players/" . $player->getName() . ".yml", Config::YAML);
            $playerfile->set("Stick", 0);
            $playerfile->set("PickAxe", 1);
            $playerfile->set("Block", 2);
            $playerfile->save();
            
        }
        
    }
    
    public function onJoin(PlayerJoinEvent $event)
    {

        $player = $event->getPlayer();
        $config = $this->getConfig();
        $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
        $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
        $player->teleport($spawn, 0, 0);
        $player->setGamemode(0);
        $player->setHealth(20);
        $player->setFood(20);
        $player->getInventory()->clearAll();
        $player->removeAllEffects();
        $player->setAllowFlight(false);
        $all = $this->getServer()->getOnlinePlayers();
        if ($config->get("ingame") === true) {
        	
        	$event->setJoinMessage("");
        	$player->getInventory()->clearAll();
            $player->setGamemode(3);
            $level = $this->getServer()->getLevelByName($config->get("Arena"));
            $af = new Config($this->getDataFolder() . "/" . $config->get("Arena") . ".yml", Config::YAML);
            $player->teleport(new Position($af->get("s1x"), $af->get("s1y")+1, $af->get("s1z"), $level));
        
        } else {
        	
        	$event->setJoinMessage(Color::GRAY . "> " . Color::DARK_GRAY . "> " . $player->getName() . Color::GRAY . " hat den Server Betreten!");
        	
        if ($this->players === 0) {
        	
        	$this->players++;
            $config->set("player1", $player->getName());
            $player->setGamemode(0);
            $config->save();
            
        } else if ($this->players === 1) {
        	
        	$this->players++;
            $config->set("player2", $player->getName());
            $player->setGamemode(0);
            $config->save();
              
        } else if ($this->players === 2) {
        	
        	$player->transfer("134.255.240.177", 19132);
        	
        }
        
        }
        
    }
    
    public function onQuit(PlayerQuitEvent $event) {
    	
    	$player = $event->getPlayer();
        $event->setQuitMessage(Color::GRAY . "< " . Color::DARK_GRAY . "< " . $player->getDisplayName() . Color::GRAY . " hat den Server verlassen!");
        $config = $this->getConfig();
        if ($config->get("ingame") === false) {
        	
        	if ($player->getName() === $config->get("player1")) {
        	
        	    $this->players--;
        	    $p2 = $config->get("player2");
                
                $config->set("player1", $p2);
                $config->set("player2", "");
                $config->save();
                
            } else if ($player->getName() === $config->get("player2")) {
            	
            	$this->players--;
            	$p2 = $config->get("player2");
                
                $config->set("player2", "");
                $config->save();
            	
            }
        	
        } else {
        	
        	if ($this->players < 1) {
        	
        	    $this->players = 0;
        
            } else {
        	
        	if ($player->getName() === $config->get("player1")) {
        	
        	    $this->players--;
        
            } else if ($player->getName() === $config->get("player2")) {
        	
        	    $this->players--;
        
            }
            
            }
        	
        }
        
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
    	
    	switch ($command->getName()) {
    	
    	    case "MLGRush":
            if (isset($args[0])) {
            	
            	if (strtolower($args[0]) === "lobby") {
            	
            	    if ($sender->isOp()) {
            	
            	        if (isset($args[1])) {
            	
            	            $config = $this->getConfig();
                            $config->set("Server", $args[1]);
                            $config->save();
                            $sender->sendMessage($this->prefix . "Der " . Color::GOLD . "Server Name " . Color::WHITE . "wurde gesetzt!");
                            
                        }
            	
                    }
                    
                } else if (strtolower($args[0]) === "make") {
                	
                	if ($sender->isOp()) {
                	
                        if (isset($args[1])) {
                        	
                        	if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1])) {
                        	
                        	   if (!$this->getServer()->getLevelByName($args[1]) instanceof Level) {
                                    	
                                        $this->getServer()->loadLevel($args[1]);
                                        
                                    }
                                    
                                    $spawn = $this->getServer()->getLevelByName($args[1])->getSafeSpawn();
                                    $this->getServer()->getLevelByName($args[1])->loadChunk($spawn->getX(), $spawn->getZ());
                                    $sender->teleport($spawn, 0, 0);
                                    $config = $this->getConfig();
                                    $config->set("Arena", $args[1]);
                                    $sender->sendMessage($this->prefix . "Du hast die Arena " . Color::RED . $args[1] . Color::WHITE . " ausgewaehlt. Jetzt musst du auf den Spawn fuer den Blauen Spieler tippen");
                                    $this->mode++;
                                    return true;
                                    
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        
        return true;
        
    }
    
    public function onInteract(PlayerInteractEvent $event) {
    	
    	$player = $event->getPlayer();
        $player->setFood(20);
        $player->setHealth(20);
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);
        $config = $this->getConfig();
        $item = $player->getInventory()->getItemInHand();
        $af = new Config($this->getDataFolder() . "/" . $config->get("Arena") . ".yml", Config::YAML);
        if ($this->mode === 1 && $player->isOp()) {
        	
        	$af->set("s1x", $block->getX() + 0.5);
            $af->set("s1y", $block->getY() + 1);
            $af->set("s1z", $block->getZ() + 0.5);
            $af->save();
            
            $player->sendMessage($this->prefix . "Jetzt den Roten Spawn");
            $this->mode++;
            
        } else if ($this->mode === 2 && $player->isOp()) {
        	
        	$af->set("s2x", $block->getX() + 0.5);
            $af->set("s2y", $block->getY() + 1);
            $af->set("s2z", $block->getZ() + 0.5);
            $af->save();
            
            $player->sendMessage($this->prefix . "Jetzt den Blauen Block");
            $this->mode++;
            
        } else if ($this->mode === 3 && $player->isOp()) {
        	
        	if ($player->getLevel()->getBlock(new Vector3($block->getX() + 1, $block->getY(), $block->getZ()))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX() + 1, $block->getY(), $block->getZ()));
        	    $af->set("sb1x", $block->getX());
                $af->set("sb1y", $block->getY());
                $af->set("sb1z", $block->getZ());
                $af->set("sb1x1", $block2->getX());
                $af->set("sb1y1", $block2->getY());
                $af->set("sb1z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX() - 1, $block->getY(), $block->getZ()))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX() - 1, $block->getY(), $block->getZ()));
        	    $af->set("sb1x", $block->getX());
                $af->set("sb1y", $block->getY());
                $af->set("sb1z", $block->getZ());
                $af->set("sb1x1", $block2->getX());
                $af->set("sb1y1", $block2->getY());
                $af->set("sb1z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() + 1))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() + 1));
        	    $af->set("sb1x", $block->getX());
                $af->set("sb1y", $block->getY());
                $af->set("sb1z", $block->getZ());
                $af->set("sb1x1", $block2->getX());
                $af->set("sb1y1", $block2->getY());
                $af->set("sb1z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() - 1))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() - 1));
        	    $af->set("sb1x", $block->getX());
                $af->set("sb1y", $block->getY());
                $af->set("sb1z", $block->getZ());
                $af->set("sb1x1", $block2->getX());
                $af->set("sb1y1", $block2->getY());
                $af->set("sb1z1", $block2->getZ());
                $af->save();
                
            }
            
            $player->sendMessage($this->prefix . "Jetzt den Roten Block");
            $this->mode++;
            
        } else if ($this->mode === 4 && $player->isOp()) {
        	
        	if ($player->getLevel()->getBlock(new Vector3($block->getX() + 1, $block->getY(), $block->getZ()))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX() + 1, $block->getY(), $block->getZ()));
        	    $af->set("sb2x", $block->getX());
                $af->set("sb2y", $block->getY());
                $af->set("sb2z", $block->getZ());
                $af->set("sb2x1", $block2->getX());
                $af->set("sb2y1", $block2->getY());
                $af->set("sb2z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX() - 1, $block->getY(), $block->getZ()))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX() - 1, $block->getY(), $block->getZ()));
        	    $af->set("sb2x", $block->getX());
                $af->set("sb2y", $block->getY());
                $af->set("sb2z", $block->getZ());
                $af->set("sb2x1", $block2->getX());
                $af->set("sb2y1", $block2->getY());
                $af->set("sb2z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() + 1))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() + 1));
        	    $af->set("sb2x", $block->getX());
                $af->set("sb2y", $block->getY());
                $af->set("sb2z", $block->getZ());
                $af->set("sb2x1", $block2->getX());
                $af->set("sb2y1", $block2->getY());
                $af->set("sb2z1", $block2->getZ());
                $af->save();
                
            }
            
            if ($player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() - 1))->getId() == 26) {
        	
        	    $block2 = $player->getLevel()->getBlock(new Vector3($block->getX(), $block->getY(), $block->getZ() - 1));
        	    $af->set("sb2x", $block->getX());
                $af->set("sb2y", $block->getY());
                $af->set("sb2z", $block->getZ());
                $af->set("sb2x1", $block2->getX());
                $af->set("sb2y1", $block2->getY());
                $af->set("sb2z1", $block2->getZ());
                $af->save();
                
            }
            
            $player->sendMessage($this->prefix . "Die Arena ist nun Spielbereit");
            $this->mode = 0;
            
            $this->copymap($this->getServer()->getDataPath() . "/worlds/" . $player->getLevel()->getFolderName(), $this->getDataFolder() . "/maps/" . $player->getLevel()->getFolderName());
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
            $player->teleport($spawn, 0, 0);
            
        }
        
    }
    
    public function onDamage(EntityDamageEvent $event) {
    	
    	$player = $event->getEntity();
        $config = $this->getConfig();
        if ($config->get("ingame") === false) {
        	
        	$event->setCancelled(true);
        
        } else {
        	
            $player->setHealth(20);
        	
        }
        
    }
    
    public function onDeath(PlayerDeathEvent $event) {
    	
    	$player = $event->getEntity();
    	$event->setDeathMessage("");
    
    }
    
    public function onRespawn(PlayerRespawnEvent $event) {
    	
    	$player = $event->getPlayer();
        $player->getInventory()->clearAll();
        $this->giveKit($player);
        
    }
    
    public function onPlace(BlockPlaceEvent $event) {
    
        $player = $event->getPlayer();
        $config = $this->getConfig();
        if ($config->get("ingame") === false) {
        	
        	$event->setCancelled();
        
        }
        
    }
    
    public function onBreak(BlockBreakEvent $event) {
    	
    	$player = $event->getPlayer();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $config = $this->getConfig();
        $af = new Config($this->getDataFolder() . "/" . $config->get("Arena") . ".yml", Config::YAML);
        foreach($player->getLevel()->getPlayers() as $p) {
        	
        	if ($config->get("ingame") === false) {
                	
                    $event->setCancelled();
                    
            } else if ($block->getId() === Block::BED_BLOCK) {
                	
                $event->setDrops(array());
            	if ($x === $af->get("sb1x") && $y === $af->get("sb1y") && $z === $af->get("sb1z")) {
            	
            	    if ($player->getName() === $config->get("player1")) {
            	
            	        $event->setCancelled(true);
                        $player->sendMessage($this->prefix . Color::RED . "Du kannst deinen Bett nicht abbauen!");
                        
                    } else {
                    	
                    	$this->pc2++;
                        $config->set("mreset", true);
                        $config->save();
                    	
                    }
                    
                } else if ($x === $af->get("sb1x1") && $y === $af->get("sb1y1") && $z === $af->get("sb1z1")) {
            	
            	    if ($player->getName() === $config->get("player1")) {
            	
            	        $event->setCancelled(true);
                        $player->sendMessage($this->prefix . Color::RED . "Du kannst deinen Bett nicht abbauen!");
                        
                    } else {
                    	
                    	$this->pc2++;
                        $config->set("mreset", true);
                        $config->save();
                    	
                    }
                    
                }
                
                if ($x === $af->get("sb2x") && $y === $af->get("sb2y") && $z === $af->get("sb2z")) {
            	
            	    if ($player->getName() === $config->get("player2")) {
            	
            	        $event->setCancelled(true);
                        $player->sendMessage($this->prefix . Color::RED . "Du kannst deinen Bett nicht abbauen!");
                        
                    } else {
                    	
                    	$this->pc1++;
                        $config->set("mreset", true);
                        $config->save();
                    	
                    }
                    
                } else if ($x === $af->get("sb2x1") && $y === $af->get("sb2y1") && $z === $af->get("sb2z1")) {
            	
            	    if ($player->getName() === $config->get("player2")) {
            	
            	        $event->setCancelled(true);
                        $player->sendMessage($this->prefix . Color::RED . "Du kannst deinen Bett nicht abbauen!");
                        
                    } else {
                    	
                    	$this->pc1++;
                        $config->set("mreset", true);
                        $config->save();
                    	
                    }
                    
                }
                	
            } else if ($block->getId() === Block::RED_SANDSTONE) {
                	
            	$event->setCancelled(false);
                
             } else {
                	
             	$event->setCancelled(true);
                
             }
        	
        }
    	
    }
    
    public function onMove(PlayerMoveEvent $event) {
    	
        $player = $event->getPlayer();
        $player->setFood(20);
        $player->setHealth(20);
        
    }
    
    public function giveKit(Player $player) {   	
        	
        $player->getInventory()->clearAll();
        $pf = new Config("/home/Test/MLGRush/players/" . $player->getName() . ".yml", Config::YAML);
        $enchantment = Enchantment::getEnchantment(12);
        $stick = Item::get(280, 0, 1);
        $stick->addEnchantment(new EnchantmentInstance($enchantment, 2));
        $pickaxe = Item::get(274, 0, 1);
        $block = Item::get(179, 0, 64);
        $player->getInventory()->setItem($pf->get("Stick"), $stick);
        $player->getInventory()->setItem($pf->get("PickAxe"), $pickaxe);
        $player->getInventory()->setItem($pf->get("Block"), $block);
        
    }
    
    public function delPlayer(Player $player) {
    	
    	$config = $this->getConfig();
        if ($player->getName() === $config->get("player1")) {
        	
        	$config->set("player1", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player2")) {
        	
        	$config->set("player2", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player3")) {
        	
        	$config->set("player3", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player4")) {
        	
        	$config->set("player4", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player5")) {
        	
        	$config->set("player5", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player6")) {
        	
        	$config->set("player6", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player7")) {
        	
        	$config->set("player7", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player8")) {
        	
        	$config->set("player8", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player9")) {
        	
        	$config->set("player9", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player10")) {
        	
        	$config->set("player10", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player11")) {
        	
        	$config->set("player11", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player12")) {
        	
        	$config->set("player12", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player13")) {
        	
        	$config->set("player13", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player14")) {
        	
        	$config->set("player14", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player15")) {
        	
        	$config->set("player15", "");
            $config->save();
        	
        } else if ($player->getName() === $config->get("player16")) {
        	
        	$config->set("player16", "");
            $config->save();
        	
        }
    	
    }
    
    public function spawn(Player $player) {
    	
    	$pos = $player->getPosition();
        $player->setSpawn($pos);
        
    }
    
    public function teleportIngame(Player $player) {
    	
    	$config = $this->getConfig();
        if (!$this->getServer()->getLevelByName($config->get("Arena")) instanceof Level) {
        	
            $this->getServer()->loadLevel($config->get("Arena"));
            
        }
        
        $level = $this->getServer()->getLevelByName($config->get("Arena"));
        $af = new Config($this->getDataFolder() . "/" . $config->get("Arena") . ".yml", Config::YAML);
        if ($player->getName() === $config->get("player1")) {
        	
        	$player->teleport(new Position($af->get("s1x"), $af->get("s1y")+1, $af->get("s1z"), $level));
        
        } else if ($player->getName() === $config->get("player2")) {
            
        	$player->teleport(new Position($af->get("s2x"), $af->get("s2y")+1, $af->get("s2z"), $level));
        
        } else {
        	
        	$player->teleport(new Position($af->get("s1x"), $af->get("s1y")+1, $af->get("s1z"), $level));
        	
        }
        
    }
	
}

class ResetMap extends Task {
	
	public function __construct($plugin)
    {

        $this->plugin = $plugin;

    }

    public function onRun($tick)
    {
    	
    	$level = $this->plugin->getServer()->getDefaultLevel();
        $config = $this->plugin->getConfig();
        $all = $this->plugin->getServer()->getOnlinePlayers();
        if ($config->get("ingame") === true) {
        	
        	foreach ($all as $player) {
        	
        	    if ($player->getName() === $config->get("player1")) {
        	
        	        $y = $player->getY();
                    if ($y <= 0) {
                    	
                    	$player->setHealth(20);
                        $player->setFood(20);
                        $this->plugin->teleportIngame($player);
                        $this->plugin->spawn($player);
                        $this->plugin->giveKit($player);
                    	
                    }
                    
                }
                
                if ($player->getName() === $config->get("player2")) {
        	
        	        $y = $player->getY();
                    if ($y <= 0) {
                    	
                    	$player->setHealth(20);
                        $player->setFood(20);
                        $this->plugin->teleportIngame($player);
                        $this->plugin->spawn($player);
                        $this->plugin->giveKit($player);
                    	
                    }
                    
                }
                
            }
        	
        	if ($config->get("mreset") === true) {
        	
        	    foreach ($all as $player) {
        	
        	        $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                    $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                    $player->teleport($spawn, 0, 0);
                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    
                }
                
                $config->set("mreset", false);
                $config->set("preset", true);
                $config->save();
        	    
        	    $levelname = $config->get("Arena");
                $lev = $this->plugin->getServer()->getLevelByName($levelname);
                $this->plugin->getServer()->unloadLevel($lev);
                $this->plugin->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                $this->plugin->copymap($this->plugin->getDataFolder() . "/maps/" . $levelname, $this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                $this->plugin->getServer()->loadLevel($levelname);
                
            }
            
            if ($config->get("preset") === true) {
            	
            	foreach ($all as $player) {
            	
            	    $player->setHealth(20);
                    $player->setFood(20);
                    $this->plugin->teleportIngame($player);
                    $this->plugin->spawn($player);
                    $this->plugin->giveKit($player);
                    $config->set("mreset", false);
                    $config->set("preset", false);
                    $config->save();
                    
                }
            	
            }
        	
        }
    	
    }
	
}

class PlayerSender extends Task
{
	
	public function __construct($plugin)
    {

        $this->plugin = $plugin;

    }

    public function onRun($tick)
    {
    	
    	$config = $this->plugin->getConfig();
        $all = $this->plugin->getServer()->getOnlinePlayers();
        $config->set("players", $this->plugin->players);
        $config->save();
        if (count($all) === 0) {

            if ($config->get("state") === true) {

                $config->set("ingame", false);
                $config->set("state", false);
                $config->set("reset", false);
                $config->set("rtime", 10);
                $config->set("time", 20);
                $config->set("playtime", 3600);
                $config->save();

            }

        }
    	
    }
	
}

class GameSender extends Task
{

    public function __construct($plugin)
    {

        $this->plugin = $plugin;

    }

    public function onRun($tick)
    {

        $level = $this->plugin->getServer()->getDefaultLevel();
        $config = $this->plugin->getConfig();
        $all = $this->plugin->getServer()->getOnlinePlayers();
        if ($config->get("ingame") === false) {

            if ($this->plugin->players < 2) {

                foreach ($all as $player) {

                    $player->sendPopup(Color::GRAY . ">> Warten auf weitere Spieler <<");

                }

            }

            if ($this->plugin->players >= 2) {

                $config->set("time", $config->get("time") - 1);
                $config->save();
                $time = $config->get("time") + 1;
                foreach ($all as $player) {
                	
                	$player->sendPopup(Color::GREEN . "Map: " . Color::WHITE . $config->get("Arena"));
                	
                }
                
                if ($time % 5 === 0 && $time > 0) {

                    foreach ($all as $player) {

                        $player->sendMessage(Color::DARK_PURPLE . ">> " . Color::WHITE . "Das Match startet in " . Color::DARK_PURPLE . $time . Color::WHITE . " Sekunden!");

                    }

                } else if ($time === 15) {
                
                	$config->set("state", true);
                    $config->save();
                	foreach ($all as $player) {

                        $player->sendMessage(Color::DARK_PURPLE . ">> " . Color::WHITE . "Das Match startet in " . Color::DARK_PURPLE . $time . Color::WHITE . " Sekunden!");

                    }
                	
                } else if ($time === 4 || $time === 3 || $time === 2 || $time === 1) {

                    foreach ($all as $player) {

                        $player->sendMessage(Color::DARK_PURPLE . ">> " . Color::WHITE . "Das Match startet in " . Color::DARK_PURPLE . $time . Color::WHITE . " Sekunden!");

                    }

                } else if ($time === 0) {

                    $config->set("ingame", true);
                    $config->set("state", true);
                    foreach ($all as $player) {

                        $player->setHealth(20);
                        $player->setFood(20);
                        $this->plugin->teleportIngame($player);
                        $this->plugin->spawn($player);
                        $this->plugin->giveKit($player);

                    }

                    $config->save();

                }

            }

        } else if ($config->get("ingame") === true) {

            $all = $this->plugin->getServer()->getOnlinePlayers();
            if ($this->plugin->players <= 1) {

                foreach ($all as $player) {

                    $player->getInventory()->clearAll();
                    $player->setHealth(20);
                    $player->setFood(20);
                    $player->removeAllEffects();
                    $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                    $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                    $player->teleport($spawn, 0, 0);
                    $player->sendMessage(Color::WHITE . "[" . Color::DARK_PURPLE . "+" . Color::WHITE . "] 100 Coins");
                    $pc = new Config("/home/Test/Coins/" . $player->getName() . ".yml", Config::YAML);
                    $pc->set("coins", $pc->get("coins")+100);
                    $pc->save();
                    $pf = new Config("/home/Test/players/" . $player->getName() . ".yml", Config::YAML);
                    $pf->set("wins", $pf->get("wins") + 1);
                    $pf->save();
                    $config->set("ingame", false);
                    $config->set("reset", true);
                    $config->set("rtime", 10);
                    $config->set("time", 20);
                    $config->set("playtime", 3600);
                    $config->set("player1", "");
                    $config->set("player2", "");
                    $this->plugin->pc1 = 0;
                    $this->plugin->pc2 = 0;
                    $config->save();
                    $this->plugin->players = 0;
                    $levelname = $config->get("Arena");
                    $lev = $this->plugin->getServer()->getLevelByName($levelname);
                    $this->plugin->getServer()->unloadLevel($lev);
                    $this->plugin->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                    $this->plugin->copymap($this->plugin->getDataFolder() . "/maps/" . $levelname, $this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                    $this->plugin->getServer()->loadLevel($levelname);

                }

            } elseif ($this->plugin->players >= 2) {

                $config->set("playtime", $config->get("playtime") - 1);
                $config->save();
                $time = $config->get("playtime") + 1;
                foreach ($all as $player) {
                	
                	if ($this->plugin->pc1 === $this->plugin->pc2) {
                	
                	    $player->sendPopup(Color::YELLOW . $config->get("player1") . Color::GRAY . " [ " . Color::YELLOW . $this->plugin->pc1 / count($all) . Color::GRAY . " ] / " . Color::YELLOW . $config->get("player2") . Color::GRAY . " [ " . Color::YELLOW . $this->plugin->pc2 / count($all) . Color::GRAY . " ]");
                
                    }
                    
                    if ($this->plugin->pc1 > $this->plugin->pc2) {
                	
                	    $player->sendPopup(Color::GREEN . $config->get("player1") . Color::GRAY . " [ " . Color::GREEN . $this->plugin->pc1 / count($all) . Color::GRAY . " ] / " . Color::RED . $config->get("player2") . Color::GRAY . " [ " . Color::RED . $this->plugin->pc2 / count($all) . Color::GRAY . " ]");
                
                    }
                   
                    if ($this->plugin->pc1 < $this->plugin->pc2) {
                	
                	    $player->sendPopup(Color::RED . $config->get("player1") . Color::GRAY . " [ " . Color::RED . $this->plugin->pc1 / count($all) . Color::GRAY . " ] / " . Color::GREEN . $config->get("player2") . Color::GRAY . " [ " . Color::GREEN . $this->plugin->pc2 / count($all) . Color::GRAY . " ]");
                
                    }
                    
                	if ($time === 0) {

                        $player->getInventory()->clearAll();
                        $player->setHealth(20);
                        $player->setFood(20);
                        $player->removeAllEffects();
                        $player->sendMessage($this->plugin->prefix . Color::GREEN . "Du hast das Match gewonnen!");
                        $this->plugin->getServer()->broadcastMessage($this->plugin->prefix . $player->getName() . Color::GREEN . " hat das Match in " . Color::WHITE . $config->get("Arena") . Color::GREEN . " Gewonnen!");
                        $spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                        $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
                        $player->teleport($spawn, 0, 0);
                        $config->set("ingame", false);
                        $config->set("reset", true);
                        $config->set("rtime", 10);
                        $config->set("time", 20);
                        $config->set("playtime", 3600);
                    $config->set("player1", "");
                    $config->set("player2", "");
                    $this->plugin->pc1 = 0;
                    $this->plugin->pc2 = 0;
                        $config->save();
                        $this->plugin->players = 0;
                        $levelname = $config->get("Arena");
                        $lev = $this->plugin->getServer()->getLevelByName($levelname);
                        $this->plugin->getServer()->unloadLevel($lev);
                        $this->plugin->deleteDirectory($this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                        $this->plugin->copymap($this->plugin->getDataFolder() . "/maps/" . $levelname, $this->plugin->getServer()->getDataPath() . "/worlds/" . $levelname);
                        $this->plugin->getServer()->loadLevel($levelname);

                    }

                }

            }

        } 
        
        if ($config->get("reset") === true) {

            $config->set("rtime", $config->get("rtime") - 1);
            $config->save();
            $time = $config->get("rtime") + 1;
            if ($time === 10) {
            	
            	$clouddata = new Config("/home/Test/Daten.yml", Config::YAML);
                $clouddata->set("ServerMessage", "Der Server: " . $config->get("Server") . " wird heruntergefahren!");
                $clouddata->set("ServerMessageStatus", true);
                $clouddata->set($config->get("Server"), false);
                $clouddata->save();
            	$this->plugin->getServer()->broadcastMessage(Color::DARK_PURPLE . ">> " . Color::WHITE . "Das Server restartet in " . Color::DARK_PURPLE . $time . Color::WHITE . " Sekunden!");
            	
            } else if ($time === 5) {
            	
            	$this->plugin->getServer()->broadcastMessage(Color::DARK_PURPLE . ">> " . Color::WHITE . "Das Server restartet in " . Color::DARK_PURPLE . $time . Color::WHITE . " Sekunden!");
            	
            } else if ($time === 0) {
            	
            	$clouddata = new Config("/home/Test/Daten.yml", Config::YAML);
                $clouddata->set("ServerMessage", "Der Server: " . $config->get("Server") . " wird hochgefahren!");
                $clouddata->set("ServerMessageStatus", true);
                $clouddata->set($config->get("Server"), true);
                $clouddata->save();
                foreach ($all as $player) {
                	
                	$player->transfer("82.211.44.7", 19132);
                	
                }
                
                $config->set("reset", false);
                $config->set("rtime", 10);
                $config->set("state", false);
                $config->save();
                $this->plugin->players = 0;
            	
            }

        }

        foreach ($all as $player) {

            $playerfile = new Config("/home/Test/players/" . $player->getName() . ".yml", Config::YAML);
            if ($playerfile->get("teleport") === true) {

                $playerfile->set("teleport", $playerfile->get("teleport") - 1);
                $playerfile->save();
                $time = $playerfile->get("teleport") + 1;
                if ($time === 0) {

                    $playerfile->set("teleport", false);
                    $playerfile->set("teleport", 2);
                    $playerfile->save();
                    $this->plugin->teleportIngame($player);
                    $this->plugin->spawn($player);
                    $this->plugin->giveKit($player);

                }


            }

        }

    }

}

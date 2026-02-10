<?php
require "../web/kernel/public.php";
require "$sPath/lib/classes/Thread.php";

if($argc != 3){
	echo "usage: runThreads.php <thread_count> <php script name with arguments>\n";
	echo "will run threads in infinite loop\n";
	exit(1);
}
echo "running {$argv[1]} threads\n";

$threads = array();
for($n = 0; $n < $argv[1]; $n++)
	$threads[] = Thread::create($argv[2]); 
do{
	$isActive = false;
	foreach($threads as $n => $thread){
		$s = $thread->listen();
		if($s != "")
			echo "[$n] ".$s;
		if(!$thread->isActive()){
			echo "restarting thread $n\n";
			$threads[$n] = Thread::create($argv[2]);
		}
	}
	usleep(100000);
} while(true);
foreach($threads as $thread)
	$thread->close();
echo "main thread done\n";
?>

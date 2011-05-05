Pronto Exception at <?php echo "$file:$line" ?> 

-------------------------------------------------------------------------
<?php echo $message ?> 
-------------------------------------------------------------------------

<?php if($errno != E_USER_ERROR): ?>
SOURCE:
-------------------------------------------------------------------------
<?php
	if($source_lines) {
		$count = $source_start + 1;
		foreach($source_lines as $l) {
			$curr = $count == $line ? '>' : ' ';
			echo sprintf("%s line %s:\t %s", $curr, $count++, $l);
		}
	} else {
		echo "Source unavailable.\n";
	}
?>
-------------------------------------------------------------------------

<?php endif ?>

BACKTRACE:
-------------------------------------------------------------------------
<?php
	if($backtrace) {
		echo "Method/Function\t\t\tCaller\n";
		echo "...............\t\t\t......\n";
		foreach($backtrace as $bt) {
			echo "{$bt['function']}\t\t\t{$bt['caller']}\n";
		}
	} else {
		echo "Backtrace unavailable.\n";
	}
?>
-------------------------------------------------------------------------


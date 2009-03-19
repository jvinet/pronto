Pronto Exception at <?php echo "$file:$line" ?> 

-------------------------------------------------------------------------
Exception making <?php echo $method ?> request at <?php echo $uri ?> 
<?php echo $message ?> 

PHP: <?php echo $file ?>, line <?php echo $line ?> 
Web: <?php echo "$method $uri" ?> 
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


REQUEST INFORMATION:
-------------------------------------------------------------------------<?php
	foreach($data as $name=>$global) {
		echo "\n{$name}:\t\t";
		if(!empty($global)) {
			// trim off the "Array (" and ")" portions from print_r()
			$o = array_slice(explode("\n", print_r($global, true)), 2);
			array_pop($o);
			array_pop($o);
			echo "\n".implode("\n", $o)."\n";
		} else {
			echo "No data.\n";
		}
	}
?>
-------------------------------------------------------------------------


OUTPUT:
-------------------------------------------------------------------------
<?php echo $output ? $output : "Nothing sent to browser." ?> 
-------------------------------------------------------------------------


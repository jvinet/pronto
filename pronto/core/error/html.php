<?php
/*
	 Error page is based on the 500 page from Django (by Wilson Miner)
	 and Webpy (Aaron Swartz).
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>Pronto Exception at <?php echo "$file:$line" ?></title>
	<style type="text/css">
			* { font-family: sans-serif; font-size: 13px; margin: 0; padding: 0; }
			h1 { font-size: 24px; }
			h2 { font-size: 18px; font-weight: normal; margin-bottom: 10px; }
			h3 { font-size: 16px; }

			body, html, #summary, #output, #source, #request { display: block; }

			code { font-family: Monospace, Courier, Courier New; font-size: 9pt; }
			.current-line { color: #f00; }

			#summary { background: #ffc; border-bottom: 1px solid #ddd; }
			#summary h1 { color: #222; }
			#summary h1, #summary h2 { font-weight: normal; }
			#summary * { color: #222; }
			#summary table { margin-top: 10px; }
			#summary th { padding-right: 5px; text-align: left; }

			#source { padding: 15px; background: #eee; border-bottom: 1px solid #ddd; display: block; }
			#source ul { margin-left: 10px; list-style: none; }
			#source .line-number { float: left; text-align: right; padding-right: 20px; }

			#trace { padding: 15px; background: #fff; border-bottom: 1px solid #ddd; display: block; }
			#trace ul { margin-left: 10px; list-style: none; }
			#trace .line-number { float: left; text-align: right; padding-right: 20px; }
			#trace th { text-align: left; padding-right: 20px; }
			#trace td { padding-right: 20px; }

			#summary, #request, #response, #explanation { padding: 15px; }

			#request { background: #f6f6f6; }

			.table { width: 100%; margin-bottom: 10px; }
			.table th { text-align: left; vertical-align: top; }
			.table td, .table th { padding: 3px; }
			.table caption { text-align: left; background: #555; color: #fff; }

			.table .source { border-collapse: collapse; background: #fff; border: 1px solid #ccc; width: 100%; }
			.table .varname { width: 20%; }
			.table .varname, .table .value { font-family: monospace; font-size: 11px; }

			#output { border-top: 1px solid #ddd; }
			#output { padding: 15px; background: #eee; border-bottom: 1px solid #ddd; }

			#explanation { background: #f6f6f6; }
	</style>
</head>

<body>
<div id="summary">
	<h1>Exception making <?php echo $method ?> request at <?php echo $uri ?></h1>
	<h2><?php echo $message ?></h2>
<table>
	<tr>
		<th>PHP</th>
		<td><?php echo $file ?>, line <?php echo $line ?></td>
	</tr>
	<tr>
		<th>Web</th>
		<td><?php echo "$method $uri" ?></td>
	</tr>
</table>
</div>

<?php if($errno != E_USER_ERROR): ?>
<div id="source">
	<h2>Source</h2>
	<?php
		if($source_lines) {
			$count = $source_start + 1;
			foreach($source_lines as $l) {
				$css = $count == $line ? ' class="current-line"' : '';
				$l = str_replace(array(' ',"\t"), array('&nbsp;','&nbsp; '), htmlspecialchars($l));
				$l = trim($l) == ''? '&nbsp;' : $l;
				$source .= sprintf('<li%s><code><span class="line-number">line %s.</span> %s</code></li>', $css, $count++, $l);
			}
			echo "<ul>{$source}</ul>";
		} else {
			echo "Source unavailable.";
		}
	?>
</div>
<?php endif ?>

<div id="trace">
	<h2>Backtrace</h2>
	<?php
		if($backtrace) {
			echo '<table><tr><th>Method/Function</th><th>Caller</th></tr>';
			foreach($backtrace as $bt) {
				echo "<tr><td>{$bt['function']}</td><td>{$bt['caller']}</td></tr>";
			}
			echo '</table>';
		} else {
			echo "Backtrace unavailable.";
		}
	?>
</div>

<div id="request">
	<h2>Request information</h2>
	<table class="table">
	<?php
		foreach($data as $name=>$global) {
			echo "<tr class=\"varname\"><th>{$name}</th>";
			if(!empty($global)) {
				echo "<td><table class=\"source\"><tr><th>Name</th><th>Value</th></tr>";
				foreach($global as $k=>$v) {
					echo "<tr><th class=\"varname\">{$k}</th><td class=\"value\">".htmlspecialchars($v)."</td></tr>";
				}
				echo "</table></td></tr>";
			} else {
				echo "<td>No data.</td>";
			}
		}
	?>
	</table>
</div>

<div id="output">
	<h2>Output</h2>
	<?php if($output): ?>
		<pre><?php echo $output ?></pre>
	<?php else: ?>
		<pre>Nothing sent to browser.</pre>
	<?php endif ?>
</div>

<div id="explanation">
	<p>You're seeing this error because DEBUG is enabled.  To disable this error output, disable DEBUG.</p>
</div>
</body>
</html>

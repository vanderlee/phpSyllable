<?php

	header ('Content-type: text/html; charset=utf-8');

	$text		= str_replace(array("\r", "\n"), ' ', <<<EOF
Dorothy lived in the midst of the great Kansas prairies, with Uncle
Henry, who was a farmer, and Aunt Em, who was the farmer's wife.  Their
house was small, for the lumber to build it had to be carried by wagon
many miles.  There were four walls, a floor and a roof, which made one
room; and this room contained a rusty looking cookstove, a cupboard for
the dishes, a table, three or four chairs, and the beds.  Uncle Henry
and Aunt Em had a big bed in one corner, and Dorothy a little bed in
another corner.  There was no garret at all, and no cellar--except a
small hole dug in the ground, called a cyclone cellar, where the family
could go in case one of those great whirlwinds arose, mighty enough to
crush any building in its path.  It was reached by a trap door in the
middle of the floor, from which a ladder led down into the small, dark
hole.
EOF
);
	
	$source		= isset($_REQUEST['source'])? $_REQUEST['source'] : $text;
	$language	= isset($_REQUEST['language'])? $_REQUEST['language'] : 'en-us';

	$languages = array(
		'af'					=> 'Afrikaans'
	,	'hyph-zh-latn-pinyin'	=> 'Chinese - Pinyin'
	,	'da'					=> 'Danish'
	,	'nl'					=> 'Dutch'
	,	'en-us'					=> 'English - American'
	,	'en-gb'					=> 'English - British'
	,	'de'					=> 'German'
	,	'fi'					=> 'Finnish'
	,	'fr'					=> 'French'
	,	'id'					=> 'Indonesian'
	,	'it'					=> 'Italian'
	,	'la'					=> 'Latin'
	,	'no'					=> 'Norwegian'
	,	'pl'					=> 'Polish'
	,	'pt'					=> 'Portuguese'
	,	'ru'					=> 'Russian'
	,	'sl'					=> 'Slovenian'
	,	'es'					=> 'Spanish'
	,	'sv'					=> 'Swedish'
	,	'tr'					=> 'Turkish'
	);
	asort($languages);

	// phpSyllable code
	require_once(dirname(__FILE__) . '/classes/autoloader.php');

    $syllable = new Syllable($language);
	$syllable->getCache()->setPath(dirname(__FILE__).'/cache');
	$syllable->getSource()->setPath(dirname(__FILE__).'/languages');
?><html>
	<head>
		<title>phpSyllable</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style>
			* {
				font-family: 'Segoe UI', Verdana, Arial, Helvetica, sans-serif;
				font-size: 14px;
			}

			html {
				padding: 1em 2em;
			}

			h1, h2, h3, h4, h5, h6 { margin: 0 0 10px 0; }

			h1 {	font-size: 180%;	}
			h4 {	font-size: 140%;	}
			h2 {	font-size: 140%;	}
			h5 {	font-size: 120%;	}
			h3 {	font-size: 120%;	}
			h6 {	font-size: 110%;	}

			h4, h5, h6 {
				font-weight: normal;
				font-style: italic;
				font-family: Georgia, Times, "Times New Roman", serif;
				color: #666;
			}

			.example {
				text-align: justify;
				border: solid 1px silver;
				padding: 1em;
				width: 180px;
				float: left;
				margin-right: 1em;
			}

			.debug-hyphen {
				background-color: #fc0;
				padding: 0 .2em;
				margin: 0 .1em;
			}

			hr {
				border: solid 1px #ccc;
				margin: 2em;
			}
		</style>
	</head>

	<body>
		<h1>phpSyllable</h1>
		<h4>PHP Hyphenation library based on Frank Liang's algorithm used in TeX.</h4>

		<form method="POST">
			<div>
				<select name="language">
					<?php foreach($languages as $value => $name) { ?>
						<option value="<?php echo $value; ?>" <?php echo $value == $language? 'selected="selected"' : '' ?>><?php echo $name; ?></option>
					<?php } ?>
				</select>
			</div>
			<div>
				<textarea name="source" cols="80" rows="10"><?php echo $source; ?></textarea>
			</div>
			<div>
				<button>Hyphenate</button>
			</div>
		</form>
		<hr/>
		<div class="example">
			<h2>Source</h2>
			<h5>Without hyphens</h5>
			<?php
				echo nl2br($source);
			?>
		</div>

		<div class="example">
			<h2>Soft-hyphens</h2>
			<h5>&amp;shy; entities</h5>
			<?php
				$syllable->setHyphen(new Syllable_Hyphen_Soft);
				echo nl2br($syllable->hyphenateText($source));
			?>
		</div>
		
		<div class="example">
			<h2>Hyphens</h2>
			<h5>All hyphen locations</h5>
			<?php
				$syllable->setHyphen('<span class="debug-hyphen">-</span>');
				echo nl2br($syllable->hyphenateText($source));
			?>
		</div>

		<div class="example">
			<h2>Zero-width spaces</h2>
			<h5>&amp;#8203; entities</h5>
			<?php
				$syllable->setHyphen(new Syllable_Hyphen_ZeroWidthSpace);
				echo nl2br($syllable->hyphenateText($source));
			?>
		</div>

		<div class="example">
			<h2>Dashes</h2>
			<h5>For pre-school reading</h5>
			<?php
				$syllable->setHyphen(new Syllable_Hyphen_Dash());
				echo nl2br($syllable->hyphenateText($source));
			?>
		</div>
	</body>
</html>
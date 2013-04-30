<?php

	header ('Content-type: text/html; charset=utf-8');

	require_once(dirname(__FILE__) . '/Syllable/Syllable.php');

    class Perf {
        private static $start;

        public static function Start() {
            self::$start = microtime(TRUE);
        }

        public static function Stop($label) {
            $end = microtime(TRUE);
            $overhead = microtime(TRUE);
            echo $label.': '.(($end - self::$start) - ($overhead - $end));
        }
    }

    //Perf::Start();
    $syllable = new Syllable('en-us');
	$syllable->getCache()->setPath(dirname(__FILE__).'/cache');
	$syllable->getSource()->setPath(dirname(__FILE__).'/languages');
    //Perf::Stop('Init');

	$english_text = 'If my poor Flatland friend retained the vigour of mind which he enjoyed when he began to compose these Memoirs, I should not now need to represent him in this preface, in which he desires, firstly, to return his thanks to his readers and critics in Spaceland, whose appreciation has, with unexpected celerity, required a second edition of his work; secondly, to apologize for certain errors and misprints (for which, however, he is not entirely responsible); and, thirdly, to explain one or two misconceptions. But he is not the Square he once was. Years of imprisonment, and the still heavier burden of general incredulity and mockery, have combined with the natural decay of old age to erase from his mind many of the thoughts and notions, and much also of the terminology, which he acquired during his short stay in Spaceland. He has, therefore, requested me to reply in his behalf to two special objections, one of an intellectual, the other of a moral nature.';
?><html>
	<head>
		<title>phpSyllable</title>
		<style>
			.example {
				text-align: justify;
				margin: 0 25%;
				border: solid 1px silver;
				padding: 1em;
			}

			.debug-hyphen {
				background-color: #fc0;
				padding: 0 .2em;
				margin: 0 .1em;
			}
		</style>
	</head>

	<body>
		<div style="width: 60px; float: right; text-align: justify; border: solid 1px silver; padding: 1em;">
			<?php
				ob_start();
				?>
				<html>
					<body>
						The content of this &copy; HTML webpage is <b>guarenteed</b> to be adequately split into syllables with the help of hyphenation.
					</body>
				</html>
				<?php
				$html = ob_get_clean();
				$syllable->setTreshold(Syllable::TRESHOLD_MOST);
				echo $syllable->hyphenateHtml($html);
				$syllable->setTreshold(Syllable::TRESHOLD_AVERAGE);
			?>
		</div>

		<p>
			<h1>phpSyllable</h1>
			<br/>TODO
			<br/>- HTML syntax
			<br/>- Other hyphenation splitter
			<br/>- Different languages
			<br/>- Different hyphen styles (visualize)
			<br/>- Case insensitivity
		</p>

		<h2>Without hyphenation</h2>
		<p class="example">
			<?php
				$syllable->setHyphen('<span class="debug-hyphen">-</span>');
				
				echo utf8_encode($english_text);
			?>
		</p>

		<h2>Hyphenated - Standard</h2>
		<p class="example">
			<?php
				echo utf8_encode($syllable->hyphenateText($english_text));
			?>
		</p>

		<h2>Hyphenated - All possible hyphens</h2>
		<p class="example">
			<?php
				$syllable->setTreshold(Syllable::TRESHOLD_MOST);
				echo utf8_encode($syllable->hyphenateText($english_text));
			?>
		</p>
	</body>
</html>
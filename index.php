<?php

	header ('Content-type: text/html; charset=utf-8');

	require_once(dirname(__FILE__) . '/Syllable.php');

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
    //Perf::Stop('Init');

	$english_text = 'Alice was beginning to get very tired of sitting by her sister on the bank, and of having nothing to do: once or twice she had peeped into the book her sister was reading, but it had no pictures or conversations in it, \'and what is the use of a book,\' thought Alice \'without pictures or conversation?\' So she was considering in her own mind (as well as she could, for the hot day made her feel very sleepy and stupid), whether the pleasure of making a daisy-chain would be worth the trouble of getting up and picking the daisies, when suddenly a White Rabbit with pink eyes ran close by her. There was nothing so VERY remarkable in that; nor did Alice think it so VERY much out of the way to hear the Rabbit say to itself, \'Oh dear! Oh dear! I shall be late!\' (when she thought it over afterwards, it occurred to her that she ought to have wondered at this, but at the time it all seemed quite natural); but when the Rabbit actually TOOK A WATCH OUT OF ITS WAISTCOAT-POCKET, and looked at it, and then hurried on, Alice started to her feet, for it flashed across her mind that she had never before seen a rabbit with either a waistcoat-pocket, or a watch to take out of it, and burning with curiosity, she ran across the field after it, and fortunately was just in time to see it pop down a large rabbit-hole under the hedge.';
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
				echo $syllable->hyphenateHTML($html);
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
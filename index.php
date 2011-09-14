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
	
Perf::Start();
	$hyph = new Syllable('nl');
Perf::Stop('Init');
?><html>
	<body>
		<p style="width: 50%; text-align: justify;">
			<?php
				$text = 'Er werd al ruim 10 jaar aandachtig geluisterd. Waarom zou het onwaarschijnlijke geval, dat ik zo graag veronderstelde, niet bewaarheid worden in het uiteindelijke resultaat? Waarlijk, er is geen reden te beredeneren en geen gedachte te bedenken die het tegendeel bewijzen zou.';				
				echo $hyph->hyphenateText($text);
			?>
		</p>
		
		<p style="width: 50%; text-align: justify;">
			<?php
				ob_start();
				?>
				<html>
					<body>
						De inhoud van deze &copy; HTML webpagina is zo goed als
						<b>verzekerd</b>
						van een adequate vertaling met behulp van verbindingsstreepjes.
					</body>
				</html>
				<?php
				$html = ob_get_clean();
//				$hyph->setTreshold(Syllable::TRESHOLD_MOST);
//				$hyph->setHyphen('-');
				echo $hyph->hyphenateHTML($html);
			?>
		</p>

		<h1>Plain</h1>
		<p style="width: 50%; text-align: justify;">
			<?php
				$text = 'Wat doe je als je veel wilt zeggen, maar toch beknopt wilt zijn? Je zoekt een formulering waarbij je zo min mogelijk woorden gebruikt. Lekker kort en bondig, want de lezer heeft weinig tijd.
	Zo denken veel schrijvers: korte tekst is goede tekst. Toch is het nodig om dat te nuanceren. Er schuilt namelijk een gevaar in korte teksten. In je drang om zo kort mogelijk te zijn, sluipen containerbegrippen de tekst binnen.
	Wat is een containerbegrip? Van Dale zegt: “een begrip zonder scherp afgebakende betekenis waaraan de taalgebruiker zelf nader invulling kan geven en dat op veel verschillende toestanden, gebeurtenissen of zaken wordt toegepast.”
	Een woord als ‘ding’ is misschien wel het het meest gebruikte containerbegrip. Het laat de lezer (of luisteraar) volledig de ruimte om te bedenken wat dat dan precies is. In die zin is het woord ‘containerbegrip’ trouwens ook een containerbegrip.
	Wat bestempelen mensen zelf als containerbegrip? Even googlen op het woord ‘containerbegrip’ leverde de volgende resultaten op: ‘dialoog’, ‘klantgerichtheid’, ‘crossmedia’, ‘competentie’. Voor de duidelijkheid: het gaat hier om teksten waarin de schrijvers deze woorden containerbegrippen noemden.
	Misschien denk je: wat is nou precies het probleem? Het probleem is dat containerbegrippen algemeen zijn en abstract. Ze laten veel ruimte voor de beoordeling van de lezer. Dat lijkt een voordeel. Maar je maakt het je lezer moeilijk. Hij moet namelijk gaan nadenken over wat jij (misschien wel) hebt bedoeld. En dat kost tijd.
	Je maakt je tekst dus juist vaag door algemene woorden te gebruiken. Dus wel lekker kort en bondig, maar niet helder!';
		
				echo utf8_encode($text);
			?>
		</p>
		
		<h1>Hyphenated</h1>
		<p style="width: 50%; text-align: justify;">
			<?php
				echo utf8_encode($hyph->hyphenateText($text));
			?>
		</p>
		
		<h1>Hyphenated - MOST</h1>
		<p style="width: 50%; text-align: justify;">
			<?php
				$hyph->setTreshold(Syllable::TRESHOLD_MOST);
				echo utf8_encode($hyph->hyphenateText($text));	
			?>
		</p>
	</body>
</html>
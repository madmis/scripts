<?php

header("Content-type: text/vnd.wap.wml"); 

$file_content = file_get_contents('http://xml.weather.co.ua/1.2/forecast/23?dayf=5');

$xml = simplexml_load_string($file_content);


print '<?xml version="1.0" encoding="UTF-8"?>
		<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">
		<wml> 
		<card title="' . $xml -> city -> name . ' (Обновлено: ' . date('d.m.Y H:i', strtotime($xml -> attributes() -> last_updated)) . '. Разработано: http://sd-group.org.ua)">';

print '<table width="100%" border="0"><tr><td>';

foreach ($xml -> forecast -> day as $day)
{ 
	//определяем знак
	if (strpos($day -> t -> min, '-') !== false)
	{
		$sign_char_min = '';
	}
	else
	{
		$sign_char_min = '+';
	}
	
	if (strpos($day -> t -> max, '-') !== false)
	{
		$sign_char_max = '';
	}
	else
	{
		$sign_char_max = '+';
	}

	switch ($day -> attributes() -> hour)
	{
		case 3:
			$time_day = 'ночь';
			break;

		case 9:
			$time_day = 'утро';
			break;

		case 15:
			$time_day = 'день';
			break;

		case 21:
			$time_day = 'вечер';
			break;
	}
	
	// определяем напраление ветра
	if ($day -> wind -> rumb >= 0 && $day -> wind -> rumb < 20)
	{
		$direct_wind = 'С';
	}
	else if ($day -> wind -> rumb >= 20 && $day -> wind -> rumb < 35)
	{
		$direct_wind = 'С, С-В';
	}
	else if ($day -> wind -> rumb >= 35 && $day -> wind -> rumb < 55)
	{
		$direct_wind = 'С-В';
	}
	else if ($day -> wind -> rumb >= 55 && $day -> wind -> rumb < 70)
	{
		$direct_wind = 'В, С-В';
	}
	else if ($day -> wind -> rumb >= 70 && $day -> wind -> rumb < 110)
	{
		$direct_wind = 'В';
	}
	else if ($day -> wind -> rumb >= 125 && $day -> wind -> rumb < 145)
	{
		$direct_wind = 'Ю-В';
	}
	else if ($day -> wind -> rumb >= 145 && $day -> wind -> rumb < 160)
	{
		$direct_wind = 'Ю, Ю-В';
	}
	else if ($day -> wind -> rumb >= 160 && $day -> wind -> rumb < 200)
	{
		$direct_wind = 'Ю';
	}
	else if ($day -> wind -> rumb >= 200 && $day -> wind -> rumb < 215)
	{
		$direct_wind = 'Ю, Ю-З';
	}
	else if ($day -> wind -> rumb >= 215 && $day -> wind -> rumb < 235)
	{
		$direct_wind = 'Ю-З';
	}
	else if ($day -> wind -> rumb >= 235 && $day -> wind -> rumb < 250)
	{
		$direct_wind = 'З, Ю-З';
	}
	else if ($day -> wind -> rumb >= 250 && $day -> wind -> rumb < 290)
	{
		$direct_wind = 'З';
	}
	else if ($day -> wind -> rumb >= 290 && $day -> wind -> rumb < 305)
	{
		$direct_wind = 'З, С-З';
	}
	else if ($day -> wind -> rumb >= 305 && $day -> wind -> rumb < 325)
	{
		$direct_wind = 'С-З';
	}
	else if ($day -> wind -> rumb >= 325 && $day -> wind -> rumb < 340)
	{
		$direct_wind = 'С, С-З';
	}
	else if ($day -> wind -> rumb >= 340 && $day -> wind -> rumb < 360)
	{
		$direct_wind = 'С';
	}
	else
	{
		$direct_wind = 'Н';
	}
	
	if(!isset($last_day))
	{
		$last_day = $day -> attributes() -> date;
		$flag_print = true;
	}
	else if ((string) $last_day === (string) $day -> attributes() -> date)
	{
		$flag_print = false;
	}
	else
	{
		$last_day = $day -> attributes() -> date;
		$flag_print = true;
		echo '</tr></table></td><td>';
	}
	
?>
				<?php if ($flag_print) { ?>
				<table style="border: 1px solid #78A3C8;">
					<tr>
						<td colspan="4" align="center" style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8; background-color: #78A3C8; color: #FFFFFF; font-weight: bold; font-size: 10px;">
							<?php if ($flag_print) { echo date('d.m.Y', strtotime($day -> attributes() -> date)); } ?>
						</td>
					</tr>
					<tr>
				<?php } ?>
						<td style="border: 1px solid #78A3C8;">
					<div style="border: 0px solid #000000; text-align: center; font-size: 10px;">
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8; background-color: #EAEAEA;"><?php echo $time_day; ?></p>
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8; background-color: #78A3C8; color: #FFFFFF; font-weight: bold;"><?php echo $sign_char_min . $day -> t -> min . '...' . $sign_char_max . $day -> t -> max ?></p>
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8;"><img src="clipart/<?php echo $day -> pict ?>" /></p>
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8;"><?php echo $day -> ppcp ?>%</p>
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8;"><?php echo $day -> p -> min . '-' . $day -> p -> max ?></p>
						<p style="margin: 0px; padding: 2px; border-bottom: 1px solid #78A3C8; background-color: #FF9700; color: #FFFFFF; font-weight: bold;"><?php echo $direct_wind ?><br /><?php echo $day -> wind -> min . '-' . $day -> wind -> max ?> м/с</p>
						<p style="margin: 0px; padding: 2px; border-bottom: 0px solid #78A3C8;"><?php echo $day -> hmid -> min . '%-' . $day -> hmid -> max ?>%</p>
					</div>
						</td>
<?php
}


?>
	</tr>
</table>
		</td>
	</tr>
</table>


<table border="1">
	<tr>
		<td colspan="4" style="background: #FFFFFF; text-align: center; font-size: 10px;">
			Дата
		</td>
	</tr>
	<tr>
		<td>
			<div style="border: 0px solid #000000; text-align: center; font-size: 10px;">
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Время суток</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Температура</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Картинка</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Вероятность осадков в % от 0 до 100</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Давление (мм. ртутного столба)</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Скорость ветра (м/с) : направление ветра *</p>
				<p style="margin: 0px; border-bottom: 1px solid #000000;">Влажность в % от 0 до 100</p>
			</div>
		</td>
	</tr>
</table>

<p style="margin: 0px; font-size: 10px;">* Направление в градусах. 0&#176; - север; 90&#176; - восток; 180&#176; - юг; 270&#176; - запад;</p>

</card></wml>
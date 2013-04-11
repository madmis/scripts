<?php
/********************************************************
	SD-PR
	powered by Script Developers Group (SD-Group)
	email: info@sd-group.org.ua
	url: http://sd-group.org.ua/
	Copyright 2010-2015 (c) SD-Group
	All rights reserved
=========================================================
	Функции получения ТИЦ и вычисления PR
	взяты из готового скрипта (кто автор не знаю)
=========================================================
	Скрипт вычисления Google PageRank и Яндекс-ТИЦ
********************************************************/
/**
* @package
* @todo
*/

	require_once 'pr.class.php';
	$pr = new pr();
?>

<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="content-language" content="ru">
	<title>PHP-скрипт определения ТИЦ (Яндекс) и PR (Google Pagerank) сайта!</title>
	<meta content="проверка ТИЦ и PR сайта, скрипт проверки ТИЦ, скрипт проверки PR, скрипт проверки Google Pagerank, проверка индекса тиц и pr, скрипт проверки ТИЦ Яндекса, определение ТИЦ и PR сайта, скрипт определения ТИЦ и PR сайта" name="Keywords">
	<meta content="PHP-скрипт определения ТИЦ (Яндекс) и PR (Google Pagerank) сайта!" name="Description">
</head>
<body style="margin-top: 100px;">
<table width="100%">
	<tr>
		<td align="center">
			<form method="post" action="index.php" style="background-repeat: repeat-x; background-image: url(bg.png); width: 450px; height: 100px;">
				<p>Введите адрес (например, <b>yandex.ru</b> или <b>google.ru</b>):</p>
				<p><input style="width: 200px;" type="text" name="url" value="<?php if (isset($_POST['url'])) echo $_POST['url']; ?>"></p>
				<p><input style="width: 200px;" type="submit" value="Определение ТИЦ и PR"></p>
			</form>
 
<?php
	if (isset($_POST['url']))
	{
?>
			<table cellspacing="2" cellpadding="10">
				<tr>
					<td style="border: 1px solid #DDDDDD;">
						&nbsp;
					</td>
					<td title="Яндекс ТИЦ" style="font-weight: bold; border: 1px solid #DDDDDD;" align="center">
						Яндекс ТИЦ
					</td>
					<td title="Google PageRank" style="font-weight: bold; border: 1px solid #DDDDDD;" align="center">
						Google PageRank
					</td>
				</tr>
				<tr>
<?php
   		print '<td style="border: 1px solid #DDDDDD;">' . $_POST['url'] . '</td>'
   			. '<td align="center" style="border: 1px solid #DDDDDD;">' . $pr -> getTIC($_POST['url']) . '</td>'
   			. '<td align="center" style="border: 1px solid #DDDDDD;">' . $pr -> getPageRank($_POST['url']) . '</td>';
	}
?> 
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
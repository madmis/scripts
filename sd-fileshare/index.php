<?php

/**
 * SD-FileShare
 * powered by Script Developers Group (SD-Group)
 * email: info@sd-group.org.ua
 * url: http://sd-group.org.ua/
 * Copyright 2010-2015 (c) SD-Group
 * All rights reserved
  =========================================================
  SD-FileShare - скрипт позволюящий вывести файлы определенного каталога для закачки,
  с учетом количества закачек файлов.
  Скрипт выводит имя файла со ссылкой на закачку, размер файла в Кб,
  дату последнего изменения файла и количество скачиваний файла.

  ЛИЦЕНЗИЯ
  Скрипт абсолютно бесплатный.
  Продажа и любое коммерческое распространение требует обязательного согласования с автором скрипта.
  Вы можете изменять любой код на свой страх и риск,
  при этом Автор (madmis) не несёт ответственности за работу скрипта.
  Все права на скрипт принадлежат SD-Group.
  При использовании Вами этого скрипта Вы автоматически соглашаетесь с этим Лицензионным соглашением.

  ОПИСАНИЕ
  PHP-скрипт.
  Выводит все файлы из указанной папки, со ссылкой на закачку файла.
  Ведется учет закачек файла (счетчик хранится в текстовом файле).
  Прямого доступа к файлам нет. Файлы отдаются PHP-скриптом.

  ПРЕДУПРЕЖДЕНИЯ
  Под учетом скачиваний подразумевается клик по ссылке.
  Если после клика файл не был скачан, это все равно учитывается как закачка

  При работе скрипта производится полная загрузка всего файла в память,
  что при больших его размерах может привести к ее переполнению.
  Рекомендуется использовать данный скрипт только для небольших файлов.
  О том, как доработать скрипт для больших файлов читайте
  по ссылке http://forum.sd-group.org.ua/index.php/topic,50.0.html

  ТРЕБОВАНИЯ
  PHP не ниже 5-ой версии.

  УСТАНОВКА
  Очистить файл counter.dat (файл может быть переименован в любой другой.
  В случае смены имени файла, на забудьте указать его имя в файле index.php,
  константа COUNTER_FILE_NAME).

  Открыть на редактирование файл index.php и изменить значения констант
  COUNTER_FILE_NAME и FILES_DIR на необходимые. Сохранить и закрыть файл.

  Скопировать файлы index.php, файл счетчика (counter.dat или ваш), .htaccess
  и папку (download или вашу, с вложенным в нее файлом .htaccess) на хостинг.

  Загрузить в папку файлов (download или вашу) доступные для загрузки файлы.

  Все. По идее скрипт должен работать без проблем.
  В случае возникновения проблем пишите на форум (http://forum.sd-group.org.ua/index.php/topic,54.0.html).
 */

///////////////////////////////////////////////////////////////////////
//			КОНСТАНТЫ
///////////////////////////////////////////////////////////////////////
// имя файла счетчика
define('COUNTER_FILE_NAME', 'counter.dat');

// путь к каталогу, где хранятся файлы
// слеш ( / ) в конце пути обязятелен
define('FILES_DIR', 'download/');

// Логировать данные закачек
define('LOG_DATA', true);
// Имя файла логов (обязательное расширение файла .log)
define('LOG_FILE_NAME', 'data.log');

///////////////////////////////////////////////////////////////////////
//			КОНСТАНТЫ
///////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////
//			ФУНКЦИИ
///////////////////////////////////////////////////////////////////////
/**
 * функция возвращает массив файлов из указанной директории
 * @param (string) $dir - путь к дирректории
 * @return (array) - массив файлов в дирректории
 */
function getFilesInDir($dir = false) {
	$arrFiles = array();
	$dir = dir(getcwd() . '/' . FILES_DIR);
	
	// Получаме массив счетчика файлов
	// и помещаем счетчик в ощий массив файлов
	// это необходимо для сортировки
	$counter = @unserialize(@file_get_contents(COUNTER_FILE_NAME));

	while (false !== ($entry = $dir->read())) {
		if (is_file($dir->path . $entry) 
				&& $entry !== '.' && $entry !== '..' 
				&& $entry !== 'index.php' 
				&& $entry !== COUNTER_FILE_NAME 
				&& $entry !== '.htaccess') {
			$fileData = getFileData($entry);
			$fileData['counter'] = is_array($counter) && isset($counter[$entry]) ? $counter[$entry] : 0;
			$arrFiles[] = $fileData;
		}
	}
	$dir->close();

	if (!empty($_GET['ord'])) {
		$arrFiles = orderFilesArray($arrFiles);
	}

	return $arrFiles;
}

function orderFilesArray($arrFiles) {
	//var_dump($arrFiles);
	if (!empty($_GET['ord']) && !empty($_GET['by'])) {
		switch ($_GET['ord']) {
			case 'file':
				if ($_GET['by'] === 'asc') {
					uasort($arrFiles, 'sortByNameAsc');
				}
				break;
			case 'date':
				if ($_GET['by'] === 'asc') {
					uasort($arrFiles, 'sortByDateAsc');
				} else if ($_GET['by'] === 'desc') {
					uasort($arrFiles, 'sortByDateDesc');
				}
				break;
			case 'size':
				if ($_GET['by'] === 'asc') {
					uasort($arrFiles, 'sortBySizeAsc');
				} else if ($_GET['by'] === 'desc') {
					uasort($arrFiles, 'sortBySizeDesc');
				}
				break;
			case 'count':
				if ($_GET['by'] === 'asc') {
					uasort($arrFiles, 'sortByCounterAsc');
				} else if ($_GET['by'] === 'desc') {
					uasort($arrFiles, 'sortByCounterDesc');
				}
				break;
		}
	}
	return $arrFiles;
}

function sortByNameAsc($a, $b) {
	return strcasecmp($b['name'], $a['name']);
}
function sortByDateAsc($a1, $a2) {
	return $a1['date'] - $a2['date'];
}
function sortByDateDesc($a1, $a2) {
	return $a2['date'] - $a1['date'];
}
function sortBySizeAsc($a1, $a2) {
	return $a1['size'] - $a2['size'];
}
function sortBySizeDesc($a1, $a2) {
	return $a2['size'] - $a1['size'];
}
function sortByCounterAsc($a1, $a2) {
	return $a1['counter'] - $a2['counter'];
}
function sortByCounterDesc($a1, $a2) {
	return $a2['counter'] - $a1['counter'];
}

/**
 * функция получает все необходимые данные о файле
 * возвращает массив свойств
 * @param (string) $path - путь к файлу
 * @param (string) $file - имя файла
 * @return (array) - массив свойств файла
 */
function getFileData($file) {
	$arrData = @stat(getcwd() . '/' . FILES_DIR . $file);

	$sizekb = sprintf("%01.1f", $arrData['size'] / 1024);
	//$sizemb = sprintf("%01.3f", $arrData['size'] / 1048576);

	$arrFileData = array(
		'name' => $file,
		'size' => $arrData['size'],
		'date' => $arrData['mtime'],
		'sizekb' => $sizekb
			//'sizemb'		=> $sizemb
	);

	return $arrFileData;
}

/**
 * функция ищет в массиве необходимый файл
 * @param (string) $md5 - хеш имени файла
 * @return (bool)
 */
function find($file) {
	foreach (getFilesInDir() as $value) {
		if (in_array($file, $value)) {
			return true;
		}
	}

	return false;
}

function fileDownload($filename, $mimetype = 'application/octet-stream') {
	if (file_exists($filename)) {
		// Отправляем требуемые заголовки
		header($_SERVER["SERVER_PROTOCOL"] . ' 200 OK');
		// Тип содержимого. Может быть взят из заголовков полученных от клиента
		// при закачке файла на сервер. Может быть получен при помощи расширения PHP Fileinfo.
		header('Content-Type: ' . $mimetype);
		// Дата последней модификации файла        
		header('Last-Modified: ' . gmdate('r', filemtime($filename)));
		// Отправляем уникальный идентификатор документа, 
		// значение которого меняется при его изменении. 
		// В нижеприведенном коде вычисление этого заголовка производится так же,
		// как и в программном обеспечении сервера Apache
		header('ETag: ' . sprintf('%x-%x-%x', fileinode($filename), filesize($filename), filemtime($filename)));
		// Размер файла
		header('Content-Length: ' . (filesize($filename)));
		header('Connection: close');
		// Имя файла, как он будет сохранен в браузере или в программе закачки.
		// Без этого заголовка будет использоваться базовое имя скрипта PHP.
		// Но этот заголовок не нужен, если вы используете mod_rewrite для
		// перенаправления запросов к серверу на PHP-скрипт
		header('Content-Disposition: attachment; filename="' . basename($filename) . '";');
		// Отдаем содержимое файла
		echo file_get_contents($filename);
	} else {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
		header('Status: 404 Not Found');
	}
	exit;
}

/**
 * Отдаем файл на загрузку
 * @param string $fileName 
 */
function getFile($fileName) {
	$counter = @unserialize(@file_get_contents(COUNTER_FILE_NAME));
	if (!empty($counter)) {
		if (@array_key_exists($fileName, $counter)) {
			$counter[$fileName]++;
		} else {
			$counter[$fileName] = 1;
		}
	} else {
		$counter[$fileName] = 1;
	}

	@file_put_contents(COUNTER_FILE_NAME, @serialize($counter));

	// пишем в лог дополнительные данные
	if (LOG_DATA === true) {
		logData($fileName);
	}

	fileDownload(FILES_DIR . $_GET['f']);
}

/**
 * Логируем дополнительные данные
 * @param string $fileName 
 */
function logData($fileName) {
	// Формируем строку для записи в лог
	$str = '[' . date('d.m.Y H:i') . '] - '
		 . $fileName . ' - ' . $_SERVER['REMOTE_ADDR']
		 . PHP_EOL;
	$data = @file_get_contents(LOG_FILE_NAME);
	$data === false ? $data = '' : null;
	@file_put_contents(LOG_FILE_NAME, $data . $str);
	
}

///////////////////////////////////////////////////////////////////////
//			END ФУНКЦИИ
///////////////////////////////////////////////////////////////////////

if (!empty($_GET['f']) && find($_GET['f'])) {
	getFile($_GET['f']);
} else {
	print '<html><head></head><body style="margin: 40px;">';
	print '<table width="100%" border="0">'
			. '<tr style="text-align: center; background-color: #9C0001; color: #FFFFFF; font-weight: bold;">'
			. '<td style="border: 1px solid #ED8714; border-style: dashed; padding: 2px;">Файл '
			. '(<a href="index.php?ord=file&by=asc" style="color: #fff; text-decoration: none;" title="сортировка по возрастанию">&#8657;</a> '
			. '<a href="index.php?ord=file&by=desc" style="color: #fff; text-decoration: none;" title="сортировка по убыванию">&#8659;</a>)</td>'
			. '<td style="border: 1px solid #ED8714; border-style: dashed; padding: 2px;">Дата '
			. '(<a href="index.php?ord=date&by=asc" style="color: #fff; text-decoration: none;" title="сортировка по возрастанию">&#8657;</a> '
			. '<a href="index.php?ord=date&by=desc" style="color: #fff; text-decoration: none;" title="сортировка по убыванию">&#8659;</a>)</td>'
			. '<td style="border: 1px solid #ED8714; border-style: dashed; padding: 2px;">Размер (Кб) '
			. '(<a href="index.php?ord=size&by=asc" style="color: #fff; text-decoration: none;" title="сортировка по возрастанию">&#8657;</a> '
			. '<a href="index.php?ord=size&by=desc" style="color: #fff; text-decoration: none;" title="сортировка по убыванию">&#8659;</a>)</td>'
			. '<td style="border: 1px solid #ED8714; border-style: dashed; padding: 2px;">Загружено '
			. '(<a href="index.php?ord=count&by=asc" style="color: #fff; text-decoration: none;" title="сортировка по возрастанию">&#8657;</a> '
			. '<a href="index.php?ord=count&by=desc" style="color: #fff; text-decoration: none;" title="сортировка по убыванию">&#8659;</a>)</td>'
			. '</tr>';

	$counter = @unserialize(@file_get_contents(COUNTER_FILE_NAME));

	foreach (getFilesInDir() as $value) {
		print '<tr style="background-color: #FEFDDE;">';
		print '<td style="border: 1px solid #91A1B6; border-style: dashed; padding: 5px 30px;"><a href="index.php?f=' . $value['name'] . '" style="color: #CC3333;">' . $value['name'] . '</a></td>';
		print '<td style="border: 1px solid #91A1B6; border-style: dashed; padding: 5px; text-align: center;">' . date('d.m.Y H:i', $value['date']) . '</td>';
		print '<td style="border: 1px solid #91A1B6; border-style: dashed; padding: 5px; text-align: center;">' . $value['sizekb'] . '</td>';
		print '<td style="border: 1px solid #91A1B6; border-style: dashed; padding: 5px; text-align: center;">' . (isset($counter[$value['name']]) ? $counter[$value['name']] : 0) . '</td>';
		print '</tr>';
	}

	print '</table>';
	print '</body></html>';
}
?>
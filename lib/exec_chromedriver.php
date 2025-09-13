<?php
function getCowData($idNumbers) {
	if ($idNumbers) {
		//$idNumber = $idNumbers[0];
		foreach ($idNumbers as $i => $idNumber) {
			if (empty($idNumber)) { continue; }
			//元サイトへの負荷を考慮して、取得htmlをcacheする。ファイルがない|ファイル日付が1ヶ月以上経過の場合、取得処理をする。|主要な情報がない場合、再取得
			$cache_file = dirname(__DIR__). '/lib/exec_chromedriver/cache/'. $idNumber. '.html';
			if (!file_exists($cache_file) || (filectime($cache_file) > strtotime('+1 month')) || (filesize($cache_file) <= 0) || (checkValues($cache_file) == false)) {
				echo '+1 month data gathering process...<br>';
				touch($cache_file);
				$execFile = dirname(__DIR__). '/lib/exec_chromedriver/tajima_cow.py';
//				$execFile = dirname(__DIR__). '/lib/exec_chromedriver/test.py';
//				$execCmd = sprintf("echo '%s' | sudo -S python3 %s %s", getPwForWebServer(), $execFile, $idNumber);
				$execCmd = sprintf("python3 %s %s", $execFile, $idNumber);
//				$execCmd = sprintf("python3 /home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/lib/exec_chromedriver/test.py %s", $idNumber);
				echo shell_exec($execCmd);
//var_dump(shell_exec($execCmd));exit;
				sleep(1);
				$cache_data = file_get_contents($cache_file);
				$r_contents = $cache_data;
			} else {
				$r_contents = file_get_contents($cache_file);
			}

			$output = explode("\n", $r_contents, -1);
			$arr['labels'] = setArray('labels', $output);
			$arr['values'] = setArray('values', $output);
			$arr['label_charts'] = setArray('label_charts', $output);
			$arr['charts'] = setArray('charts', $output);

			//dumpArray($arr);
			$html = "";
			$html .= $r_contents;

			preg_match('/(.*)Cow/', $html, $sex);
			if (empty($sex)) {
				preg_match('/(.*)Steer/', $html, $sex);
			}
			$sex = preg_replace('/&nbsp;|\t/', '', $sex[1]);
			//var_dump($sex);

			if (!empty($arr['values'][5])) {
				$hanshoku = explode('_', $arr['values'][5]);
			} else {
				$hanshoku = array("", "", "");
			}
			$hanshoku_city = $hanshoku[0];
			$hanshoku_name = $hanshoku[2];

			if (!empty($arr['values'][6])) {
				$hiiku = explode('_', $arr['values'][6]);
			} else {
				$hiiku = array("", "", "");
			}
			$hiiku_city = $hiiku[0];
			$hiiku_name = $hiiku[2];

			$csvAr = array(
				$hanshoku_city, // 市町村
				'', //
				$hanshoku_name, // 繁殖生産者
				$arr['charts'][7], // 繁殖母牛名
				$arr['charts'][5], // 祖父種
				$arr['charts'][8], // 祖母名
				$arr['charts'][2], // 父種
				$arr['values'][3], // 生年月日
				$sex, // 性別
				$arr['values'][4],// 上場日
				'', // 格　付
				'', // 枝肉重量
				'', // 単　価
				$hiiku_name, // 肥育者名
				$arr['values'][0],// 個体識別番号
			);

			$csvs[] = implode(',', $csvAr);
			$csvsAr[$idNumber] = $csvAr;
		}
		return array('csvs' => $csvs, 'csvsAr' => $csvsAr);
	}
}

/**
 * 
 * 
 **/
function setArray($array_name, $output) {
	foreach ($output as $i => $d) {
	$d = preg_replace('/\t/', '', $d);
//var_dump($d);
		switch ($array_name) {
			case 'labels':
				preg_match('/<td class="label">(.+)<\/td>/', $d, $out);
				break;
			case 'values':
				preg_match('/<td>(.+)<\/td>/', $d, $out);
				$out = preg_replace('/<img(.+)>/', '', $out);
				break;
			case 'label_charts':
				preg_match('/<td class="label_chart">(.+)<\/td>/', $d, $out);
				break;
			case 'charts':
				preg_match('/<td class="Chart">(.+)<\/td>/', $d, $out);
				break;
		}

		if (!empty($out)) {
			$ret = preg_replace('/&nbsp;&nbsp;/', '_', $out[1]);
			$ret = preg_replace('/&nbsp;/', '', $ret);

			if(!empty($ret)) {
				$arr[] = $ret;
			}
		}
	}
	return $arr;
}

/**
 * 
 * 
 **/
function getPwForWebServer() {
	include(dirname(__DIR__). '/config.php');
//		var_dump($serverInfo);
	return (!empty($serverInfo['password'])) ? $serverInfo['password'] : null;
}

/**
 * 
 * 
 **/
function dumpArray($arr) {
	echo '<pre>';
	var_dump($arr['labels']);
	echo '</pre><br>';
	echo '<pre>';
	var_dump($arr['values']);
	echo '</pre><br>';
	echo '<pre>';
	var_dump($arr['label_charts']);
	echo '</pre><br>';
	echo '<pre>';
	var_dump($arr['charts']);
	echo '</pre><br>';
}

/**
 * 
 * 
 **/
function checkValues($cache_file = null) {
	$c_data = file_get_contents($cache_file);
	$r_contents = $c_data;
	$arr = setArrayProcess($r_contents);

	$sex = getValuleSex($r_contents);
	$hanshoku = getValueHanshoku($r_contents, $arr);
	$hiiku = getValueHiiku($r_contents, $arr);

	$csvAr = setCsvAr($sex, $hanshoku, $hiiku);
//	var_dump($csvAr);

	// 下記、主要な情報がない場合、falseを返す
	//   [0]:市町村、[2]:繁殖生産者、[13]:肥育者名
	if (empty($csvAr[0]) || empty($csvAr[2]) || empty($csvAr[13])) {
		return false;
	}
	return true;
}

/**
 * 取得した情報を配列にセット
 * 
 **/
function setArrayProcess($r_contents = null) {
	$output = explode("\n", $r_contents, -1);
	$arr['labels'] = setArray('labels', $output);
	$arr['values'] = setArray('values', $output);
	$arr['label_charts'] = setArray('label_charts', $output);
	$arr['charts'] = setArray('charts', $output);
	return $arr;
}

/**
 * キャッシュデータから情報を取得:「性別」
 * 
 **/
function getValuleSex($r_contents = null) {
	$html = "";
	$html .= $r_contents;

	preg_match('/(.*)Cow/', $html, $sex);
	if (empty($sex)) {
		preg_match('/(.*)Steer/', $html, $sex);
	}
	$sex = preg_replace('/&nbsp;|\t/', '', $sex[1]);
	return $sex;
}

/**
 * キャッシュデータから情報を取得:「繁殖者」
 * 
 **/
function getValueHanshoku($r_contents = null, $arr = null) {
	$html = "";
	$html .= $r_contents;

	if (!empty($arr['values'][5])) {
		$hanshoku = explode('_', $arr['values'][5]);
	} else {
		$hanshoku = array("", "", "");
	}
	return $hanshoku;
}

/**
 * キャッシュデータから情報を取得:「肥育者」
 * 
 **/
function getValueHiiku($r_contents = null, $arr = null) {
	$html = "";
	$html .= $r_contents;

	if (!empty($arr['values'][6])) {
		$hiiku = explode('_', $arr['values'][6]);
	} else {
		$hiiku = array("", "", "");
	}
	return $hiiku;
}

/**
 * CSVへ整形するため配列へセットする
 * 
 **/
function setCsvAr($sex = null, $hanshoku = null, $hiiku = null) {

	$hanshoku_city = $hanshoku[0];
	$hanshoku_name = $hanshoku[2];

	$hiiku_city = $hiiku[0];
	$hiiku_name = $hiiku[2];

	$csvAr = array(
		$hanshoku_city, // 市町村
		'', //
		$hanshoku_name, // 繁殖生産者
		$arr['charts'][7], // 繁殖母牛名
		$arr['charts'][5], // 祖父種
		$arr['charts'][8], // 祖母名
		$arr['charts'][2], // 父種
		$arr['values'][3], // 生年月日
		$sex, // 性別
		$arr['values'][4],// 上場日
		'', // 格　付
		'', // 枝肉重量
		'', // 単　価
		$hiiku_name, // 肥育者名
		$arr['values'][0],// 個体識別番号
	);

	return $csvAr;
}
?>

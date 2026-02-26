<?php
function getCowData($idNumbers) {
	if ($idNumbers) {
		$csvs = array();
		$csvsAr = array();
		foreach ($idNumbers as $i => $idNumber) {
			if (empty($idNumber)) { continue; }
			$idNumber = trim($idNumber);
			// 元サイトへの負荷を考慮して、取得htmlをcacheする。
			// ファイルがない|ファイル日付が1ヶ月以上経過の場合|主要な情報がない場合、再取得
			$cache_file = dirname(__DIR__). '/lib/exec_chromedriver/cache/'. $idNumber. '.html';
			if (!file_exists($cache_file) || (filectime($cache_file) > strtotime('+1 month')) || (filesize($cache_file) <= 0) || (checkValues($cache_file) == false)) {
				echo '+1 month data gathering process...<br>';
				touch($cache_file);
				$execFile = dirname(__DIR__). '/lib/exec_chromedriver/tajima_cow.py';
				$execCmd = sprintf("python3 %s %s 2>&1", $execFile, escapeshellarg($idNumber));
				echo shell_exec($execCmd);
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

			$html = "";
			$html .= $r_contents;

			preg_match('/(.*)Cow/', $html, $sex);
			if (empty($sex)) {
				preg_match('/(.*)Steer/', $html, $sex);
			}
			$sex = preg_replace('/&nbsp;?|\t/', '', $sex[1]);

			if (!empty($arr['values'][5])) {
				$hanshoku = explode('_', $arr['values'][5]);
			} else {
				$hanshoku = array("", "", "");
			}
			$hanshoku_city = $hanshoku[0];
			$hanshoku_name = isset($hanshoku[2]) ? $hanshoku[2] : "";

			if (!empty($arr['values'][6])) {
				$hiiku = explode('_', $arr['values'][6]);
			} else {
				$hiiku = array("", "", "");
			}
			$hiiku_city = $hiiku[0];
			$hiiku_name = isset($hiiku[2]) ? $hiiku[2] : "";

			$csvAr = array(
				$hanshoku_city, // 市町村
				'', //
				$hanshoku_name, // 繁殖生産者
				isset($arr['charts'][7]) ? $arr['charts'][7] : '', // 繁殖母牛名
				isset($arr['charts'][5]) ? $arr['charts'][5] : '', // 祖父種
				isset($arr['charts'][8]) ? $arr['charts'][8] : '', // 祖母名
				isset($arr['charts'][2]) ? $arr['charts'][2] : '', // 父種
				isset($arr['values'][3]) ? $arr['values'][3] : '', // 生年月日
				$sex, // 性別
				isset($arr['values'][4]) ? $arr['values'][4] : '', // 上場日
				'', // 格　付
				'', // 枝肉重量
				'', // 単　価
				$hiiku_name, // 肥育者名
				isset($arr['values'][0]) ? $arr['values'][0] : '', // 個体識別番号
			);

			$csvs[] = implode(',', $csvAr);
			$csvsAr[$idNumber] = $csvAr;
		}
		return array('csvs' => $csvs, 'csvsAr' => $csvsAr);
	}
}

/**
 * HTMLの行を正規表現でパースして配列にセット
 * &nbsp と &nbsp; の両方に対応（元サイトがセミコロンなしの場合あり）
 **/
function setArray($array_name, $output) {
	$arr = array();
	foreach ($output as $i => $d) {
		$d = preg_replace('/\t/', '', $d);
		$out = array();
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
			// &nbsp;&nbsp; または &nbsp&nbsp のパターンを _ に置換
			$ret = preg_replace('/(&nbsp;?\s*){2,}/', '_', $out[1]);
			// 残った単独の &nbsp; or &nbsp を除去
			$ret = preg_replace('/&nbsp;?/', '', $ret);
			if(!empty($ret)) {
				$arr[] = $ret;
			}
		}
	}
	return $arr;
}

/**
 * キャッシュデータの主要情報チェック
 **/
function checkValues($cache_file = null) {
	$c_data = file_get_contents($cache_file);
	if (empty($c_data)) { return false; }
	$r_contents = $c_data;

	$output = explode("\n", $r_contents, -1);
	$arr['labels'] = setArray('labels', $output);
	$arr['values'] = setArray('values', $output);
	$arr['label_charts'] = setArray('label_charts', $output);
	$arr['charts'] = setArray('charts', $output);

	$html = "";
	$html .= $r_contents;

	preg_match('/(.*)Cow/', $html, $sex);
	if (empty($sex)) {
		preg_match('/(.*)Steer/', $html, $sex);
	}
	$sex = preg_replace('/&nbsp;?|\t/', '', $sex[1]);

	if (!empty($arr['values'][5])) {
		$hanshoku = explode('_', $arr['values'][5]);
	} else {
		$hanshoku = array("", "", "");
	}

	if (!empty($arr['values'][6])) {
		$hiiku = explode('_', $arr['values'][6]);
	} else {
		$hiiku = array("", "", "");
	}

	// 主要な情報がない場合、falseを返す
	if (empty($hanshoku[0]) || empty(isset($hanshoku[2]) ? $hanshoku[2] : '') || empty(isset($hiiku[2]) ? $hiiku[2] : '')) {
		return false;
	}
	return true;
}
?>

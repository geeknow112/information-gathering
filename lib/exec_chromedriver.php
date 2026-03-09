<?php
/**
 * 但馬牛血統証明システムから情報を取得し、CSV用配列に整形する
 * Python依存なし、PHPのみで完結
 **/
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
				fetchAndCache($idNumber, $cache_file);
				sleep(1);
				$r_contents = file_get_contents($cache_file);
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

			// 繁殖生産者: "市町村_____名前" → 空要素を除去して取得
			$hanshoku_city = "";
			$hanshoku_name = "";
			if (!empty($arr['values'][5])) {
				$parts = array_values(array_filter(explode('_', $arr['values'][5]), 'strlen'));
				$hanshoku_city = isset($parts[0]) ? $parts[0] : "";
				$hanshoku_name = isset($parts[1]) ? $parts[1] : "";
			}

			// 肥育者: 同様の処理
			$hiiku_city = "";
			$hiiku_name = "";
			if (!empty($arr['values'][6])) {
				$parts = array_values(array_filter(explode('_', $arr['values'][6]), 'strlen'));
				$hiiku_city = isset($parts[0]) ? $parts[0] : "";
				$hiiku_name = isset($parts[1]) ? $parts[1] : "";
			}

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
 * 対象サイトにPOSTリクエストを送信し、取得したHTMLをcacheファイルに保存
 **/
function fetchAndCache($idNumber, $cache_file) {
	$url = 'http://www.tajimagyu-trace.com/trace_back.php';
	$post_data = array(
		'__EVENTTARGET' => 'submit_search',
		'__EVENTARGUMENT' => '',
		'id_number' => $idNumber,
		'trc_agreement' => '',
	);

	$context = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => 'Content-Type: application/x-www-form-urlencoded',
			'content' => http_build_query($post_data),
			'timeout' => 30,
		),
	));

	$response = @file_get_contents($url, false, $context);
	if ($response === false) {
		return;
	}

	// euc-jp → UTF-8 変換（eucJP-win で㈱等のNEC特殊文字に対応）
	$html = mb_convert_encoding($response, 'UTF-8', 'eucJP-win');

	// cacheディレクトリがなければ作成
	$cache_dir = dirname($cache_file);
	if (!is_dir($cache_dir)) {
		mkdir($cache_dir, 0755, true);
	}

	file_put_contents($cache_file, $html);
}

/**
 * HTMLの行を正規表現でパースして配列にセット
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
			if ($array_name === 'values') {
				// values: 2段階で&nbsp;を処理（SVN版の仕様を踏襲）
				// ※ 意図: 連続する&nbsp;はセパレータ（市町村と名前の区切り等）なので _ に変換
				//          単独の&nbsp;は単なるスペーサーなので除去
				// Stage1: 2個以上連続する &nbsp;? → _ 1つ（セパレータとして使用）
				$ret = preg_replace('/(&nbsp;?){2,}/', '_', $out[1]);
				// Stage2: 残った単独の &nbsp;? → 除去（単なるスペーサー）
				$ret = preg_replace('/&nbsp;?/', '', $ret);
				$ret = trim($ret, '_');
				$ret = trim($ret);
			} else {
				// charts等: &nbsp;? を単純に除去
				$ret = preg_replace('/&nbsp;?/', '', $out[1]);
				$ret = trim($ret);
			}
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

	// 繁殖生産者チェック
	$hanshoku_city = "";
	$hanshoku_name = "";
	if (!empty($arr['values'][5])) {
		$parts = array_values(array_filter(explode('_', $arr['values'][5]), 'strlen'));
		$hanshoku_city = isset($parts[0]) ? $parts[0] : "";
		$hanshoku_name = isset($parts[1]) ? $parts[1] : "";
	}

	// 肥育者チェック
	$hiiku_name = "";
	if (!empty($arr['values'][6])) {
		$parts = array_values(array_filter(explode('_', $arr['values'][6]), 'strlen'));
		$hiiku_name = isset($parts[1]) ? $parts[1] : "";
	}

	// 主要な情報がない場合、falseを返す
	if (empty($hanshoku_city) || empty($hanshoku_name) || empty($hiiku_name)) {
		return false;
	}
	return true;
}
?>

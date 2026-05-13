<?php
/**
 * 但馬牛血統証明システムから情報を取得し、CSV用配列に整形する
 * ※ データ整形ロジックはSVN版と完全同一。データ取得のみPHP化（Python依存なし）
 **/
function getCowData($idNumbers) {
	if ($idNumbers) {
		foreach ($idNumbers as $i => $idNumber) {
			if (empty($idNumber)) { continue; }
			$idNumber = trim($idNumber);
			// 元サイトへの負荷を考慮して、取得htmlをcacheする。ファイルがない|ファイル日付が1ヶ月以上経過の場合、取得処理をする。|主要な情報がない場合、再取得
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
			$sex = preg_replace('/&nbsp(?!;)/', '&nbsp;', $sex[1]);
			$sex = preg_replace('/&nbsp;|\t/', '', $sex);

			if (!empty($arr['values'][5])) {
				$hanshoku = explode('_', $arr['values'][5]);
			} else {
				$hanshoku = array("", "", "");
			}
			$hanshoku_city = $hanshoku[0];
			$hanshoku_name = normalizeHanshokuName($hanshoku[2]);

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
 * 対象サイトにPOSTリクエストを送信し、取得したHTMLをcacheファイルに保存
 * ※ SVN版ではPython(Selenium)で取得していた部分をPHPに置き換え
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
 * ※ SVN版と完全同一のロジック
 **/
function setArray($array_name, $output) {
	foreach ($output as $i => $d) {
	$d = preg_replace('/\t/', '', $d);
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
			// &nbsp（セミコロンなし）→ &nbsp;（セミコロン付き）に正規化
			$normalized = preg_replace('/&nbsp(?!;)/', '&nbsp;', $out[1]);
			// SVN版と同一の2段階処理
			$ret = preg_replace('/&nbsp;&nbsp;/', '_', $normalized);
			$ret = preg_replace('/&nbsp;/', '', $ret);

			if(!empty($ret)) {
				$arr[] = $ret;
			}
		}
	}
	return $arr;
}

/**
 * キャッシュデータの主要情報チェック
 * ※ SVN版と同一のロジック（ヘルパー関数を使わずインライン化）
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
	$sex = preg_replace('/&nbsp(?!;)/', '&nbsp;', $sex[1]);
	$sex = preg_replace('/&nbsp;|\t/', '', $sex);

	if (!empty($arr['values'][5])) {
		$hanshoku = explode('_', $arr['values'][5]);
	} else {
		$hanshoku = array("", "", "");
	}
	$hanshoku_city = $hanshoku[0];
	$hanshoku_name = normalizeHanshokuName($hanshoku[2]);

	if (!empty($arr['values'][6])) {
		$hiiku = explode('_', $arr['values'][6]);
	} else {
		$hiiku = array("", "", "");
	}
	$hiiku_name = $hiiku[2];

	// 主要な情報がない場合、falseを返す
	if (empty($hanshoku_city) || empty($hanshoku_name) || empty($hiiku_name)) {
		return false;
	}
	return true;
}

/**
 * 繁殖生産者名の正規化（全角スペース削除、㈱→(株)）
 **/
function normalizeHanshokuName($name) {
	$name = str_replace('　', '', $name);
	$name = str_replace('㈱', '(株)', $name);
	return $name;
}
?>

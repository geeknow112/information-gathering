        <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div><h2>情報収集（但馬牛血統情報）</h2>
<script>
function submit_search() {
        document.order_export.action = "";
        document.order_export.cmd_search.value = "on";
        document.order_export.submit();
}
</script>
<script src="https://lober-env-imp.work/wp-content/plugins/order-export/js/jquery.min.js"></script>
<script src="https://lober-env-imp.work/wp-content/plugins/order-export/js/encoding.min.js"></script>
<script>
const csvDownload = function(data, filename) {
//console.log(data);

// convert to sjis --
const csvData = [data.replace(/\n/g, '\r\n')].join('\r\n');

const unicodeList = [];
for (let i = 0; i < csvData.length; i += 1) {
  unicodeList.push(csvData.charCodeAt(i));
}

// 変換処理の実施
const shiftJisCodeList = Encoding.convert(unicodeList, 'sjis', 'unicode');
const uInt8List = new Uint8Array(shiftJisCodeList);
//-- convet to sjis

    // UTF BOM
    var bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
    // リンククリエイト
    var downloadLink = document.createElement("a");
    downloadLink.download = filename + ".csv";
    // ファイル情報設定
    downloadLink.href = URL.createObjectURL(new Blob([uInt8List], { type: "text/csv; charset=SJIS" }));
    downloadLink.dataset.downloadurl = ["text/csv; charset=SJIS", downloadLink.download, downloadLink.href].join(":");
    // イベント実行
    downloadLink.click();
}
</script>
    <form name="order_export" id="order_export" method="post" action="" enctype="multipart/form-data" onSubmit="submit()">
        <input type="hidden" name="type_no" id="type_no" value="">
        <label for="InputName">個体識別番号</label>
        <input type="text" name="id_number" id="id_number"  multiple="false" value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : '' ?>" />&nbsp;&nbsp;
        <input id="cmd_search" name="cmd_search" type="hidden" value="" />
        <input id="search" name="search" type="button" value="検索" onclick="submit_search();" />
        <p>(※カンマ区切りで複数入力可)</p>
        <br />
        <?php if (isset($makeCsvStatus) && ($makeCsvStatus == true)) {
                $dd = new DownloadDashboard(3);
        } ?>

<?php 
	//$header  = "市町村,,繁殖生産者,繁殖母牛名,祖父種,祖母名,父種,生年月日,性別,上場日,格　付,枝肉重量,単　価,肥育者名". PHP_EOL;
	$header  = "市町村		繁殖生産者	繁殖母牛名	祖父種	祖母名	父種	生年月日	性別	上場日	格　付	枝肉重量	単　価	肥育者名	個体識別番号". PHP_EOL;
	$csvs = array();
	
	$idNumbers = !empty($_POST['id_number']) ? explode(',', $_POST['id_number']) : null; 
	require_once dirname(__DIR__). '/information-gathering/lib/exec_chromedriver.php';
	$csvsAr = getCowData($idNumbers);
	$csvs = $csvsAr['csvs'];

	$result = !empty($csvs) ? $csvs : null;
?>

    <?php if(isset($result) && count($result)): ?>
<?php
//var_dump($result);

$cur_user = wp_get_current_user();
if ($cur_user->user_login == 'root') {
//	print_r($ret);
}

?>
<!--
	<input type="button" onclick="csvDownload(<?php echo $ret; ?>, 'test');" value="CSVエクスポート" /><br />
-->
        <p class="alert alert-success"><?php echo count($result) ?>件 取得しました。</p>
        <table border="1">
            <thead style="background: lightsteelblue;">
                <tr>
			<th width="200">市町村</th>
			<th width="80"></th>
			<th width="80">繁殖生産者</th>
			<th width="80">繁殖母牛名</th>
			<th width="80">祖父種</th>
			<th width="80">祖母名</th>
			<th width="80">父種</th>
			<th width="80">生年月日</th>
			<th width="80">性別</th>
			<th width="80">上場日</th>
			<th width="80">格　付</th>
			<th width="80">枝肉重量</th>
			<th width="80">単　価</th>
			<th width="80">肥育者名</th>
			<th width="100">個体識別番号</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result as $i => $row): $r = explode(',', $row); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r[0]) ?></td>
                        <td><?php echo htmlspecialchars($r[1]) ?></td>
                        <td><?php echo htmlspecialchars($r[2]) ?></td>
                        <td><?php echo htmlspecialchars($r[3]) ?></td>
                        <td><?php echo htmlspecialchars($r[4]) ?></td>
                        <td><?php echo htmlspecialchars($r[5]) ?></td>
                        <td><?php echo htmlspecialchars($r[6]) ?></td>
                        <td><?php echo htmlspecialchars($r[7]) ?></td>
                        <td><?php echo htmlspecialchars($r[8]) ?></td>
                        <td><?php echo htmlspecialchars($r[9]) ?></td>
                        <td><?php echo htmlspecialchars($r[10]) ?></td>
                        <td><?php echo htmlspecialchars($r[11]) ?></td>
                        <td><?php echo htmlspecialchars($r[12]) ?></td>
                        <td><?php echo htmlspecialchars($r[13]) ?></td>
                        <td><?php echo htmlspecialchars($r[14]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
<!--
        <p class="alert alert-danger">検索対象は見つかりませんでした。</p>
-->
    <?php endif; ?>

<br />
<br />
<br />
<textarea style="width:1200px; height:200px;" id="export_html">
<?php if (!empty($csvsAr)) { 
//	echo $header;
	foreach ($csvsAr['csvsAr'] as $idNumber => $data) {
		$data[14] = ""; // 末尾の個体識別番号削除
		echo implode('	', $data). PHP_EOL;
	}
} ?>
</textarea>
<input type="button" value="copy" onclick="copy_html();" />
<script>
function copy_html() {
        var textarea = document.getElementsByTagName("textarea")[0];
        textarea.select();
        document.execCommand("copy");
}
</script>


    </form>
         <!-- /.wrap --></div>



# Information Gathering Plugin

WordPressプラグイン - Seleniumを使用した情報収集システム

## セットアップ手順

### 1. 必要なソフトウェアのインストール

```bash
# Chrome 96のインストール
wget https://github.com/webnicer/chrome-downloads/raw/master/x64.deb/google-chrome-stable_96.0.4664.110-1_amd64.deb
sudo dpkg -i google-chrome-stable_96.0.4664.110-1_amd64.deb
sudo apt-mark hold google-chrome-stable

# ChromeDriver 96のシステムPATHへの配置
sudo cp tmp/124/chromedriver /usr/local/bin/chromedriver
sudo chmod +x /usr/local/bin/chromedriver
```

### 2. 設定ファイルの作成

```bash
# config.phpを作成（config.php.exampleを参考に）
cp config.php.example config.php
```

config.phpの内容例：
```php
<?php
$serverInfo = array(
    'software' => 'bitnami',
    'password' => 'YOUR_PASSWORD_HERE',
    'domain' => 'your-domain.com',
);
?>
```

### 3. 権限設定

```bash
# WEBサーバー用の権限設定
sudo chown -R daemon:daemon /opt/bitnami/wordpress/wp-content/plugins/information-gathering/
sudo chmod +x tmp/124/chromedriver
sudo chmod 755 lib/exec_chromedriver/cache/
```

### 4. 動作確認

```bash
# CLI実行テスト
sudo -u daemon python3 lib/exec_chromedriver/tajima_cow.py 1655580001

# WEB管理画面からの実行テスト
# WordPress管理画面 > Information Gathering で実行
```

## 必要な環境

- Chrome 96.0.4664.110
- ChromeDriver 96.0.4664.45
- Python 3.9+
- Selenium WebDriver

## 注意事項

- `config.php`は機密情報を含むため、Gitリポジトリには含まれていません
- ChromeDriverはシステムのPATH（/usr/local/bin/）に配置する必要があります
- WEBサーバー（daemon）ユーザーでの実行権限が必要です

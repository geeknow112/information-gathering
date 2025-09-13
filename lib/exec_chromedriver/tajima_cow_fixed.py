#!/usr/bin/env python3
# 但馬牛血統情報検索システム

import sys
import os
import json
from pathlib import Path

# 設定ファイルから環境情報を取得
def load_config():
    script_dir = Path(__file__).parent.parent.parent
    config_file = script_dir / 'runtime_config.json'
    
    if config_file.exists():
        with open(config_file, 'r') as f:
            return json.load(f)
    
    # デフォルト設定
    return {
        'site_packages_path': str(script_dir / 'tmp' / 'site-packages'),
        'cache_path': str(script_dir / 'lib' / 'exec_chromedriver' / 'cache'),
        'chromedriver_path': str(script_dir / 'tmp' / '124' / 'chromedriver'),
        'chrome_binary': '/usr/bin/google-chrome'
    }

# 設定読み込み
config = load_config()

# Python パス追加
sys.path.insert(0, config['site_packages_path'])

try:
    from selenium import webdriver
    from selenium.webdriver.chrome.options import Options
    from selenium.webdriver.common.keys import Keys
    from selenium.webdriver.chrome.service import Service
    from selenium.webdriver.common.by import By
except ImportError as e:
    print(f"Error importing selenium: {e}")
    sys.exit(1)

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 tajima_cow_fixed.py <id_number>")
        sys.exit(1)
    
    id_number = sys.argv[1]
    
    # Chrome オプション設定
    options = Options()
    options.add_argument('--headless')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')
    
    # Chrome バイナリパス設定（存在する場合）
    if os.path.exists(config['chrome_binary']):
        options.binary_location = config['chrome_binary']
    
    # ChromeDriver サービス設定
    service = None
    if os.path.exists(config['chromedriver_path']):
        service = Service(config['chromedriver_path'])
    
    try:
        # WebDriver 初期化
        if service:
            driver = webdriver.Chrome(service=service, options=options)
        else:
            driver = webdriver.Chrome(options=options)
        
        # 対象サイトにアクセス
        driver.get('http://www.tajimagyu-trace.com/trace_back.php')
        
        # 個体識別番号を入力
        input_element = driver.find_element(By.NAME, 'id_number')
        input_element.send_keys(id_number)
        
        # 検索実行
        search_button = driver.find_element(By.XPATH, "//a/img")
        search_button.click()
        
        # 結果取得
        page_source = driver.page_source
        
        # キャッシュファイルに保存
        cache_file = os.path.join(config['cache_path'], f'{id_number}.html')
        os.makedirs(os.path.dirname(cache_file), exist_ok=True)
        
        with open(cache_file, 'w', encoding='utf-8') as f:
            f.write(page_source)
        
        print(f"Data saved to: {cache_file}")
        
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)
    
    finally:
        if 'driver' in locals():
            driver.quit()

if __name__ == "__main__":
    main()

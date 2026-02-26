# tajima cow 検索をする（requests版）
# HTTPリクエストで直接HTMLを取得する

import sys
import os
import requests

def main():
    if len(sys.argv) < 2:
        sys.exit(1)

    id_number = sys.argv[1]

    # cacheファイルパス（スクリプトと同階層の cache/ ディレクトリ）
    script_dir = os.path.dirname(os.path.abspath(__file__))
    cache_dir = os.path.join(script_dir, 'cache')
    cache_file = os.path.join(cache_dir, id_number + '.html')

    # cacheディレクトリがなければ作成
    if not os.path.exists(cache_dir):
        os.makedirs(cache_dir)

    # POSTリクエストで但馬牛血統証明システムにアクセス
    url = 'http://www.tajimagyu-trace.com/trace_back.php'
    post_data = {
        '__EVENTTARGET': 'submit_search',
        '__EVENTARGUMENT': '',
        'id_number': id_number,
        'trc_agreement': '',
    }

    try:
        response = requests.post(url, data=post_data, timeout=30)
        # euc-jpエンコーディング対応
        response.encoding = 'euc-jp'
        html_content = response.text
    except requests.exceptions.RequestException as e:
        sys.exit(1)

    # HTMLをcacheファイルに保存（既存PHPのパース処理と互換性維持）
    with open(cache_file, mode='w', encoding='utf-8') as f:
        f.write(html_content)

if __name__ == '__main__':
    main()

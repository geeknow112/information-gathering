<?php
// 環境設定ファイル
class EnvironmentConfig {
    
    public static function getConfig() {
        // 環境検出
        $isProduction = self::isProductionEnvironment();
        
        if ($isProduction) {
            return self::getProductionConfig();
        } else {
            return self::getTestConfig();
        }
    }
    
    private static function isProductionEnvironment() {
        // 本番環境の判定ロジック
        $hostname = gethostname();
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        // 本番環境の特徴で判定
        if (strpos($documentRoot, '/var/www/') !== false) {
            return true;
        }
        if (strpos($hostname, 'prod') !== false || strpos($hostname, 'production') !== false) {
            return true;
        }
        
        return false;
    }
    
    private static function getProductionConfig() {
        return [
            'python_path' => 'python3',
            'base_path' => dirname(__FILE__),
            'site_packages_path' => dirname(__FILE__) . '/tmp/site-packages',
            'cache_path' => dirname(__FILE__) . '/lib/exec_chromedriver/cache',
            'chromedriver_path' => dirname(__FILE__) . '/tmp/124/chromedriver',
            'chrome_binary' => '/usr/bin/google-chrome',
            'debug' => false
        ];
    }
    
    private static function getTestConfig() {
        return [
            'python_path' => 'python3',
            'base_path' => dirname(__FILE__),
            'site_packages_path' => dirname(__FILE__) . '/tmp/site-packages',
            'cache_path' => dirname(__FILE__) . '/lib/exec_chromedriver/cache',
            'chromedriver_path' => dirname(__FILE__) . '/tmp/124/chromedriver',
            'chrome_binary' => '/usr/bin/google-chrome',
            'debug' => true
        ];
    }
    
    public static function createDirectories() {
        $config = self::getConfig();
        
        // 必要なディレクトリを作成
        $dirs = [
            $config['cache_path'],
            dirname($config['chromedriver_path'])
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}
?>

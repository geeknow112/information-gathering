<?php
$idNumber = '1422852246';
$cache_file = '/home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/lib/exec_chromedriver/cache/'. $idNumber. '.html';
$pw = 'a@Lgq5:=0GN,';
                                touch($cache_file);
echo exec(sprintf("ls -l %s", $cache_file));
echo "\n";
exec(sprintf('sudo chmod 777 %s', $cache_file));
                                $execFile = dirname(__DIR__). '/lib/exec_chromedriver/tajima_cow.py';
                                $execCmd = sprintf("echo '%s' | sudo -S python3 %s %s", $pw, $execFile, $idNumber);
                              var_dump($execCmd);
                                echo shell_exec($execCmd);
                                sleep(1);
                                $cache_data = file_get_contents($cache_file);
//print_r($cache_data);

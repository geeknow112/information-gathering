import subprocess
import sys

#sys.path.append('/home/bitnami/.local/lib/python3.7/site-packages')
sys.path.append('/home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/tmp/site-packages')

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.keys import Keys
import chromedriver_binary

args = sys.argv
#subprocess.run(('whoami'))
#print(args[1])

options = Options()
options.add_argument('--headless')
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
driver = webdriver.Chrome(chrome_options=options)
driver.get('http://www.tajimagyu-trace.com/trace_back.php')
input_element = driver.find_element_by_name('id_number')
input_element.send_keys(args[1])

path_e = '/home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/lib/exec_chromedriver'
path_w = path_e + '/cache/' + args[1] + '.html'

s = 'New file'

with open(path_w, mode='w') as f:
    f.write(s)
    f.write('\n')
    f.write('before')
    f.write('\n')
    f.write('after')
    f.write('\n')
    #exit()
    driver.find_element_by_xpath("//a/img").click()
    f.write(driver.page_source)

#subprocess.run(('date', '>>', '/opt/bitnami/apps/wp-sales/htdocs/wp-content/plugins/information-gathering/lib/exec_chromedriver/cache/' + args[1] + '.html'))
print('---end py')
#subprocess.run(('sudo', '-S', '-u', 'cli_user', 'mv', './lib/exec_chromedriver/cache/test.hmlt', './lib/exec_chromedriver/cache/test.txt'))
#subprocess.run(('sudo', '-S', '-u', 'bitnami', 'touch', './lib/exec_chromedriver/cache/'+args[1]))
#subprocess.run(('sudo', '-S', '-u', 'bitnami', 'ls', '-lha', './lib/exec_chromedriver/cache/'))

driver.quit()
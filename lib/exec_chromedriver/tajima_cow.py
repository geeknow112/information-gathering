#tajima cow 検索をする

import sys
#sys.path.append('/home/bitnami/.local/lib/python3.7/site-packages')
sys.path.append('/home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/tmp/site-packages')

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.keys import Keys
import chromedriver_binary

args = sys.argv
#print(args[1])

options = Options()
options.add_argument('--headless')
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
driver = webdriver.Chrome(chrome_options=options)
driver.get('http://www.tajimagyu-trace.com/trace_back.php')
input_element = driver.find_element_by_name('id_number')
input_element.send_keys(args[1])

#path_w = '/var/www/html/wp-content/plugins/information-gathering/lib/exec_chromedriver/cache/' + args[1] + '.html'
path_e = '/home/bitnami/stack/wordpress/wp-content/plugins/information-gathering/lib/exec_chromedriver'
path_w = path_e + '/cache/' + args[1] + '.html'

s = 'New file'

with open(path_w, mode='w') as f:
    f.write(s)
    f.write('before')
    f.write('after')
    #exit()

#assert 'Google' in driver.title

#input_element.send_keys('1568329862')
#input_element.send_keys(Keys.RETURN)
#input_element.find_element_by_link_text("javascript:void(0)")
#input_element.click()
    #f.write(driver.find_element_by_xpath("//a/img").is_displayed())
    driver.find_element_by_xpath("//a/img").click()

    f.write(driver.page_source)

"""
assert 'Python' in driver.title

driver.save_screenshot('search_results.png')

for h3 in driver.find_elements_by_css_selector('a > h3'):
  a = h3.find_element_by_xpath('..')
  print(h3.text)
  print(a.get_attribute('href'))
"""

driver.quit()


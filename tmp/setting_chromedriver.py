#!/usr/bin/env python3

import os
import chromedriver_autoinstaller

if __name__=='__main__':
    chrome_ver = chromedriver_autoinstaller.get_chrome_version().split('.')[0]
    driver_path = f'./{chrome_ver}/chromedriver'
    if os.path.exists(driver_path):
        print(f"chrom driver is insatlled: {driver_path}")
    else:
        print(f"install the chrome driver(ver: {chrome_ver})")
        chromedriver_autoinstaller.install(True)
        os.system(f'sudo cp {driver_path} /usr/local/bin/')
        os.system('sudo chmod 755 /usr/local/bin/chromedriver')

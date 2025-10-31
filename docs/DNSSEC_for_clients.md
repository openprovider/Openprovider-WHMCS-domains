# DNSSEC page for end users

- Navigate to the **target client profile > domains** select the desired domain and enable **"DNSSEC Management"** checkbox.
<img src="img/DNSSEC management checkbox.png" style="zoom: 67%;" />
- The below option will appear in the domain details page of the chosen domain

<img src="img/DNSSEC management.png" alt="Screenshot_20210203_183243" style="zoom: 67%;" />

- **Notes**:
  -  If you experience any difficulty accessing the DNSSEC page, please copy the file `<Module directory>/registrars/openprovider/custom-pages/dnssec.php` to the top level of your WHMCS folder (i.e. `<your WHMCS directory>/`)
  -  If you see the menu option as "**dnssectabname**" as shown in the screenshot below, copy the folder `<Module directory>/lang/overrides` to `<your WHMCS directory>/lang/` folder. 
 <img width="567" alt="image" src="https://github.com/openprovider/Openprovider-WHMCS-domains/assets/97894083/c4bf574c-2b2f-4367-bb6e-789578535564">
# [Experimental feature] WHMCS Installation and Upgrade Scripts

- This directory contains scripts for installing and upgrading the Openprovider domain registrar module. Follow the instructions below to use the scripts.
- To install/upgrade the Openprovider domain registrar module, run the following commands from **WHMCS root directory** as the hosting/website user (e.g., on a cPanel server, run it as the cPanel user under which the WHMCS website is hosted).

## Prerequisites
- Ensure `curl` is installed on your system.
- User has privileges to execute bash scripts.

## Installation

```bash
curl -s https://raw.githubusercontent.com/openprovider/Openprovider-WHMCS-domains/refs/heads/master/scripts/install_openprovider.sh | /bin/bash -s
```

## Upgrade

```bash
curl -s https://raw.githubusercontent.com/openprovider/Openprovider-WHMCS-domains/refs/heads/master/scripts/update_openprovider.sh | /bin/bash -s
```

**Important**: If you are running the commands as the 'root' user, ensure to correct the ownership of following folders and files to avoid permission issues:
- 'modules/registrars/openprovider'
- 'modules/addons/openprovider'
- 'includes/hooks/openprovider.js'
- 'includes/hooks/transliterate.php'
- 'resources/domains/additionalfields.php'

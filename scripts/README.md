# WHMCS Installation and Upgrade Scripts

This repository contains scripts for installing and upgrading a WHMCS instance with the Openprovider module. Follow the instructions below to use the scripts.

## Prerequisites

- Ensure `curl` is installed on your system.
- You need `sudo` privileges to execute the scripts.

## Installation

To install the WHMCS instance with the Openprovider module, run the following command:

```bash
curl https://raw.githubusercontent.com/openprovider/Openprovider-WHMCS-domains/refs/heads/master/scripts/install_openprovider.sh | sudo /bin/bash -s
```

## Upgrade

To upgrade an existing WHMCS instance, run this command:

```bash
curl https://raw.githubusercontent.com/openprovider/Openprovider-WHMCS-domains/refs/heads/master/scripts/update_openprovider.sh | sudo /bin/bash -s
```

## Script Location

The scripts are located in the `/scripts` directory of this repository.


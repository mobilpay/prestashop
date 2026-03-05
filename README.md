# NETOPIA Payments

NETOPIA Payments module for PrestaShop — credit card payments via NETOPIA.

## Setting up

Set up the plugin in 3 steps:

1. **Create a Point of Sale** — Have a PrestaShop-specific Point of Sale and its signature and encryption keys.
2. **Install and activate the plugin** — Download, pack, and upload the module in PrestaShop.
3. **Configure and validate** — Enter your signature and keys, then request final validation from NETOPIA.

[Full setup guide →](https://doc.netopia-payments.com/docs/payment-plugins/prestashop)

---

## Installation

1. **Create a Point of Sale**  
   Create a Point of Sale for PrestaShop and get your signature and keys.  
   See [Points of Sale](https://doc.netopia-payments.com/docs/get-started/point-of-sale).

2. **Download and pack the module**
   - Download this repo from [GitHub](https://github.com/mobilpay/prestashop) (e.g. “Download ZIP”).
   - Unzip and open the folder for your PrestaShop version.
   - Zip only the **`mobilpay_cc`** folder and name the file `mobilpay_cc.zip`.

3. **Install in PrestaShop**
   - In the back office: **Modules** → **Module Manager**.
   - Upload `mobilpay_cc.zip` and install.
   - When you see “Modules installed!”, click **Configure** (or later: **Modules** → **Module Manager** → **Payments** → Mobilpay – credit card → **Configure**).

4. **Configure the module**
   - Enter your **Account (Point of Sale) Signature**.
   - Upload or paste your **public** and **private** key files.  
   Keys are in the [NETOPIA merchant account](https://admin.netopia-payments.com) → **Points of Sale** → **Options** (⋮) → **Technical Settings**.

5. **Request activation**  
   Email [implementare@netopia.ro](mailto:implementare@netopia.ro) to request final validation. After NETOPIA activates your Point of Sale, you can accept payments.

---

## Compatibility

- The NETOPIA team aims to keep this module compatible with the latest PrestaShop version to speed up your integration.
- For older PrestaShop versions, use the matching branches in this repository.
- **Tested up to:** PrestaShop 9.0.2, PHP 8.2, SSL 3.0.

---

## Documentation

- [PrestaShop plugin guide](https://doc.netopia-payments.com/docs/payment-plugins/prestashop)
- [NETOPIA Developer Portal](https://doc.netopia-payments.com/)

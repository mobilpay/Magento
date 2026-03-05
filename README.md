# NETOPIA Payments module for Magento 2.4.X

###### For previous versions, use the other branches of this GitHub repository.

## Options

* Card payment
* mobilPay WALLET

## Installation

Download the Magento module from this GitHub repository (Code → Download ZIP). After unzipping, you will find a folder called **Netopia** in `Magento-master/app/code`.

1. Place the **Netopia** folder inside `<your_magento_root>/app/code/`
   * Verify ownership and make sure you have the correct user and/or group ownership.

2. SSH into your Magento server, go to the Magento root folder and run:

   * `php bin/magento module:enable Netopia_Netcard`
   * `php bin/magento setup:upgrade`
   * `php bin/magento setup:static-content:deploy`  
     *(If the above command fails, use `php bin/magento setup:static-content:deploy -f` to force it.)*
   * `php bin/magento cache:flush`

**Important:** Enable the module with the commands above first; otherwise you will not see it in your Magento dashboard.

## Configuration

After installation, open the Netopia configuration from the Magento dashboard:

**Stores → Configuration** (under Settings) → **SALES → Payment Methods → Other payment methods** → click **Configure** on the Netopia module.

You will have four main sections: **Basic configuration**, **Advanced configuration**, **Mode configuration**, and **Countries / Currencies available**.

### Basic configuration

Enter your **Merchant ID** and **Account Signature**.  
The Account Signature is available in the merchant account [admin.netopia-payments.com](https://admin.netopia-payments.com) → **Puncte de vanzare** → Options (⋮) → **Setari tehnice**.

### Mode configuration

Set the **Public** and **Private** key files and switch between **Live** and **Sandbox** via the **Live Mode** dropdown.  
The Public and Private key files are in the same place in the merchant account: **Puncte de vanzare** → Options (⋮) → **Setari tehnice**.

**Note:** Until you receive final validation from NETOPIA, use only the **SANDBOX** keys. For Sandbox keys, open **Mediu de testare / Testing environment** first in the merchant account, then download the keys as above.

### Keys / Certificates location

Uploaded Public and Private key files are stored under:

`<your_magento_root>/app/code/Netopia/Netcard/etc/certificates`

Ensure correct ownership and permissions on this directory.

### mobilPay WALLET (optional)

By enabling **Allow mobilPay WALLET Payment**, customers can pay via **mobilPay WALLET** by scanning a QR code.  
For mobilPay WALLET, set the **Username** and **Password** to match your account on [admin.mobilpay.ro](https://admin.mobilpay.ro).

If you need both **Sandbox** and **Live** modes, you must upload the keys for each mode separately.

## Final validation

After configuration, send an email to **implementare@netopia.ro** to request final validation. The NETOPIA technical support team will activate your Point of Sale so you can start receiving payments.

## Verification

To confirm the module is installed correctly:

```bash
php bin/magento module:status
```

## After installation

1. Go to the Admin panel and complete the configuration:  
   **Stores → Configuration → Sales → Payment Methods** (Netopia / Other payment methods).

2. **Configuration sections** (depending on module version):
   * **Basic configuration** – Merchant ID, Signature, enable/disable, Live vs Sandbox.
   * **Certificate / Mode configuration** – Upload or remove public and private keys for Live and Sandbox.
   * **Custom configuration** – Order status per payment status (defaults are recommended).
   * **mobilPay WALLET configuration** – mobilPay WALLET settings.
   * **Conditions / Agreements** – Declare and send agreements to NETOPIA Payments.  
     Before sending, ensure keys are uploaded and settings are saved.

Fields left empty in configuration will use default values.

## Troubleshooting

If you do not see changes after updating or upgrading the module, clear caches and/or recompile:

* `php bin/magento cache:flush` (or `php bin/magento ca:cl`)
* `php bin/magento setup:di:compile`

You can also clear contents of: `MagentoRoot/var/cache/`, `MagentoRoot/var/page_cache/`, and `MagentoRoot/generated/code` if needed.

## Compatibility

* To use this module with older Magento versions (e.g. 2.3.X), use the corresponding branch, for example:  
  `git clone --single-branch --branch V2.3 https://github.com/mobilpay/Magento.git`

The NETOPIA Payments development team aims to keep the Magento module compatible with the latest Magento versions.

## Tested up to

| Component | Version  |
|----------|----------|
| Magento | **2.4.6** |
| PHP     | **8.2.8** |
| OpenSSL | **3.0**   |

## Changelog

* **v1.0.2:** Set matched order comment on foreign currency.
* **v1.0.3:** Add INCREMENT ID on transaction description.

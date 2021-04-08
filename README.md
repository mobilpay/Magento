# NETOPIA Payments module for Magento 2.4.2
###### (for previous versions using the other branches of this GitHub repository)

## Options
* Card payment
* mobilPay WALLET

## Installation
The Module placed in folder "Netopia"
1. put **Netopia** folder inside of <your_magento_root>/app/code/
    * verify the ownership & make sure have right user and/or group ownership
2. SSH to your Magento server and run the following commands
    * <code>php bin/magento setup:upgrade</code>
    * <code>php bin/magento setup:static-content:deploy</code>
    * <code>php bin/magento ca:cl</code>
3.  Complete the **Configuration**   
4.  **Enable** the module.
    ###### Additional option
    By Enable the **Allow mobilPay WALLET Payment** you will give this option to your clients to pay also via **mobilPay WALLET** by scan a simple **QrCode** 
5. Download your keys from https://admin.mobilpay.ro/ for Live mode and https://sandbox.mobilpay.ro for Sandbox mode.
   Set your Mode at Mode **Configuration** and  Upload the certificates.
   
   Note : if you would like to have possibility to work with both mode (**Sandbox and Live**), you will need the keys for each mode.


## Verification
By run the following command you can make sure, if this module is installed successfully on your Magento Proiect
* <code>php bin/magento module:status</code>

## After installation
Recommended to firstly, go to Admin panel & fill the necessary data
<code><your_magento_admin>->Stores->Configuration->Sales->Payment Methods->Netopia Payments</code>

* #### Configuration
    * **Base configuration** : To enable / disable the payment method, switch to Live or Sandbox, ....
    * **Certificate Configuration** : To uploade / remove the public & private keys for Live & Sandbox
    * **Custom configuration** : To set order status regarding the payment status, recommanded to use Defulte one 
    * **mobilPay WALLET Configuration** : To set mobilPay WALLET setting
    * **Conditions / Agreements** : To declare de agreements with NETOPIA Payments and send the agreement to NETOPIA Payments.
        * before send the agreements, make sure you already uploaded the keys & save the setting & agreements.
        
    Note : The fileds are not complited from configuration section, will set by default value, 

## Where Keys / Certificates are located
The Public & Private Keys for Live and Sandbox are located in <your_magento_root>/app/code/Netopia/Netcard/etc/certificates
* make sure you have right ownership & permition
### Other general usefull note
If in any case you update/upgrade your Magento Module & not see the changes, so maybe is cached.
You can using such command like this <code>php bin/magento ca:cl</code> to clean the cache or
Using such command <code>bin/magento setup:di:compile</code> to compile or regenerate your modules.
Sometime remove the contents of actual cache folders such as <code>MagentoRoot/var/cache/</code> | <code>MagentoRoot/var/page_cache/</code> | <code>MagentoRoot/generated/code</code> is helping too

##### Good to know
* To get the module compatible with previous versions of Magento, using the other branches of this GitHub repository. for ex. for Magento Version 2.3.X we can get the module by run git command like : **git clone --single-branch --branch V2.3 https://github.com/mobilpay/Magento.git**

NETOPIA Payments development team try to keep compatibility of the Magento module with latest version of Magento, in order to helping you to implamenting your ecomerce website faster.

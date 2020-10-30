# NETOPIA Payments module for Magento 2.4
###### (for previous versions using the other branches of this GitHub repository)

## Options
* Card payment
* mobilPay WALLET

## Installation
The Module placed in folder "Netopia"
1. put **Netopia** folder inside of <your_magento_root>/app/code/
2. SSH to your Magento server and run the following commands
    * <code>php bin/magento setup:upgrade</code>
    * <code>php bin/magento setup:static-content:deploy</code>
    * <code>php bin/magento ca:cl</code>
3.  Complete the **Basic Configuration**   
4. Enable the module from **Advanced configuration**
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

* ####Necessary fileds
    * Basic Configuration
        * Merchant Id / Signature
        * Username
        * Password
    * Advanced configuration 
        * Enabled
    * Mode Configuration
        * Live Mode
        
    Note : Except the **Basic Configuration** which is obligatory 
    the other items, will configure with default value, if you don't set them.


### Other general usefull note
If in any case you update/upgrade your Magento Module & not see the changes, so maybe is cached.
You can using such command like this <code>php bin/magento ca:cl</code> to clean the cache or
Using such command <code>bin/magento setup:di:compile</code> to compile or regenerate your modules.
Sometime remove the contents of actual cache folders such as <code>MagentoRoot/var/cache/</code> | <code>MagentoRoot/var/page_cache/</code> | <code>MagentoRoot/generated/code</code> is helping too

##### Good to know
* NETOPIA Payments development team try to keep compatibility of the Magento module with latest version of Magento, in order to helping you to implamenting your ecomerce website faster.
* To get the module compatible with previous versions of Magento, using the other branches of this GitHub repository.

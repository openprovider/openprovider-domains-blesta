# Openprovider Domains module for Blesta

Offer your customers almost any TLD, from the most popular to the most exotic, at unbeatable prices all from one registrar - [Openprovider](https://www.openprovider.com)

### Useful features

- Create packages to register domains from any of the 2300 TLDs available with an Openprovider account
- Domain search
- Automated domain registration and transfer for domains which do not require additional data
- End users and administrators can seamlessly manage domain details from their respective areas:
  - Change domain contact details, e.g. modify whois data 
  - Change nameservers
  - Toggle domain transfer lock
  - Retrieve or reset domain authcode/transfer code

### Getting started

- Copy the folder `openprovider/` into the `<blesta root>/components/modules/` folder of your Blesta instance.
- Sign up for a free [Openprovider account](https://cp.openprovider.eu/signup) 
- Activate the Openprovider registrar module in Blesta, and add your Openprovider credentials 
- Follow the tutorial for the [Blesta Domain Manager](https://docs.blesta.com/display/user/Domain+Manager)



### Expanding the list of supported TLDs

- We've included the 100 most popular TLDs in the module. But if you find that you need something else, you can add any TLD supported by Openprovider, provided that it does not require additional data fields (check the [Openprovider Knowledge Base](https://support.openprovider.eu/hc/en-us) to determine if your target TLD needs additional data)
- If you're interested in offering domains which require additional data, please make an issue in this repository and we'll try to add support as soon as possible.
- Navigate to `<blesta root>/components/modules/openprovider/config/openprovider.php`and configure the array `OpenProvider.tlds` with the TLDs which you would like to support. Check your Openprovider account for a full list of the more than 2800 TLDs available for registration. 

```php
// Allowed tlds
Configure::set('OpenProvider.tlds', [
    '.com',
    '.nl',
    '.shop', 
    //...etc
    // add any missing TLDs here
]);
```

- We want the module to reflect your needs and feedback is welcome. Please contact us: integrations@openprovider.com

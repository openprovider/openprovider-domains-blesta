# The OpenProvider registrar module has been added to Blesta 5.9
[Please see the release notes](https://www.blesta.com/2024/01/16/blesta-5.9-released/)

To install the Openprovider registrar module provided with Blesta:

1. Visit [**TLDs**] > [**Registrars**].

![image](https://github.com/openprovider/openprovider-domains-blesta/assets/97894083/d2fe1592-9623-4308-8a5a-3e9ca4ee38ba)

2. Find the Openprovider module and click the "**Install**" button.

![image](https://github.com/openprovider/openprovider-domains-blesta/assets/97894083/dd5a648d-954f-4643-8d93-1a19858d978a)

## Not Actively Maintained
Currently, this module is not actively maintained by Openprovider. Please use the Openprovider registrar module provided with Blesta, see instructions above.

# Openprovider Domains module for Blesta 5.2

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

### Adding additional TLDs
By default the Openprovider module supports the most popular 100 TLDs. If you'd like to add more, simply add the desired TLDs to the list of allowed TLDs in the file `/openprovider/config/openprovider.php` 


### Development Pause
Development on the module is paused. If you have interest in the module, please create an issue with the requested features, which will be considered at the earliest possible time.


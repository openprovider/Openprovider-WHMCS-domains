# Premium DNS setup

Openprovider’s Premium DNS is the latest addition to our product portfolio, and you can offer it to your customers directly via WHMCS. We are extending our successful collaboration with Sectigo, an industry leader in SSL certificates and web security products, to introduce a Premium DNS service that will be the perfect complement to our current, free of charge, standard DNS offering. While our standard DNS service is sufficient for users who don’t have any special needs when it comes to DNS resolution, we recognize the increasing demand of our customers in this area, and we are launching this new product in order to address their needs.

Openprovider’s Premium DNS leverages Sectigo’s advanced DNS infrastructure to provide a single, integrated and easy to adopt solution that offers the following advantages to your customers:



- **Blazing Fast Resolution**: The first part of a request to your website is typically a DNS lookup. Sectigo’s Premium DNS ensures that your customers are always connected to the closest server, resulting in lower latency and faster access times to your web properties.
- **Global Presence**: All your DNS zones are pushed to Sectigo’s DNS servers around the globe, ensuring an optimal user experience for your global customers.
- **DDoS Protection**: DNS is the first step in the journey to your website. Sectigo’s Premium DNS keeps your DNS zone protected from DDoS DNS attacks at all times.
- **99.99% Uptime Guarantee**: Sectigo’s built-in smart routing and anycast capabilities ensure that your DNS zones are always online. Your websites will always be accessible regardless of regional disruptions.
- **Near Real-Time Updates**: Updates to your DNS records are carried out globally in near real-time.
- **Competitive Pricing**

## Technical dependencies

The Premium DNS module can be used with domains from any registrar, not only domains with Openprovider. However, the premium DNS module needs the Openprovider domain module to be activated in WHMCS in order to work correctly, even if you do not use the Openprovider domain module for any domain registrations. 

## Initial Setup

Download the files from our [github repository](https://github.com/openprovider/OP-WHMCS7) 

- Copy the contents of `/modules/registrars/openprovider` to `<your WHMCS directory>/modules/registrars/openprovider`
- Copy the contents of `/modules/servers/openprovidersectigodns` to `<your WHMCS directory>/modules/servers/openprovidersectigodns`
- Activate the Openprovider Registrar module in WHMCS. You don’t need to enter credentials unless you are selling Openprovider domains as well

![img](img/activate_module)    



## Configure premium DNS product

- Navigate to *products/services*, and create an appropriate product group if necessary.
- Create a new product, and select **Other** as product type and **Openprovider-premiumDNS** from the module dropdown
- Select the desired product group and name your DNS product something fun.

![img](img/create_new_product_step1)

- After continuing to the *edit product* page: on the *details* tab, check the *require domain* tickbox. 
- Add the desired description and select welcome email options.

![img](img/create_new_product_step2)



- Set up pricing on the pricing tab. Consult your Openprovider account to determine the cost price for provisioning premium DNS.

- Under the *module settings* tab, enter the credentials for the Openprovider account with which you’d like to provision premium DNS. Select your desired automation settings for provisioning this product.



![img](img/create_new_product_step3)

- Configure any other parameters of the product which you deem necessary and then you're ready to start offering premium DNS to your customers.



## End user workflow in WHMCS basic cart

The below example shows one way the provisioning module can work from the end user point of view. Various upsell options can be implemented via WHMCS to improve .

- End user selects from the categories sidebar “Premium DNS” (the name of your product group where premium DNS is located) and choose a premium DNS product

![img](img/end_user_workflow_step1)



- Customer will have several options for choosing a domain to be connected with the premium DNS service:



![img](img/end_user_workflow_step2)



- Once the customer completes the purchase, the module will provision the premium DNS zone in your Openprovider account. 
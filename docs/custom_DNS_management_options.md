## Add support for additional DNS records
WHMCS does not natively support all DNS records supported by Openprovider, so to add support for additional records, you will need to edit your template files. We have created a [request for WHMCS to make this easier. Please upvote this request here](https://requests.whmcs.com/topic/add-support-for-custom-dns-types).

Current as of WHMCS version 8.1 

### Find desired template files to edit.

Open `whmcs/templates/CURRENT_THEME_TEMPLATE/clientareadomaindns.tpl`  

### Add any additional record types which you would like to display.  

Search for the version with the options like the one below. Note that it includes the "selected" substring:

`<option value="A"{if $dnsrecord.type eq "A"} selected="selected"{/if}>{lang key="domainDns.a"}</option>`

To include an SPF and SRV records, see examples below:

```
<option value="SPF"{if $dnsrecord.type eq "SPF"} selected="selected"{/if}>SPF</option>
<option value="SRV"{if $dnsrecord.type eq "SRV"} selected="selected"{/if}>SRV</option>
```

### Find place to edit which records can be created

Search for the second `<select name="dnsrecordtype[]" class="form-control">`. Search for the version similar to the one below. Note that the "selected" substring is not used.

`<option value="A">{lang key="domainDns.a"}</option>`

Replace the options with the following:
```
<option value="SPF">SPF</option>
<option value="SRV">SRV</option>
```

### Add priority field if necessary (for example SRV)

find the block similar to the below, where the priority field is added. 

```
{if $dnsrecord.type eq "MX"}
```

This 'if' statement is used twice in the file. Add an  `or $dnsrecord.type eq "SRV"`  as below to both conditional blocks:


```
{if $dnsrecord.type eq "MX" or $dnsrecord.type eq "SRV"} 
```
## Install the DNS management options
To match the DNS types supported by Openprovider in WHMCS, you will need to install this in the template files. We have created a [request for WHMCS to make this easier. Please upvote this request here](https://requests.whmcs.com/topic/add-support-for-custom-dns-types).

_Step 1:_ Open `whmcs/templates/CURRENT_THEME_TEMPLATE/clientareadomaindns.tpl` and search for `<select name="dnsrecordtype[]" class="form-control">`. This will appear two times. 

Search for the version with the options like the one below. Note that it includes the "selected" substring:

`<option value="A"{if $dnsrecord.type eq "A"} selected="selected"{/if}>A (Address)</option>`

Replace the options with the following:
```
<option value="A"{if $dnsrecord.type eq "A"} selected="selected"{/if}>A (Address)</option>
<option value="AAAA"{if $dnsrecord.type eq "AAAA"} selected="selected"{/if}>AAAA (Address)</option>
<option value="CAA"{if $dnsrecord.type eq "CAA"} selected="selected"{/if}>CAA</option>
<option value="CNAME"{if $dnsrecord.type eq "CNAME"} selected="selected"{/if}>CNAME (Alias)</option>
<option value="MX"{if $dnsrecord.type eq "MX"} selected="selected"{/if}>MX (Mail)</option>
<option value="SPF"{if $dnsrecord.type eq "SPF"} selected="selected"{/if}>SPF (spf, not recommended)</option>
<option value="SSHFP"{if $dnsrecord.type eq "SSHFP"} selected="selected"{/if}>SSHFP</option>
<option value="SRV"{if $dnsrecord.type eq "SRV"} selected="selected"{/if}>SRV</option>
<option value="TLSA"{if $dnsrecord.type eq "TLSA"} selected="selected"{/if}>TLSA</option>
<option value="TXT"{if $dnsrecord.type eq "TXT"} selected="selected"{/if}>TXT (recommended for SPF)</option>
```

_Step 2:_ Search for the second `<select name="dnsrecordtype[]" class="form-control">`. Search for the version with the options like the one below. 

Note that this time, the "selected" substring is not used.

`<option value="A">A (Address)</option>`

Replace the options with the following:
```
<option value="A">A (Address)</option>
<option value="AAAA">AAAA (Address)</option>
<option value="CAA">CAA</option>
<option value="CNAME">CNAME (Alias)</option>
<option value="MX">MX (Mail)</option>
<option value="SPF">SPF (spf, not recommended)</option>
<option value="SSHFP">SSHFP</option>
<option value="SRV">SRV</option>
<option value="TLSA">TLSA</option>
<option value="TXT">TXT (recommended for SPF)</option>
```

_Step 3:_ Update the MX priority field with `or $dnsrecord.type eq "SRV"` in the 'if/else' block. 

This 'if' statement is used twice in the file. Replace both.

```
{if $dnsrecord.type eq "MX"} 
```


```
{if $dnsrecord.type eq "MX" or $dnsrecord.type eq "SRV"} 
```
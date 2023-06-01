<?php
namespace WeDevelopCoffee\wPower\Core;

use Illuminate\Database\ConnectionResolver as ConnectionResolverBase;

class ConnectionResolver extends ConnectionResolverBase
{
    public function connection($name = null)
    {
        return parent::connection(null);
    }
}

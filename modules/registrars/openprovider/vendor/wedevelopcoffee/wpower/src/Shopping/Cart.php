<?php
namespace WeDevelopCoffee\wPower\Shopping;

use WHMCS\Product\Product;

/**
 * Basic functions to add or remove items from the cart.
 *
 * @package default
 * @license  WeDevelop.coffee
 **/
class Cart
{

    /**
     * Cart constructor.
     */
    public function __construct()
    {
        // For this; we need to include the orderfunctions.php
        require_once (realpath(__DIR__ .'/../../../../../../../../includes/orderfunctions.php'));
    }

    /**
     * Add a product to the cart
     *
     * @param $productId
     * @param $domain
     * @param string $billingCycle
     * @param array $addons
     * @param array $server
     * @return $this
     */
    public function addProductToCart($productId, $domain, $billingCycle = 'annually', $addons = [], $server = [])
    {
        $product['pid'] = $productId;
        $product['domain'] = $domain;
        $product['billingcycle'] = $billingCycle;
        $product['configoptions'] = $configOptions;
        $product['customfields'] = '';
        $product['addons'] = $addons;
        $product['server'] = $server;
        $product['skipConfig'] = false;

        $_SESSION['cart']['products'][] = $product;

        return $this;
    }

    public function removeProductFromCart($productId)
    {
        $products = $_SESSION['cart']['products'];
        $_SESSION['cart']['products'] = [];

        foreach($products as $product)
        {
            $this->addProductToCart($product['pid'], $product['domain'], $product['billingcycle'], $product['configoptions'], $product['addons'], $product['server']);
        }

        return $this;
    }

    /**
     * Add the domain to the cart.
     *
     * @param $domain
     * @param string $type
     * @param string $regPeriod
     * @param bool $isPremium
     * @return $this
     */
    public function addDomainToCart($domain, $type = 'register', $regPeriod = '1', $isPremium = false)
    {
        // Make sure that the domain is unique.
        $this->removeDomainFromCart($domain);

        $cartDomain['type'] = $type;
        $cartDomain['domain'] = $domain;
        $cartDomain['regperiod'] = $regPeriod;
        $cartDomain['isPremium'] = $isPremium;

        $_SESSION['cart']['domains'][] = $cartDomain;

        return $this;
    }

    /**
     * Remove domain from cart
     *
     * @param $remove_domain
     * @return $this
     */
    public function removeDomainFromCart($remove_domain)
    {
        $domains = $_SESSION['cart']['domains'];
        $_SESSION['cart']['domains'] = [];

        // Add all domains individually
        foreach($domains as $domain)
        {
            // Do not add the $remove_domain
            if($remove_domain == $domain['domain'])
                continue;

            $this->addDomainToCart($domain['domain'], $domain['type'], $domain['regperiod'], $domain['isPremium']);
        }

        return $this;
    }

    /**
     * Return the cart
     * 
     * @return array
     */
    public function getCart()
    {
        $cart = calcCartTotals();

        return $cart;
    }

    /**
     * Empty the shopping cart.
     *
     * @return $this
     */
    public function emptyCart()
    {
        $_SESSION['cart']['products'] = [];
        $_SESSION['cart']['domains'] = [];

        return $this;
    }
}
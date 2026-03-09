<?php
/**
 * Openprovider Cross-Sell Widget
 *
 * WHMCS Admin Dashboard Widget that randomly shows resellers either
 * an Email or Premium DNS revenue opportunity based on their domain count.
 *
 * Experiment: Test if revenue messaging drives module adoption.
 * Built-in A/B test: random rotation between products per page load.
 *
 * @copyright Openprovider
 * @version 2.0.0
 */

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks\Widgets;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use Illuminate\Database\Schema\Blueprint;

class CrossSellWidget extends \WHMCS\Module\AbstractWidget
{
    const DISMISS_TABLE = 'mod_openprovider_crosssell_dismiss';

    private static $dismissTableChecked = false;

    // =========================================================================
    // PRODUCT CONFIGURATION
    // =========================================================================

    /**
     * Product definitions. Each product has its own copy, formula, and module detection.
     * Add or remove products here to control what the widget can show.
     */
    const PRODUCTS = [
        'email' => [
            'title'           => 'Top resellers earn more per domain',
            'body'            => 'Many agencies attach email to 20-30%% of domains. Typical profit is €15-25 per mailbox/year.',
            'cta_text'        => '👉 Enable Email Module (5-minute setup)',
            'footer'          => 'Basic mailbox hosting. Auto-provisioned in WHMCS. No migration needed.',
            'adoption_rate'   => 0.20,
            'units_per_domain'=> 2,       // 2 mailboxes per domain
            'margin_per_unit' => 20,      // €20/mailbox/year
            'module_name'     => 'email_solution',
            'setup_guide_url' => 'https://support.openprovider.com/hc/en-us/articles/email-module-whmcs-setup',
            'dismiss_key'     => 'op_crosssell_email_dismissed',
        ],
        'dns' => [
            'title'           => 'Improve reliability and earn more per domain',
            'body'            => 'Many resellers and agencies attach Premium DNS to ~10%% of domains to improve uptime and performance. Typical profit is €15–40 per domain/year.',
            'cta_text'        => '👉 Enable Premium DNS Module (5-minute setup)',
            'footer'          => 'Anycast DNS. Auto-provisioned in WHMCS. No migrations needed.',
            'adoption_rate'   => 0.10,
            'units_per_domain'=> 1,       // 1 DNS zone per domain
            'margin_per_unit' => 20,      // €20/domain/year
            'module_name'     => 'openproviderpremiumdns',
            'setup_guide_url' => 'https://support.openprovider.com/hc/en-us/articles/dns-module-whmcs-setup',
            'dismiss_key'     => 'op_crosssell_dns_dismissed',
        ],
    ];

    // =========================================================================
    // GENERAL CONFIGURATION
    // =========================================================================

    /**
     * Weight for product rotation: probability of showing email vs dns.
     * 50 = 50/50 even split. 70 = 70% email, 30% dns.
     * Set to 50 for a clean A/B test.
     */
    const EMAIL_WEIGHT = 50;

    /**
     * Tracking redirect base URL on your server.
     * Query params appended: ?reseller_id=X&product=email|dns&source=widget
     */
    const TRACKING_URL = 'https://www.openprovider.com/crosssell/track';

    /**
     * Use tracking redirect (true) or direct guide links (false).
     * Set to false during development, true for the live experiment.
     */
    const USE_TRACKING_URL = true;

    /**
     * Openprovider registrar handle in WHMCS tbldomains.
     */
    const REGISTRAR_HANDLE = 'openprovider';

    /**
     * Minimum domain count to show the widget.
     * Don't bother resellers with <10 domains — the revenue number looks too small.
     */
    const MIN_DOMAINS = 10;

    // =========================================================================
    // WHMCS Widget Configuration
    // =========================================================================

    protected $title = 'Openprovider Revenue Opportunity';
    protected $description = 'Estimated extra revenue from your domain portfolio.';
    protected $weight = 50;
    protected $columns = 1;
    protected $cache = true;
    protected $cacheExpiry = 3600; // 1 hour — reseller sees same product per hour
    protected $requiredPermission = '';
    protected $wrapper = true;
    protected $draggable = true;

    // This is needed because WHMCS includes namespace when referring to the ID in the HTML when the widget
    // is loaded from another namespace.
    public function getId()
    {
        return 'OPCrossSellWidget';
    }

    /**
     * Gather data for the widget.
     *
     * @return array
     */
    public function getData()
    {
        // Pick which product to show (random, weighted)
        $selectedProduct = $this->pickProduct();

        // If both products are dismissed or installed, nothing to show
        if ($selectedProduct === null) {
            return ['hidden' => true];
        }

        $config = self::PRODUCTS[$selectedProduct];

        // Check if this specific product was dismissed
        if ($this->isDismissed($config['module_name'])) {
            // Try the other product
            $selectedProduct = $this->getOtherProduct($selectedProduct);
            if ($selectedProduct === null) {
                return ['hidden' => true];
            }
            $config = self::PRODUCTS[$selectedProduct];
        }

        // Check if this product's module is already installed
        if ($this->isModuleInstalled($config['module_name'])) {
            // Try the other product
            $selectedProduct = $this->getOtherProduct($selectedProduct);
            if ($selectedProduct === null) {
                return ['hidden' => true];
            }
            $config = self::PRODUCTS[$selectedProduct];
        }

        // Count active domains
        $domainCount = $this->getOpenproviderDomainCount();

        // Calculate estimated revenue using this product's formula
        $estimatedRevenue = $domainCount
            * $config['adoption_rate']
            * $config['units_per_domain']
            * $config['margin_per_unit'];

        // Build tracked CTA URL
        $resellerId = $this->getResellerId();
        $ctaUrl = $this->buildCtaUrl($resellerId, $selectedProduct, $config['setup_guide_url']);

        // Build dismiss URL (registrar module, not addon module)
        $dismissUrl = 'index.php?op_crosssell_action=dismiss'
            . '&crosssell_product=' . $selectedProduct
            . '&token=' . generate_token('link');

        return [
            'hidden'             => false,
            'product'            => $selectedProduct,
            'title'              => $config['title'],
            'body'               => $config['body'],
            'cta_text'           => $config['cta_text'],
            'footer'             => $config['footer'],
            'domain_count'       => $domainCount,
            'estimated_revenue'  => number_format($estimatedRevenue, 0, ',', ','),
            'cta_url'            => $ctaUrl,
            'dismiss_url'        => $dismissUrl,
            'reseller_id'        => $resellerId,
        ];
    }

    /**
     * Render the widget HTML.
     *
     * @param array $data From getData()
     * @return string HTML output
     */
    public function generateOutput($data)
    {
        if (isset($data['error'])) {
            return <<<EOF
<div class="widget-content-padded">
            <div style="color:red; font-weight: bold">
                {$data['error']}
            </div>
</div>
EOF;
        }

        if (!empty($data['hidden'])) {
            return '';
        }

        if (empty($data['domain_count']) || $data['domain_count'] < self::MIN_DOMAINS) {
            return '';
        }

        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $body = sprintf($data['body']); // Resolve %% to %
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $ctaText = htmlspecialchars($data['cta_text'], ENT_QUOTES, 'UTF-8');
        $footer = htmlspecialchars($data['footer'], ENT_QUOTES, 'UTF-8');
        $domainCount = (int) $data['domain_count'];
        $estimatedRevenue = htmlspecialchars($data['estimated_revenue'], ENT_QUOTES, 'UTF-8');
        $ctaUrl = htmlspecialchars($data['cta_url'], ENT_QUOTES, 'UTF-8');
        $dismissUrl = htmlspecialchars($data['dismiss_url'], ENT_QUOTES, 'UTF-8');

        return <<<EOF
<div class="widget-content-padded">
    <div class="row">
        <div class="col-sm-12 text-right">
            <a href="{$dismissUrl}" title="Dismiss" onclick="return confirm('Hide this widget? You can re-enable it in the Openprovider registrar settings.');">
                Dismiss
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="item">
                <div class="note">{$title}</div>
                <div>{$body}</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6 bordered-right">
            <div class="item">
                <div class="data color-orange">{$domainCount}</div>
                <div class="note">Openprovider Domains</div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="item">
                <div class="data color-green">EUR {$estimatedRevenue}/year</div>
                <div class="note">Estimated Revenue</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="item">
                <div><a href="{$ctaUrl}" target="_blank">{$ctaText}</a></div>
                <div class="note">{$footer}</div>
            </div>
        </div>
    </div>
</div>
EOF;
    }

    // =========================================================================
    // Product Selection
    // =========================================================================

    /**
     * Randomly pick which product to show, weighted by EMAIL_WEIGHT.
     * Returns null if both products are dismissed or installed.
     *
     * @return string|null 'email' or 'dns' or null
     */
    private function pickProduct()
    {
        $available = [];

        foreach (self::PRODUCTS as $key => $config) {
            if (!$this->isDismissed($config['module_name']) && !$this->isModuleInstalled($config['module_name'])) {
                $available[] = $key;
            }
        }

        if (empty($available)) {
            return null;
        }

        // If only one product is available, return it
        if (count($available) === 1) {
            return $available[0];
        }

        // Both available — use weighted random
        $rand = mt_rand(1, 100);
        return ($rand <= self::EMAIL_WEIGHT) ? 'email' : 'dns';
    }

    /**
     * Get the other product key (for fallback when one is dismissed/installed).
     * Returns null if the other product is also dismissed or installed.
     *
     * @param string $currentProduct
     * @return string|null
     */
    private function getOtherProduct($currentProduct)
    {
        $other = ($currentProduct === 'email') ? 'dns' : 'email';
        $config = self::PRODUCTS[$other];

        if ($this->isDismissed($config['module_name']) || $this->isModuleInstalled($config['module_name'])) {
            return null;
        }

        return $other;
    }

    // =========================================================================
    // Data Queries
    // =========================================================================

    /**
     * Count active domains registered through Openprovider.
     *
     * @return int
     */
    private function getOpenproviderDomainCount()
    {
        try {
            return Capsule::table('tbldomains')
                ->where('registrar', self::REGISTRAR_HANDLE)
                ->where('status', 'Active')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if a specific server module is installed and has active products.
     *
     * @param string $moduleName
     * @return bool
     */
    private function isModuleInstalled($moduleName)
    {
        try {
            $hasProducts = Capsule::table('tblproducts')
                ->where('servertype', $moduleName)
                ->where('retired', '!=', 1)
                ->count();

            return $hasProducts > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the reseller has dismissed the widget for a specific product.
     *
     * @param string $dismissKey
     * @return bool
     */
    private function isDismissed($moduleName)
    {
        self::ensureDismissTableExists();

        try {
            $setting = Capsule::table(self::DISMISS_TABLE)
                ->where('module_name', $moduleName)
                ->first();

            if (!$setting) {
                return false;
            }

            return isset($setting->dismissed) && (int) $setting->dismissed === 1;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function ensureDismissTableExists()
    {
        if (self::$dismissTableChecked) {
            return;
        }

        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable(self::DISMISS_TABLE)) {
                $schema->create(self::DISMISS_TABLE, function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('module_name', 191)->unique();
                    $table->boolean('dismissed')->default(1);
                    $table->timestamps();
                });
            } elseif (!$schema->hasColumn(self::DISMISS_TABLE, 'module_name')) {
                // Lightweight migration from old dismiss_key-based schema to module_name-based schema.
                $schema->table(self::DISMISS_TABLE, function (Blueprint $table) {
                    $table->string('module_name', 191)->nullable()->after('id');
                });

                $mapping = [
                    'op_crosssell_email_dismissed' => 'email_solution',
                    'op_crosssell_dns_dismissed' => 'openproviderpremiumdns',
                ];

                foreach ($mapping as $dismissKey => $moduleName) {
                    Capsule::table(self::DISMISS_TABLE)
                        ->where('dismiss_key', $dismissKey)
                        ->update(['module_name' => $moduleName]);
                }
            }
        } catch (\Exception $e) {
            // Silent fail: widget will continue without persistence.
        }

        self::$dismissTableChecked = true;
    }

    /**
     * Get a unique reseller identifier using the WHMCS license key.
     * Hashed so we don't transmit the actual license key.
     *
     * @return string
     */
    private function getResellerId()
    {
        try {
            $license = Capsule::table('tblconfiguration')
                ->where('setting', 'License')
                ->value('value');

            return $license ? substr(hash('sha256', $license . 'op_crosssell'), 0, 16) : 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Build the CTA URL with tracking parameters.
     *
     * @param string $resellerId
     * @param string $product 'email' or 'dns'
     * @param string $fallbackUrl Direct setup guide URL
     * @return string
     */
    private function buildCtaUrl($resellerId, $product, $fallbackUrl)
    {
        $baseUrl = self::USE_TRACKING_URL ? self::TRACKING_URL : $fallbackUrl;

        $params = http_build_query([
            'reseller_id' => $resellerId,
            'product'     => $product,
            'source'      => 'widget',
        ]);

        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';

        return $baseUrl . $separator . $params;
    }
}

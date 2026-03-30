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
            'body'            => 'Many agencies attach email to 20-30% of domains. Typical profit is €15-25 per mailbox/year.',
            'cta_text'        => '👉 Enable Email Module (5-minute setup)',
            'footer'          => 'Basic mailbox hosting. Auto-provisioned in WHMCS. No migration needed.',
            'adoption_rate'   => 0.20,
            'units_per_domain'=> 2,       // 2 mailboxes per domain
            'margin_per_unit' => 20,      // €20/mailbox/year
            'module_name'     => 'email_solution',
            'setup_guide_url' => 'https://support.openprovider.eu/hc/en-us/articles/30203886347282-WHMCS-Email-Solution-Module-Installation-and-configuration',
        ],
        'pdns' => [
            'title'           => 'Improve reliability and earn more per domain',
            'body'            => 'Many resellers and agencies attach Premium DNS to ~10% of domains to improve uptime and performance. Typical profit is €15-40 per domain/year.',
            'cta_text'        => '👉 Enable Premium DNS Module (5-minute setup)',
            'footer'          => 'Anycast DNS. Auto-provisioned in WHMCS. No migrations needed.',
            'adoption_rate'   => 0.10,
            'units_per_domain'=> 1,       // 1 DNS zone per domain
            'margin_per_unit' => 20,      // €20/domain/year
            'module_name'     => 'openproviderpremiumdns',
            'setup_guide_url' => 'https://support.openprovider.eu/hc/en-us/articles/32384594691730-WHMCS-Premium-Global-Anycast-DNS-module-Installation-configuration-and-management',
        ],
    ];

    // =========================================================================
    // GENERAL CONFIGURATION
    // =========================================================================

    /**
     * Weight for product rotation: probability of showing email vs pdns.
     * 50 = 50/50 even split. 70 = 70% email, 30% pdns.
     * Set to 50 for a clean A/B test.
     */
    const EMAIL_WEIGHT = 50;

    /**
     * Tracking redirect base URL on your server.
     * Query params appended: ?reseller_hash_id=X&product=email|pdns&source=WHMCSCrossSellWidget
     */
    const TRACKING_URL = 'https://assets.openprovider.com/crosssell/track.php';

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
    protected $cacheExpiry = 3600; // 1 hour
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
        try {

            $selectedProduct = $this->pickProduct();

            if ($selectedProduct === null) {
                return ['hidden' => true];
            }

            $config = self::PRODUCTS[$selectedProduct];

            // Count active domains
            $domainCount = $this->getOpenproviderDomainCount();

            // Calculate estimated revenue using this product's formula
            $estimatedRevenue = $domainCount
                * $config['adoption_rate']
                * $config['units_per_domain']
                * $config['margin_per_unit'];

            // Build tracked CTA URL
            $resellerHashId = $this->getResellerHashId();
            $ctaUrl = $this->buildCtaUrl($resellerHashId, $selectedProduct, $config['setup_guide_url']);

            $result = [
                'hidden'            => false,
                'product'           => $selectedProduct,
                'title'             => $config['title'],
                'body'              => $config['body'],
                'cta_text'          => $config['cta_text'],
                'footer'            => $config['footer'],
                'domain_count'      => $domainCount,
                'estimated_revenue' => number_format($estimatedRevenue, 0, ',', ','),
                'cta_url'           => $ctaUrl,
                'dismiss_url'       => 'index.php?op_crosssell_action=dismiss&crosssell_product=' . $selectedProduct . '&token=' . generate_token('link'),
                'reseller_hash_id'       => $resellerHashId,
            ];

            return $result;
        } catch (\Throwable $e) {
            return [
                'error' => 'Unable to load widget data.',
            ];
        }
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
            <div class="alert alert-danger" style="margin-bottom:0;">
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
        $body = $data['body'];
        $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        $ctaText = htmlspecialchars($data['cta_text'], ENT_QUOTES, 'UTF-8');
        $footer = htmlspecialchars($data['footer'], ENT_QUOTES, 'UTF-8');
        $domainCount = (int) $data['domain_count'];
        $estimatedRevenue = htmlspecialchars($data['estimated_revenue'], ENT_QUOTES, 'UTF-8');
        $ctaUrl = htmlspecialchars($data['cta_url'], ENT_QUOTES, 'UTF-8');
        $dismissUrl = htmlspecialchars($data['dismiss_url'], ENT_QUOTES, 'UTF-8');
        $product = htmlspecialchars($data['product'], ENT_QUOTES, 'UTF-8');

        if ($product === 'email') {
            $icon = '<i class="fas fa-envelope"></i>';
            $badgeLabel = 'Email opportunity';
        } else {
            $icon = '<i class="fas fa-globe"></i>';
            $badgeLabel = 'Premium DNS opportunity';
        }

        return <<<EOF
        <div class="widget-content-padded op-crosssell-widget">
            <style>
                .op-crosssell-widget .op-crosssell-top {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 6px;
                }

                .op-crosssell-widget .op-crosssell-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    color: #666;
                    background: #f7f7f9;
                    border: 1px solid #ececec;
                    border-radius: 4px;
                    padding: 5px 8px;
                }

                .op-crosssell-widget .op-crosssell-badge i {
                    color: #5bc0de;
                    font-size: 12px;
                }

                .op-crosssell-widget .op-crosssell-dismiss {
                    font-size: 12px;
                    color: #777;
                    text-decoration: none;
                }

                .op-crosssell-widget .op-crosssell-dismiss:hover {
                    color: #333;
                    text-decoration: underline;
                }

                .op-crosssell-widget .op-crosssell-highlight {
                    background: linear-gradient(180deg, #fcfcfd 0%, #f7f8fa 100%);
                    border: 1px solid #eceef2;
                    border-left: 3px solid #3c8dbc;
                    border-radius: 4px;
                    padding: 10px 12px;
                    margin-bottom: 6px;
                }

                .op-crosssell-widget .op-crosssell-title {
                    font-size: 15px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 6px;
                    line-height: 1.35;
                }

                .op-crosssell-widget .op-crosssell-body {
                    color: #666;
                    font-size: 13px;
                    line-height: 1.55;
                    margin: 0;
                }

                .op-crosssell-widget .op-crosssell-metrics {
                    display: flex;
                    gap: 12px;
                    margin-bottom: 6px;
                }

                .op-crosssell-widget .op-crosssell-metric {
                    flex: 1;
                    border: 1px solid #ececec;
                    border-radius: 4px;
                    padding: 10px;
                    background: #fff;
                    text-align: center;
                }

                .op-crosssell-widget .op-crosssell-metric-value {
                    font-size: 22px;
                    font-weight: 600;
                    line-height: 1.1;
                    margin-bottom: 4px;
                }

                .op-crosssell-widget .op-crosssell-metric-value.domains {
                    color: #f0ad4e;
                }

                .op-crosssell-widget .op-crosssell-metric-value.revenue {
                    color: #5cb85c;
                }

                .op-crosssell-widget .op-crosssell-metric-label {
                    font-size: 12px;
                    color: #888;
                    text-transform: uppercase;
                    letter-spacing: .3px;
                }

                .op-crosssell-widget .op-crosssell-cta {
                    display: inline-block;
                    background: #3c8dbc;
                    color: #fff !important;
                    text-decoration: none;
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 4px;
                    padding: 8px 12px;
                    margin-bottom: 6px;
                }

                .op-crosssell-widget .op-crosssell-cta:hover {
                    background: #337ab7;
                    text-decoration: none;
                }

                .op-crosssell-widget .op-crosssell-note {
                    color: #777;
                    font-size: 12px;
                    line-height: 1.5;
                    margin-top: 0px;
                }

                @media (max-width: 767px) {
                    .op-crosssell-widget .op-crosssell-metrics {
                        flex-direction: column;
                    }
                }
            </style>

            <div class="op-crosssell-top">
                <div class="op-crosssell-badge">
                    {$icon}
                    <span>{$badgeLabel}</span>
                </div>
                <a
                    href="{$dismissUrl}"
                    class="op-crosssell-dismiss"
                    data-op-dismiss-url="{$dismissUrl}"
                    title="Dismiss"
                    onclick="return opCrossSellDismiss(this);"
                >
                    Dismiss
                </a>
            </div>

            <div class="op-crosssell-highlight">
                <div class="op-crosssell-title">{$title}</div>
                <p class="op-crosssell-body">{$body}</p>
            </div>

            <div class="op-crosssell-metrics">
                <div class="op-crosssell-metric">
                    <div class="op-crosssell-metric-value domains">{$domainCount}</div>
                    <div class="op-crosssell-metric-label">Openprovider Domains</div>
                </div>
                <div class="op-crosssell-metric">
                    <div class="op-crosssell-metric-value revenue">EUR {$estimatedRevenue}/year</div>
                    <div class="op-crosssell-metric-label">Estimated Revenue</div>
                </div>
            </div>

            <div class="op-crosssell-footer">
                <a href="{$ctaUrl}" target="_blank" class="op-crosssell-cta">{$ctaText}</a>
                <p class="op-crosssell-note">{$footer}</p>
            </div>

            <script>
                (function () {
                    if (typeof window.opCrossSellDismiss === 'function') {
                        return;
                    }

                    window.opCrossSellDismiss = function (link) {
                        if (!confirm('Are you sure you want to dismiss this recommendation?')) {
                            return false;
                        }

                        var dismissUrl = link.getAttribute('data-op-dismiss-url') || link.getAttribute('href');
                        var ajaxUrl = dismissUrl + (dismissUrl.indexOf('?') === -1 ? '?' : '&') + 'op_crosssell_ajax=1';

                        var refreshWidget = function () {
                            window.location.reload();
                        };

                        if (typeof window.fetch !== 'function') {
                            window.location.href = dismissUrl;
                            return false;
                        }

                        window.fetch(ajaxUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {'X-Requested-With': 'XMLHttpRequest'}
                        }).then(function () {
                            refreshWidget();
                        }).catch(function () {
                            window.location.href = dismissUrl;
                        });

                        return false;
                    };
                })();
            </script>
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
     * @return string|null 'email' or 'pdns' or null
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
        return (mt_rand(1, 100) <= self::EMAIL_WEIGHT) ? 'email' : 'pdns';
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if the reseller has dismissed the widget for a specific product.
     *
     * @param string $moduleName
     * @return bool
     */
    private function isDismissed($moduleName)
    {
        try {
            $schema = Capsule::schema();

            if (!$schema->hasTable(self::DISMISS_TABLE)) {
                return false;
            }

            $setting = Capsule::table(self::DISMISS_TABLE)
                ->where('module_name', $moduleName)
                ->first();

            if (!$setting) {
                return false;
            }

            return (int) $setting->dismissed === 1;
        } catch (\Throwable $e) {
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
    private function getResellerHashId()
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
     * @param string $resellerHashId
     * @param string $product 'email' or 'pdns'
     * @param string $fallbackUrl Direct setup guide URL
     * @return string
     */
    private function buildCtaUrl($resellerHashId, $product, $fallbackUrl)
    {
        $baseUrl = self::USE_TRACKING_URL ? self::TRACKING_URL : $fallbackUrl;

        $params = http_build_query([
            'reseller_hash_id' => $resellerHashId,
            'product'     => $product,
            'source'      => 'WHMCSCrossSellWidget',
        ]);

        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';

        return $baseUrl . $separator . $params;
    }
}

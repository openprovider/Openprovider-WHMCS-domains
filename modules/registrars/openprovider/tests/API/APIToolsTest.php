<?php

namespace OpenProvider\WhmcsRegistrar\Tests\API;

use Mockery;
use OpenProvider\API\APITools;
use OpenProvider\API\DomainNameServer;
use OpenProvider\WhmcsRegistrar\Tests\TestCase;

/**
 * Class APIToolsTest
 *
 * Regression tests for issue #525: transferDomain silently replaced
 * external nameservers with module defaults because
 * APITools::createNameserversArray gated NS inclusion on IP resolution.
 *
 * @package OpenProvider\WhmcsRegistrar\Tests\API
 */
class APIToolsTest extends TestCase
{
    /**
     * Base params shape used by createNameserversArray. Tests override only
     * the keys they care about.
     */
    private function baseParams(array $overrides = []): array
    {
        return array_merge([
            'sld'       => 'example',
            'tld'       => 'com',
            'test_mode' => 'off',
            'ns1'       => null,
            'ns2'       => null,
            'ns3'       => null,
            'ns4'       => null,
            'ns5'       => null,
        ], $overrides);
    }

    /**
     * Regression test for issue #525.
     *
     * External (out-of-bailiwick) nameservers must be accepted by name
     * alone. The function must NOT call gethostbyname or the registry to
     * decide whether to include them, and it must NOT silently drop them.
     */
    public function test_external_nameservers_are_accepted_without_ip_lookup()
    {
        $params = $this->baseParams([
            'ns1' => 'ns1.cloudflare.com',
            'ns2' => 'ns2.cloudflare.com',
        ]);

        $apiHelper = Mockery::mock();
        // The fix must not consult the registry for non-glue NS.
        $apiHelper->shouldNotReceive('getNameserverList');

        $result = APITools::createNameserversArray($params, $apiHelper);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(DomainNameServer::class, $result);
        $this->assertSame('ns1.cloudflare.com', $result[0]->name);
        $this->assertSame('ns2.cloudflare.com', $result[1]->name);
        $this->assertNull($result[0]->ip);
        $this->assertNull($result[1]->ip);
    }

    /**
     * When the user provides "name/ip" the IP must be preserved on the
     * resulting object — both for glue and non-glue records.
     */
    public function test_explicit_ip_in_name_slash_ip_form_is_preserved()
    {
        $params = $this->baseParams([
            'ns1' => 'ns1.example.com/192.0.2.10',
            'ns2' => 'ns2.example.com/192.0.2.11',
        ]);

        $result = APITools::createNameserversArray($params);

        $this->assertCount(2, $result);
        $this->assertSame('ns1.example.com', $result[0]->name);
        $this->assertSame('192.0.2.10', $result[0]->ip);
        $this->assertSame('ns2.example.com', $result[1]->name);
        $this->assertSame('192.0.2.11', $result[1]->ip);
    }

    /**
     * Glue records (NS that are subdomains of the registered/transferred
     * domain) require an IP. When none is supplied AND none can be
     * resolved, the function must throw with a clear message instead of
     * silently dropping the NS — the symptom that caused issue #525.
     */
    public function test_glue_record_without_resolvable_ip_throws()
    {
        $params = $this->baseParams([
            'sld' => 'example',
            'tld' => 'invalid', // .invalid is reserved and unresolvable
            'ns1' => 'ns1.example.invalid',
            'ns2' => 'ns2.example.invalid',
        ]);

        $apiHelper = Mockery::mock();
        $apiHelper->shouldReceive('getNameserverList')->andReturn([]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/in-bailiwick \(a glue record\)/');

        APITools::createNameserversArray($params, $apiHelper);
    }

    /**
     * Glue records with a registry hit must use the registry IP without
     * falling back to gethostbyname.
     */
    public function test_glue_record_uses_registry_ip_when_available()
    {
        $params = $this->baseParams([
            'sld' => 'example',
            'tld' => 'com',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
        ]);

        $apiHelper = Mockery::mock();
        $apiHelper->shouldReceive('getNameserverList')
            ->with('ns1.example.com')
            ->once()
            ->andReturn([(object) ['name' => 'ns1.example.com', 'ip' => '192.0.2.10']]);
        $apiHelper->shouldReceive('getNameserverList')
            ->with('ns2.example.com')
            ->once()
            ->andReturn([(object) ['name' => 'ns2.example.com', 'ip' => '192.0.2.11']]);

        $result = APITools::createNameserversArray($params, $apiHelper);

        $this->assertCount(2, $result);
        $this->assertSame('192.0.2.10', $result[0]->ip);
        $this->assertSame('192.0.2.11', $result[1]->ip);
    }

    /**
     * Empty ns slots (the user only filled ns1) must still raise the
     * minimum-2-nameservers error.
     */
    public function test_throws_when_fewer_than_two_nameservers_supplied()
    {
        $params = $this->baseParams([
            'ns1' => 'ns1.cloudflare.com',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must enter minimum 2 nameservers');

        APITools::createNameserversArray($params);
    }

    /**
     * Mixed list (one external, one glue with IP) must round-trip
     * correctly.
     */
    public function test_mixed_glue_and_external_nameservers()
    {
        $params = $this->baseParams([
            'sld' => 'example',
            'tld' => 'com',
            'ns1' => 'ns1.example.com/192.0.2.10', // glue, with IP
            'ns2' => 'ns2.cloudflare.com',         // external, no IP
        ]);

        $result = APITools::createNameserversArray($params);

        $this->assertCount(2, $result);
        $this->assertSame('ns1.example.com', $result[0]->name);
        $this->assertSame('192.0.2.10', $result[0]->ip);
        $this->assertSame('ns2.cloudflare.com', $result[1]->name);
        $this->assertNull($result[1]->ip);
    }

    /**
     * Nameserver names are normalised to lowercase to match how
     * Openprovider keys glue records.
     */
    public function test_nameserver_names_are_lowercased()
    {
        $params = $this->baseParams([
            'ns1' => 'NS1.Cloudflare.COM',
            'ns2' => 'NS2.CLOUDFLARE.com',
        ]);

        $result = APITools::createNameserversArray($params);

        $this->assertSame('ns1.cloudflare.com', $result[0]->name);
        $this->assertSame('ns2.cloudflare.com', $result[1]->name);
    }
}

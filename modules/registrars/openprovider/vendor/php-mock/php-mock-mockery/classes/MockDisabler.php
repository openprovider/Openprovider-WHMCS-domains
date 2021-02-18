<?php

namespace phpmock\mockery;

use phpmock\Deactivatable;
use Mockery\Mock;

/**
 * Deactivatable Mockery integration.
 *
 * This class disables mock functions with Mockery::close().
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class MockDisabler extends Mock
{

    /**
     * @var Deactivatable The function mocks.
     */
    private $deactivatable;
    
    /**
     * Sets the function mocks.
     *
     * @param Deactivatable $deactivatable The function mocks.
     */
    public function __construct(Deactivatable $deactivatable)
    {
        $this->deactivatable = $deactivatable;
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function mockery_teardown()
    {
        // @codingStandardsIgnoreEnd
        $this->deactivatable->disable();
    }
}

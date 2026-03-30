namespace {namespace};

use phpmock\functions\FunctionProvider;

/**
 * Function provider which delegates to a mockable MockDelegate.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class MockDelegateFunction implements FunctionProvider
{
    
    /**
     * A mocked function will redirect its call to this method.
     *
     * @return mixed Returns the function output.
     */
    public function delegate({signatureParameters})
    {
    }

    public function getCallable()
    {
        return [$this, "delegate"];
    }

    {function}
}

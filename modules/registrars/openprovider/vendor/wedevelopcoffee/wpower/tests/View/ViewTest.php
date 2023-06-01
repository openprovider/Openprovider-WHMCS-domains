<?php
namespace WeDevelopCoffee\wPower\Tests\View;
use Mockery;
use Smarty;
use WeDevelopCoffee\wPower\Security\Csrf;
use WeDevelopCoffee\wPower\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Core\Router;
use WeDevelopCoffee\wPower\View\Asset;
use WeDevelopCoffee\wPower\View\View;

class ViewTest extends TestCase
{
    protected $view;
    protected $mockedSmarty;
    protected $mockedRouter;
    protected $mockedAsset;
    protected $mockedPath;
    private $mockedCsrf;

    /**
    * test_
    * 
    * @return 
    */
    public function test_render ()
    {
        $addonPath  = '/home/admin/domains/domain.com/private_html/modules/addons/addon/';
        $expectedViewData   = 'some-data';

        $this->prep_smarty_mock('get_route', 'getRoute');
        $this->prep_smarty_mock('get_admin_route', 'getAdminRoute');
        $this->prep_smarty_mock('get_current_url', 'getCurrentURL', $this->mockedRouter);
        $this->prep_smarty_mock('generate_csrf', 'generateCsrf');
        $this->prep_smarty_mock('asset', 'asset', $this->mockedAsset);
        $this->prep_smarty_mock('asset_url', 'assetURL', $this->mockedAsset);

        $this->mockedSmarty->shouldReceive('assign')
            ->with([])
            ->once();

        $this->mockedPath->shouldReceive('getModulePath')
            ->andReturn($addonPath)
            ->once();
        
        $this->mockedSmarty->shouldReceive('display')
            ->once()
            ->andReturn($expectedViewData);

        $data = $this->view->render();

        // We do not expect any data.
        $this->assertEquals($data,$expectedViewData);
    }

    protected function prep_smarty_mock ($function, $callbackFunction, $object = null)
    {
        if($object == null)
            $object = $this->view;

        $this->mockedSmarty->shouldReceive('registerPlugin')
            ->with('function', $function, [$object, $callbackFunction])
            ->once();
    }

    public function test_generate_csrf_input_tag()
    {
        $token = '123';

        $this->mockedCsrf->shouldReceive('generateCsrf')
            ->andReturn($token)
            ->once();

        $expected = '<input type="hidden" name=\'_csrf\' value="' . $token . '">';

        $result = $this->view->generateCsrf();
        $this->assertEquals($expected, $result);

    }
    
    /**
    * setUp
    * 
    * @return void
    */
    public function setUp ()
    {
        $this->mockedSmarty = Mockery::mock(Smarty::class);
        $this->mockedRouter = Mockery::mock(Router::class);
        $this->mockedAsset  = Mockery::mock(Asset::class);
        $this->mockedPath   = Mockery::mock(Path::class);
        $this->mockedCsrf   = Mockery::mock(Csrf::class);

        $this->view         = new View($this->mockedSmarty, $this->mockedRouter, $this->mockedAsset, $this->mockedPath, $this->mockedCsrf);
    }
    
    
}
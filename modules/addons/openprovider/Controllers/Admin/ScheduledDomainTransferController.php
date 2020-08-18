<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use Carbon\Carbon;
use OpenProvider\WhmcsDomainAddon\Models\ScheduledDomainTransfer;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use WHMCS\Database\Capsule;
use ZipArchive;
use ZipStream\ZipStream;


/**
 * Client controller dispatcher.
 */
class ScheduledDomainTransferController extends ViewBaseController {

    /**
     * @var object WHMCS\Database\Capsule;
     */
    private $capsule;

    /**
     * @var object Carbon
     */
    private $carbon;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator,Capsule $capsule, Carbon $carbon)
    {
        parent::__construct($core, $view, $validator);

        $this->capsule = $capsule;
        $this->carbon = $carbon;
    }

    /**
     * Show an index with all the domains.
     * 
     * @return string
     */
    public function index($params, $notification = '')
    {
        $module_link = $this->view->getRoute(['route' => 'scheduledDomainTransfers']);
        $query = new ScheduledDomainTransfer();

        if(isset($_SESSION['op_sch_domain_hide_act_domains']) && $_SESSION['op_sch_domain_hide_act_domains'] == 'yes')
        {
            $query = $query->where('status', '!=', 'ACT');
        }

        list($pagination, $record_list) = $this->pagination($module_link, $query);

        return $this->view('scheduled_domain_transfer/index', ['scheduled_domain_transfers' => $record_list, 'notification' => $notification, 'pagination' => $pagination, 'LANG' => $params['_lang']]);
    }

    /**
     * Show an index with all the domains.
     *
     * @return string
     */
    public function clean($params, $notification = '')
    {
        $query = new ScheduledDomainTransfer();
        $query->where('status', 'ACT')->delete();


        return $this->index($params, ['type' => 'success', 'message' => 'deleted_all_scheduled_domain_transfers']);
    }

    /**
     * Toggle the filter
     *
     * @return string
     */
    public function toggle($params, $notification = '')
    {
        if(isset($_SESSION['op_sch_domain_hide_act_domains']) && $_SESSION['op_sch_domain_hide_act_domains'] == 'yes')
        {
            unset($_SESSION['op_sch_domain_hide_act_domains']);
        }
        else
            $_SESSION['op_sch_domain_hide_act_domains'] = 'yes';

        header("Location: " . $this->view->getRoute(['route' => 'scheduledDomainTransfers']));
        exit;
    }


    protected function pagination($module_link, $query)
    {
        $record_count = $query->count();
        $offset=25;
        $offsets=$record_count/$offset;
        if (!$_REQUEST['page'])
            $page = 1;
        else
            $page = $_REQUEST['page'];

        if ($record_count != null)
        {
            $record_list = $query
                ->offset(($page-1)*$offset)
                ->limit($offset)
                ->get();
        }

        $HTMLpagePagination = "";
        if ($record_count < $offset+1){
            $HTMLpagePagination .= '<div class="clearfix">';
            $HTMLpagePagination .= '<div class="hint-text pull-left">Showing <b>'.$record_count.'</b> out of <b>'.$record_count.'</b> pages</div>';
            $HTMLpagePagination .= '</div>';
        }
        else
        {
            if ($record_count > $offset)
                $HTMLpagePagination .= '<div class="clearfix">';
            if ($page*$offset<$record_count)
                $HTMLpagePagination .= '<div class="hint-text pull-left">Showing <b>'.$page*$offset.'</b> out of <b>'.$record_count.'</b> pages</div>';
            else
                $HTMLpagePagination .= '<div class="hint-text pull-left">Showing <b>'.$record_count.'</b> out of <b>'.$record_count.'</b> pages</div>';
        }


        $HTMLpagePagination .= '<div class="clearfix"></div>
<div class="text-center"><ul style="margin:0px 0px" class="pagination">';
        if ($page<2)
            $HTMLpagePagination .= '<li class="page-item disabled"><a href="#">Previous</a></li>';
        else
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page-1).'">Previous</a></li>';

        if ($page-2>0)
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page-2).'" class="page-link">'.($page-2).'</a></li>';

        if ($page-1>0)
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page-1).'" class="page-link">'.($page-1).'</a></li>';

        $HTMLpagePagination .= '<li class="page-item active"><a href="'.$module_link.'&page='.($page).'" class="page-link">'.($page).'</a></li>';

        if ($page+1<$offsets+1)
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page+1).'" class="page-link">'.($page+1).'</a></li>';

        if ($page+2<$offsets+1)
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page+2).'" class="page-link">'.($page+2).'</a></li>';

        if ($page+1<$offsets+1)
            $HTMLpagePagination .= '<li class="page-item"><a href="'.$module_link.'&page='.($page+1).'" class="page-link">Next</a></li>';
        else
            $HTMLpagePagination .= '<li class="page-item disabled"><a href="#" class="page-link">Next</a></li>';

        $HTMLpagePagination .= '</ul>';
        $HTMLpagePagination .= '</div></div>';

        return [$HTMLpagePagination, $record_list];
    }



}

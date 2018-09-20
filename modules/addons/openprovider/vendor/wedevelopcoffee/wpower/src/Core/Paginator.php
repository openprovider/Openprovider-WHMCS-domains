<?php
namespace WeDevelopCoffee\wPower\Core;

use WHMCS\Database\Capsule as DB;
use Illuminate\Pagination\LengthAwarePaginator;

class Paginator
{
    /**
     * Number of items per page.
     *
     * @var integer
     */
    protected $perPage = 25;

    /**
     * Total number of results.
     *
     * @var integer
     */
    protected $total = 0;

    /**
     * The query object.
     *
     * @var object
     */
    protected $query;

    /**
     * The model object
     *
     * @var object
     */
    protected $model;

    /**
     * The current page.
     *
     * @var integer
     */
    protected $currentPage;

    /**
     * The last page.
     *
     * @var integer
     */
    protected $lastPage;

    /**
     * The first item.
     *
     * @var integer
     */
    protected $firstItem;

    /**
     * The last item.
     *
     * @var integer
     */
    protected $lastItem;

    /**
    * Generate the results.
    * 
    * @return $object
    */
    public function getResult ()
    {
        $paginator = new LengthAwarePaginator($this->query, $this->total, $this->perPage);

        $this->currentPage  = $paginator->currentPage();
        $this->lastPage     = $paginator->lastPage();
        $this->firstItem    = $paginator->firstItem() - 1;
        $this->lastItem     = $paginator->lastItem();

        $return['items']    = $this->query->skip($this->firstItem)
                                ->take($this->perPage)->get();
        $return['pagination'] = $paginator;

        return $return;
    }

    /**
    * Set the query
    * 
    * @return $this
    */
    public function setQueryAndModel ($query, $model)
    {
        $this->query    = $query;
        $this->model    = $model;

        $countQuery     = $model->selectRaw('COUNT(*) AS total')
            ->from(DB::raw('(' . $query->toSql() . ') AS count_total'));
        $this->total = $countQuery->get()[0]['total'];

        return $this;
    }

    /**
    * Set the amount of items per page.
    * 
    * @return $this
    */
    public function setPerPage ($perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }
}

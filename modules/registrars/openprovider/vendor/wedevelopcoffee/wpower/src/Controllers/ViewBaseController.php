<?php
namespace WeDevelopCoffee\wPower\Controllers;

use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;


/**
 * Controller dispatcher
 */
class ViewBaseController
{
    /**
     * @var Core
     */
    protected $core;
    /**
     * @var View
     */
    protected $view;
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var array $rules The rules.
     */
    protected $rules;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator)
    {
        $this->view = $view;
        $this->validator = $validator;

        $this->validator->setRules($this->rules);
    }

    /**
     * Validate the input data.
     */
    protected function validate()
    {
        return !$this->validator->failed();
    }

    /**
     * Generate the view.
     *
     * @param $view
     * @param $data
     * @return mixed
     */
    protected function view($view, $data)
    {
        $data['errors'] = $this->validator->getFailedRules();

        return $this->view
            ->setData($data)
            ->setView($view)
            ->render();
    }
}

<?php

namespace OpenProvider\API;

/**
 * Customer Tags
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2020
 */
class CustomerTags extends \OpenProvider\API\AutoloadConstructor
{
    /**
     *
     * @var array
     */
    protected $tags = [];
    
    public function __construct($tags = array())
    {
        parent::__construct($tags);

        $this->setTags($tags);
    }
    
    /**
     * Set new tags
     * @param array
     */
    public function setTags($tags = array())
    {
        if (!count($tags) || empty($tags))
            $this->tags = [];

        $this->tags = array_map(function ($tag) {
            return [
                'key'   => 'customer',
                'value' => $tag,
            ];
        }, $tags);
    }

    /**
     * Get tags
     * 
     * @licensed to Openprovider
     * @return array|string
     */
    public function getTags()
    {
        return $this->tags;
    }
}
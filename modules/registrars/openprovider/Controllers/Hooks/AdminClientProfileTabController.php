<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use Carbon\Carbon;
use OpenProvider\WhmcsRegistrar\enums\DatabaseTable;
use OpenProvider\WhmcsRegistrar\helpers\DB as DBHelper;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use WHMCS\Database\Capsule;

/**
 * Class AdminClientProfileTabController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */


class AdminClientProfileTabController
{
    public function additionalFields($vars)
    {
        $tagsData     = [];
        $userId       = $vars['userid'];
        $OpenProvider = new OpenProvider();

        try {
            $tagsData = $OpenProvider->api->sendRequest('searchTagRequest');
        } catch (\Exception $e) {}

        $tags = [];
        if (isset($tagsData['results']) && count($tagsData['results']) > 0) {
            $tags = array_map(function ($item) {
                return $item['value'];
            }, $tagsData['results']);
        }

        $selectedTag = '';
        try {
            $clientTag = false;
            if (DBHelper::checkTableExist(DatabaseTable::ClientTags))
                $clientTag = Capsule::table(DatabaseTable::ClientTags)
                    ->where('clientid', $userId)
                    ->first();

            if ($clientTag)
                $selectedTag = $clientTag->tag;
        } catch (\Exception $e) {}

        $options = false;
        if (count($tags))
            $options = implode('', array_map(function ($tag) use ($selectedTag) {
                if ($selectedTag == $tag)
                    return "<option value='{$tag}' selected>{$tag}</option>";
                return "<option value='{$tag}'>{$tag}</option>";
            }, $tags));

        $onClickUpdateContactsTag = "
<script>
    $('.update-contacts-tag').on('click', function (e) {
        e.preventDefault();
        let btn = $(this);
        const searchUrlParams = new URLSearchParams(window.location.search);
        let userid = searchUrlParams.has('userid') ? searchUrlParams.get('userid') : '';
        btn.attr('disabled', true);
        $.ajax({
            method: 'GET',
            url: '" . Configuration::getApiUrl('contacts-tag-update') . "',
            data: {
                userid,
            }
        }).done(function (reply) {
            btn.attr('disabled', false);
        });
        return false;
    })            
</script>
        ";

        return [
            'Tag' => "<select tabindex='50' class='form-control input-300' name='additionalFieldTag'><option value=''>no tags</option>{$options}</select>",
            'Update customer tags (might take a while, save changes before start)' => '<button class="update-contacts-tag">Update</button>' . $onClickUpdateContactsTag,
        ];
    }

    public function saveFields($vars)
    {
        $tag = $vars['additionalFieldTag'];
        $userId = $vars['userid'];

        if (!DBHelper::checkTableExist(DatabaseTable::ClientTags)) {
            try {
                Capsule::schema()
                    ->create(
                        DatabaseTable::ClientTags,
                        function ($table) {
                            $table->increments('id');
                            $table->bigInteger('clientid');
                            $table->string('tag');
                            $table->timestamps();
                        }
                    );
            } catch (\Exception $e) {}
        }

        try {
            Capsule::table(DatabaseTable::ClientTags)
                ->updateOrInsert(
                    ['clientid' => $userId],
                    ['tag' => $tag, 'updated_at' => Carbon::now()]
                );
        } catch (\Exception $e) {
            var_dump($e->getMessage());die;
        }
    }
}
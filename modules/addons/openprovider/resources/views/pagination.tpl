<div style="text-align:center">
    {if $pagination->hasPages()}
        <ul class="pagination">
            <!-- Previous Page Link -->
            {if $pagination->currentPage() == '1'}
                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
            {else}
                <li class="page-item"><a class="page-link" href="{get_route route=$route page=($pagination->currentPage()-1)}" rel="prev">&laquo;</a></li>
            {/if}

            <!-- Pagination Elements -->
            {foreach $elements as $element}
                <!-- "Three Dots" Separator -->
                {if is_string($element)}
                    <li class="page-item disabled"><span class="page-link">{$element}</span></li>
                {/if}

                <!-- Array Of Links -->
                {if is_array($element)}
                    {foreach $element as $page => $url}
                        {if $page == $pagination->currentPage()}
                            <li class="page-item active"><span class="page-link">{$page}</span></li>
                        {else}
                            <li class="page-item"><a class="page-link" href="{$url}">{$page}</a></li>
                        {/if}
                    {/foreach}
                {/if}
            {/foreach}

            <!-- Next Page Link -->
            {if $pagination->hasMorePages()}
                <li class="page-item"><a class="page-link" href="{get_route route=$route page=($pagination->currentPage()+1)}" rel="next">&raquo;</a></li>
            {else}
                <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
            {/if}
        </ul>
    {/if}
</div>
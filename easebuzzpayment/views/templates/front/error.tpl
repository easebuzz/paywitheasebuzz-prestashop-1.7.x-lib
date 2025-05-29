{extends 'page.tpl'}

{block name='page_content'}
  <h2>{l s='Error in Easebuzz' mod='easebuzz'}</h2>

  <div class="table-responsive-row clearfix">
    <p class="easebuzz_error_msg">
        {if $error_msg}<span class="short">{$error_msg|escape:'htmlall':'UTF-8'}</span>{/if}
    </p>
  </div>
{/block}
{extends 'page.tpl'}

{block name='page_content'}
<style>
#cancel_order_div .order-cancelled-image {
    text-align: center;
    margin-bottom: 20px;
}

#cancel_order_div .order-cancelled-image img {
    max-width: 100%;
    height: auto;
}

#cancel_order_div .box {
    padding: 30px;
    max-width: 100%;
    margin: 0 auto;
}

#cancel_order_div h1 {
    text-align: center;
    color: #dc3545;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
}

#cancel_order_div h2 {
    text-align: center;
    color: #333;
    font-size: 20px;
    font-weight: bold;
    margin: 20px 0;
}

#cancel_order_div p {
    font-size: 16px;
    color: #555;
    text-align: center;
}

#cancel_order_div .order-details-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ddd;
}

#cancel_order_div .table-cell {
    padding: 15px;
    text-align: left;
    font-size: 16px;
    border: 1px solid #ddd;
}

#cancel_order_div .title-cell {
    background-color: #f7f7f7;
    font-weight: bold;
    color: #333;
    width: 25%;
}

#cancel_order_div .data-cell {
    background-color: #fff;
}

#cancel_order_div .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #28a745;
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 5px;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

#cancel_order_div .btn-primary span.material-icons {
    font-size: 20px;
    margin-right: 8px;
}

#cancel_order_div .btn-primary:hover {
    background-color: #218838;
}

#cancel_order_div .btn-primary:active {
    background-color: #1e7e34;
}

#cancel_order_div .material-icons {
    vertical-align: middle;
    margin-right: 8px;
}

</style>

<div class="box" id="cancel_order_div">
    <!-- Title -->
    <h1>{l s='Order Failed' mod='easebuzzpayment'}</h1>

    <!-- Display the dynamic message -->
    <p>
        <strong class="dark">
            {$message|escape:'html'}
        </strong>
    </p>

    

    <!-- Additional Information -->
    <h2>{l s='What You Can Do Next?' mod='easebuzzpayment'}</h2>
    <p>
        {l s='If the issue persists, please contact our customer support team or retry the payment.' mod='easebuzzpayment'}
    </p>

    <!-- View Order Details Button -->
    <p style="text-align: center;">
        {if $id_order && $id_order > 0}
            <a href="{$link->getPageLink('order-detail', true, null, ['id_order' => $id_order])}" class="btn btn-primary">
                <span class="material-icons">visibility</span> {l s='View Order Details' mod='easebuzzpayment'}
            </a>
        {/if}
    </p>
    
    <!-- Contact Support -->
    <p>{l s='For further assistance, please reach out to our friendly' mod='easebuzzpayment'} 
        <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support team.' mod='easebuzzpayment'}</a>
    </p>
</div>
{/block}

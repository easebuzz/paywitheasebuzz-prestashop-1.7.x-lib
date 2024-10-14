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
    <!-- Cancellation Image -->
    <div class="order-cancelled-image">
        <img src="{$module_dir}views/img/order_cancel.jpg" alt="{l s='Order Cancelled' mod='easebuzzpayment'}" />
    </div>

    <!-- Title -->
    <h1>{l s='Order Cancelled' mod='easebuzzpayment'}</h1>

    <p>
        <strong class="dark">
            {l s='We’re sorry to inform you that your order placed on %s has been cancelled.' sprintf=[$shop_name] mod='easebuzzpayment'}
        </strong>
    </p>
    
    <!-- Message -->
    <p>{l s='Unfortunately, the payment for your order did not go through. Please check your account for any pending transactions and feel free to reach out if you need assistance.' mod='easebuzzpayment'}</p>

    <!-- Order Details Title -->
    <h2>{l s='Your Order Details' mod='easebuzzpayment'}</h2>

    <!-- Order Details Table -->
    <table class="order-details-table">
        <tr>
            <td class="table-cell title-cell"><span class="material-icons">person</span> {l s='Customer Name' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$firstname}</strong></td>
            <td class="table-cell title-cell"><span class="material-icons">email</span> {l s='Customer Email' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$email}</strong></td>
        </tr>
        <tr>
            <td class="table-cell title-cell"><span class="material-icons">phone</span> {l s='Customer Phone' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$phone}</strong></td>
            <td class="table-cell title-cell"><span class="material-icons">attach_money</span> {l s='Order Amount' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><span class="price"><strong>{$total_to_pay}</strong></span></td>
        </tr>
        <tr>
            <td class="table-cell title-cell"><span class="material-icons">confirmation_number</span> {l s='Transaction No' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$txnid}</strong></td>
            <td class="table-cell title-cell"><span class="material-icons">vpn_key</span> {l s='Easebuzz ID' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$easepayid}</strong></td>
        </tr>
        <tr>
            <td class="table-cell title-cell"><span class="material-icons">credit_card</span> {l s='Payment Mode' mod='easebuzzpayment'}</td>
            <td class="table-cell data-cell"><strong>{$mode}</strong></td>
            <td class="table-cell title-cell">
                {if !isset($reference)}
                    <span class="material-icons">assignment</span> {l s='Order ID' mod='easebuzzpayment'}
                {else}
                    <span class="material-icons">bookmark</span> {l s='Order Reference' mod='easebuzzpayment'}
                {/if}
            </td>
            <td class="table-cell data-cell">
                {if !isset($reference)}
                    <strong>{$id_order}</strong>
                {else}
                    <strong>{$reference}</strong>
                {/if}
            </td>
        </tr>
    </table>

    <!-- View Order Details Button -->
    <p style="text-align: center;">
        <a href="{$link->getPageLink('order-detail', true, null, ['id_order' => $id_order])}" class="btn btn-primary">
            <span class="material-icons">visibility</span> {l s='View Order Details' mod='easebuzzpayment'}
        </a>
    </p>
    
    <p>{l s='If you have any questions or need assistance, please don’t hesitate to reach out to our friendly' mod='easebuzzpayment'} 
        <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support team.' mod='easebuzzpayment'}</a>
    </p>
</div>
{/block}

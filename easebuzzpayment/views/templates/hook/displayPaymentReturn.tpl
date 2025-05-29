<div class="box">
    <h2 class="dark" style="text-align: center; color: #333; font-size: 24px;">
        {l s='Your order on %s is complete.' sprintf=[$shop_name] mod='easebuzzpayment'}
    </h2>
    
    <p style="text-align: center; color: #555; font-size: 18px;">
        Your order has been processed successfully with Easebuzz Payment Solution.
    </p>

    <p style="text-align: center; font-weight: bold; font-size: 20px; margin-top: 20px;">
        {l s='Order Details' mod='easebuzzpayment'}
    </p>

    <table class="order-details-table" style="width: 100%; border-collapse: collapse; margin: 20px auto; border: 1px solid #ddd;">
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

    <p style="text-align: center; margin-top: 30px;">
        <a href="{$link->getPageLink('order-detail', true, null, ['id_order' => $id_order])}" class="btn btn-primary">
            <span class="material-icons">visibility</span> {l s='View Order Details' mod='easebuzzpayment'}
        </a>
    </p>

    <!-- Customer Support Information -->
    <p style="text-align: center; margin-top: 20px;">
        {l s='If you have any questions, comments, or concerns, please contact our' mod='easebuzzpayment'}
        <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}" style="color: #007bff; font-weight: bold;">
            {l s='expert customer support team.' mod='easebuzzpayment'}
        </a>
    </p>
</div>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<style>
    #content-hook_payment_return .order-details-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-family: Arial, sans-serif;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #ddd;
    }

    #content-hook_payment_return .table-cell {
        padding: 15px;
        text-align: left;
        font-size: 16px;
        border: 1px solid #ddd;
    }

    #content-hook_payment_return .title-cell {
        background-color: #f7f7f7;
        font-weight: bold;
        color: #333;
        width: 25%;
    }

    #content-hook_payment_return .data-cell {
        background-color: #fff;
    }

    #content-hook_payment_return .box {
        padding: 30px;
        max-width: 100%;
        margin: auto;
    }

    #content-hook_payment_return .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #28a745;
        color: white;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 5px;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    #content-hook_payment_return .btn-primary span.material-icons {
        font-size: 20px;
        margin-right: 8px;
        color:#fff !important;
    }

    #content-hook_payment_return .btn-primary:hover {
        background-color: #218838;
    }

    #content-hook_payment_return .btn-primary:active {
        background-color: #1e7e34;
    }

    #content-hook_payment_return .material-icons {
        vertical-align: middle;
        margin-right: 8px;
        color: #555;
    }
</style>

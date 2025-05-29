<script src="https://ebz-static.s3.ap-south-1.amazonaws.com/easecheckout/v2.0.0/easebuzz-checkout-v2.min.js"></script>

<script>
    // Initialize EasebuzzCheckout
    var easebuzzCheckout = new EasebuzzCheckout('{$easebuzz_checkout_key}', {$app_env});
    
    // Auto-trigger the checkout flow when the payment template loads
    document.addEventListener('DOMContentLoaded', function() {
        var options = {
            access_key: 'd874f5b912f8bb4ddbe4947ab74dc4e0bfbf840d73b8dd3a83bf2bc545c8cc8d',
            onResponse: (response) => {
                console.log(response);
                if (response.success) {
                    window.location.href = 'index.php?controller=order-confirmation&id_cart={$cart_id}&id_module={$module_id}&key={$cart_secure_key}';
                } else {
                    alert('Payment failed: ' + response.error_message);
                    window.location.href = 'index.php?controller=order';
                }
            },
            theme: "#123456" // Replace with your desired color theme
        };

        // Trigger the payment initiation
        easebuzzCheckout.initiatePayment(options);
    });
</script>

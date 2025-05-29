<script src="https://ebz-static.s3.ap-south-1.amazonaws.com/easecheckout/v2.0.0/easebuzz-checkout-v2.min.js"></script>
<script>
    // ✅ Reusable Bootstrap Alert Function
    function showBootstrapAlert(message, type = "warning") {
        const contentWrapper = document.getElementById("content-wrapper");

        if (contentWrapper) {
            // Remove existing alert if it exists
            const existingAlert = contentWrapper.querySelector(".my-easebuzz-alert");
            if (existingAlert) {
                existingAlert.remove();
            }

            // Create a new alert
            const alertDiv = document.createElement("div");
            alertDiv.className = 'alert alert-'+type+' alert-dismissible show my-easebuzz-alert';
            alertDiv.role = "alert";
            alertDiv.innerHTML = message+'<button type="button" class="btn-close" data-dismiss="alert" aria-label="Close" style="background-color:  transparent !important;color: red !important;border: none !important;font-size: 18px;float: right;cursor: pointer;">X</button>';

            // Prepend the new alert
            contentWrapper.prepend(alertDiv);
        } else {
            alert(message); // Fallback if contentWrapper is missing
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var paymentButton = document.querySelector('#payment-confirmation button');
        var moduleLink = '{$module_link}';
        var validationLink = '{$validation_link}';

        if (paymentButton) {
            paymentButton.addEventListener('click', function (e) {
                e.preventDefault();
                const button = this;
                button.disabled = true;
                button.textContent = 'Processing...';

                fetch(moduleLink, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ /* Add necessary payment data here */ }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var easebuzzCheckout = new EasebuzzCheckout('{$easebuzz_checkout_key}', '{$app_env}');
                        var options = {
                            access_key: data.access_token,
                            onResponse: (response) => {
                                button.textContent = 'Please Wait...';

                                fetch(validationLink, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(response),
                                })
                                .then(response2 => response2.json())
                                .then(data => {
                                    if (data.success) {
                                        if (data.data.redirect_url) {
                                            window.location.href = data.data.redirect_url;
                                        }
                                    } else {
                                        button.disabled = false;
                                        button.textContent = 'Pay with Easebuzz';

                                        var res_msg = 'Transaction failed. Current transaction status: ' + response.status;
                                        showBootstrapAlert(res_msg, "warning");
                                    }
                                })
                                .catch(error => {
                                    button.disabled = false;
                                    button.textContent = 'Pay with Easebuzz';
                                    showBootstrapAlert('An unexpected error occurred.', "danger");
                                });
                            },
                            theme: "#123456"
                        };
                        easebuzzCheckout.initiatePayment(options);
                    } else {                            
                        button.disabled = false;
                        button.textContent = 'Pay with Easebuzz';
                        showBootstrapAlert('Payment failed: ' + data.message, "danger");
                    }
                })
                .catch(error => {
                    button.disabled = false;
                    button.textContent = 'Pay with Easebuzz';
                    showBootstrapAlert('An error occurred: ' + error.message, "danger");
                });
            });
        } else {
            showBootstrapAlert('Something went wrong.', "danger");
        }
    });
</script>

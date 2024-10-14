<script type="text/javascript">
    var url = '{$paymentStatusUrl}';
    document.addEventListener("DOMContentLoaded", function() {
        
        var orderHistorySection = document.getElementById("order-history");

        if (orderHistorySection) {
            var existingH3 = orderHistorySection.querySelector("h3");
            if (existingH3) {
                existingH3.remove();
            }
    
            var newRowDiv = document.createElement("div");
            newRowDiv.className = "row";
    
            var colLeft = document.createElement("div");
            colLeft.className = "col-md-6";
            var newH3 = document.createElement("h3");
            newH3.textContent = "Follow your order's status step-by-step";
            colLeft.appendChild(newH3);
    
            var colRight = document.createElement("div");
            colRight.className = "col-md-6";
            var button = document.createElement("button");
            button.id = "check-payment-status";
            button.className = "btn btn-warning btn-sm";
            button.style.cssText = "float: right; margin-bottom: 8px;";
            button.innerHTML = '<span class="material-icons" style="margin-right: 5px;">check_circle</span>Update Payment Status';
    
            colRight.appendChild(button);
    
            newRowDiv.appendChild(colLeft);
            newRowDiv.appendChild(colRight);
    
            orderHistorySection.prepend(newRowDiv);
            
            button.addEventListener("click", function() {
                button.textContent = "Checking...";
                button.disabled = true;
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error updating payment status: ' + data.message);
                    }
                    button.textContent = "Update Payment Status";
                    button.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while checking the payment status.');
                    button.textContent = "Update Payment Status";
                    button.disabled = false;
                });
            });
            
        }
    });
</script>

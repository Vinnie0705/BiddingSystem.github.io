// Update total price when quantity changes
document.querySelectorAll('.cart-item-quantity input').forEach(function(input) {
    input.addEventListener('input', function() {
      updateTotalPrice();
    });
  });
  
  // Increase and decrease quantity buttons
  document.querySelectorAll('.quantity-increase').forEach(function(button) {
    button.addEventListener('click', function() {
      let quantityInput = button.previousElementSibling;
      quantityInput.value = parseInt(quantityInput.value) + 1;
      updateTotalPrice();
    });
  });
  
  document.querySelectorAll('.quantity-decrease').forEach(function(button) {
    button.addEventListener('click', function() {
      let quantityInput = button.nextElementSibling;
      if (parseInt(quantityInput.value) > 1) {
        quantityInput.value = parseInt(quantityInput.value) - 1;
        updateTotalPrice();
      }
    });
  });
  
  // Update total price calculation
  function updateTotalPrice() {
    let totalPrice = 0;
    document.querySelectorAll('.cart-item').forEach(function(item) {
      let price = parseFloat(item.querySelector('.cart-item-price-value').textContent);
      let quantity = parseInt(item.querySelector('.cart-item-quantity-input').value);
      totalPrice += price * quantity;
    });
    document.querySelector('.cart-total-price').textContent = totalPrice.toFixed(2);
  }
  
  // Remove item from cart
  document.querySelectorAll('.remove-item-btn').forEach(function(button) {
    button.addEventListener('click', function() {
      button.closest('.cart-item').remove();
      updateTotalPrice();
    });
  });
  
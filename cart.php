<?php include("includes/client-header.php"); ?>

<div class="cart-page">
  <!-- Cart Header -->
  <div class="cart-header">
    <h2>Shopping Cart</h2>
    <p>Review the packages you’ve added to your cart.</p>
  </div>

  <!-- Cart Items Section -->
  <div class="cart-items">
    <!-- Loop through cart items -->
    <div class="cart-item">
      <div class="cart-item-details">
        <div class="cart-item-info">
          <h3 class="cart-item-title">Package Title</h3>
          <p class="cart-item-type">Package Type</p>
        </div>
      </div>
      <div class="cart-item-quantity">
        <button class="quantity-decrease">-</button>
        <input type="number" value="1" min="1" class="cart-item-quantity-input">
        <button class="quantity-increase">+</button>
      </div>
      <div class="cart-item-price">
        RM<span class="cart-item-price-value">100.00</span>
      </div>
      <div class="cart-item-remove">
        <button class="remove-item-btn">Remove</button>
      </div>
    </div>
    <!-- End Loop -->

    <!-- Total Price Section -->
    <div class="cart-total">
      <p><strong>Total Price:</strong> $<span class="cart-total-price">100.00</span></p>
    </div>
  </div>

  <!-- Cart Actions -->
  <div class="cart-actions">
    <button class="continue-shopping-btn" onclick="window.location.href='packages-view.php'">Continue Shopping</button>
    <button class="checkout-btn" onclick="window.location.href='checkout.php'">Proceed to Checkout</button>
  </div>
</div>

<?php include("includes/footer.php"); ?>
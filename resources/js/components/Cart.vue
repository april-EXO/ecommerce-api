<template>
  <div class="cart-page">
    <!-- Cart Header -->
    <div class="cart-header">
      <div class="container">
        <div class="row align-items-center py-3">
          <div class="col">
            <h2 class="cart-title mb-0">
              <i class="fas fa-shopping-cart me-2"></i>
              Shopping Cart
            </h2>
          </div>
          <div class="col-auto">
            <button 
              @click="goBack" 
              class="btn btn-outline-secondary"
            >
              <i class="fas fa-arrow-left me-1"></i>
              Continue Shopping
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="container py-4">
      <!-- Loading State -->
      <div v-if="cartStore.loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading your cart...</p>
      </div>

      <!-- Empty Cart -->
      <div v-else-if="!cartStore.cartItems.length" class="empty-cart text-center py-5">
        <div class="empty-cart-icon mb-3">
          <i class="fas fa-shopping-cart fa-4x text-muted"></i>
        </div>
        <h3 class="text-muted">Your cart is empty</h3>
        <p class="text-muted mb-4">Add some products to get started</p>
        <button @click="goBack" class="btn btn-primary">
          <i class="fas fa-shopping-bag me-1"></i>
          Start Shopping
        </button>
      </div>

      <!-- Cart Items -->
      <div v-else class="row">
        <!-- Cart Items List -->
        <div class="col-lg-8">
          <div class="cart-items">
            <div class="cart-item-header d-none d-md-block">
              <div class="row text-muted fw-bold">
                <div class="col-md-6">Product</div>
                <div class="col-md-2 text-center">Price</div>
                <div class="col-md-2 text-center">Quantity</div>
                <div class="col-md-2 text-center">Total</div>
              </div>
              <hr>
            </div>

            <!-- Cart Item -->
            <div 
              v-for="item in cartStore.cartItems" 
              :key="item.id"
              class="cart-item mb-4"
            >
              <div class="row align-items-center">
                <!-- Product Info -->
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <div class="product-image me-3">
                      <img 
                        :src="item.product.image_full_url" 
                        :alt="item.product.name"
                        class="img-fluid rounded"
                        style="width: 80px; height: 80px; object-fit: cover;"
                        @error="handleImageError"
                      >
                    </div>
                    <div class="product-info">
                      <h5 class="product-name mb-1">{{ item.product.name }}</h5>
                      <p class="product-description text-muted mb-1">
                        {{ item.product.description }}
                      </p>
                      <small class="text-muted">
                        Category: {{ item.product.category?.name }}
                      </small>
                    </div>
                  </div>
                </div>

                <!-- Price -->
                <div class="col-md-2 text-center">
                  <div class="price">
                    <strong>{{ getFormattedPrice(item.product.current_price?.price) }}</strong>
                  </div>
                </div>

                <!-- Quantity Controls -->
                <div class="col-md-2 text-center">
                  <div class="quantity-controls d-flex align-items-center justify-content-center">
                    <button 
                      @click="decreaseQuantity(item)"
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="item.quantity <= 1 || updating === item.id"
                    >
                      <i class="fas fa-minus"></i>
                    </button>
                    <span class="quantity mx-2 fw-bold">{{ item.quantity }}</span>
                    <button 
                      @click="increaseQuantity(item)"
                      class="btn btn-outline-secondary btn-sm"
                      :disabled="item.quantity >= 99 || updating === item.id"
                    >
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>

                <!-- Total Price -->
                <div class="col-md-2 text-center">
                  <div class="item-total">
                    <strong>{{ item.formatted_total_price }}</strong>
                  </div>
                  <button 
                    @click="removeFromCart(item)"
                    class="btn btn-link text-danger btn-sm mt-1"
                    :disabled="updating === item.id"
                  >
                    <i class="fas fa-trash-alt"></i>
                    Remove
                  </button>
                </div>
              </div>
              
              <!-- Mobile Layout -->
              <div class="d-md-none mt-3">
                <div class="row">
                  <div class="col-6">
                    <small class="text-muted">Price:</small><br>
                    <strong>{{ getFormattedPrice(item.product.current_price?.price) }}</strong>
                  </div>
                  <div class="col-6">
                    <small class="text-muted">Total:</small><br>
                    <strong>{{ item.formatted_total_price }}</strong>
                  </div>
                </div>
              </div>

              <hr class="mt-3">
            </div>
          </div>
        </div>

        <!-- Cart Summary -->
        <div class="col-lg-4">
          <div class="cart-summary bg-light p-4 rounded">
            <h4 class="mb-3">Order Summary</h4>
            
            <div class="summary-line d-flex justify-content-between">
              <span>Items ({{ cartStore.totalQuantity }})</span>
              <span>{{ cartStore.getFormattedPrice(cartStore.totalPrice) }}</span>
            </div>
            
            <div class="summary-line d-flex justify-content-between">
              <span>Shipping</span>
              <span class="text-success">Free</span>
            </div>
            
            <hr>
            
            <div class="summary-total d-flex justify-content-between">
              <strong>Total</strong>
              <strong class="text-primary">{{ cartStore.getFormattedPrice(cartStore.totalPrice) }}</strong>
            </div>

            <div class="mt-4">
              <button 
                class="btn btn-primary btn-lg w-100 mb-2"
                :disabled="!cartStore.cartItems.length"
                @click="proceedToCheckout"
              >
                <i class="fas fa-credit-card me-1"></i>
                Proceed to Checkout
              </button>
              
              <button 
                @click="clearCart"
                class="btn btn-outline-danger w-100"
                :disabled="!cartStore.cartItems.length || clearing"
              >
                <i class="fas fa-trash me-1"></i>
                {{ clearing ? 'Clearing...' : 'Clear Cart' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div 
      v-if="showToast"
      class="toast-container position-fixed bottom-0 end-0 p-3"
    >
      <div class="toast show" role="alert">
        <div class="toast-header">
          <i class="fas fa-check-circle text-success me-2"></i>
          <strong class="me-auto">Success</strong>
          <button 
            type="button" 
            class="btn-close" 
            @click="showToast = false"
          ></button>
        </div>
        <div class="toast-body">
          {{ toastMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { cartStore } from '../stores/cart.js';

export default {
  name: 'Cart',
  data() {
    return {
      cartStore,
      updating: null,
      clearing: false,
      showToast: false,
      toastMessage: ''
    };
  },
  async mounted() {
    // Initialize cart with current country
    const currentCountry = this.$globalCountry?.getCurrentCountry() || 'MY';
    await cartStore.init(currentCountry);
  },
  methods: {
    goBack() {
      this.$router.go(-1);
    },

    async increaseQuantity(item) {
      this.updating = item.id;
      const result = await cartStore.updateItem(item.id, item.quantity + 1);
      if (result.success) {
        this.showSuccess('Quantity updated');
      }
      this.updating = null;
    },

    async decreaseQuantity(item) {
      this.updating = item.id;
      const result = await cartStore.updateItem(item.id, item.quantity - 1);
      if (result.success) {
        this.showSuccess('Quantity updated');
      }
      this.updating = null;
    },

    async removeFromCart(item) {
      if (confirm('Are you sure you want to remove this item from your cart?')) {
        this.updating = item.id;
        const result = await cartStore.removeItem(item.id);
        if (result.success) {
          this.showSuccess('Item removed from cart');
        }
        this.updating = null;
      }
    },

    async clearCart() {
      if (confirm('Are you sure you want to clear your entire cart?')) {
        this.clearing = true;
        const result = await cartStore.clearCartRemote();
        if (result.success) {
          this.showSuccess('Cart cleared');
        } else if (result.requireLogin) {
          this.$router.push({ name: 'Auth' });
        } else {
          this.showError(result.error || 'Failed to clear cart');
        }
        this.clearing = false;
      }
    },

    proceedToCheckout() {
      // TODO: Implement checkout functionality
      alert('Checkout functionality will be implemented next!');
    },

    getFormattedPrice(price) {
      return cartStore.getFormattedPrice(price);
    },

    handleImageError(event) {
      event.target.src = '/images/product-placeholder.jpg';
    },

    showSuccess(message) {
      this.toastMessage = message;
      this.showToast = true;
      setTimeout(() => {
        this.showToast = false;
      }, 3000);
    },

    showError(message) {
      console.error('Cart Error:', message);
      alert(message); // Simple alert for now
    }
  }
};
</script>

<style scoped>
.cart-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.cart-header {
  background: white;
  border-bottom: 1px solid #dee2e6;
}

.cart-title {
  color: #495057;
  font-weight: 600;
}

.cart-item {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.product-name {
  font-size: 1.1rem;
  font-weight: 600;
}

.product-description {
  font-size: 0.9rem;
  line-height: 1.4;
}

.quantity-controls {
  gap: 0.5rem;
}

.quantity {
  min-width: 2rem;
  text-align: center;
}

.cart-summary {
  position: sticky;
  top: 2rem;
}

.summary-line {
  padding: 0.5rem 0;
}

.summary-total {
  font-size: 1.2rem;
  padding: 1rem 0;
}

.empty-cart-icon {
  opacity: 0.3;
}

@media (max-width: 768px) {
  .cart-item {
    padding: 1rem;
  }
  
  .product-image img {
    width: 60px !important;
    height: 60px !important;
  }
  
  .product-name {
    font-size: 1rem;
  }
}
</style> 
<template>
  <div class="add-to-cart-container">
    <!-- Add to Cart Button -->
    <button
      v-if="!isInCart"
      @click="addToCart"
      :disabled="loading || !hasPrice"
      class="btn add-to-cart-btn"
      :class="{ 
        'btn-lg': large, 
        'w-100': fullWidth,
        'btn-primary': isUserLoggedIn && hasPrice,
        'btn-outline-primary': !isUserLoggedIn && hasPrice,
        'btn-secondary': !hasPrice
      }"
    >
      <span v-if="loading">
        <i class="fas fa-spinner fa-spin me-1"></i>
        Adding...
      </span>
      <span v-else-if="!hasPrice">
        <i class="fas fa-ban me-1"></i>
        Not Available
      </span>
      <span v-else-if="!isUserLoggedIn">
        <i class="fas fa-sign-in-alt me-1"></i>
        Login to Add to Cart
      </span>
      <span v-else>
        <i class="fas fa-shopping-cart me-1"></i>
        Add to Cart
      </span>
    </button>

    <!-- Quantity Controls (when item is in cart) -->
    <div v-else class="quantity-controls d-flex align-items-center">
      <button
        @click="decreaseQuantity"
        :disabled="loading || cartItem.quantity <= 1"
        class="btn btn-outline-secondary btn-sm quantity-btn"
      >
        <i class="fas fa-minus"></i>
      </button>
      
      <span class="quantity-display mx-2">
        {{ cartItem.quantity }}
      </span>
      
      <button
        @click="increaseQuantity"
        :disabled="loading || cartItem.quantity >= 99"
        class="btn btn-outline-secondary btn-sm quantity-btn"
      >
        <i class="fas fa-plus"></i>
      </button>
      
      <button
        @click="removeFromCart"
        :disabled="loading"
        class="btn btn-outline-danger btn-sm ms-2"
        title="Remove from cart"
      >
        <i class="fas fa-trash-alt"></i>
      </button>
    </div>

    <!-- Success Animation -->
    <div
      v-if="showSuccess"
      class="success-animation position-absolute"
    >
      <i class="fas fa-check-circle text-success"></i>
    </div>
  </div>
</template>

<script>
import { cartStore } from '../stores/cart.js';

export default {
  name: 'AddToCartButton',
  props: {
    product: {
      type: Object,
      required: true
    },
    quantity: {
      type: Number,
      default: 1
    },
    large: {
      type: Boolean,
      default: false
    },
    fullWidth: {
      type: Boolean,
      default: false
    },
    country: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      cartStore,
      loading: false,
      showSuccess: false
    };
  },
  computed: {
    isInCart() {
      return cartStore.isInCart(this.product.id);
    },
    
    cartItem() {
      return cartStore.getCartItem(this.product.id);
    },
    
    hasPrice() {
      const country = this.country || this.$globalCountry?.getCurrentCountry() || 'MY';
      return this.product.prices && 
             this.product.prices.some(price => price.country_code === country);
    },
    
    currentPrice() {
      const country = this.country || this.$globalCountry?.getCurrentCountry() || 'MY';
      return this.product.prices?.find(price => price.country_code === country);
    },

    isUserLoggedIn() {
      return cartStore.isUserLoggedIn();
    }
  },
  methods: {
    async addToCart() {
      if (this.loading || !this.hasPrice) return;
      
      // 检查是否登录
      if (!cartStore.isUserLoggedIn()) {
        this.showLoginPrompt();
        return;
      }
      
      this.loading = true;
      
      try {
        const country = this.country || this.$globalCountry?.getCurrentCountry() || 'MY';
        const result = await cartStore.addItem(this.product.id, this.quantity, country);
        
        if (result.success) {
          this.showSuccessAnimation();
          this.$emit('added-to-cart', {
            product: this.product,
            quantity: this.quantity,
            cartItem: result.data.cart_item
          });
        } else if (result.requireLogin) {
          this.showLoginPrompt();
        } else {
          this.showError(result.error);
        }
      } catch (error) {
        this.showError('Failed to add item to cart');
      } finally {
        this.loading = false;
      }
    },

    async increaseQuantity() {
      if (this.loading || !this.cartItem) return;
      
      // 检查是否登录
      if (!cartStore.isUserLoggedIn()) {
        this.showLoginPrompt();
        return;
      }
      
      this.loading = true;
      
      try {
        const result = await cartStore.updateItem(this.cartItem.id, this.cartItem.quantity + 1);
        
        if (result.success) {
          this.showSuccessAnimation();
          this.$emit('quantity-updated', {
            product: this.product,
            newQuantity: this.cartItem.quantity + 1
          });
        } else if (result.requireLogin) {
          this.showLoginPrompt();
        } else {
          this.showError(result.error);
        }
      } catch (error) {
        this.showError('Failed to update quantity');
      } finally {
        this.loading = false;
      }
    },

    async decreaseQuantity() {
      if (this.loading || !this.cartItem || this.cartItem.quantity <= 1) return;
      
      // 检查是否登录
      if (!cartStore.isUserLoggedIn()) {
        this.showLoginPrompt();
        return;
      }
      
      this.loading = true;
      
      try {
        const result = await cartStore.updateItem(this.cartItem.id, this.cartItem.quantity - 1);
        
        if (result.success) {
          this.showSuccessAnimation();
          this.$emit('quantity-updated', {
            product: this.product,
            newQuantity: this.cartItem.quantity - 1
          });
        } else if (result.requireLogin) {
          this.showLoginPrompt();
        } else {
          this.showError(result.error);
        }
      } catch (error) {
        this.showError('Failed to update quantity');
      } finally {
        this.loading = false;
      }
    },

    async removeFromCart() {
      if (this.loading || !this.cartItem) return;
      
      // 检查是否登录
      if (!cartStore.isUserLoggedIn()) {
        this.showLoginPrompt();
        return;
      }
      
      if (!confirm('Remove this item from cart?')) return;
      
      this.loading = true;
      
      try {
        const result = await cartStore.removeItem(this.cartItem.id);
        
        if (result.success) {
          this.$emit('removed-from-cart', {
            product: this.product
          });
        } else if (result.requireLogin) {
          this.showLoginPrompt();
        } else {
          this.showError(result.error);
        }
      } catch (error) {
        this.showError('Failed to remove item');
      } finally {
        this.loading = false;
      }
    },

    showLoginPrompt() {
      if (confirm('Please login to manage your cart. Would you like to login now?')) {
        this.$router.push({ name: 'Auth' });
      }
    },

    showSuccessAnimation() {
      this.showSuccess = true;
      setTimeout(() => {
        this.showSuccess = false;
      }, 1000);
    },

    showError(message) {
      // You can implement a toast notification system here
      console.error(message);
      // Or emit an event for parent to handle
      this.$emit('error', message);
    }
  }
};
</script>

<style scoped>
.add-to-cart-container {
  position: relative;
  display: inline-block;
}

.add-to-cart-btn {
  transition: all 0.3s ease;
  font-weight: 600;
}

.add-to-cart-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.add-to-cart-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.quantity-controls {
  gap: 0.5rem;
}

.quantity-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.quantity-btn:hover:not(:disabled) {
  transform: scale(1.1);
}

.quantity-display {
  min-width: 2rem;
  text-align: center;
  font-weight: 600;
  font-size: 1rem;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid #dee2e6;
}

.success-animation {
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 1.5rem;
  animation: successPulse 1s ease-out;
  pointer-events: none;
}

@keyframes successPulse {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.5);
  }
  50% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.2);
  }
  100% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(1);
  }
}

/* Responsive design */
@media (max-width: 576px) {
  .quantity-controls {
    gap: 0.25rem;
  }
  
  .quantity-btn {
    width: 28px;
    height: 28px;
  }
  
  .quantity-display {
    min-width: 1.5rem;
    font-size: 0.9rem;
  }
}
</style> 
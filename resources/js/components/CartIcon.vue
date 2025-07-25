<template>
  <div class="cart-icon-container">
    <button 
      @click="goToCart" 
      class="btn btn-outline-light position-relative cart-button"
      :title="`${cartStore.totalQuantity} items in cart`"
    >
      <i class="fas fa-shopping-cart"></i>
      
      <!-- Cart Count Badge -->
      <span 
        v-if="cartStore.totalQuantity > 0"
        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge"
      >
        {{ cartStore.totalQuantity > 99 ? '99+' : cartStore.totalQuantity }}
        <span class="visually-hidden">items in cart</span>
      </span>
    </button>
    
    <!-- Mini Cart Dropdown (Optional) -->
    <div 
      v-if="showDropdown && cartStore.cartItems.length > 0" 
      class="cart-dropdown position-absolute"
      @mouseleave="hideDropdown"
    >
      <div class="cart-dropdown-header">
        <h6 class="mb-0">Cart ({{ cartStore.totalQuantity }} items)</h6>
      </div>
      
      <div class="cart-dropdown-items">
        <div 
          v-for="item in cartStore.cartItems.slice(0, 3)" 
          :key="item.id"
          class="cart-dropdown-item d-flex align-items-center p-2"
        >
          <img 
            :src="item.product.image_full_url" 
            :alt="item.product.name"
            class="cart-item-image me-2"
            @error="handleImageError"
          >
          <div class="flex-grow-1">
            <div class="item-name">{{ item.product.name }}</div>
            <div class="item-details text-muted">
              {{ item.quantity }}x {{ getFormattedPrice(item.product.current_price?.price) }}
            </div>
          </div>
          <div class="item-total">
            {{ item.formatted_total_price }}
          </div>
        </div>
        
        <div v-if="cartStore.cartItems.length > 3" class="text-center p-2 text-muted">
          And {{ cartStore.cartItems.length - 3 }} more items...
        </div>
      </div>
      
      <div class="cart-dropdown-footer">
        <div class="cart-total d-flex justify-content-between mb-2">
          <strong>Total: {{ cartStore.getFormattedPrice(cartStore.totalPrice) }}</strong>
        </div>
        <button @click="goToCart" class="btn btn-primary btn-sm w-100">
          View Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { cartStore } from '../stores/cart.js';

export default {
  name: 'CartIcon',
  props: {
    showMiniCart: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      cartStore,
      showDropdown: false,
      dropdownTimeout: null
    };
  },
  async mounted() {
    // Initialize cart count
    await cartStore.fetchCartCount();
    
    // Listen for country changes
    this.$emitter.on('country-changed', this.handleCountryChange);
    
    // Listen for auth changes
    this.$emitter.on('auth-success', this.handleAuthSuccess);
  },
  beforeUnmount() {
    this.$emitter.off('country-changed', this.handleCountryChange);
    this.$emitter.off('auth-success', this.handleAuthSuccess);
    
    if (this.dropdownTimeout) {
      clearTimeout(this.dropdownTimeout);
    }
  },
  methods: {
    goToCart() {
      this.$router.push({ name: 'Cart' });
    },

    showMiniCart() {
      if (this.showMiniCart && cartStore.cartItems.length > 0) {
        if (this.dropdownTimeout) {
          clearTimeout(this.dropdownTimeout);
        }
        this.showDropdown = true;
      }
    },

    hideDropdown() {
      this.dropdownTimeout = setTimeout(() => {
        this.showDropdown = false;
      }, 300);
    },

    async handleCountryChange(newCountry) {
      await cartStore.updateCountry(newCountry);
    },

    async handleAuthSuccess() {
      // When user logs in, refresh cart
      await cartStore.fetchCart();
    },

    getFormattedPrice(price) {
      return cartStore.getFormattedPrice(price);
    },

    handleImageError(event) {
      event.target.src = '/products/product-placeholder.png';
    }
  }
};
</script>

<style scoped>
.cart-icon-container {
  position: relative;
}

.cart-button {
  border-color: rgba(255, 255, 255, 0.5);
  color: white;
  transition: all 0.3s ease;
}

.cart-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: white;
  color: white;
  transform: scale(1.05);
}

.cart-badge {
  font-size: 0.6rem;
  padding: 0.25em 0.4em;
  animation: bounce 0.5s ease-in-out;
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translate(-50%, -50%) scale(1);
  }
  40%, 43% {
    transform: translate(-50%, -50%) scale(1.2);
  }
  70% {
    transform: translate(-50%, -50%) scale(1.1);
  }
  90% {
    transform: translate(-50%, -50%) scale(1.05);
  }
}

.cart-dropdown {
  top: 100%;
  right: 0;
  width: 320px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  z-index: 1050;
  margin-top: 0.5rem;
}

.cart-dropdown-header {
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 0.5rem 0.5rem 0 0;
}

.cart-dropdown-items {
  max-height: 300px;
  overflow-y: auto;
}

.cart-dropdown-item {
  border-bottom: 1px solid #f1f3f4;
}

.cart-dropdown-item:last-child {
  border-bottom: none;
}

.cart-item-image {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 0.25rem;
}

.item-name {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.2;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-details {
  font-size: 0.75rem;
}

.item-total {
  font-size: 0.875rem;
  font-weight: 600;
  color: #495057;
}

.cart-dropdown-footer {
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 0 0 0.5rem 0.5rem;
}

.cart-total {
  font-size: 1rem;
  color: #495057;
}

/* Hide dropdown on mobile */
@media (max-width: 768px) {
  .cart-dropdown {
    display: none;
  }
}
</style> 
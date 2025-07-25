<template>
  <div class="container py-4">
    <!-- Back Button -->
    <div class="row mb-4">
      <div class="col-12">
        <button @click="goBack" class="btn btn-outline-secondary mb-3">
          <i class="fas fa-arrow-left"></i> Back to Products
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-spinner">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <h4 class="alert-heading">Error!</h4>
      <p>{{ error }}</p>
      <button @click="fetchProduct" class="btn btn-outline-danger">Try Again</button>
    </div>

    <!-- Product Detail -->
    <div v-else-if="product" class="row">
      <!-- Product Image -->
      <div class="col-lg-6 mb-4">
        <div class="card">
          <img 
            :src="product.image_full_url || 'https://via.placeholder.com/600x400?text=No+Image'" 
            :alt="product.name"
            class="card-img-top"
            style="height: 400px; object-fit: cover;"
          >
        </div>
      </div>

      <!-- Product Information -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <!-- Category Badge -->
            <div v-if="product.category" class="mb-3">
              <span class="badge bg-secondary">{{ product.category.name }}</span>
            </div>

            <h1 class="card-title display-5 mb-3">{{ product.name }}</h1>
            
            <!-- Description -->
            <div class="mb-4">
              <h5 class="text-muted">Description</h5>
              <p class="card-text">{{ product.description || 'No description available for this product.' }}</p>
            </div>

            <!-- Current Country Display -->
            <div v-if="currentCountry" class="mb-3">
              <small class="text-muted">
                Pricing in {{ currentCountry === 'MY' ? 'Malaysia' : 'Singapore' }}
              </small>
            </div>

            <!-- Pricing Information -->
            <div class="mb-4">
              <h5 class="text-muted">Price</h5>
              <div v-if="product.prices && product.prices.length > 0">
                <div v-for="price in product.prices" :key="price.id" class="price-display mb-2">
                  <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                    <div>
                      <h3 class="text-primary mb-0">{{ price.country.currency_code }} {{ parseFloat(price.price).toFixed(2) }}</h3>
                      <small class="text-muted">{{ price.country.name }}</small>
                    </div>
                    <div class="text-end">
                      <i class="fas fa-tag text-primary"></i>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Price not available for your selected country.
              </div>
            </div>

            <!-- Quantity Selector (only when item is NOT in cart) -->
            <div class="mb-4" v-if="hasCurrentPrice && !isInCart">
              <h5 class="text-muted">Quantity</h5>
              <div class="d-flex align-items-center">
                <button 
                  @click="decreaseQuantity"
                  class="btn btn-outline-secondary"
                  :disabled="selectedQuantity <= 1"
                >
                  <i class="fas fa-minus"></i>
                </button>
                <span class="mx-3 fw-bold fs-5">{{ selectedQuantity }}</span>
                <button 
                  @click="increaseQuantity"
                  class="btn btn-outline-secondary"
                  :disabled="selectedQuantity >= 99"
                >
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>

            <!-- Already in Cart Notice -->
            <div class="mb-4" v-if="isInCart">
              <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <span>This item is already in your cart. Use the controls below to adjust quantity.</span>
              </div>
            </div>

            <!-- Current Price Display -->
            <div class="mb-4" v-if="currentPrice">
              <div class="current-price-card p-3 border rounded bg-light">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h3 class="text-primary mb-1">{{ currentPrice.country.currency_code }} {{ parseFloat(currentPrice.price).toFixed(2) }}</h3>
                    <small class="text-muted">Price in {{ currentPrice.country.name }}</small>
                  </div>
                  <div v-if="selectedQuantity > 1" class="text-end">
                    <div class="text-muted">Total:</div>
                    <h4 class="text-success mb-0">{{ currentPrice.country.currency_code }} {{ (parseFloat(currentPrice.price) * selectedQuantity).toFixed(2) }}</h4>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2">
              <AddToCartButton 
                v-if="product"
                :product="productWithCurrentPrice"
                :quantity="selectedQuantity"
                :country="currentCountry"
                :full-width="true"
                :large="true"
                @added-to-cart="handleAddedToCart"
                @quantity-updated="handleQuantityUpdated"
                @removed-from-cart="handleRemovedFromCart"
                @error="handleCartError"
              />
              
              <button @click="goBack" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Products
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="fas fa-search fa-3x text-muted mb-3"></i>
      <h4 class="text-muted">Product not found</h4>
      <p class="text-muted">The product you're looking for doesn't exist or is no longer available.</p>
      <button @click="goBack" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>
        Back to Products
      </button>
    </div>
  </div>
</template>

<script>
import AddToCartButton from './AddToCartButton.vue';
import { cartStore } from '../stores/cart.js';

export default {
  name: 'ProductDetail',
  components: {
    AddToCartButton
  },
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      product: null,
      loading: false,
      error: null,
      currentCountry: 'MY', // 缓存当前选择的国家
      selectedQuantity: 1,
      showSuccessMessage: false
    }
  },
  computed: {
    currentPrice() {
      if (!this.product || !this.product.prices) return null;
      return this.product.prices.find(price => price.country_code === this.currentCountry);
    },
    
    hasCurrentPrice() {
      return !!this.currentPrice;
    },
    
    productWithCurrentPrice() {
      if (!this.product) return null;
      
      return {
        ...this.product,
        current_price: this.currentPrice
      };
    },

    isInCart() {
      return this.product ? cartStore.isInCart(this.product.id) : false;
    }
  },
  async mounted() {
    this.initializeCountry();
    this.fetchProduct();
    
    // Listen for country changes
    this.$emitter.on('country-changed', this.handleCountryChange);
  },
  beforeUnmount() {
    this.$emitter.off('country-changed', this.handleCountryChange);
  },
  methods: {
    initializeCountry() {
      // 纯前端状态管理
      if (this.$globalCountry) {
        this.currentCountry = this.$globalCountry.getCurrentCountry();
      } else {
        // 降级方案
        this.currentCountry = localStorage.getItem('selected_country') || 'MY';
      }
    },

    async fetchProduct() {
      this.loading = true;
      this.error = null;
      
      try {
        const params = {
          country: this.currentCountry // 🔑 关键修复：总是传递当前国家
        };
        
        const response = await this.$http.get(`/api/products/${this.id}`, { params });
        
        if (response.data.success) {
          this.product = response.data.data;
        } else {
          this.error = response.data.message || 'Failed to fetch product';
        }
      } catch (error) {
        console.error('Error fetching product:', error);
        if (error.response?.status === 404) {
          this.error = 'Product not found';
        } else {
          this.error = error.response?.data?.message || 'Failed to connect to server';
        }
      } finally {
        this.loading = false;
      }
    },

    handleCountryChange(newCountry) {
      this.currentCountry = newCountry;
      localStorage.setItem('selected_country', newCountry); // 保存到localStorage
      this.fetchProduct(); // 重新获取产品数据
    },

    goBack() {
      this.$router.push({ name: 'ProductList' });
    },

    // Quantity control methods
    increaseQuantity() {
      if (this.selectedQuantity < 99) {
        this.selectedQuantity++;
      }
    },

    decreaseQuantity() {
      if (this.selectedQuantity > 1) {
        this.selectedQuantity--;
      }
    },

    // Cart event handlers
    handleAddedToCart(data) {
      this.showSuccess('Product added to cart!');
      // Optionally reset quantity to 1 after adding
      // this.selectedQuantity = 1;
    },

    handleQuantityUpdated(data) {
      this.showSuccess('Cart updated!');
    },

    handleRemovedFromCart(data) {
      this.showSuccess('Product removed from cart!');
    },

    handleCartError(error) {
      this.showError(error);
    },

    showSuccess(message) {
      // You can implement a toast notification here
      console.log('Success:', message);
      this.showSuccessMessage = true;
      setTimeout(() => {
        this.showSuccessMessage = false;
      }, 3000);
    },

    showError(message) {
      // You can implement error notification here
      console.error('Cart Error:', message);
      alert(message); // Simple alert for now
    }
  }
}
</script>

<style scoped>
.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.price-display {
  transition: all 0.2s ease-in-out;
}

.price-display:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card {
  border: 1px solid #e0e0e0;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn {
  transition: all 0.2s ease-in-out;
}

.btn:hover {
  transform: translateY(-1px);
}

.badge {
  font-size: 0.8rem;
  padding: 0.5rem 0.75rem;
}

@media (max-width: 768px) {
  .display-5 {
    font-size: 1.5rem;
  }
  
  .card-body {
    padding: 1rem;
  }
}
</style> 
<template>
  <div class="order-detail-page">
    <!-- Order Header -->
    <div class="order-header">
      <div class="container">
        <div class="row align-items-center py-3">
          <div class="col">
            <button @click="goBack" class="btn btn-outline-secondary me-3">
              <i class="fas fa-arrow-left me-1"></i>
              Back
            </button>
            <span class="order-title">
              <i class="fas fa-receipt me-2"></i>
              Order Details
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="container py-4">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading order details...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Error!</h4>
        <p>{{ error }}</p>
        <button @click="loadOrder" class="btn btn-outline-danger">Try Again</button>
      </div>

      <!-- Order Details -->
      <div v-else-if="order" class="row">
        <!-- Order Summary -->
        <div class="col-lg-4 mb-4">
          <div class="order-summary-card">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Order Summary</h5>
              </div>
              <div class="card-body">
                <div class="order-info">
                  <div class="info-row">
                    <strong>Order Number:</strong>
                    <span>{{ order.order_number }}</span>
                  </div>
                  
                  <div class="info-row">
                    <strong>Status:</strong>
                    <span 
                      class="badge"
                      :class="getStatusBadgeClass(order.status)"
                    >
                      {{ order.status_label }}
                    </span>
                  </div>
                  
                  <div class="info-row">
                    <strong>Order Date:</strong>
                    <span>{{ formatDate(order.created_at) }}</span>
                  </div>
                  
                  <div class="info-row">
                    <strong>Items:</strong>
                    <span>{{ order.total_quantity }} item{{ order.total_quantity !== 1 ? 's' : '' }}</span>
                  </div>
                  
                  <div class="info-row">
                    <strong>Shipping to:</strong>
                    <span>{{ order.country_name }}</span>
                  </div>
                  
                  <div class="info-row total-row">
                    <strong>Total:</strong>
                    <strong class="text-primary">{{ order.formatted_total_price }}</strong>
                  </div>
                </div>
                
                <!-- Actions -->
                <div class="order-actions mt-4">
                  <button 
                    v-if="order.can_be_cancelled"
                    @click="cancelOrder"
                    class="btn btn-outline-danger w-100 mb-2"
                    :disabled="cancelling"
                  >
                    <span v-if="cancelling">
                      <i class="fas fa-spinner fa-spin me-1"></i>
                      Cancelling...
                    </span>
                    <span v-else>
                      <i class="fas fa-times me-1"></i>
                      Cancel Order
                    </span>
                  </button>
                  
                  <button 
                    v-if="order.status === 'cancelled' || order.status === 'refunded'"
                    @click="deleteOrder"
                    class="btn btn-outline-secondary w-100 mb-2"
                    :disabled="deleting"
                  >
                    <span v-if="deleting">
                      <i class="fas fa-spinner fa-spin me-1"></i>
                      Deleting...
                    </span>
                    <span v-else>
                      <i class="fas fa-trash me-1"></i>
                      Delete Order
                    </span>
                  </button>
                  
                  <div v-if="order.is_completed" class="text-success text-center mt-2">
                    <i class="fas fa-check-circle me-1"></i>
                    Order Completed
                  </div>
                  
                  <div v-if="order.is_cancelled" class="text-danger text-center mt-2">
                    <i class="fas fa-times-circle me-1"></i>
                    Order Cancelled
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Items -->
        <div class="col-lg-8">
          <div class="order-items-card">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Order Items ({{ order.order_items.length }})</h5>
              </div>
              <div class="card-body p-0">
                <div class="order-items-list">
                  <div 
                    v-for="item in order.order_items" 
                    :key="item.id"
                    class="order-item"
                  >
                    <div class="row align-items-center">
                      <!-- Product Image -->
                      <div class="col-md-2">
                        <div class="product-image">
                          <img 
                            :src="item.product?.image_full_url || '/products/product-placeholder.png'" 
                            :alt="item.product_name"
                            class="img-fluid rounded"
                            @error="handleImageError"
                          >
                        </div>
                      </div>
                      
                      <!-- Product Info -->
                      <div class="col-md-4">
                        <div class="product-info">
                          <h6 class="product-name mb-1">{{ item.product_name }}</h6>
                          <p class="product-meta text-muted mb-0">
                            <span v-if="item.product?.category">
                              Category: {{ item.product.category.name }}
                            </span>
                          </p>
                          <small class="text-muted">
                            Product ID: {{ item.product_id }}
                          </small>
                        </div>
                      </div>
                      
                      <!-- Price -->
                      <div class="col-md-2 text-center">
                        <div class="item-price">
                          <div class="unit-price">{{ item.formatted_unit_price }}</div>
                          <small class="text-muted">per item</small>
                        </div>
                      </div>
                      
                      <!-- Quantity -->
                      <div class="col-md-2 text-center">
                        <div class="item-quantity">
                          <span class="quantity-badge">{{ item.quantity }}</span>
                        </div>
                      </div>
                      
                      <!-- Subtotal -->
                      <div class="col-md-2 text-end">
                        <div class="item-subtotal">
                          <strong>{{ item.formatted_subtotal }}</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h4 class="text-muted">Order not found</h4>
        <p class="text-muted">The order you're looking for doesn't exist or is no longer available.</p>
        <router-link :to="{ name: 'Orders' }" class="btn btn-primary">
          <i class="fas fa-arrow-left me-1"></i>
          Back to Orders
        </router-link>
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
export default {
  name: 'OrderDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      order: null,
      loading: false,
      error: null,
      cancelling: false,
      deleting: false,
      showToast: false,
      toastMessage: ''
    };
  },
  async mounted() {
    await this.loadOrder();
  },
  watch: {
    id: {
      handler() {
        this.loadOrder();
      },
      immediate: false
    }
  },
  methods: {
    async loadOrder() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await this.$http.get(`/api/orders/${this.id}`);
        
        if (response.data.success) {
          this.order = response.data.data;
        } else {
          this.error = response.data.message || 'Failed to load order';
        }
      } catch (error) {
        console.error('Error loading order:', error);
        if (error.response?.status === 404) {
          this.error = 'Order not found';
        } else if (error.response?.status === 401) {
          this.$router.push({ name: 'Auth' });
        } else {
          this.error = error.response?.data?.message || 'Failed to connect to server';
        }
      } finally {
        this.loading = false;
      }
    },

    async cancelOrder() {
      if (!confirm(`Are you sure you want to cancel order ${this.order.order_number}?`)) {
        return;
      }
      
      this.cancelling = true;
      
      try {
        const response = await this.$http.put(`/api/orders/${this.order.id}/cancel`);
        
        if (response.data.success) {
          this.showSuccess('Order cancelled successfully');
          // Update the order status
          this.order.status = 'cancelled';
          this.order.status_label = 'Cancelled';
          this.order.can_be_cancelled = false;
          this.order.is_cancelled = true;
        } else {
          this.showError(response.data.message || 'Failed to cancel order');
        }
      } catch (error) {
        console.error('Error cancelling order:', error);
        this.showError(error.response?.data?.message || 'Failed to cancel order');
      } finally {
        this.cancelling = false;
      }
    },

    getStatusBadgeClass(status) {
      const statusClasses = {
        'pending': 'bg-warning text-dark',
        'confirmed': 'bg-info text-white',
        'processing': 'bg-primary text-white',
        'shipped': 'bg-secondary text-white',
        'delivered': 'bg-success text-white',
        'cancelled': 'bg-danger text-white',
        'refunded': 'bg-dark text-white'
      };
      
      return statusClasses[status] || 'bg-secondary text-white';
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },

    handleImageError(event) {
      event.target.src = '/products/product-placeholder.png';
    },

    goBack() {
      this.$router.go(-1);
    },

    showSuccess(message) {
      this.toastMessage = message;
      this.showToast = true;
      setTimeout(() => {
        this.showToast = false;
      }, 3000);
    },

    async deleteOrder() {
      if (!confirm(`Are you sure you want to delete order ${this.order.order_number}? This action cannot be undone.`)) {
        return;
      }
      
      this.deleting = true;
      
      try {
        const response = await this.$http.delete(`/api/orders/${this.order.id}`);
        
        if (response.data.success) {
          this.showSuccess('Order deleted successfully');
          // Redirect to orders list after successful deletion
          setTimeout(() => {
            this.$router.push({ name: 'Orders' });
          }, 1500);
        } else {
          this.showError(response.data.message || 'Failed to delete order');
        }
      } catch (error) {
        console.error('Error deleting order:', error);
        this.showError(error.response?.data?.message || 'Failed to delete order');
      } finally {
        this.deleting = false;
      }
    },

    showError(message) {
      console.error('Order Error:', message);
      alert(message); // Simple alert for now
    }
  }
};
</script>

<style scoped>
.order-detail-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.order-header {
  background: white;
  border-bottom: 1px solid #dee2e6;
}

.order-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #495057;
}

.order-summary-card {
  position: sticky;
  top: 2rem;
}

.order-info .info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f3f4;
}

.order-info .info-row:last-child {
  border-bottom: none;
}

.order-info .total-row {
  font-size: 1.1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 2px solid #dee2e6;
  border-bottom: none;
}

.order-item {
  padding: 1.5rem;
  border-bottom: 1px solid #f1f3f4;
}

.order-item:last-child {
  border-bottom: none;
}

.product-image img {
  width: 80px;
  height: 80px;
  object-fit: cover;
}

.product-name {
  font-weight: 600;
  color: #495057;
}

.product-meta {
  font-size: 0.875rem;
}

.item-price,
.item-quantity,
.item-subtotal {
  text-align: center;
}

.unit-price {
  font-weight: 600;
  color: #495057;
}

.quantity-badge {
  display: inline-block;
  background: #e9ecef;
  color: #495057;
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  font-weight: 600;
  min-width: 3rem;
}

.item-subtotal {
  font-size: 1.1rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .order-summary-card {
    position: static;
    margin-bottom: 2rem;
  }
  
  .order-item {
    padding: 1rem;
  }
  
  .product-image img {
    width: 60px;
    height: 60px;
  }
  
  .item-price,
  .item-quantity,
  .item-subtotal {
    text-align: left;
    margin-top: 0.5rem;
  }
  
  .quantity-badge {
    min-width: auto;
    padding: 0.25rem 0.75rem;
  }
}

/* Print styles */
@media print {
  .order-header,
  .order-actions,
  .toast-container {
    display: none !important;
  }
  
  .container {
    max-width: none !important;
    margin: 0 !important;
    padding: 0 !important;
  }
}
</style> 
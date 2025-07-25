<template>
  <div class="orders-page">
    <!-- Orders Header -->
    <div class="orders-header">
      <div class="container">
        <div class="row align-items-center py-3">
          <div class="col">
            <h2 class="orders-title mb-0">
              <i class="fas fa-receipt me-2"></i>
              My Orders
            </h2>
          </div>
          <div class="col-auto">
            <router-link 
              :to="{ name: 'ProductList' }" 
              class="btn btn-outline-primary"
            >
              <i class="fas fa-shopping-bag me-1"></i>
              Continue Shopping
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <div class="container py-4">
      <!-- Status Filter -->
      <div class="row mb-4">
        <div class="col-md-6">
          <label for="statusFilter" class="form-label">Filter by Status</label>
          <select 
            id="statusFilter"
            v-model="filters.status" 
            @change="applyFilter"
            class="form-select"
          >
            <option value="">All Orders</option>
            <option 
              v-for="(label, status) in orderStatuses" 
              :key="status"
              :value="status"
            >
              {{ label }}
            </option>
          </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <button 
            @click="refreshOrders" 
            class="btn btn-outline-secondary"
            :disabled="loading"
          >
            <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && orders.length === 0" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading your orders...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && orders.length === 0" class="empty-orders text-center py-5">
        <div class="empty-orders-icon mb-3">
          <i class="fas fa-receipt fa-4x text-muted"></i>
        </div>
        <h3 class="text-muted">No orders found</h3>
        <p class="text-muted mb-4">
          <span v-if="filters.status">
            No orders with status "{{ orderStatuses[filters.status] }}"
          </span>
          <span v-else>
            You haven't placed any orders yet
          </span>
        </p>
        <router-link 
          :to="{ name: 'ProductList' }" 
          class="btn btn-primary"
        >
          <i class="fas fa-shopping-bag me-1"></i>
          Start Shopping
        </router-link>
      </div>

      <!-- Orders List -->
      <div v-else class="orders-list">
        <div 
          v-for="order in orders" 
          :key="order.id"
          class="order-card mb-4"
        >
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1">Order {{ order.order_number }}</h5>
                <small class="text-muted">
                  Placed on {{ formatDate(order.created_at) }}
                </small>
              </div>
              <div class="text-end">
                <span 
                  class="badge"
                  :class="getStatusBadgeClass(order.status)"
                >
                  {{ order.status_label }}
                </span>
              </div>
            </div>
            
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <div class="order-summary">
                    <div class="d-flex justify-content-between mb-2">
                      <span>Items:</span>
                      <span>{{ order.total_quantity }} item{{ order.total_quantity !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span>Total:</span>
                      <strong class="text-primary">{{ order.formatted_total_price }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                      <span>Shipping to:</span>
                      <span>{{ order.country_name }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-4 text-end">
                  <div class="order-actions">
                    <router-link 
                      :to="{ name: 'OrderDetail', params: { id: order.id } }"
                      class="btn btn-outline-primary btn-sm me-2"
                    >
                      <i class="fas fa-eye me-1"></i>
                      View Details
                    </router-link>
                    
                    <button 
                      v-if="order.can_be_cancelled"
                      @click="cancelOrder(order)"
                      class="btn btn-outline-danger btn-sm"
                      :disabled="cancelling === order.id"
                    >
                      <span v-if="cancelling === order.id">
                        <i class="fas fa-spinner fa-spin me-1"></i>
                        Cancelling...
                      </span>
                      <span v-else>
                        <i class="fas fa-times me-1"></i>
                        Cancel
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="pagination.last_page > 1" aria-label="Orders pagination" class="mt-4">
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
            <button 
              class="page-link" 
              @click="changePage(pagination.current_page - 1)" 
              :disabled="pagination.current_page <= 1 || loading"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
          </li>
          
          <li 
            v-for="page in visiblePages" 
            :key="page" 
            class="page-item" 
            :class="{ active: page === pagination.current_page }"
          >
            <button 
              class="page-link" 
              @click="changePage(page)"
              :disabled="loading"
            >
              {{ page }}
            </button>
          </li>
          
          <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
            <button 
              class="page-link" 
              @click="changePage(pagination.current_page + 1)" 
              :disabled="pagination.current_page >= pagination.last_page || loading"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </li>
        </ul>
      </nav>
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
  name: 'Orders',
  data() {
    return {
      orders: [],
      orderStatuses: {},
      pagination: {},
      loading: false,
      cancelling: null,
      filters: {
        status: ''
      },
      showToast: false,
      toastMessage: '',
      currentPage: 1
    };
  },
  computed: {
    visiblePages() {
      const total = this.pagination.last_page || 1;
      const current = this.pagination.current_page || 1;
      const pages = [];
      
      let start = Math.max(1, current - 2);
      let end = Math.min(total, current + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  async mounted() {
    await this.loadOrderStatuses();
    await this.loadOrders();
  },
  methods: {
    async loadOrderStatuses() {
      try {
        const response = await this.$http.get('/api/orders/statuses');
        if (response.data.success) {
          this.orderStatuses = response.data.data;
        }
      } catch (error) {
        console.error('Failed to load order statuses:', error);
      }
    },

    async loadOrders(page = 1) {
      this.loading = true;
      
      try {
        const params = {
          page: page,
          per_page: 10
        };
        
        if (this.filters.status) {
          params.status = this.filters.status;
        }
        
        const response = await this.$http.get('/api/orders', { params });
        
        if (response.data.success) {
          this.orders = response.data.data;
          this.pagination = response.data.pagination;
          this.currentPage = page;
        } else {
          this.showError('Failed to load orders');
        }
      } catch (error) {
        console.error('Error loading orders:', error);
        if (error.response?.status === 401) {
          this.$router.push({ name: 'Auth' });
        } else {
          this.showError('Failed to connect to server');
        }
      } finally {
        this.loading = false;
      }
    },

    async refreshOrders() {
      await this.loadOrders(this.currentPage);
    },

    async applyFilter() {
      this.currentPage = 1;
      await this.loadOrders(1);
    },

    async changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page && !this.loading) {
        await this.loadOrders(page);
      }
    },

    async cancelOrder(order) {
      if (!confirm(`Are you sure you want to cancel order ${order.order_number}?`)) {
        return;
      }
      
      this.cancelling = order.id;
      
      try {
        const response = await this.$http.put(`/api/orders/${order.id}/cancel`);
        
        if (response.data.success) {
          this.showSuccess('Order cancelled successfully');
          // Update the order status in the list
          const orderIndex = this.orders.findIndex(o => o.id === order.id);
          if (orderIndex !== -1) {
            this.orders[orderIndex].status = 'cancelled';
            this.orders[orderIndex].status_label = 'Cancelled';
            this.orders[orderIndex].can_be_cancelled = false;
          }
        } else {
          this.showError(response.data.message || 'Failed to cancel order');
        }
      } catch (error) {
        console.error('Error cancelling order:', error);
        this.showError(error.response?.data?.message || 'Failed to cancel order');
      } finally {
        this.cancelling = null;
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

    showSuccess(message) {
      this.toastMessage = message;
      this.showToast = true;
      setTimeout(() => {
        this.showToast = false;
      }, 3000);
    },

    showError(message) {
      console.error('Orders Error:', message);
      alert(message); // Simple alert for now
    }
  }
};
</script>

<style scoped>
.orders-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.orders-header {
  background: white;
  border-bottom: 1px solid #dee2e6;
}

.orders-title {
  color: #495057;
  font-weight: 600;
}

.order-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.order-summary {
  font-size: 0.9rem;
}

.order-actions {
  white-space: nowrap;
}

.empty-orders-icon {
  opacity: 0.3;
}

.pagination .page-link {
  border-color: #dee2e6;
  color: #6c757d;
}

.pagination .page-link:hover {
  background-color: #e9ecef;
  border-color: #adb5bd;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

@media (max-width: 768px) {
  .order-actions {
    margin-top: 1rem;
  }
  
  .order-actions .btn {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .order-actions .btn:last-child {
    margin-bottom: 0;
  }
}
</style> 
<template>
  <div class="container mt-4">
    <h1>Products</h1>
    <p class="text-muted mb-4">Showing products available in your selected country with local pricing.</p>
    
    <!-- Filters Section -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-filter me-2"></i>Filters & Sorting
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <!-- Search -->
          <div class="col-md-3">
            <label for="search" class="form-label">Search</label>
            <input 
              type="text" 
              class="form-control" 
              id="search"
              v-model="filters.search" 
              placeholder="Search products..."
              @input="debouncedSearch"
            >
          </div>
          
          <!-- Category Filter -->
          <div class="col-md-2">
            <label for="category" class="form-label">Category</label>
            <select 
              class="form-select" 
              id="category"
              v-model="filters.categoryId"
              @change="applyFilters"
            >
              <option value="">All Categories</option>
              <option 
                v-for="category in categories" 
                :key="category.id" 
                :value="category.id"
              >
                {{ category.name }} ({{ category.products_count }})
              </option>
            </select>
          </div>
          
          <!-- Price Range -->
          <div class="col-md-2">
            <label for="priceFrom" class="form-label">Price From</label>
            <input 
              type="number" 
              class="form-control" 
              id="priceFrom"
              v-model.number="filters.priceFrom" 
              placeholder="Min"
              step="0.01"
              min="0"
              @input="debouncedPriceFilter"
            >
          </div>
          
          <div class="col-md-2">
            <label for="priceTo" class="form-label">Price To</label>
            <input 
              type="number" 
              class="form-control" 
              id="priceTo"
              v-model.number="filters.priceTo" 
              placeholder="Max"
              step="0.01"
              min="0"
              @input="debouncedPriceFilter"
            >
          </div>

          <!-- Sort By -->
          <div class="col-md-2">
            <label for="sortBy" class="form-label">Sort By</label>
            <select 
              class="form-select" 
              id="sortBy"
              v-model="sorting.sortBy"
              @change="applySorting"
            >
              <option value="name">Name (A-Z)</option>
              <option value="price">Price</option>
            </select>
          </div>
          
          <!-- Sort Order -->
          <div class="col-md-1">
            <label for="sortOrder" class="form-label">Order</label>
            <button 
              type="button" 
              class="btn btn-outline-primary w-100"
              @click="toggleSortOrder"
              :title="sorting.sortOrder === 'asc' ? 'Sort Ascending' : 'Sort Descending'"
            >
              <i :class="sorting.sortOrder === 'asc' ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down'"></i>
            </button>
          </div>
        </div>
        
        <div class="row g-3 mt-2">
          <!-- Clear Filters Button -->
          <div class="col-md-11">
            <!-- Active Filters Display -->
            <div v-if="hasActiveFilters || hasActiveSorting" class="d-flex flex-wrap gap-2 align-items-center">
              <small class="text-muted me-2">Active:</small>
              <span v-if="filters.search" class="badge bg-primary">
                Search: "{{ filters.search }}"
                <button type="button" class="btn-close btn-close-white ms-1" @click="clearFilter('search')"></button>
              </span>
              <span v-if="filters.categoryId" class="badge bg-success">
                Category: {{ getCategoryName(filters.categoryId) }}
                <button type="button" class="btn-close btn-close-white ms-1" @click="clearFilter('categoryId')"></button>
              </span>
              <span v-if="filters.priceFrom" class="badge bg-info">
                Min: {{ formatPrice(filters.priceFrom) }}
                <button type="button" class="btn-close btn-close-white ms-1" @click="clearFilter('priceFrom')"></button>
              </span>
              <span v-if="filters.priceTo" class="badge bg-info">
                Max: {{ formatPrice(filters.priceTo) }}
                <button type="button" class="btn-close btn-close-white ms-1" @click="clearFilter('priceTo')"></button>
              </span>
              <span v-if="hasActiveSorting" class="badge bg-warning text-dark">
                Sort: {{ getSortingLabel() }}
                <button type="button" class="btn-close ms-1" @click="resetSorting"></button>
              </span>
            </div>
          </div>

          <div class="col-md-1">
            <button 
              type="button" 
              class="btn btn-outline-secondary w-100"
              @click="clearAll"
              :disabled="!hasActiveFilters && !hasActiveSorting"
              title="Clear all filters and sorting"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Results Summary -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <span class="text-muted">
          Showing {{ pagination.from || 0 }}-{{ pagination.to || 0 }} of {{ pagination.total || 0 }} products
          <span v-if="currentCountry">
            in {{ currentCountry === 'MY' ? 'Malaysia' : 'Singapore' }}
          </span>
          <span v-if="meta && meta.sorting">
            • Sorted by {{ meta.sorting.sort_by === 'name' ? 'Name' : 'Price' }} 
            ({{ meta.sorting.sort_order === 'asc' ? 'Low to High' : 'High to Low' }})
          </span>
        </span>
      </div>
      <div v-if="loading" class="text-muted">
        <i class="fas fa-spinner fa-spin me-1"></i>Loading...
      </div>
    </div>

    <!-- Products Grid -->
    <div v-if="products.length > 0" class="row">
      <div v-for="product in products" :key="product.id" class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 product-card">
          <img 
            :src="product.image_full_url || 'https://via.placeholder.com/300x200?text=No+Image'" 
            class="card-img-top" 
            :alt="product.name"
            style="height: 200px; object-fit: cover;"
          >
          <div class="card-body d-flex flex-column">
            <div class="mb-2">
              <span v-if="product.category" class="badge bg-secondary mb-2">
                {{ product.category.name }}
              </span>
            </div>
            <h5 class="card-title">{{ product.name }}</h5>
            <p class="card-text text-muted flex-grow-1">{{ product.description }}</p>
            
            <!-- Price Display -->
            <div class="mt-auto">
              <div v-if="product.prices && product.prices.length > 0" class="mb-3">
                <div v-for="price in product.prices" :key="price.id" class="price-info">
                  <span class="h5 text-primary fw-bold">
                    {{ price.country.currency_code }} {{ parseFloat(price.price).toFixed(2) }}
                  </span>
                  <small class="text-muted d-block">{{ price.country.name }}</small>
                </div>
              </div>
              <div v-else class="mb-3">
                <span class="text-muted">Price not available</span>
              </div>
              
              <router-link 
                :to="{ name: 'ProductDetail', params: { id: product.id } }" 
                class="btn btn-primary w-100"
              >
                View Details
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-5">
      <i class="fas fa-search fa-3x text-muted mb-3"></i>
      <h4 class="text-muted">No products found</h4>
      <p class="text-muted">
        <span v-if="hasActiveFilters || hasActiveSorting">
          Try adjusting your filters or <button type="button" class="btn btn-link p-0" @click="clearAll">clear all filters</button>
        </span>
        <span v-else>
          No products are available for your selected criteria.
        </span>
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading && products.length === 0" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-2">Loading products...</p>
    </div>

    <!-- Pagination -->
    <nav v-if="pagination.last_page > 1" aria-label="Products pagination" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: currentPage <= 1 }">
          <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage <= 1">
            <i class="fas fa-chevron-left"></i>
          </button>
        </li>
        
        <li 
          v-for="page in visiblePages" 
          :key="page" 
          class="page-item" 
          :class="{ active: page === currentPage }"
        >
          <button class="page-link" @click="changePage(page)">{{ page }}</button>
        </li>
        
        <li class="page-item" :class="{ disabled: currentPage >= pagination.last_page }">
          <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage >= pagination.last_page">
            <i class="fas fa-chevron-right"></i>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'ProductList',
  data() {
    return {
      products: [],
      categories: [],
      pagination: {},
      meta: {},
      loading: false,
      currentPage: 1,
      currentCountry: 'MY', // 缓存当前选择的国家
      filters: {
        search: '',
        categoryId: '',
        priceFrom: '',
        priceTo: ''
      },
      sorting: {
        sortBy: 'name',
        sortOrder: 'asc'
      },
      searchTimeout: null,
      priceTimeout: null
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.search || 
             this.filters.categoryId || 
             this.filters.priceFrom || 
             this.filters.priceTo;
    },
    hasActiveSorting() {
      return this.sorting.sortBy !== 'name' || this.sorting.sortOrder !== 'asc';
    },
    visiblePages() {
      const total = this.pagination.last_page || 1;
      const current = this.currentPage;
      const pages = [];
      
      const start = Math.max(1, current - 2);
      const end = Math.min(total, current + 2);
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    }
  },
  async mounted() {
    // 使用纯前端状态
    this.initializeCountry();
    this.fetchCategories();
    this.fetchProducts();
    
    // Listen for country changes
    this.$emitter.on('country-changed', this.handleCountryChange);
  },
  beforeUnmount() {
    this.$emitter.off('country-changed', this.handleCountryChange);
    
    // Clear timeouts
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    if (this.priceTimeout) clearTimeout(this.priceTimeout);
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

    async loadCurrentCountry() {
      // 保留这个方法以保持兼容性
      return this.initializeCountry();
    },

    async fetchCategories() {
      try {
        const response = await this.$http.get('/api/categories');
        if (response.data.success) {
          this.categories = response.data.data;
        }
      } catch (error) {
        console.error('Failed to fetch categories:', error);
      }
    },
    
    async fetchProducts() {
      this.loading = true;
      
      try {
        const params = {
          page: this.currentPage,
          per_page: 12,
          country: this.currentCountry // 总是传递当前国家
        };
        
        // Add filters to params
        if (this.filters.search) params.search = this.filters.search;
        if (this.filters.categoryId) params.category_id = this.filters.categoryId;
        if (this.filters.priceFrom) params.price_from = this.filters.priceFrom;
        if (this.filters.priceTo) params.price_to = this.filters.priceTo;
        
        // Add sorting to params
        params.sort_by = this.sorting.sortBy;
        params.sort_order = this.sorting.sortOrder;
        
        console.log('Fetching products with country:', this.currentCountry, 'params:', params); // Debug log
        
        const response = await this.$http.get('/api/products', { params });
        
        if (response.data.success) {
          this.products = response.data.data;
          this.pagination = response.data.pagination;
          this.meta = response.data.meta;
        }
      } catch (error) {
        console.error('Failed to fetch products:', error);
      } finally {
        this.loading = false;
      }
    },
    
    debouncedSearch() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.applyFilters();
      }, 500);
    },
    
    debouncedPriceFilter() {
      if (this.priceTimeout) clearTimeout(this.priceTimeout);
      this.priceTimeout = setTimeout(() => {
        this.applyFilters();
      }, 800);
    },
    
    applyFilters() {
      this.currentPage = 1;
      this.fetchProducts();
    },

    applySorting() {
      this.currentPage = 1;
      this.fetchProducts();
    },

    toggleSortOrder() {
      this.sorting.sortOrder = this.sorting.sortOrder === 'asc' ? 'desc' : 'asc';
      this.applySorting();
    },

    resetSorting() {
      this.sorting = {
        sortBy: 'name',
        sortOrder: 'asc'
      };
      this.applySorting();
    },
    
    clearFilters() {
      this.filters = {
        search: '',
        categoryId: '',
        priceFrom: '',
        priceTo: ''
      };
      this.applyFilters();
    },

    clearAll() {
      this.filters = {
        search: '',
        categoryId: '',
        priceFrom: '',
        priceTo: ''
      };
      this.sorting = {
        sortBy: 'name',
        sortOrder: 'asc'
      };
      this.applyFilters();
    },
    
    clearFilter(filterKey) {
      this.filters[filterKey] = '';
      this.applyFilters();
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.currentPage = page;
        this.fetchProducts();
      }
    },
    
    handleCountryChange(newCountry) {
      this.currentCountry = newCountry;
      localStorage.setItem('selected_country', newCountry); // 保存到localStorage
      this.currentPage = 1;
      this.fetchProducts();
      this.fetchCategories(); // Refresh categories count
    },
    
    getCategoryName(categoryId) {
      const category = this.categories.find(c => c.id === parseInt(categoryId));
      return category ? category.name : 'Unknown';
    },

    getSortingLabel() {
      const field = this.sorting.sortBy === 'name' ? 'Name' : 'Price';
      const order = this.sorting.sortOrder === 'asc' ? 'A-Z' : 'Z-A';
      if (this.sorting.sortBy === 'price') {
        return `${field} (${this.sorting.sortOrder === 'asc' ? 'Low-High' : 'High-Low'})`;
      }
      return `${field} (${order})`;
    },
    
    formatPrice(price) {
      const currency = this.currentCountry === 'SG' ? 'SGD' : 'MYR';
      return `${currency} ${parseFloat(price).toFixed(2)}`;
    }
  }
}
</script>

<style scoped>
.product-card {
  transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
  border: 1px solid #e0e0e0;
}

.product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.price-info {
  border-left: 3px solid #007bff;
  padding-left: 8px;
}

.badge {
  font-size: 0.75em;
}

.btn-close-white {
  filter: invert(1);
  opacity: 0.8;
  font-size: 0.6em;
}

.btn-close-white:hover {
  opacity: 1;
}

.btn-close {
  font-size: 0.6em;
}

.btn-close:hover {
  opacity: 1;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.badge.bg-warning {
  color: #000 !important;
}
</style> 
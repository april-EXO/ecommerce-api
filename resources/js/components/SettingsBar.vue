<template>
  <div class="settings-bar py-3 sticky-top">
    <div class="container">
      <div class="row align-items-center">
        <!-- Country/Pricing Section -->
        <div class="col-auto">
          <label for="country" class="form-label text-white mb-0">
            <strong>Country/Pricing:</strong>
          </label>
        </div>
        
        <div class="col-auto">
          <select 
            class="form-select" 
            id="country"
            v-model="selectedCountry"
            @change="updateCountry"
            :disabled="loading"
          >
            <option 
              v-for="country in countries" 
              :key="country.code" 
              :value="country.code"
            >
              {{ country.name }} ({{ country.currency }})
            </option>
          </select>
        </div>
        
        <div class="col-auto">
          <div v-if="loading" class="text-white">
            <i class="fas fa-spinner fa-spin me-1"></i>
            <small>Updating...</small>
          </div>
          <div v-else-if="updateMessage" class="update-message" :class="{ success: updateMessage.includes('updated'), error: updateMessage.includes('failed') }">
            <small>{{ updateMessage }}</small>
          </div>
        </div>

        <!-- Spacer to push auth section to right -->
        <div class="col"></div>

        <!-- Authentication Section -->
        <div class="col-auto">
          <div v-if="authStore && authStore.isAuthenticated" class="auth-section authenticated">
            <div class="user-info d-flex align-items-center">
              <div class="user-avatar me-2">
                <i class="fas fa-user-circle"></i>
              </div>
              <div class="user-details me-3">
                <div class="user-name">{{ authStore.user?.name }}</div>
                <div class="user-email">{{ authStore.user?.email }}</div>
              </div>
              <div class="auth-actions">
                <button 
                  @click="logout" 
                  class="btn btn-outline-light btn-sm"
                  :disabled="authStore.loading"
                >
                  <i class="fas fa-sign-out-alt me-1"></i>
                  {{ authStore.loading ? 'Logging out...' : 'Logout' }}
                </button>
              </div>
            </div>
          </div>
          
          <div v-else class="auth-section unauthenticated">
            <button 
              @click="goToAuth" 
              class="btn btn-light btn-sm"
            >
              <i class="fas fa-sign-in-alt me-1"></i>
              Login / Register
            </button>
          </div>
        </div>

        <!-- Cart Icon -->
        <div class="col-auto">
          <CartIcon />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CartIcon from './CartIcon.vue';

export default {
  name: 'SettingsBar',
  components: {
    CartIcon
  },
  props: {
    authStore: {
      type: Object,
      default: null
    },
    cartStore: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      selectedCountry: 'MY',
      loading: false,
      updateMessage: '',
      countries: [
        { code: 'MY', name: 'Malaysia', currency: 'MYR' },
        { code: 'SG', name: 'Singapore', currency: 'SGD' }
      ]
    };
  },
  mounted() {
    // Load country preference: user's saved preference > localStorage > default MY
    this.initializeCountry();
    
    // Listen for global country changes
    this.$emitter.on('country-changed', (newCountry) => {
      this.selectedCountry = newCountry;
    });

    // Listen for auth changes to update country when user logs in
    this.$emitter.on('auth-success', () => {
      this.initializeCountry();
    });
  },
  beforeUnmount() {
    // Clean up event listeners
    this.$emitter.off('country-changed');
    this.$emitter.off('auth-success');
  },
  methods: {
    initializeCountry() {
      let countryToUse = 'MY'; // default
      
      // If user is authenticated, use their country_code
      if (this.authStore && this.authStore.isAuthenticated && this.authStore.user?.country_code) {
        countryToUse = this.authStore.user.country_code;
      } else {
        // Otherwise use localStorage preference
        const savedCountry = localStorage.getItem('selected_country');
        if (savedCountry && ['MY', 'SG'].includes(savedCountry)) {
          countryToUse = savedCountry;
        }
      }
      
      this.selectedCountry = countryToUse;
      
      // Update global state silently (without triggering events)
      if (this.$globalCountry) {
        this.$globalCountry.currentCountry = countryToUse;
        localStorage.setItem('selected_country', countryToUse);
      }
    },
    updateCountry() {
      this.loading = true;
      this.updateMessage = '';
      
      // Simulate API call or processing delay
      setTimeout(async () => {
        try {
          // Update global country state
          const success = this.$globalCountry.updateCountry(this.selectedCountry);
          
          if (success) {
            // If user is authenticated, update their country preference on server
            if (this.authStore && this.authStore.isAuthenticated) {
              const result = await this.authStore.updateCountry(this.selectedCountry);
              
              if (result.success) {
                this.updateMessage = `Pricing and profile updated to ${this.getCountryName(this.selectedCountry)}`;
              } else {
                this.updateMessage = `Pricing updated to ${this.getCountryName(this.selectedCountry)}, but failed to save preference: ${result.error}`;
              }
            } else {
              this.updateMessage = `Pricing updated to ${this.getCountryName(this.selectedCountry)}`;
            }
            
            // Emit event to parent component
            this.$emit('country-changed', this.selectedCountry);
            
            // Clear message after 3 seconds
            setTimeout(() => {
              this.updateMessage = '';
            }, 3000);
          } else {
            this.updateMessage = 'Failed to update country';
          }
        } catch (error) {
          console.error('Error updating country:', error);
          this.updateMessage = 'Failed to update country';
        }
        
        this.loading = false;
      }, 500);
    },
    
    getCountryName(code) {
      const country = this.countries.find(c => c.code === code);
      return country ? country.name : code;
    },

    async logout() {
      try {
        await this.authStore.logout();
        this.$router.push({ name: 'ProductList' });
      } catch (error) {
        console.error('Logout error:', error);
      }
    },

    goToAuth() {
      this.$router.push({ name: 'Auth' });
    }
  }
};
</script>

<style scoped>
.settings-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.form-select {
  border: 1px solid rgba(255, 255, 255, 0.2);
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
  min-width: 200px;
}

.form-select:focus {
  border-color: rgba(255, 255, 255, 0.5);
  background-color: rgba(255, 255, 255, 0.2);
  color: white;
  box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
}

.form-select option {
  background-color: #343a40;
  color: white;
}

.update-message {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.875rem;
}

.update-message.success {
  background-color: rgba(40, 167, 69, 0.2);
  color: #d4edda;
  border: 1px solid rgba(40, 167, 69, 0.3);
}

.update-message.error {
  background-color: rgba(220, 53, 69, 0.2);
  color: #f8d7da;
  border: 1px solid rgba(220, 53, 69, 0.3);
}

/* Authentication Section Styles */
.auth-section {
  margin-left: 20px;
}

.auth-section.authenticated .user-info {
  color: white;
}

.user-avatar {
  font-size: 24px;
  color: rgba(255, 255, 255, 0.9);
}

.user-details {
  text-align: left;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 2px;
}

.user-email {
  font-size: 12px;
  opacity: 0.8;
}

.btn-outline-light {
  border-color: rgba(255, 255, 255, 0.5);
  color: white;
}

.btn-outline-light:hover {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: white;
  color: white;
}

.btn-light {
  background-color: rgba(255, 255, 255, 0.9);
  border-color: transparent;
  color: #495057;
  font-weight: 500;
}

.btn-light:hover {
  background-color: white;
  color: #495057;
}

@media (max-width: 768px) {
  .user-details {
    display: none;
  }
  
  .auth-section {
    margin-left: 10px;
  }
}
</style> 
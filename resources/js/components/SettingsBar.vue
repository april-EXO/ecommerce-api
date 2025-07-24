<template>
  <div class="settings-bar py-3 sticky-top">
    <div class="container">
      <div class="row align-items-center">
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
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SettingsBar',
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
    this.loadCurrentSettings();
  },
  methods: {
    loadCurrentSettings() {
      // 纯前端状态管理
      if (this.$globalCountry) {
        this.selectedCountry = this.$globalCountry.getCurrentCountry();
      } else {
        this.selectedCountry = localStorage.getItem('selected_country') || 'MY';
      }
    },
    
    updateCountry() {
      this.loading = true;
      this.updateMessage = '';
      
      // 纯前端更新，无需API调用
      if (this.$globalCountry) {
        const success = this.$globalCountry.updateCountry(this.selectedCountry);
        if (success) {
          this.updateMessage = 'Country updated!';
        } else {
          this.updateMessage = 'Invalid country';
          this.loadCurrentSettings(); // 重新加载正确的值
        }
      } else {
        // 降级方案
        localStorage.setItem('selected_country', this.selectedCountry);
        this.$emit('country-changed', this.selectedCountry);
        
        if (this.$emitter) {
          this.$emitter.emit('country-changed', this.selectedCountry);
        }
        
        this.updateMessage = 'Country updated!';
      }
      
      this.loading = false;
      
      // Clear message after 2 seconds
      setTimeout(() => {
        this.updateMessage = '';
      }, 2000);
    }
  }
}
</script>

<style scoped>
.settings-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  z-index: 1020;
}

.settings-bar .form-select {
  border: 1px solid rgba(255,255,255,0.3);
  background: rgba(255,255,255,0.1);
  color: white;
  min-width: 200px;
}

.settings-bar .form-select:focus {
  border-color: rgba(255,255,255,0.5);
  box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
  background: rgba(255,255,255,0.15);
}

.settings-bar .form-select option {
  background: #495057;
  color: white;
}

.update-message {
  font-size: 0.8rem;
}

.update-message.success {
  color: #d4edda;
}

.update-message.error {
  color: #f8d7da;
}
</style> 
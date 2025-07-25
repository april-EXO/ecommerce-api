<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <h2>{{ isLogin ? 'Login' : 'Register' }}</h2>
        <p class="auth-subtitle">
          {{ isLogin ? 'Welcome back!' : 'Create your account' }}
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <!-- Registration fields -->
        <div v-if="!isLogin" class="form-group">
          <label for="name">Full Name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-input"
            :class="{ 'error': errors.name }"
            placeholder="Enter your full name"
            required
          />
          <span v-if="errors.name" class="error-text">{{ errors.name[0] }}</span>
        </div>

        <!-- Email field -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'error': errors.email }"
            placeholder="Enter your email"
            required
          />
          <span v-if="errors.email" class="error-text">{{ errors.email[0] }}</span>
        </div>

        <!-- Password field -->
        <div class="form-group">
          <label for="password">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ 'error': errors.password }"
            placeholder="Enter your password"
            required
          />
          <span v-if="errors.password" class="error-text">{{ errors.password[0] }}</span>
        </div>

        <!-- Confirm Password (Registration only) -->
        <div v-if="!isLogin" class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            class="form-input"
            :class="{ 'error': errors.password_confirmation }"
            placeholder="Confirm your password"
            required
          />
          <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation[0] }}</span>
        </div>

        <!-- Country Code (Registration only) -->
        <div v-if="!isLogin" class="form-group">
          <label for="country_code">Country</label>
          <select
            id="country_code"
            v-model="form.country_code"
            class="form-input"
            :class="{ 'error': errors.country_code }"
          >
            <option value="">Select Country</option>
            <option value="MY">Malaysia</option>
            <option value="SG">Singapore</option>
          </select>
          <span v-if="errors.country_code" class="error-text">{{ errors.country_code[0] }}</span>
        </div>

        <!-- General Error Message -->
        <div v-if="errors.general" class="error-message">
          {{ errors.general[0] }}
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          class="auth-button"
          :disabled="authStore.loading"
        >
          <span v-if="authStore.loading">
            {{ isLogin ? 'Logging in...' : 'Creating account...' }}
          </span>
          <span v-else>
            {{ isLogin ? 'Login' : 'Create Account' }}
          </span>
        </button>
      </form>

      <!-- Toggle between login/register -->
      <div class="auth-toggle">
        <p v-if="isLogin">
          Don't have an account?
          <a href="#" @click.prevent="toggleMode">Create one</a>
        </p>
        <p v-else>
          Already have an account?
          <a href="#" @click.prevent="toggleMode">Login here</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { authStore } from '../stores/auth.js';

export default {
  name: 'Auth',
  data() {
    return {
      authStore,
      isLogin: true,
      form: {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        country_code: this.$globalCountry?.getCurrentCountry() || 'MY'
      },
      errors: {}
    };
  },
  methods: {
    toggleMode() {
      this.isLogin = !this.isLogin;
      this.clearForm();
      this.errors = {};
    },

    clearForm() {
      this.form = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        country_code: this.$globalCountry?.getCurrentCountry() || 'MY'
      };
    },

    async handleSubmit() {
      this.errors = {};

      const result = this.isLogin 
        ? await authStore.login(this.form)
        : await authStore.register(this.form);

      if (result.success) {
        // Successful authentication, redirect to home
        this.$router.push({ name: 'ProductList' });
        this.$emitter.emit('auth-success', result.user);
      } else {
        // Handle errors
        this.errors = result.errors;
      }
    }
  }
};
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.auth-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 40px;
  width: 100%;
  max-width: 440px;
}

.auth-header {
  text-align: center;
  margin-bottom: 30px;
}

.auth-header h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 8px;
}

.auth-subtitle {
  color: #718096;
  font-size: 16px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input.error {
  border-color: #f56565;
}

.error-text {
  color: #f56565;
  font-size: 14px;
  margin-top: 4px;
  display: block;
}

.error-message {
  background: #fed7d7;
  color: #c53030;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
}

.auth-button {
  width: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.auth-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.auth-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.auth-toggle {
  margin-top: 30px;
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.auth-toggle p {
  color: #718096;
  font-size: 14px;
}

.auth-toggle a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.auth-toggle a:hover {
  text-decoration: underline;
}
</style> 
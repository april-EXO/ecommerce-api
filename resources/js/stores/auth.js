import { reactive } from 'vue';
import axios from 'axios';

// Auth state management
export const authStore = reactive({
    user: null,
    token: localStorage.getItem('auth_token'),
    isAuthenticated: false,
    loading: false,

    // Initialize auth state
    async init() {
        const token = localStorage.getItem('auth_token');
        if (token) {
            this.token = token;
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            await this.fetchUser();
        }
    },

    // Register new user
    async register(userData) {
        this.loading = true;
        try {
            const response = await axios.post('/api/register', userData);
            const { user, access_token } = response.data;
            
            this.setAuthData(user, access_token);
            return { success: true, user };
        } catch (error) {
            console.error('Registration failed:', error);
            return { 
                success: false, 
                errors: error.response?.data?.errors || { general: ['Registration failed'] }
            };
        } finally {
            this.loading = false;
        }
    },

    // Login user
    async login(credentials) {
        this.loading = true;
        try {
            const response = await axios.post('/api/login', credentials);
            const { user, access_token } = response.data;
            
            this.setAuthData(user, access_token);
            return { success: true, user };
        } catch (error) {
            console.error('Login failed:', error);
            return { 
                success: false, 
                errors: error.response?.data?.errors || { general: ['Login failed'] }
            };
        } finally {
            this.loading = false;
        }
    },

    // Logout user
    async logout() {
        try {
            if (this.token) {
                await axios.post('/api/logout');
            }
        } catch (error) {
            console.error('Logout request failed:', error);
        } finally {
            this.clearAuthData();
        }
    },

    // Logout from all devices
    async logoutAll() {
        try {
            if (this.token) {
                await axios.post('/api/logout-all');
            }
        } catch (error) {
            console.error('Logout all request failed:', error);
        } finally {
            this.clearAuthData();
        }
    },

    // Fetch current user
    async fetchUser() {
        try {
            const response = await axios.get('/api/user');
            this.user = response.data.user;
            this.isAuthenticated = true;
            return this.user;
        } catch (error) {
            console.error('Fetch user failed:', error);
            this.clearAuthData();
            return null;
        }
    },

    // Set authentication data
    setAuthData(user, token) {
        this.user = user;
        this.token = token;
        this.isAuthenticated = true;
        
        localStorage.setItem('auth_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    },

    // Clear authentication data
    clearAuthData() {
        this.user = null;
        this.token = null;
        this.isAuthenticated = false;
        
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
    },

    // Update user's country preference
    async updateCountry(countryCode) {
        if (!this.isAuthenticated) {
            return { success: false, error: 'User not authenticated' };
        }

        try {
            const response = await axios.put('/api/user/country', {
                country_code: countryCode
            });
            
            // Update local user data
            this.user = response.data.data.user;
            
            return { 
                success: true, 
                user: this.user,
                country_code: countryCode
            };
        } catch (error) {
            console.error('Update country failed:', error);
            return { 
                success: false, 
                error: error.response?.data?.message || 'Failed to update country'
            };
        }
    }
}); 
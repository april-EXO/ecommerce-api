import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import mitt from 'mitt';

// Import components
import ProductList from './components/ProductList.vue';
import ProductDetail from './components/ProductDetail.vue';
import SettingsBar from './components/SettingsBar.vue';
import Auth from './components/Auth.vue';
import Cart from './components/Cart.vue';

// Import stores
import { authStore } from './stores/auth.js';
import { cartStore } from './stores/cart.js';

// Configure axios
axios.defaults.baseURL = 'http://127.0.0.1:8000';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.withCredentials = true; // Enable cookies/session handling

// Add CSRF token support
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Axios interceptors for token management
axios.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            authStore.clearAuthData();
            // Redirect to login if not already there
            if (router.currentRoute.value.name !== 'Auth') {
                router.push({ name: 'Auth' });
            }
        }
        return Promise.reject(error);
    }
);

// Create event emitter
const emitter = mitt();

// Create router
const routes = [
    { path: '/', name: 'ProductList', component: ProductList },
    { path: '/product/:id', name: 'ProductDetail', component: ProductDetail, props: true },
    { path: '/auth', name: 'Auth', component: Auth },
    { path: '/cart', name: 'Cart', component: Cart },
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Router guards for authentication
router.beforeEach((to, from, next) => {
    // Routes that don't require authentication
    const publicRoutes = ['ProductList', 'ProductDetail', 'Auth', 'Cart'];
    
    if (!publicRoutes.includes(to.name) && !authStore.isAuthenticated) {
        // Redirect to auth page if trying to access protected route
        next({ name: 'Auth' });
    } else if (to.name === 'Auth' && authStore.isAuthenticated) {
        // Redirect to home if already authenticated and trying to access auth page
        next({ name: 'ProductList' });
    } else {
        next();
    }
});

// Global country state management (Frontend-only)
const globalCountryState = {
    currentCountry: localStorage.getItem('selected_country') || 'MY',
    
    updateCountry(newCountry) {
        if (!['MY', 'SG'].includes(newCountry)) return false;
        
        this.currentCountry = newCountry;
        localStorage.setItem('selected_country', newCountry);
        emitter.emit('country-changed', newCountry);
        return true;
    },
    
    getCurrentCountry() {
        return this.currentCountry;
    }
};

// Create and mount the Vue app
const app = createApp({
    components: {
        SettingsBar
    },
    data() {
        return {
            currentCountry: globalCountryState.currentCountry,
            authStore,
            cartStore
        };
    },
    async mounted() {
        // Initialize auth store
        await authStore.init();
        
        // Initialize cart store with current country
        await cartStore.init(this.currentCountry);
        
        // 监听国家变化
        emitter.on('country-changed', async (newCountry) => {
            this.currentCountry = newCountry;
            // Update cart store when country changes
            await cartStore.updateCountry(newCountry);
        });

        // 监听认证成功事件
        emitter.on('auth-success', async (user) => {
            console.log('User authenticated:', user);
            // When user logs in, fetch their cart
            await cartStore.fetchCart();
        });

        // 监听登出事件
        window.addEventListener('auth-logout', () => {
            console.log('User logged out');
            // Clear cart when user logs out
            cartStore.clearCart();
        });
    },
    methods: {
        handleCountryChange(countryCode) {
            const success = globalCountryState.updateCountry(countryCode);
            if (success) {
                this.currentCountry = countryCode;
            }
        }
    },
    template: `
        <div>
            <SettingsBar 
                @country-changed="handleCountryChange"
                :auth-store="authStore"
                :cart-store="cartStore"
            />
            <router-view></router-view>
        </div>
    `
});

// Make axios, emitter and globalCountryState available globally
app.config.globalProperties.$http = axios;
app.config.globalProperties.$emitter = emitter;
app.config.globalProperties.$globalCountry = globalCountryState;
app.config.globalProperties.$authStore = authStore;
app.config.globalProperties.$cartStore = cartStore;

app.use(router);
app.mount('#app');

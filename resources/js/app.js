import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import mitt from 'mitt';

// Import components
import ProductList from './components/ProductList.vue';
import ProductDetail from './components/ProductDetail.vue';
import SettingsBar from './components/SettingsBar.vue';

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

// Create event emitter
const emitter = mitt();

// Create router
const routes = [
    { path: '/', name: 'ProductList', component: ProductList },
    { path: '/product/:id', name: 'ProductDetail', component: ProductDetail, props: true },
];

const router = createRouter({
    history: createWebHistory(),
    routes
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
            currentCountry: globalCountryState.currentCountry
        };
    },
    mounted() {
        // 监听国家变化
        emitter.on('country-changed', (newCountry) => {
            this.currentCountry = newCountry;
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
            />
            <router-view></router-view>
        </div>
    `
});

// Make axios, emitter and globalCountryState available globally
app.config.globalProperties.$http = axios;
app.config.globalProperties.$emitter = emitter;
app.config.globalProperties.$globalCountry = globalCountryState;

app.use(router);
app.mount('#app');

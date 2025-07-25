import { reactive } from 'vue';
import axios from 'axios';

// Cart state management - 只支持登录用户
export const cartStore = reactive({
    cart: null,
    cartItems: [],
    totalQuantity: 0,
    totalPrice: 0,
    loading: false,
    country: 'MY',

    // Initialize cart - 只有登录用户才初始化
    async init(country = 'MY') {
        this.country = country;
        
        // 检查用户是否登录
        const token = localStorage.getItem('auth_token');
        if (token) {
            await this.fetchCart();
        } else {
            this.clearCart();
        }
    },

    // Fetch cart from server - 只有登录用户
    async fetchCart() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            this.clearCart();
            return;
        }

        this.loading = true;
        try {
            const response = await axios.get('/api/cart', {
                params: { country: this.country }
            });
            
            if (response.data.success) {
                const data = response.data.data;
                this.cart = data;
                this.cartItems = data.cart_items || [];
                this.totalQuantity = data.total_quantity || 0;
                this.totalPrice = data.total_price || 0;
            }
        } catch (error) {
            console.error('Failed to fetch cart:', error);
            if (error.response?.status === 401) {
                this.clearCart();
            }
        } finally {
            this.loading = false;
        }
    },

    // Get cart count only (for header display)
    async fetchCartCount() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            this.totalQuantity = 0;
            return;
        }

        try {
            const response = await axios.get('/api/cart/count');
            if (response.data.success) {
                this.totalQuantity = response.data.data.count;
            }
        } catch (error) {
            console.error('Failed to fetch cart count:', error);
            if (error.response?.status === 401) {
                this.totalQuantity = 0;
            }
        }
    },

    // Add item to cart - 需要登录
    async addItem(productId, quantity = 1, country = null) {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            return { 
                success: false, 
                error: 'Please login to add items to cart',
                requireLogin: true
            };
        }

        this.loading = true;
        try {
            const response = await axios.post('/api/cart/items', {
                product_id: productId,
                quantity: quantity,
                country: country || this.country
            });

            if (response.data.success) {
                // Update local state
                const data = response.data.data;
                this.totalQuantity = data.cart_summary.total_quantity;
                this.totalPrice = data.cart_summary.total_price;
                
                // Refresh full cart data
                await this.fetchCart();
                
                return { success: true, data: data };
            }
        } catch (error) {
            console.error('Failed to add item to cart:', error);
            if (error.response?.status === 401) {
                return { 
                    success: false, 
                    error: 'Please login to add items to cart',
                    requireLogin: true 
                };
            }
            return { 
                success: false, 
                error: error.response?.data?.message || 'Failed to add item to cart' 
            };
        } finally {
            this.loading = false;
        }
    },

    // Update cart item quantity
    async updateItem(itemId, quantity) {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            return { success: false, error: 'Login required', requireLogin: true };
        }

        this.loading = true;
        try {
            const response = await axios.put(`/api/cart/items/${itemId}`, {
                quantity: quantity
            });

            if (response.data.success) {
                const data = response.data.data;
                this.totalQuantity = data.cart_summary.total_quantity;
                this.totalPrice = data.cart_summary.total_price;
                
                // Update local cart item
                const itemIndex = this.cartItems.findIndex(item => item.id === itemId);
                if (itemIndex !== -1) {
                    this.cartItems[itemIndex].quantity = quantity;
                    this.cartItems[itemIndex].total_price = data.cart_item.total_price;
                }
                
                return { success: true, data: data };
            }
        } catch (error) {
            console.error('Failed to update cart item:', error);
            if (error.response?.status === 401) {
                return { success: false, error: 'Login required', requireLogin: true };
            }
            return { 
                success: false, 
                error: error.response?.data?.message || 'Failed to update item' 
            };
        } finally {
            this.loading = false;
        }
    },

    // Remove item from cart
    async removeItem(itemId) {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            return { success: false, error: 'Login required', requireLogin: true };
        }

        this.loading = true;
        try {
            const response = await axios.delete(`/api/cart/items/${itemId}`);

            if (response.data.success) {
                const data = response.data.data;
                this.totalQuantity = data.cart_summary.total_quantity;
                this.totalPrice = data.cart_summary.total_price;
                
                // Remove from local state
                this.cartItems = this.cartItems.filter(item => item.id !== itemId);
                
                return { success: true };
            }
        } catch (error) {
            console.error('Failed to remove cart item:', error);
            if (error.response?.status === 401) {
                return { success: false, error: 'Login required', requireLogin: true };
            }
            return { 
                success: false, 
                error: error.response?.data?.message || 'Failed to remove item' 
            };
        } finally {
            this.loading = false;
        }
    },

    // Clear entire cart
    async clearCartRemote() {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            return { success: false, error: 'Login required', requireLogin: true };
        }

        this.loading = true;
        try {
            const response = await axios.delete('/api/cart');

            if (response.data.success) {
                this.cartItems = [];
                this.totalQuantity = 0;
                this.totalPrice = 0;
                
                return { success: true };
            }
        } catch (error) {
            console.error('Failed to clear cart:', error);
            if (error.response?.status === 401) {
                return { success: false, error: 'Login required', requireLogin: true };
            }
            return { 
                success: false, 
                error: error.response?.data?.message || 'Failed to clear cart' 
            };
        } finally {
            this.loading = false;
        }
    },

    // Update country and refresh cart
    async updateCountry(newCountry) {
        if (this.country !== newCountry) {
            this.country = newCountry;
            const token = localStorage.getItem('auth_token');
            if (token) {
                await this.fetchCart();
            }
        }
    },

    // Clear local cart state (when user logs out)
    clearCart() {
        this.cart = null;
        this.cartItems = [];
        this.totalQuantity = 0;
        this.totalPrice = 0;
    },

    // Get formatted currency
    getFormattedPrice(price) {
        const currency = this.country === 'MY' ? 'RM' : 'S$';
        return `${currency} ${Number(price).toFixed(2)}`;
    },

    // Check if product is in cart
    isInCart(productId) {
        return this.cartItems.some(item => item.product.id === productId);
    },

    // Get cart item by product id
    getCartItem(productId) {
        return this.cartItems.find(item => item.product.id === productId);
    },

    // Check if user is logged in
    isUserLoggedIn() {
        return !!localStorage.getItem('auth_token');
    }
}); 
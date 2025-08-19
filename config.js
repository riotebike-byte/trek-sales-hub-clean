// Trek Sales Hub - Configuration File
// Update API endpoints to use B2B API

const CONFIG = {
    // API Configuration
    API_BASE_URL: '/api/b2b', // Use local proxy server
    
    // Dashboard URLs
    DASHBOARD_BASE: '.',
    
    // Database Configuration (for reference)
    DB_NAME: 'trek_sales_db',
    
    // Features
    FEATURES: {
        REAL_TIME_SYNC: true,
        PROFIT_ANALYSIS: true,
        AI_AGENTS: true,
        MULTI_WAREHOUSE: true,
        COMMISSION_TRACKING: true
    },
    
    // API Endpoints
    ENDPOINTS: {
        PRODUCTS: '/products',
        WAREHOUSES: '/warehouses',
        INVENTORY: '/inventory',
        ORDERS: '/orders',
        CUSTOMERS: '/customers'
    }
};

// Make available globally
window.TrekConfig = CONFIG;
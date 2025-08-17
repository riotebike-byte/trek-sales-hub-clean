// Trek Sales Hub - Configuration File
// Update API endpoints to use PHP proxy

const CONFIG = {
    // API Configuration
    API_BASE_URL: 'https://video.trek-turkey.com/api-proxy.php',
    
    // Dashboard URLs
    DASHBOARD_BASE: 'https://video.trek-turkey.com',
    
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
        PRODUCTS: '?endpoint=/api/b2b/products',
        WAREHOUSES: '?endpoint=/api/b2b/warehouses',
        INVENTORY: '?endpoint=/api/b2b/inventory',
        ORDERS: '?endpoint=/api/b2b/orders',
        CUSTOMERS: '?endpoint=/api/b2b/customers'
    }
};

// Make available globally
window.TrekConfig = CONFIG;
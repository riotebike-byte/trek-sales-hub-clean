# 🚀 Trek Sales Management Hub - Production Deployment Guide

## 📋 System Overview

**Trek Sales Management Hub** is a comprehensive business intelligence platform featuring:
- 💹 **Real-time profit margin analysis**
- 🤖 **AI-powered sales agents** 
- 📊 **Live BizimHesap API integration**
- 🏢 **Multi-warehouse management**
- 💰 **Commission tracking system**
- 📈 **Advanced analytics dashboard**

## 🏗️ Architecture

### Core Components
1. **Main Dashboard** (`main-dashboard.html`) - Central hub with 9 modules
2. **SQL Database** (`database/setup.sql`) - MySQL schema for sales tracking
3. **API Proxy** (`simple-proxy.js`) - Node.js proxy for BizimHesap integration
4. **AI Agents** - Sales & profit optimization agents

### Key Features
- ✅ **Real-time data sync** with BizimHesap
- ✅ **Kar marj analizi** with 4-tab modal system
- ✅ **Agent communication** via localStorage
- ✅ **Responsive design** for mobile/desktop
- ✅ **Security features** with CORS and rate limiting

## 🚀 Production Deployment Steps

### 1. Server Requirements
```bash
# Server Specifications
- **Platform**: Niobe Server / VPS
- **PHP**: 8.1+ with MySQL support
- **MySQL**: 8.0+ 
- **Node.js**: 18+ for API proxy
- **SSL**: Required for production
- **Storage**: 10GB+ recommended
```

### 2. Database Setup
```sql
-- Create database and import schema
mysql -u root -p
CREATE DATABASE trek_sales_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

mysql -u root -p trek_sales_db < database/setup.sql
```

### 3. File Upload Structure
```
/public_html/
├── main-dashboard.html          # 🏠 Main hub
├── database/
│   └── setup.sql               # 🗄️ Database schema
├── dashboards/
│   ├── modern-dashboard.html   # 📊 Sales tracking
│   ├── real-bicycle-dashboard.html # 🚴 Live BizimHesap
│   ├── profit-optimization-agent.html # 💰 Margin analysis
│   ├── sales-campaign-agent.html # 🎯 AI recommendations
│   ├── customer-analytics.html # 👥 Customer insights
│   ├── invoice-management.html # 🧾 Invoice system
│   ├── product-catalog.html    # 🛍️ Product management
│   ├── warehouse-management.html # 🏭 Warehouse control
│   ├── financial-reports-dashboard.html # 💹 Financial analytics
│   └── admin-panel.html        # ⚙️ Administration
├── api/
│   ├── simple-proxy.js         # 🔄 API proxy server
│   ├── bizimhesap-api-integration.js # 🔗 BizimHesap integration
│   └── real-bizimhesap-business-logic.js # 📈 Business logic
├── tools/                      # 🛠️ Development tools (40+ files)
└── ai-agents/                  # 🤖 AI intelligence tools
```

### 4. Node.js Proxy Setup
```bash
# Install dependencies
npm install express cors helmet rate-limiter-flexible

# Start proxy server
node simple-proxy.js

# Setup PM2 for production
pm2 start simple-proxy.js --name "trek-sales-proxy"
pm2 startup
pm2 save
```

### 5. Environment Configuration
```bash
# .env file
DB_HOST=localhost
DB_NAME=trek_sales_db
DB_USER=trek_sales_user
DB_PASS=secure_password
BIZIMHESAP_API_URL=https://api.bizimhesap.com
NODE_ENV=production
```

### 6. SSL & Security Setup
```apache
# .htaccess configuration
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

## 📊 Dashboard Modules

### 1. Main Dashboard (Entry Point)
- **URL**: `/main-dashboard.html`
- **Features**: Central hub, profit filter panel, agent notifications
- **Data Source**: Real-time from other modules

### 2. Real Bicycle Dashboard 
- **URL**: `/real-bicycle-dashboard.html` 
- **Features**: Live BizimHesap data, landed cost calculation, modal analysis
- **API**: Direct BizimHesap integration

### 3. Sales Dashboard
- **URL**: `/modern-dashboard.html`
- **Features**: Commission tracking, employee management, targets
- **Database**: MySQL with sales tracking

### 4. AI Agents
- **Sales Agent**: `/sales-campaign-agent.html`
- **Profit Agent**: `/profit-optimization-agent.html`
- **Features**: AI recommendations, margin optimization

## 🔧 API Integration

### BizimHesap Endpoints
```javascript
// Core endpoints proxied through simple-proxy.js
GET /api/b2b/products      // Product catalog
GET /api/b2b/warehouses    // Warehouse list  
GET /api/b2b/inventory/:id // Warehouse inventory
```

### Real-time Data Flow
```
BizimHesap API → simple-proxy.js → Dashboard → localStorage → AI Agents
```

## 🛡️ Security Features

- **CORS Protection**: Configured for cross-origin requests
- **Rate Limiting**: API endpoint protection
- **SSL Encryption**: HTTPS required
- **Session Management**: Secure user sessions
- **Input Validation**: XSS and injection protection

## 📈 Performance Optimization

### Caching Strategy
```javascript
// Browser caching for static assets
Cache-Control: max-age=86400 for CSS/JS
Cache-Control: no-cache for API responses
```

### Database Optimization
```sql
-- Key indexes for performance
INDEX idx_depot (depot)
INDEX idx_category (category) 
INDEX idx_sale_date (sale_date)
INDEX idx_employee (employee_id)
```

## 🔍 Monitoring & Maintenance

### Health Checks
- **Database**: Connection and query performance
- **API Proxy**: Response times and error rates
- **BizimHesap**: API availability and rate limits
- **Disk Space**: Storage monitoring

### Backup Strategy
```bash
# Daily automated backups
0 2 * * * mysqldump trek_sales_db > /backups/trek_sales_$(date +%Y%m%d).sql
0 3 * * * tar -czf /backups/files_$(date +%Y%m%d).tar.gz /public_html/
```

## 🚨 Troubleshooting

### Common Issues
1. **BizimHesap API errors**: Check proxy server logs
2. **Database connection**: Verify credentials and permissions
3. **CORS errors**: Update simple-proxy.js CORS settings
4. **Agent communication**: Check localStorage in browser dev tools

### Log Locations
- **API Proxy**: `logs/proxy.log`
- **MySQL**: `/var/log/mysql/error.log` 
- **Apache**: `/var/log/apache2/error.log`

## 📞 Support & Updates

### Version Information
- **Current Version**: 2.0.0
- **Last Updated**: August 2025
- **Compatibility**: Modern browsers, PHP 8.1+, MySQL 8.0+

### Feature Roadmap
- ✅ Real-time profit analysis
- ✅ AI agent integration  
- ✅ Multi-warehouse support
- 🔄 Advanced reporting (planned)
- 🔄 Mobile app integration (planned)

---

## 🎯 Quick Start Commands

```bash
# 1. Database setup
mysql -u root -p < database/setup.sql

# 2. Start API proxy  
node simple-proxy.js

# 3. Upload files to server
scp -r * user@server:/public_html/

# 4. Test deployment
curl https://yourdomain.com/main-dashboard.html
```

**Production URL**: `https://yourdomain.com/main-dashboard.html`

---

**Trek Sales Management Hub** - Comprehensive business intelligence for the modern enterprise 🚀
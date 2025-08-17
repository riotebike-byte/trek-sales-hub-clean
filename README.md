# 🚴‍♂️ Trek Sales Management Hub

A comprehensive business intelligence platform for Trek bicycle sales with real-time BizimHesap API integration, AI-powered analytics, and advanced profit margin analysis.

## 🎯 Features

### 📊 Core Dashboards
- **Main Dashboard** - Central hub with profit filter panel and agent coordination
- **Real Bicycle Dashboard** - Live BizimHesap integration with landed cost calculations
- **Sales Dashboard** - Commission tracking and employee management
- **Profit Optimization Agent** - Advanced margin analysis and optimization strategies
- **Sales Campaign Agent** - AI-powered sales recommendations

### 🧮 Advanced Analytics
- **Landed Cost Calculations** - Category-based tax rate calculations (35%-70%)
- **Profit Margin Analysis** - Real-time margin tracking with optimization recommendations
- **AI Agent Communication** - Cross-dashboard data synchronization via localStorage
- **Multi-warehouse Management** - Complete inventory tracking across locations

### 🔗 API Integration
- **BizimHesap API** - Real-time product, warehouse, and inventory data
- **PHP Proxy Server** - Secure API endpoint management with CORS handling
- **Rate Limiting** - API protection with configurable limits

## 🏗️ Architecture

```
┌─────────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Main Dashboard    │    │  BizimHesap API  │    │   MySQL DB      │
│   (Central Hub)     │◄──►│     (Live Data)  │◄──►│  (Sales Data)   │
└─────────────────────┘    └──────────────────┘    └─────────────────┘
           │                           │
           ▼                           ▼
┌─────────────────────┐    ┌──────────────────┐
│ Real Bicycle        │    │   PHP Proxy      │
│ Dashboard           │    │   (api-proxy.php)│
└─────────────────────┘    └──────────────────┘
           │
           ▼
┌─────────────────────┐
│   AI Agents         │
│ (Sales & Profit)    │
└─────────────────────┘
```

## 🚀 Quick Start

### Production Deployment

1. **Upload to Server:**
   ```bash
   # Upload all files to web server
   scp -r * user@server:/public_html/
   ```

2. **Database Setup:**
   ```sql
   mysql -u root -p < database/setup.sql
   ```

3. **Configuration:**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Access:**
   - Main Hub: `https://yourdomain.com/main-dashboard.html`
   - Live Data: `https://yourdomain.com/real-bicycle-dashboard.html`

## 📋 Tax Rate Schema

The system uses category-based tax calculations:

| Category | Tax Rate | Products |
|----------|----------|----------|
| DSW Bisiklet | 70% | FX, DS, MARLIN |
| NCI Bisiklet | 35% | MADONE, EMONDA, DOMANE, POWERFLY |
| DSW Aksesuar | 60% | VERVE, AYDINLATMA, ÇANTA, KASK |
| NCI Aksesuar | 40% | JANT SETİ, GİDON |
| Gobik | 33% | FORMA, ÇORAP, TAYT-ŞORT |
| TriEye | 68% | Gözlük |
| Bryton | 65% | KM SAATİ |
| Saris | 65% | ARAÇ ARKASI TAŞIYICI |

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Backend:** PHP 8.1+ (API Proxy)
- **Database:** MySQL 8.0+
- **APIs:** BizimHesap REST API
- **Security:** CORS, Rate Limiting, SSL

## 📊 Performance Features

- **Real-time Synchronization** - Live data updates every 30 seconds
- **Caching Strategy** - Browser and server-side caching
- **Responsive Design** - Mobile and desktop optimized
- **Error Handling** - Comprehensive error logging and recovery

## 🔧 Development

### Local Development
```bash
# Start local API proxy (if needed)
node simple-proxy.js

# Or use PHP built-in server
php -S localhost:8000
```

### File Structure
```
├── main-dashboard.html          # Central hub
├── real-bicycle-dashboard.html  # Live BizimHesap integration
├── api-proxy.php               # API endpoint proxy
├── database/
│   └── setup.sql              # Database schema
├── config.js                  # Configuration
└── production-readme.md       # Deployment guide
```

## 🎯 Key Metrics

- **9 Dashboard Modules** - Complete business coverage
- **8 Tax Categories** - Precise margin calculations  
- **Real-time Data** - Live API synchronization
- **AI Recommendations** - Automated insights
- **Multi-warehouse** - Complete inventory tracking

## 📞 Support

For technical support or feature requests, refer to:
- `production-readme.md` - Detailed deployment guide
- `deploy-config.json` - System configuration
- Console logs for debugging

---

**Trek Sales Management Hub** - Empowering bicycle business with intelligent analytics 🚀
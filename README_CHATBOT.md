# Trek Sales AI Chatbot

Hybrid AI chatbot combining Hugging Face BF space functionality with Trek sales data.

## Features

### 📊 Dual Data Sources
- **Sales API**: 3,111 products from localhost:3002/api/b2b/products
- **Trek XML Catalog**: 863 products from trekbisiklet.com.tr
- Real-time synchronization and profit calculations

### 🤖 AI Capabilities
- **GPT-5 powered** responses with Turkish language support
- **Contextual awareness** of data sources
- **Profit analysis** with margin, landed cost, and profit potential
- **Product search** across both datasets

### 💻 Web Interface
- Modern responsive design with Trek branding
- Real-time chat with streaming responses
- Quick action buttons for common queries
- Auto-refresh data functionality

## Setup

1. **Install Dependencies**
   ```bash
   pip install flask requests
   ```

2. **Configure API Key**
   ```bash
   export OPENAI_API_KEY="your-openai-api-key"
   ```

3. **Run Application**
   ```bash
   python flask_sales_chatbot.py
   ```

4. **Access Interface**
   - Open http://127.0.0.1:5000 in your browser

## API Endpoints

- `GET /` - Web interface
- `POST /api/chat` - Chatbot interaction
- `POST /api/refresh` - Refresh data sources

## Usage Examples

### Sales Analytics
```
"Sales analiz raporu ver"
```

### Product Search
```
"FX bisiklet modelleri"
"Madone fiyatları"
```

### Profit Analysis
```
"En karlı ürünler neler?"
"Kar marjı analizi yap"
```

## Data Integration

The chatbot automatically:
- Loads sales data from B2B API
- Fetches Trek catalog from XML feed
- Calculates profit metrics with tax rates
- Provides unified search across both sources

## Architecture

```
Frontend (HTML/JS) 
    ↓
Flask API (/api/chat)
    ↓
Data Sources (Sales API + Trek XML)
    ↓
OpenAI GPT-5 Processing
    ↓
Response with Products & Analytics
```

## Files

- `flask_sales_chatbot.py` - Main Flask application
- `templates/chat.html` - Web interface
- `README_CHATBOT.md` - This documentation
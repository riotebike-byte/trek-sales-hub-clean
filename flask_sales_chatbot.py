from flask import Flask, render_template, request, jsonify
import requests
import json
import xml.etree.ElementTree as ET
from datetime import datetime
import threading
import time
import os

app = Flask(__name__)

# Configuration - Use environment variable or update with your API key
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY', 'your-openai-api-key-here')
API_URL = "https://api.openai.com/v1/chat/completions"
SALES_API_URL = "http://localhost:3002/api/b2b/products"

# Global data
sales_data = []
trek_products = []
data_lock = threading.Lock()

def load_sales_data():
    """Load sales data from API"""
    global sales_data
    try:
        print("🔄 Loading sales data...")
        response = requests.get(SALES_API_URL, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            if data and data.get('data') and data['data'].get('products'):
                sales_data = data['data']['products']
                print(f"✅ Loaded {len(sales_data)} sales products")
                return True
    except Exception as e:
        print(f"❌ Failed to load sales data: {e}")
    return False

def load_trek_products():
    """Load Trek products from XML"""
    global trek_products
    try:
        print("🔄 Loading Trek products...")
        url = 'https://www.trekbisiklet.com.tr/output/8582384479'
        response = requests.get(url, verify=False, timeout=30)
        
        if response.status_code == 200:
            root = ET.fromstring(response.content)
            trek_products = []
            
            for item in root.findall('item'):
                rootlabel_elem = item.find('rootlabel')
                stock_elem = item.find('stockAmount')
                
                if rootlabel_elem is not None and stock_elem is not None:
                    name = rootlabel_elem.text
                    stock = "stokta" if stock_elem.text and stock_elem.text > '0' else "stokta değil"
                    
                    if stock == "stokta":
                        price_elem = item.find('priceTaxWithCur')
                        price = price_elem.text if price_elem is not None else "Fiyat bilgisi yok"
                        
                        trek_products.append({
                            'name': name,
                            'stock': stock,
                            'price': price
                        })
            
            print(f"✅ Loaded {len(trek_products)} Trek products")
            return True
    except Exception as e:
        print(f"❌ Failed to load Trek products: {e}")
    return False

def calculate_profit_metrics(product):
    """Calculate profit metrics"""
    try:
        buying_price = float(product.get('buyingPrice', 0))
        selling_price = float(product.get('price', 0))
        quantity = int(product.get('quantity', 0))
        
        # Default tax rate
        tax_rate = 35
        category = product.get('category', '').upper()
        
        if 'DS' in category or 'POMPA' in category:
            tax_rate = 70
        elif 'AKSESUAR' in category:
            tax_rate = 40
        
        landed_cost = buying_price * (1 + tax_rate / 100)
        margin = ((selling_price - landed_cost) / selling_price * 100) if selling_price > 0 else 0
        profit_potential = (selling_price - landed_cost) * quantity
        
        return {
            'margin': margin,
            'profit_potential': profit_potential,
            'landed_cost': landed_cost
        }
    except:
        return None

def search_products(query):
    """Search in both datasets"""
    results = []
    
    # Search sales data
    for product in sales_data[:20]:  # Limit results
        title = product.get('title', '').lower()
        if query.lower() in title:
            metrics = calculate_profit_metrics(product)
            result = {
                'source': 'Sales Data',
                'title': product.get('title'),
                'price': f"€{float(product.get('price', 0)):.2f}",
                'stock': product.get('quantity', 0),
                'category': product.get('category', 'GENEL')
            }
            if metrics:
                result['margin'] = f"%{metrics['margin']:.1f}"
                result['profit_potential'] = f"€{metrics['profit_potential']:.0f}"
            results.append(result)
    
    # Search Trek products
    for product in trek_products[:10]:  # Limit results
        if query.lower() in product['name'].lower():
            results.append({
                'source': 'Trek Catalog',
                'title': product['name'],
                'price': product['price'],
                'stock': product['stock']
            })
    
    return results

def get_sales_summary():
    """Generate sales summary"""
    if not sales_data:
        return "Henüz sales verisi yüklenmedi."
    
    total_products = len(sales_data)
    total_stock = sum(int(p.get('quantity', 0)) for p in sales_data)
    
    # Calculate basic metrics
    profit_products = []
    for product in sales_data:
        metrics = calculate_profit_metrics(product)
        if metrics:
            profit_products.append(metrics)
    
    if profit_products:
        avg_margin = sum(p['margin'] for p in profit_products) / len(profit_products)
        total_profit = sum(p['profit_potential'] for p in profit_products)
        
        return f"""📊 SALES ANALYTICS:
• Toplam Ürün: {total_products:,}
• Toplam Stok: {total_stock:,} adet  
• Ortalama Kar Marjı: %{avg_margin:.1f}
• Toplam Kar Potansiyeli: €{total_profit:,.0f}"""
    
    return f"Toplam {total_products} ürün analiz edildi."

def generate_ai_response(message, context=""):
    """Generate AI response using OpenAI"""
    try:
        # Check if API key is configured
        if OPENAI_API_KEY == 'your-openai-api-key-here':
            return "OpenAI API key henüz yapılandırılmamış. Lütfen OPENAI_API_KEY environment variable'ını ayarlayın."
        
        # Create context
        full_context = f"""
Sen Trek Sales Hub'ın AI asistanısın. {len(sales_data)} sales ürünü ve {len(trek_products)} katalog ürünü erişimin var.

{context}

Kullanıcı mesajı: {message}

Bu bilgileri kullanarak detaylı, yardımsever ve veri odaklı bir cevap ver. Türkçe konuş.
"""

        payload = {
            "model": "gpt-5-chat-latest",
            "messages": [
                {"role": "system", "content": "Sen Trek bisiklet satış verilerini analiz eden AI asistansın. Türkçe konuşuyorsun."},
                {"role": "user", "content": full_context}
            ],
            "max_tokens": 500,
            "temperature": 0.7
        }
        
        headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {OPENAI_API_KEY}"
        }
        
        response = requests.post(API_URL, headers=headers, json=payload, timeout=30)
        
        if response.status_code == 200:
            data = response.json()
            return data['choices'][0]['message']['content']
        else:
            return f"API Hatası: {response.status_code}"
            
    except Exception as e:
        return f"Hata: {str(e)}"

# Initialize data on startup
print("🚀 Trek Sales Chatbot initializing...")
load_sales_data()
load_trek_products()
print("🤖 Chatbot ready!")

@app.route('/')
def index():
    return render_template('chat.html')

@app.route('/api/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        message = data.get('message', '')
        
        if not message:
            return jsonify({'error': 'No message provided'}), 400
        
        # Search for products
        products = search_products(message)
        
        # Build context
        context = ""
        if products:
            context += "\n\nÜRÜN ARAMA SONUÇLARI:\n"
            for product in products[:5]:  # Limit to 5 results
                context += f"• {product['title']} ({product['source']}) - {product['price']}\n"
        
        # Add analytics if requested
        if any(word in message.lower() for word in ['analiz', 'rapor', 'kar', 'profit']):
            context += f"\n\n{get_sales_summary()}"
        
        # Generate AI response
        ai_response = generate_ai_response(message, context)
        
        return jsonify({
            'response': ai_response,
            'products': products[:10],  # Return found products
            'stats': {
                'sales_products': len(sales_data),
                'trek_products': len(trek_products)
            }
        })
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/api/refresh', methods=['POST'])
def refresh_data():
    """Refresh data endpoint"""
    try:
        sales_success = load_sales_data()
        trek_success = load_trek_products()
        
        return jsonify({
            'success': True,
            'sales_loaded': sales_success,
            'trek_loaded': trek_success,
            'sales_count': len(sales_data),
            'trek_count': len(trek_products)
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, host='127.0.0.1', port=5000)
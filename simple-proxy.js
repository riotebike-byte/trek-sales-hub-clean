// Simple BizimHesap Proxy Server for Production
import express from 'express';
import cors from 'cors';
import fetch from 'node-fetch';

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());
app.use(express.json());

const BIZIMHESAP_API_KEY = '6F4BAF303FA240608A39653824B6C495';
const BIZIMHESAP_BASE_URL = 'https://bizimhesap.com/api';

app.use('/api', async (req, res) => {
    try {
        const endpoint = req.url;
        let targetUrl = `${BIZIMHESAP_BASE_URL}${endpoint}`;
        
        // Prepare headers
        const headers = {
            'User-Agent': 'Trek-Sales-Hub/1.0',
            'Content-Type': 'application/json'
        };
        
        // Forward original headers if available
        if (req.headers['content-type']) {
            headers['Content-Type'] = req.headers['content-type'];
        }
        
        // Check if it's a B2B API request (needs token) or legacy API (needs apikey)
        if (endpoint.includes('/b2b/')) {
            // B2B API - use token from request header or API key as fallback
            const token = req.headers.token || BIZIMHESAP_API_KEY;
            headers['token'] = token;
            console.log(`🔄 Proxying B2B API ${req.method}: ${targetUrl} (with token: ${token.substring(0,8)}...)`);
        } else {
            // Legacy API - add API key to URL
            const url = new URL(targetUrl);
            if (!url.searchParams.has('apikey')) {
                url.searchParams.set('apikey', BIZIMHESAP_API_KEY);
            }
            console.log(`🔄 Proxying Legacy API ${req.method}: ${url.toString()}`);
            targetUrl = url.toString();
        }
        
        // Prepare request options
        const fetchOptions = {
            method: req.method,
            headers: headers
        };
        
        // Add body for POST/PUT/PATCH requests
        if (['POST', 'PUT', 'PATCH'].includes(req.method) && req.body) {
            fetchOptions.body = JSON.stringify(req.body);
            console.log(`📝 Request body: ${JSON.stringify(req.body).substring(0, 200)}...`);
        }
        
        const response = await fetch(targetUrl, fetchOptions);
        
        // Handle different content types
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json();
            res.status(response.status).json(data);
        } else {
            const data = await response.text();
            res.status(response.status).send(data);
        }
        
    } catch (error) {
        console.error('Proxy error:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        timestamp: new Date().toISOString(),
        environment: process.env.NODE_ENV || 'development'
    });
});

// Serve static files for production
app.use(express.static('.'));

app.listen(PORT, () => {
    console.log(`🚀 Trek Sales Hub Proxy running on port ${PORT}`);
    console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`🔗 Health check: http://localhost:${PORT}/health`);
});
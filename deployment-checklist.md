# 🚀 Production Deployment Checklist

## Files to Upload

### Required Files (3):
1. **simple-category-dashboard.html** - Ana dashboard
2. **production-api.php** - API proxy (hata toleranslı)
3. **tax-admin-panel.html** - Vergi oranları yönetimi

### Upload Location:
- Server Path: `/var/www/vhosts/video.trek-turkey.com/httpdocs/`
- Or via FTP: `video.trek-turkey.com`

## Pre-Deployment Tests

### ✅ Local Tests Passed:
- [x] API returns 290 products
- [x] Dashboard loads in localhost
- [x] Tax admin panel works
- [x] Category separation (FX vs FX+, etc.)
- [x] Profit margin color coding

## Deployment Steps

### 1. Upload Files
```bash
# Via SSH
scp simple-category-dashboard.html production-api.php tax-admin-panel.html user@video.trek-turkey.com:/path/to/httpdocs/

# Or via FTP client
# Upload all 3 files to document root
```

### 2. Set Permissions
```bash
chmod 644 simple-category-dashboard.html
chmod 644 tax-admin-panel.html
chmod 755 production-api.php
```

### 3. Test URLs
- Dashboard: https://video.trek-turkey.com/simple-category-dashboard.html
- Admin Panel: https://video.trek-turkey.com/tax-admin-panel.html
- API Test: https://video.trek-turkey.com/production-api.php

## Production Features

### Robust API Proxy:
- ✅ Multiple fetch methods (cURL, file_get_contents)
- ✅ SSL verification bypass for BizimHesap
- ✅ XML error recovery with LIBXML_RECOVER
- ✅ Regex fallback for malformed XML
- ✅ Error reporting disabled for production
- ✅ CORS headers enabled

### Dashboard Features:
- ✅ Auto-detects production environment
- ✅ Modern profit margin visualization
- ✅ Category separation (FX/FX+, DOMANE/DOMANE+, DS/DS+)
- ✅ Dynamic tax rates from admin panel
- ✅ Part/accessory filtering

## Troubleshooting

### If API doesn't work:
1. Check PHP version (minimum 5.6, recommended 7.4+)
2. Verify cURL extension is enabled
3. Check allow_url_fopen setting
4. Verify file permissions (755 for PHP files)

### If dashboard shows error:
1. Open browser console (F12)
2. Check Network tab for failed requests
3. Verify production-api.php is accessible
4. Check CORS headers in response

## Success Criteria

Dashboard should:
- Load without errors
- Show ~290 products
- Display categories correctly
- Calculate profit margins
- Update from admin panel tax rates

## Support

For issues, check:
- Browser Console (F12) → Console tab
- Network tab → Check API response
- PHP error logs on server

---
Ready for production deployment! 🎯
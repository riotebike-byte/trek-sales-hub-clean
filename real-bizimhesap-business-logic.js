/**
 * Real BizimHesap Business Logic Engine
 * Gerçek BizimHesap veri yapısı ile bisiklet satış operasyonları
 */

class RealBizimHesapBusinessLogic {
    constructor() {
        // Kategori hiyerarşisi - Sizin verdiğiniz kategori şemasına göre
        this.categoryHierarchy = {
            'BISIKLET': {
                'YOL_BISIKLETI': ['MADONE', 'EMONDA', 'DOMANE', 'DOMANE_PLUS', 'SPEED_CONCEPT'],
                'GRAVEL': ['CHECKMATE', 'CHECKPOINT'],
                'ELEKTRIKLI_BISIKLET': ['FUEL_EXE', 'RAIL', 'POWERFLY_FS', 'POWERFLY', 'DOMANE_PLUS', 'DS_PLUS', 'FX_PLUS', 'VERVE_PLUS', 'TOWNIE_GO'],
                'DAG_BISIKLETI': ['MARLIN', 'PROCALIBER', 'SUPERCALIBER', 'FUEL_EX', 'FUEL_EXE', 'TOP_FUEL', 'RAIL', 'POWERFLY_FS', 'POWERFLY', 'MARLIN_PLUS'],
                'SEHIR_BISIKLETI': ['FX', 'DS', 'VERVE', 'VERVE_PLUS', 'ELECTRA_KADIN', 'DS_PLUS', 'FX_PLUS', 'TOWNIE_GO_PLUS']
            },
            'AKSESUAR': ['ARAC_ARKASI_TASIYICI', 'AYDINLATMA', 'BAGAJ_SEPET', 'BISIKLET_TRAINER', 'CAMURLUK', 'CANTA', 'ELCIK_GIDON_BANDI', 'KILIT', 'MATARA', 'MATARA_KAFESI', 'POMPA', 'YOL_BILGISAYARI', 'ZIL'],
            'YEDEK_PARCA': ['AEROBAR_GIDON', 'ANAHTAR_TAKIMI', 'BATARYA', 'BLENDR_AYDINLATMA', 'GIDON_BOGAZI', 'IC_LASTIK', 'INDIRIMLI_PARCA', 'JANT_SETI', 'KADRO_KULAGI', 'LASTIK', 'PARK_AYAGI', 'PEDAL', 'SELE_SELE_BORUSU'],
            'GIYIM': ['AYAKKABI_KILIF', 'CORAP', 'ELDIVEN', 'FORMA', 'GOBIK', 'ICLIK', 'INDIRIMLI_GIYIM', 'KASK', 'KOL_DIZ_ISITICI', 'RUZGARLIK_YAGMURLUK', 'TAYT_SORT'],
            'GOZLUK': ['TriEye']
        };

        // Bisiklet kategorileri için özel kurallar
        this.bicycleCriticalStockRules = {
            'FX': { criticalStock: 30, transferTrigger: 1 },
            'MARLIN': { criticalStock: 30, transferTrigger: 1 },
            'MADONE': { criticalStock: 15, transferTrigger: 1 },
            'EMONDA': { criticalStock: 15, transferTrigger: 1 },
            'DOMANE': { criticalStock: 20, transferTrigger: 1 },
            'FUEL_EX': { criticalStock: 25, transferTrigger: 1 },
            'PROCALIBER': { criticalStock: 20, transferTrigger: 1 }
        };

        // Depo konfigürasyonu
        this.depots = {
            'alsancak': { coefficient: 2, priority: 1, allowZeroStock: true, name: 'Alsancak' },
            'caddebostan': { coefficient: 2, priority: 1, allowZeroStock: false, name: 'Caddebostan' },
            'ortakoy': { coefficient: 1, priority: 2, allowZeroStock: false, name: 'Ortaköy' },
            'bahcekoy': { coefficient: 1, priority: 2, allowZeroStock: false, name: 'Bahçeköy' }
        };

        // Döviz kurları
        this.currencies = {
            'EUR': { rate: 1.0, symbol: '€', name: 'Euro' },
            'USD': { rate: 1.08, symbol: '$', name: 'US Dollar' },
            'TL': { rate: 32.5, symbol: '₺', name: 'Turkish Lira' }
        };

        // Satış hızı kategorileri
        this.salesVelocity = {
            'fast': { threshold: 3, period: 'week', description: 'Hızlı Giden' },
            'normal': { min: 1, max: 3, period: 'week', description: 'Normal' },
            'slow': { threshold: 1, period: 'week', description: 'Yavaş Giden' }
        };

        // Margin hedefleri kategori bazında
        this.marginTargets = {
            'BISIKLET': { min: 35, max: 55, preferred: 45 },
            'AKSESUAR': { min: 30, max: 50, preferred: 40 },
            'YEDEK_PARCA': { min: 40, max: 60, preferred: 50 },
            'GIYIM': { min: 50, max: 70, preferred: 60 }
        };
    }

    /**
     * Ürün varyantlarını analiz eder ve grup halinde stok durumunu değerlendirir
     */
    analyzeProductVariants(products) {
        const productGroups = {};
        const variantAnalysis = {};

        // Ürünleri ana ürün kartına göre grupla
        products.forEach(product => {
            const baseId = product.id;
            if (!productGroups[baseId]) {
                productGroups[baseId] = {
                    baseProduct: {
                        id: baseId,
                        title: product.title,
                        category: product.category,
                        brand: product.brand,
                        price: product.price,
                        buyingPrice: product.buyingPrice,
                        currency: product.currency
                    },
                    variants: [],
                    totalStock: 0,
                    activeVariants: 0,
                    ecommerceVariants: 0
                };
            }

            productGroups[baseId].variants.push(product);
            productGroups[baseId].totalStock += parseInt(product.quantity || 0);
            
            if (product.isActive) {
                productGroups[baseId].activeVariants++;
            }
            
            if (product.isEcommerce) {
                productGroups[baseId].ecommerceVariants++;
            }
        });

        // Her ürün grubu için analiz
        Object.keys(productGroups).forEach(baseId => {
            const group = productGroups[baseId];
            variantAnalysis[baseId] = this.analyzeProductGroup(group);
        });

        return {
            productGroups,
            variantAnalysis,
            summary: this.generateVariantSummary(productGroups, variantAnalysis)
        };
    }

    /**
     * Tek bir ürün grubunu analiz eder
     */
    analyzeProductGroup(group) {
        const analysis = {
            category: group.baseProduct.category,
            isBicycle: this.isBicycleCategory(group.baseProduct.category),
            totalVariants: group.variants.length,
            totalStock: group.totalStock,
            stockByVariant: {},
            criticalStock: null,
            transferNeeded: false,
            salesVelocity: 'unknown',
            margin: null,
            recommendations: []
        };

        // Bisiklet kategorisi ise kritik stok kontrolü
        if (analysis.isBicycle) {
            analysis.criticalStock = this.getCriticalStockLevel(group.baseProduct.category);
            analysis.transferNeeded = group.totalStock <= analysis.criticalStock;
            
            if (analysis.transferNeeded) {
                analysis.recommendations.push({
                    type: 'critical_restock',
                    priority: 'urgent',
                    message: `${group.baseProduct.title} kritik seviyede (${group.totalStock}/${analysis.criticalStock})`
                });
            }
        }

        // Varyant bazlı stok analizi
        group.variants.forEach(variant => {
            const variantStock = parseInt(variant.quantity || 0);
            analysis.stockByVariant[variant.variant] = {
                stock: variantStock,
                sku: variant.code,
                isActive: variant.isActive,
                isEcommerce: variant.isEcommerce,
                needsTransfer: this.shouldTriggerTransfer(group.baseProduct, variantStock)
            };

            // Varyant bazlı transfer önerileri
            if (analysis.stockByVariant[variant.variant].needsTransfer) {
                analysis.recommendations.push({
                    type: 'variant_transfer',
                    priority: 'high',
                    message: `${variant.variant} varyantı transfer seviyesinde (${variantStock})`
                });
            }
        });

        // Margin analizi
        analysis.margin = this.calculateMargin(group.baseProduct);

        return analysis;
    }

    /**
     * Kategori bisiklet kategorisi mi kontrol eder
     */
    isBicycleCategory(category) {
        const categoryUpper = category.toUpperCase();
        
        // Ana bisiklet kategorileri
        const bicycleCategories = [
            'MADONE', 'EMONDA', 'DOMANE', 'SPEED_CONCEPT',
            'CHECKMATE', 'CHECKPOINT',
            'FUEL_EXE', 'RAIL', 'POWERFLY_FS', 'POWERFLY', 'DOMANE_PLUS', 'DS_PLUS', 'FX_PLUS', 'VERVE_PLUS', 'TOWNIE_GO',
            'MARLIN', 'PROCALIBER', 'SUPERCALIBER', 'FUEL_EX', 'TOP_FUEL', 'MARLIN_PLUS',
            'FX', 'DS', 'VERVE', 'ELECTRA_KADIN', 'TOWNIE_GO_PLUS'
        ];

        return bicycleCategories.includes(categoryUpper) || 
               categoryUpper.includes('BISIKLET') || 
               categoryUpper.includes('BICYCLE');
    }

    /**
     * Kategori için kritik stok seviyesini döndürür
     */
    getCriticalStockLevel(category) {
        const categoryUpper = category.toUpperCase();
        
        // Özel kurallar
        if (this.bicycleCriticalStockRules[categoryUpper]) {
            return this.bicycleCriticalStockRules[categoryUpper].criticalStock;
        }

        // Genel bisiklet kategorileri için varsayılan
        if (this.isBicycleCategory(category)) {
            return 30; // FX ve Marlin için sizin belirttiğiniz seviye
        }

        return 10; // Aksesuar vb. için düşük seviye
    }

    /**
     * Transfer tetiklenmesi gerekip gerekmediğini kontrol eder
     */
    shouldTriggerTransfer(product, currentStock) {
        const categoryUpper = product.category.toUpperCase();
        
        // Bisiklet varyantları için seviye 1
        if (this.isBicycleCategory(product.category)) {
            const rule = this.bicycleCriticalStockRules[categoryUpper];
            return currentStock <= (rule?.transferTrigger || 1);
        }

        return currentStock <= 2; // Diğer kategoriler için
    }

    /**
     * Depo bazlı ideal stok seviyesini hesaplar
     */
    calculateIdealStockPerDepot(product, depot, salesHistory = []) {
        const depotConfig = this.depots[depot.toLowerCase()];
        if (!depotConfig) return 0;

        const baseSalesVelocity = this.calculateSalesVelocity(salesHistory);
        const criticalStock = this.getCriticalStockLevel(product.category);
        
        // Depo katsayısına göre ideal stok
        let idealStock = baseSalesVelocity.weeklyAverage * depotConfig.coefficient * 2;
        
        // Minimum kritik seviye
        idealStock = Math.max(idealStock, criticalStock * (depotConfig.coefficient / 2));
        
        // Alsancak özel durumu
        if (depot.toLowerCase() === 'alsancak' && depotConfig.allowZeroStock) {
            idealStock = Math.min(idealStock, criticalStock * 0.5);
        }

        return Math.ceil(idealStock);
    }

    /**
     * Satış hızını hesaplar
     */
    calculateSalesVelocity(salesHistory) {
        if (!salesHistory || salesHistory.length === 0) {
            return {
                category: 'slow',
                weeklyAverage: 0,
                monthlyAverage: 0,
                recommendation: 'Veri yetersiz'
            };
        }

        const weeklyAverage = this.calculateWeeklyAverage(salesHistory);
        const monthlyAverage = weeklyAverage * 4.33;

        let category = 'slow';
        if (weeklyAverage > this.salesVelocity.fast.threshold) {
            category = 'fast';
        } else if (weeklyAverage >= this.salesVelocity.normal.min) {
            category = 'normal';
        }

        return {
            category,
            weeklyAverage,
            monthlyAverage,
            description: this.salesVelocity[category].description,
            recommendation: this.getSalesVelocityRecommendation(category, weeklyAverage)
        };
    }

    /**
     * Margin hesaplama
     */
    calculateMargin(product) {
        const price = parseFloat(product.price || 0);
        const buyingPrice = parseFloat(product.buyingPrice || 0);
        
        if (price <= 0 || buyingPrice <= 0) {
            return {
                marginAmount: 0,
                marginPercentage: 0,
                status: 'unknown',
                currency: product.currency
            };
        }

        // Döviz çevirisi (buying price genelde USD, price genelde EUR)
        const priceInSameCurrency = this.convertCurrency(price, product.currency, 'EUR');
        const buyingPriceInEur = this.convertCurrency(buyingPrice, 'USD', 'EUR');
        
        const marginAmount = priceInSameCurrency - buyingPriceInEur;
        const marginPercentage = (marginAmount / priceInSameCurrency) * 100;

        // Kategori hedeflerine göre durum
        const categoryType = this.getCategoryType(product.category);
        const target = this.marginTargets[categoryType];
        
        let status = 'optimal';
        if (marginPercentage < target.min) {
            status = 'low';
        } else if (marginPercentage > target.max) {
            status = 'high';
        }

        return {
            marginAmount,
            marginPercentage,
            status,
            target,
            currency: 'EUR',
            priceInEur: priceInSameCurrency,
            buyingPriceInEur
        };
    }

    /**
     * Kategori tipini belirler
     */
    getCategoryType(category) {
        if (this.isBicycleCategory(category)) return 'BISIKLET';
        
        const categoryUpper = category.toUpperCase();
        if (categoryUpper.includes('AKSESUAR')) return 'AKSESUAR';
        if (categoryUpper.includes('YEDEK') || categoryUpper.includes('PARCA')) return 'YEDEK_PARCA';
        if (categoryUpper.includes('GIYIM') || categoryUpper.includes('FORMA') || categoryUpper.includes('KASK')) return 'GIYIM';
        
        return 'AKSESUAR'; // Varsayılan
    }

    /**
     * Döviz çevirisi
     */
    convertCurrency(amount, fromCurrency, toCurrency) {
        const fromRate = this.currencies[fromCurrency]?.rate || 1;
        const toRate = this.currencies[toCurrency]?.rate || 1;
        
        return (amount / fromRate) * toRate;
    }

    /**
     * Depo transfer önerileri oluşturur
     */
    generateTransferRecommendations(productGroups, variantAnalysis) {
        const recommendations = [];

        Object.keys(productGroups).forEach(baseId => {
            const group = productGroups[baseId];
            const analysis = variantAnalysis[baseId];

            if (analysis.transferNeeded) {
                recommendations.push({
                    productId: baseId,
                    productTitle: group.baseProduct.title,
                    category: group.baseProduct.category,
                    currentStock: group.totalStock,
                    criticalLevel: analysis.criticalStock,
                    priority: 'urgent',
                    reason: 'Kritik stok seviyesi',
                    suggestedAction: 'Acil tedarik veya depo transferi',
                    depotRecommendations: this.generateDepotTransferPlan(group, analysis)
                });
            }

            // Varyant bazlı transfer önerileri
            Object.keys(analysis.stockByVariant).forEach(variantName => {
                const variantData = analysis.stockByVariant[variantName];
                if (variantData.needsTransfer) {
                    recommendations.push({
                        productId: baseId,
                        productTitle: `${group.baseProduct.title} - ${variantName}`,
                        variant: variantName,
                        currentStock: variantData.stock,
                        priority: 'high',
                        reason: 'Varyant transfer seviyesinde',
                        suggestedAction: 'Depo transferi gerekli'
                    });
                }
            });
        });

        return recommendations.sort((a, b) => {
            const priorityOrder = { 'urgent': 0, 'high': 1, 'medium': 2, 'low': 3 };
            return priorityOrder[a.priority] - priorityOrder[b.priority];
        });
    }

    /**
     * Depo transfer planı oluşturur
     */
    generateDepotTransferPlan(group, analysis) {
        const depotPlan = {};
        
        Object.keys(this.depots).forEach(depot => {
            const depotConfig = this.depots[depot];
            const idealStock = this.calculateIdealStockPerDepot(group.baseProduct, depot);
            
            depotPlan[depot] = {
                name: depotConfig.name,
                coefficient: depotConfig.coefficient,
                priority: depotConfig.priority,
                idealStock,
                currentStock: Math.floor(group.totalStock / 4), // Simulated current depot stock
                transferNeeded: Math.max(0, idealStock - Math.floor(group.totalStock / 4))
            };
        });

        return depotPlan;
    }

    /**
     * Helper methods
     */
    calculateWeeklyAverage(salesHistory) {
        // Simulated calculation - gerçek implementasyonda satış verisi kullanılacak
        return Math.random() * 5; // 0-5 arası random
    }

    getSalesVelocityRecommendation(category, weeklyAverage) {
        switch (category) {
            case 'fast':
                return `Hızlı satış (${weeklyAverage.toFixed(1)}/hafta) - Stok artırın`;
            case 'normal':
                return `Normal satış (${weeklyAverage.toFixed(1)}/hafta) - Mevcut strateji devam`;
            case 'slow':
                return `Yavaş satış (${weeklyAverage.toFixed(1)}/hafta) - Promosyon düşünün`;
            default:
                return 'Veri yetersiz';
        }
    }

    generateVariantSummary(productGroups, variantAnalysis) {
        const summary = {
            totalProducts: Object.keys(productGroups).length,
            totalVariants: 0,
            totalStock: 0,
            bicycleProducts: 0,
            criticalProducts: 0,
            transferNeeded: 0,
            categoryBreakdown: {}
        };

        Object.values(productGroups).forEach(group => {
            summary.totalVariants += group.variants.length;
            summary.totalStock += group.totalStock;
            
            const category = group.baseProduct.category;
            if (!summary.categoryBreakdown[category]) {
                summary.categoryBreakdown[category] = {
                    products: 0,
                    variants: 0,
                    stock: 0
                };
            }
            
            summary.categoryBreakdown[category].products++;
            summary.categoryBreakdown[category].variants += group.variants.length;
            summary.categoryBreakdown[category].stock += group.totalStock;
        });

        Object.values(variantAnalysis).forEach(analysis => {
            if (analysis.isBicycle) {
                summary.bicycleProducts++;
            }
            if (analysis.transferNeeded) {
                summary.criticalProducts++;
                summary.transferNeeded++;
            }
        });

        return summary;
    }
}

// Export for use in agent system
window.RealBizimHesapBusinessLogic = RealBizimHesapBusinessLogic;

console.log('🚴 Real BizimHesap Business Logic Engine loaded');
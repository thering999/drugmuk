/**
 * Frontend Performance Optimization
 * 
 * This file contains utilities for lazy loading, image optimization,
 * and performance monitoring on the frontend
 * 
 * @version 1.0
 * @since Phase 6.2
 */

// ============================================================================
// LAZY LOADING IMAGES
// ============================================================================

/**
 * Initialize lazy loading for images
 */
function initLazyLoading() {
    // Use Intersection Observer for modern browsers
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;

                    // Load the image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        // Observe all lazy images
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img.lazy').forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.classList.remove('lazy');
            }
        });
    }
}

// ============================================================================
// DEBOUNCE & THROTTLE
// ============================================================================

/**
 * Debounce function calls
 * @param {Function} func Function to debounce
 * @param {number} wait Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function calls
 * @param {Function} func Function to throttle
 * @param {number} limit Limit in milliseconds
 * @returns {Function} Throttled function
 */
function throttle(func, limit = 300) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ============================================================================
// PERFORMANCE MONITORING
// ============================================================================

/**
 * Measure page load performance
 */
function measurePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                const connectTime = perfData.responseEnd - perfData.requestStart;
                const renderTime = perfData.domComplete - perfData.domLoading;

                console.log('Performance Metrics:');
                console.log('Page Load Time:', pageLoadTime + 'ms');
                console.log('Connect Time:', connectTime + 'ms');
                console.log('Render Time:', renderTime + 'ms');

                // Send to analytics if needed
                if (typeof sendAnalytics === 'function') {
                    sendAnalytics({
                        pageLoadTime,
                        connectTime,
                        renderTime
                    });
                }
            }, 0);
        });
    }
}

/**
 * Monitor long tasks (> 50ms)
 */
function monitorLongTasks() {
    if ('PerformanceObserver' in window) {
        try {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    console.warn('Long Task detected:', {
                        duration: entry.duration,
                        startTime: entry.startTime
                    });
                }
            });

            observer.observe({ entryTypes: ['longtask'] });
        } catch (e) {
            // PerformanceObserver not supported for longtask
        }
    }
}

// ============================================================================
// RESOURCE HINTS
// ============================================================================

/**
 * Preload critical resources
 * @param {string} url Resource URL
 * @param {string} as Resource type (script, style, image, etc.)
 */
function preloadResource(url, as = 'script') {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = url;
    link.as = as;
    document.head.appendChild(link);
}

/**
 * Prefetch resources for next page
 * @param {string} url Resource URL
 */
function prefetchResource(url) {
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = url;
    document.head.appendChild(link);
}

/**
 * DNS prefetch for external domains
 * @param {string} domain Domain to prefetch
 */
function dnsPrefetch(domain) {
    const link = document.createElement('link');
    link.rel = 'dns-prefetch';
    link.href = domain;
    document.head.appendChild(link);
}

// ============================================================================
// LOCAL STORAGE CACHE
// ============================================================================

/**
 * Simple localStorage cache with TTL
 */
const LocalCache = {
    /**
     * Set item in cache
     * @param {string} key Cache key
     * @param {*} value Value to cache
     * @param {number} ttl Time to live in seconds
     */
    set(key, value, ttl = 3600) {
        const item = {
            value: value,
            expiry: Date.now() + (ttl * 1000)
        };
        try {
            localStorage.setItem(key, JSON.stringify(item));
        } catch (e) {
            console.error('LocalStorage set error:', e);
        }
    },

    /**
     * Get item from cache
     * @param {string} key Cache key
     * @returns {*} Cached value or null
     */
    get(key) {
        try {
            const itemStr = localStorage.getItem(key);
            if (!itemStr) return null;

            const item = JSON.parse(itemStr);

            // Check if expired
            if (Date.now() > item.expiry) {
                localStorage.removeItem(key);
                return null;
            }

            return item.value;
        } catch (e) {
            console.error('LocalStorage get error:', e);
            return null;
        }
    },

    /**
     * Remove item from cache
     * @param {string} key Cache key
     */
    remove(key) {
        localStorage.removeItem(key);
    },

    /**
     * Clear all cache
     */
    clear() {
        localStorage.clear();
    }
};

// ============================================================================
// AJAX REQUEST CACHING
// ============================================================================

/**
 * Fetch with cache
 * @param {string} url URL to fetch
 * @param {object} options Fetch options
 * @param {number} cacheTTL Cache TTL in seconds
 * @returns {Promise} Fetch promise
 */
async function cachedFetch(url, options = {}, cacheTTL = 300) {
    const cacheKey = 'fetch_' + url + JSON.stringify(options);

    // Try to get from cache
    const cached = LocalCache.get(cacheKey);
    if (cached) {
        console.log('Cache hit:', url);
        return Promise.resolve(cached);
    }

    // Fetch from server
    console.log('Cache miss:', url);
    const response = await fetch(url, options);
    const data = await response.json();

    // Store in cache
    LocalCache.set(cacheKey, data, cacheTTL);

    return data;
}

// ============================================================================
// IMAGE OPTIMIZATION
// ============================================================================

/**
 * Load image with fallback
 * @param {string} src Image source
 * @param {string} fallback Fallback image
 * @returns {Promise} Promise that resolves when image loads
 */
function loadImage(src, fallback = '/images/placeholder.png') {
    return new Promise((resolve, reject) => {
        const img = new Image();

        img.onload = () => resolve(img);
        img.onerror = () => {
            img.src = fallback;
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Failed to load image'));
        };

        img.src = src;
    });
}

/**
 * Generate responsive image srcset
 * @param {string} baseUrl Base image URL
 * @param {array} sizes Array of sizes [width, height]
 * @returns {string} srcset string
 */
function generateSrcset(baseUrl, sizes = [[320, 240], [640, 480], [1280, 960]]) {
    return sizes.map(([w, h]) => {
        return `${baseUrl}?w=${w}&h=${h} ${w}w`;
    }).join(', ');
}

// ============================================================================
// INITIALIZATION
// ============================================================================

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initLazyLoading();
        measurePerformance();
        monitorLongTasks();
    });
} else {
    initLazyLoading();
    measurePerformance();
    monitorLongTasks();
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initLazyLoading,
        debounce,
        throttle,
        measurePerformance,
        preloadResource,
        prefetchResource,
        dnsPrefetch,
        LocalCache,
        cachedFetch,
        loadImage,
        generateSrcset
    };
}

/**
 * Lazy-load catalog inventory on /catalog (shell renders first).
 */
(function () {
    'use strict';

    document.documentElement.classList.remove('catalog-nav-pending');

    function esc(s) {
        if (s == null) {
            return '';
        }
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function formatNaira(n) {
        var x = Number(n);
        if (!isFinite(x)) {
            x = 0;
        }
        return x.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function findSectionByCategory(cat) {
        var sections = document.querySelectorAll('.reseller-category-section');
        for (var i = 0; i < sections.length; i++) {
            if (sections[i].getAttribute('data-category') === cat) {
                return sections[i];
            }
        }
        return null;
    }

    function buildHtml(products, canOrder) {
        if (!products.length) {
            return '<div class="card"><p class="text-muted mb-0">No products available.</p></div>';
        }
        var categories = {};
        products.forEach(function (p) {
            var cat = p.category && String(p.category).trim() !== '' ? String(p.category) : 'Uncategorized';
            if (!categories[cat]) {
                categories[cat] = [];
            }
            categories[cat].push(p);
        });
        var catNames = Object.keys(categories).sort();
        var initCount = 5;
        var html = '';
        html += '<div class="reseller-toolbar">';
        html += '<div class="reseller-search-wrap">';
        html += '<input type="search" id="product-search" class="reseller-search" placeholder="Search products..." aria-label="Search products">';
        html += '</div>';
        html += '<div class="reseller-category-dropdown-wrap">';
        html += '<label for="shop-by-category" class="reseller-category-label">Shop by category</label>';
        html += '<select id="shop-by-category" class="reseller-category-select" aria-label="Filter by category">';
        html += '<option value="*">All categories</option>';
        catNames.forEach(function (c) {
            html += '<option value="' + esc(c) + '">' + esc(c) + '</option>';
        });
        html += '</select></div></div>';

        catNames.forEach(function (catName) {
            var catProducts = categories[catName];
            var total = catProducts.length;
            var hasMore = total > initCount;
            var moreCount = hasMore ? total - initCount : 0;
            html += '<section class="reseller-category-section" data-category="' + esc(catName) + '">';
            html += '<h2 class="reseller-category-heading">' + esc(catName) + '</h2>';
            html += '<div class="reseller-product-grid">';
            catProducts.forEach(function (p, i) {
                var isMore = i >= initCount;
                var imgUrl = p.image_url || '';
                var maxStock = Math.max(1, parseInt(p.in_stock, 10) || 0);
                html += '<div class="reseller-product-card product-card" data-name="' + esc(String(p.name || '').toLowerCase()) + '" data-category="' + esc(catName) + '"';
                if (isMore) {
                    html += ' style="display:none;" data-more="1"';
                }
                html += '>';
                html += '<div class="reseller-product-card__img-wrap">';
                if (imgUrl) {
                    html += '<img src="' + esc(imgUrl) + '" alt="' + esc(p.name) + '" class="reseller-product-card__img" loading="lazy" referrerpolicy="no-referrer" decoding="async">';
                    html += '<div class="reseller-product-card__img-placeholder reseller-product-card__img-placeholder--fallback" aria-hidden="true">No image</div>';
                } else {
                    html += '<div class="reseller-product-card__img-placeholder" aria-hidden="true">No image</div>';
                }
                html += '</div><div class="reseller-product-card__body">';
                html += '<h3 class="reseller-product-card__title">' + esc(p.name) + '</h3>';
                html += '<div class="reseller-product-card__meta">';
                html += '<span class="card__pill reseller-product-card__pill reseller-product-card__pill--amount">₦' + formatNaira(p.amount) + '</span>';
                html += '<span class="card__pill reseller-product-card__pill reseller-product-card__pill--stock">Stock: ' + esc(String(parseInt(p.in_stock, 10) || 0)) + '</span>';
                html += '</div>';
                if (canOrder) {
                    html += '<form method="post" class="reseller-product-card__form">';
                    html += '<input type="hidden" name="product_id" value="' + esc(String(parseInt(p.id, 10) || 0)) + '">';
                    html += '<input type="number" name="qty" class="qty-input" value="1" min="1" max="' + esc(String(maxStock)) + '">';
                    html += '<button type="submit" class="btn btn-primary btn-cart" title="Add to cart" aria-label="Add to cart">';
                    html += '<svg class="btn-cart-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
                    html += '</button></form>';
                }
                html += '</div></div>';
            });
            if (hasMore) {
                html += '<div class="reseller-view-more-wrap">';
                html += '<button type="button" class="btn btn-secondary reseller-view-more" data-category="' + esc(catName) + '">View more (' + esc(String(moreCount)) + ' more)</button>';
                html += '</div>';
            }
            html += '</div></section>';
        });
        return html;
    }

    function wireInteractions() {
        var search = document.getElementById('product-search');
        var categorySelect = document.getElementById('shop-by-category');
        var sections = document.querySelectorAll('.reseller-category-section');
        var currentCategory = '*';

        function filter() {
            var q = search && search.value ? search.value.trim().toLowerCase() : '';
            sections.forEach(function (section) {
                var cat = section.getAttribute('data-category');
                var showSection = currentCategory === '*' || cat === currentCategory;
                var cards = section.querySelectorAll('.product-card');
                var visible = 0;
                cards.forEach(function (card) {
                    var name = card.getAttribute('data-name') || '';
                    var matchSearch = !q || name.indexOf(q) !== -1;
                    var show = showSection && matchSearch;
                    card.style.display = show ? '' : 'none';
                    if (show) {
                        visible++;
                    }
                });
                section.style.display = visible ? '' : 'none';
            });
        }

        if (search) {
            search.addEventListener('input', filter);
            search.addEventListener('search', filter);
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                currentCategory = categorySelect.value || '*';
                filter();
            });
        }

        document.querySelectorAll('.reseller-view-more').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cat = btn.getAttribute('data-category');
                var section = findSectionByCategory(cat);
                if (!section) {
                    return;
                }
                var moreCards = section.querySelectorAll('.product-card[data-more="1"]');
                moreCards.forEach(function (c) {
                    c.style.display = '';
                    c.removeAttribute('data-more');
                });
                if (btn.parentElement) {
                    btn.parentElement.style.display = 'none';
                }
            });
        });
    }

    function showError(msg) {
        var st = document.getElementById('catalog-lazy-state');
        var el = document.getElementById('catalog-lazy-error');
        var p = document.getElementById('catalog-lazy-error-text');
        if (st) {
            st.style.display = 'none';
        }
        if (p) {
            p.textContent = msg;
        }
        if (el) {
            el.hidden = false;
        }
        var root = document.getElementById('catalog-root');
        if (root) {
            root.setAttribute('aria-busy', 'false');
        }
        document.documentElement.classList.remove('catalog-products-loading');
    }

    function hideLoader() {
        var st = document.getElementById('catalog-lazy-state');
        if (st) {
            st.style.display = 'none';
        }
        var root = document.getElementById('catalog-root');
        if (root) {
            root.setAttribute('aria-busy', 'false');
        }
        document.documentElement.classList.remove('catalog-products-loading');
    }

    var root = document.getElementById('catalog-root');
    if (!root) {
        return;
    }

    var url = root.getAttribute('data-products-url') || '/catalog_products';
    var canOrder = root.getAttribute('data-can-order') === '1';

    document.documentElement.classList.add('catalog-products-loading');

    fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    })
        .then(function (r) {
            return r.text().then(function (t) {
                var data;
                try {
                    data = JSON.parse(t);
                } catch (e) {
                    data = { ok: false, error: 'Invalid response from server.' };
                }
                return { ok: r.ok, data: data };
            });
        })
        .then(function (res) {
            if (!res.ok || !res.data || res.data.ok === false) {
                var err = (res.data && res.data.error) || (res.ok === false ? 'Unauthorized or server error.' : 'Could not load products.');
                showError(err);
                return;
            }
            var main = document.getElementById('catalog-main');
            if (!main) {
                return;
            }
            main.innerHTML = buildHtml(res.data.products || [], canOrder);
            hideLoader();
            wireInteractions();
            main.querySelectorAll('.reseller-product-card__img').forEach(function (img) {
                img.addEventListener('error', function () {
                    this.style.display = 'none';
                    var ph = this.nextElementSibling;
                    if (ph && ph.classList) {
                        ph.classList.add('reseller-product-card__img-placeholder--show');
                    }
                });
            });
        })
        .catch(function () {
            showError('Network error. Check your connection and try again.');
        });
})();

const navToggle = document.querySelector('.nav-toggle');
const navLinks = document.querySelector('.nav-links');

if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
}

document.querySelectorAll('.alert').forEach((alertBox) => {
    setTimeout(() => {
        alertBox.classList.add('soft-hide');
    }, 4500);
});

const money = new Intl.NumberFormat('en-MY', {
    style: 'currency',
    currency: 'MYR',
});

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showApiMessage(message, isError = false) {
    const messageBox = document.getElementById('apiMessage');
    if (!messageBox) {
        return;
    }

    messageBox.textContent = message;
    messageBox.classList.toggle('error', isError);
    messageBox.style.display = message ? 'block' : 'none';
}

function productImage(image) {
    const value = String(image || '').trim();
    return value === '' ? 'assets/images/products/default-product.jpg' : value;
}

function imageFallbackAttribute() {
    return 'onerror="this.onerror=null;const card=this.closest(\'.product-card\');if(card){card.remove();}else{const details=this.closest(\'#productDetails\');if(details){details.innerHTML=\'<div class=&quot;content-panel&quot;><p>This product is currently unavailable because the image is missing.</p><a class=&quot;btn&quot; href=&quot;products.php&quot;>Back to shop</a></div>\';}else{this.style.display=\'none\';}}"';
}

async function fetchJson(url, options = {}) {
    const response = await fetch(url, options);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || data.message || 'API request failed.');
    }

    return data;
}

async function loadCategories() {
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect) {
        return;
    }

    try {
        const categories = await fetchJson('api/categories.php');
        categories.forEach((category) => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            option.dataset.name = category.name;
            categorySelect.appendChild(option);
        });
    } catch (error) {
        showApiMessage(error.message, true);
    }
}

async function loadBrands() {
    const brandSelect = document.getElementById('brandSelect');
    if (!brandSelect) {
        return;
    }

    try {
        const brands = await fetchJson('api/brands.php');
        brands.forEach((brand) => {
            const option = document.createElement('option');
            option.value = brand.id;
            option.textContent = brand.name;
            brandSelect.appendChild(option);
        });
    } catch (error) {
        showApiMessage(error.message, true);
    }
}

function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    if (!grid) {
        return;
    }

    if (products.length === 0) {
        grid.innerHTML = '<p class="empty">No products found.</p>';
        return;
    }

    grid.innerHTML = products.map((product) => `
        <article class="product-card">
            <img src="${escapeHtml(productImage(product.image))}" alt="${escapeHtml(product.name)}" ${imageFallbackAttribute()}>
            <div class="product-body">
                <span class="tag">${escapeHtml(product.category)}</span>
                <span class="type-badge local">${escapeHtml(product.brand)}</span>
                <h3>${escapeHtml(product.name)}</h3>
                <p class="sku-line">SKU: ${escapeHtml(product.sku)} | Stock: ${product.stock}</p>
                <p>${escapeHtml(product.description.substring(0, 90))}...</p>
                <div class="product-meta">
                    <strong>${money.format(product.price)}</strong>
                </div>
                ${productCardActions(product)}
            </div>
        </article>
    `).join('');
    bindPayButtons(grid);
}

function renderProductCardsInto(grid, products) {
    if (!grid) {
        return;
    }

    if (products.length === 0) {
        grid.innerHTML = '<p class="empty">No related products found.</p>';
        return;
    }

    grid.innerHTML = products.map((product) => `
        <article class="product-card">
            <img src="${escapeHtml(productImage(product.image))}" alt="${escapeHtml(product.name)}" ${imageFallbackAttribute()}>
            <div class="product-body">
                <span class="tag">${escapeHtml(product.category)}</span>
                <span class="type-badge local">${escapeHtml(product.brand)}</span>
                <h3>${escapeHtml(product.name)}</h3>
                <p class="sku-line">SKU: ${escapeHtml(product.sku)} | Stock: ${product.stock}</p>
                <div class="product-meta">
                    <strong>${money.format(product.price)}</strong>
                </div>
                ${productCardActions(product)}
            </div>
        </article>
    `).join('');
    bindPayButtons(grid);
}

function getProductFilters() {
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    const brandSelect = document.getElementById('brandSelect');
    const params = new URLSearchParams();
    const search = searchInput ? searchInput.value.trim() : '';

    if (search !== '') {
        params.set('search', search);
    }

    if (categorySelect && categorySelect.value !== '') {
        params.set('category_id', categorySelect.value);
    }

    if (brandSelect && brandSelect.value !== '') {
        params.set('brand_id', brandSelect.value);
    }

    return params;
}

async function loadProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) {
        return;
    }

    const params = getProductFilters();
    showApiMessage('Loading products...');

    try {
        const products = await fetchJson(`api/products.php?${params.toString()}`);
        renderProducts(products);
        showApiMessage('');
    } catch (error) {
        grid.innerHTML = '';
        showApiMessage(`Product loading failed: ${error.message}`, true);
    }
}

function setupProductsPage() {
    const form = document.getElementById('productFilterForm');
    if (!form) {
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const searchInput = document.getElementById('searchInput');
    if (searchInput && urlParams.get('search')) {
        searchInput.value = urlParams.get('search');
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadProducts();
    });

    Promise.all([loadCategories(), loadBrands()]).then(() => {
        const brandSelect = document.getElementById('brandSelect');
        if (brandSelect && urlParams.get('brand_id')) {
            brandSelect.value = urlParams.get('brand_id');
        }
        loadProducts();
    });
}

function getProductIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const id = Number.parseInt(params.get('id'), 10);
    return Number.isInteger(id) && id > 0 ? id : null;
}

function currentUserRole() {
    const page = document.querySelector('[data-product-details-page], [data-products-page]');
    return page ? page.dataset.userRole || 'guest' : 'guest';
}

async function addProductToCart(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    return fetchJson('api/cart.php', {
        method: 'POST',
        body: formData,
    });
}

async function clearCart() {
    const formData = new FormData();
    formData.append('action', 'clear');

    return fetchJson('api/cart.php', {
        method: 'POST',
        body: formData,
    });
}

async function startDirectCheckout(productId, quantity = 1) {
    await clearCart();
    await addProductToCart(productId, quantity);
    window.location.href = 'checkout.php';
}

function loginRedirectUrl() {
    const currentPage = `${window.location.pathname.split('/').pop()}${window.location.search}`;
    return `login.php?redirect=${encodeURIComponent(currentPage)}`;
}

async function payForProduct(productId) {
    const role = currentUserRole();

    if (role === 'guest') {
        window.location.href = 'login.php?redirect=products.php';
        return;
    }

    if (role === 'admin') {
        showApiMessage('Admin cannot place orders.', true);
        return;
    }

    showApiMessage('Preparing checkout...');
    try {
        await startDirectCheckout(productId, 1);
    } catch (error) {
        showApiMessage(error.message, true);
    }
}

function productCardActions(product) {
    const role = currentUserRole();

    if (role === 'admin') {
        return `
            <div class="product-actions single-action">
                <a class="btn btn-outline" href="product-details.php?id=${product.id}">Details</a>
            </div>
        `;
    }

    if (role === 'user') {
        return `
            <div class="product-actions">
                <a class="btn btn-outline" href="product-details.php?id=${product.id}">Add Cart</a>
                <button class="btn product-pay-btn" type="button" data-pay-product="${product.id}">Pay</button>
            </div>
        `;
    }

    return `
        <div class="product-actions">
            <a class="btn btn-outline" href="product-details.php?id=${product.id}">Details</a>
            <a class="btn product-pay-btn" href="login.php?redirect=${encodeURIComponent(`product-details.php?id=${product.id}`)}">Login to Buy</a>
        </div>
    `;
}

function bindPayButtons(root = document) {
    root.querySelectorAll('[data-pay-product]').forEach((button) => {
        button.addEventListener('click', () => {
            const productId = Number.parseInt(button.dataset.payProduct, 10);
            if (Number.isInteger(productId) && productId > 0) {
                payForProduct(productId);
            }
        });
    });
}

function renderProductDetails(product) {
    const details = document.getElementById('productDetails');
    if (!details) {
        return;
    }

    const role = currentUserRole();
    let actionHtml = `
        <div class="cart-form">
            <a class="btn" href="${loginRedirectUrl()}">Add to Cart</a>
            <a class="btn btn-dark" href="${loginRedirectUrl()}">Pay Now</a>
            <p class="form-note">Login as a customer to continue shopping.</p>
        </div>
    `;

    if (role === 'user') {
        actionHtml = `
            <form class="cart-form" id="apiCartForm">
                <input type="number" name="quantity" min="1" max="${product.stock}" value="1" required>
                <button class="btn" type="submit">Add to Cart</button>
                <button class="btn btn-dark" type="submit" name="buy_now" value="1">Pay Now</button>
            </form>
            <div id="cartActionLinks" class="cart-action-links" hidden>
                <a class="btn btn-outline" href="products.php">Continue Shopping</a>
                <a class="btn" href="cart.php">Go to Cart</a>
            </div>
        `;
    }

    if (role === 'admin') {
        actionHtml = `
            <div class="alert error">Admin cannot place orders.</div>
            <div class="dashboard-actions">
                <a class="btn" href="edit-product.php?id=${product.id}">Edit Product</a>
                <a class="btn btn-outline" href="dashboard.php">Dashboard</a>
            </div>
        `;
    }

    details.innerHTML = `
        <img class="details-image" src="${escapeHtml(productImage(product.image))}" alt="${escapeHtml(product.name)}" ${imageFallbackAttribute()}>
        <div class="details-content">
            <span class="tag">${escapeHtml(product.category)}</span>
            <span class="type-badge local">${escapeHtml(product.brand)}</span>
            <h1>${escapeHtml(product.name)}</h1>
            <p class="sku-line">SKU: ${escapeHtml(product.sku)}</p>
            <p>${escapeHtml(product.description)}</p>
            <h2>${money.format(product.price)}</h2>
            <p><strong>Stock:</strong> ${product.stock}</p>
            ${actionHtml}
        </div>
    `;

    const cartForm = document.getElementById('apiCartForm');
    if (!cartForm) {
        return;
    }

    cartForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const quantity = Number.parseInt(event.target.quantity.value, 10);
        const isBuyNow = event.submitter && event.submitter.name === 'buy_now';
        showApiMessage(isBuyNow ? 'Preparing checkout...' : 'Adding product to cart...');

        try {
            if (isBuyNow) {
                await startDirectCheckout(product.id, quantity);
                return;
            }

            const result = await addProductToCart(product.id, quantity);
            showApiMessage(`${result.message} You can continue shopping or go to your cart.`);
            const actionLinks = document.getElementById('cartActionLinks');
            if (actionLinks) {
                actionLinks.hidden = false;
            }
        } catch (error) {
            showApiMessage(error.message, true);
        }
    });
}

async function loadProductDetails() {
    const details = document.getElementById('productDetails');
    if (!details) {
        return;
    }

    const productId = getProductIdFromUrl();
    if (!productId) {
        showApiMessage('A valid product ID is required.', true);
        return;
    }

    showApiMessage('Loading product details...');

    try {
        const product = await fetchJson(`api/product-details.php?id=${productId}`);
        renderProductDetails(product);
        const relatedProducts = await fetchJson(`api/products.php?category_id=${product.category_id}`);
        renderProductCardsInto(
            document.getElementById('relatedProductsGrid'),
            relatedProducts.filter((item) => Number(item.id) !== Number(product.id)).slice(0, 3)
        );
        showApiMessage('');
    } catch (error) {
        details.innerHTML = '';
        showApiMessage(error.message, true);
    }
}

function setupPaymentFields() {
    const cardFields = document.getElementById('cardDemoFields');
    const paymentInputs = document.querySelectorAll('input[name="payment_method"]');

    if (!cardFields || paymentInputs.length === 0) {
        return;
    }

    const updateCardFields = () => {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        const needsCard = selected && ['Visa Card', 'MasterCard'].includes(selected.value);
        cardFields.classList.toggle('active', Boolean(needsCard));
        cardFields.querySelectorAll('input').forEach((input) => {
            input.required = Boolean(needsCard);
            if (!needsCard) {
                input.value = '';
            }
        });
    };

    paymentInputs.forEach((input) => {
        input.addEventListener('change', updateCardFields);
    });
    updateCardFields();
}

function setupRegisterRoleField() {
    const roleSelect = document.getElementById('registerRole');
    const adminCodeGroup = document.getElementById('adminCodeGroup');
    const adminCodeInput = document.getElementById('adminCodeInput');

    if (!roleSelect || !adminCodeGroup || !adminCodeInput) {
        return;
    }

    const updateAdminCode = () => {
        const isAdmin = roleSelect.value === 'admin';
        adminCodeGroup.hidden = !isAdmin;
        adminCodeInput.required = isAdmin;
        if (!isAdmin) {
            adminCodeInput.value = '';
        }
    };

    roleSelect.addEventListener('change', updateAdminCode);
    updateAdminCode();
}

setupProductsPage();
loadProductDetails();
setupPaymentFields();
setupRegisterRoleField();

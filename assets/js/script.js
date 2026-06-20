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
            <img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">
            <div class="product-body">
                <span class="tag">${escapeHtml(product.category)}</span>
                <span class="type-badge local">${escapeHtml(product.brand)}</span>
                <h3>${escapeHtml(product.name)}</h3>
                <p class="sku-line">SKU: ${escapeHtml(product.sku)} | Stock: ${product.stock}</p>
                <p>${escapeHtml(product.description.substring(0, 90))}...</p>
                <div class="product-meta">
                    <strong>${money.format(product.price)}</strong>
                    <a href="product-details.php?id=${product.id}">Details</a>
                </div>
            </div>
        </article>
    `).join('');
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
            <img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">
            <div class="product-body">
                <span class="tag">${escapeHtml(product.category)}</span>
                <span class="type-badge local">${escapeHtml(product.brand)}</span>
                <h3>${escapeHtml(product.name)}</h3>
                <p class="sku-line">SKU: ${escapeHtml(product.sku)} | Stock: ${product.stock}</p>
                <div class="product-meta">
                    <strong>${money.format(product.price)}</strong>
                    <a href="product-details.php?id=${product.id}">Details</a>
                </div>
            </div>
        </article>
    `).join('');
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

function renderProductDetails(product) {
    const details = document.getElementById('productDetails');
    if (!details) {
        return;
    }

    details.innerHTML = `
        <img class="details-image" src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">
        <div class="details-content">
            <span class="tag">${escapeHtml(product.category)}</span>
            <span class="type-badge local">${escapeHtml(product.brand)}</span>
            <h1>${escapeHtml(product.name)}</h1>
            <p class="sku-line">SKU: ${escapeHtml(product.sku)}</p>
            <p>${escapeHtml(product.description)}</p>
            <h2>${money.format(product.price)}</h2>
            <p><strong>Stock:</strong> ${product.stock}</p>
            <form class="cart-form" id="apiCartForm">
                <input type="number" name="quantity" min="1" max="${product.stock}" value="1" required>
                <button class="btn" type="submit">Add to Cart</button>
            </form>
        </div>
    `;

    const cartForm = document.getElementById('apiCartForm');
    if (!cartForm) {
        return;
    }

    cartForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const quantity = Number.parseInt(event.target.quantity.value, 10);
        showApiMessage('Adding product to cart...');

        try {
            const result = await addProductToCart(product.id, quantity);
            showApiMessage(result.message);
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

setupProductsPage();
loadProductDetails();

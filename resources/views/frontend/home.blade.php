<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Grocery App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header Styling */
        .header-title {
            color: #1b5e20;
            font-weight: 700;
        }

        .btn-cart {
            background-color: #2e7d32;
            color: white;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-cart:hover {
            background-color: #1b5e20;
            transform: scale(1.05);
        }

        .cart-badge {
            background-color: white;
            color: #2e7d32;
            border-radius: 50%;
            padding: 2px 8px;
            margin-left: 5px;
        }

        /* Search Bar */
        .search-container input {
            border-radius: 20px;
            padding-left: 40px;
            border: 1px solid #ced4da;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .search-container input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }

        .search-icon {
            position: absolute;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
        }

        /* Categories Scrollable Container */
        .category-container {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding-bottom: 10px;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .category-container::-webkit-scrollbar {
            display: none;
        }

        .category-btn {
            white-space: nowrap;
            border-radius: 20px;
            border: 1px solid #ced4da;
            background-color: white;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .category-btn.active, .category-btn:hover {
            background-color: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }

        /* Product Cards */
        .product-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background-color: white;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .product-img {
            height: 160px;
            object-fit: cover;
            padding: 15px;
            border-radius: 25px;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .price-text {
            color: #2e7d32;
            font-weight: 600;
        }

        .btn-add {
            background-color: #2e7d32;
            color: white;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            padding: 8px 0;
        }

        .btn-add:hover {
            background-color: #1b5e20;
        }

        .btn-add:active {
            transform: scale(0.95);
        }

        .no-results {
            text-align: center;
            color: #6c757d;
            padding: 40px 0;
            font-size: 1.1rem;
            width: 100%;
        }

        /* Grocery View Custom Inputs */
        .grocery-input {
            border: 1.5px solid #ced4da;
            border-radius: 10px;
            background-color: white;
            color: #495057;
        }
        .grocery-input:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.1rem rgba(46, 125, 50, 0.25);
            outline: none;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="header-title m-0">Order Groceries</h2>
        <button class="btn-cart d-flex align-items-center px-3 py-2">
            <i class="fa-solid fa-basket-shopping me-2"></i> Cart 
            <span class="cart-badge" id="cartCount">0</span>
        </button>
    </div>

    <div class="position-relative mb-4 search-container" id="searchBoxContainer">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="searchInput" class="form-control py-2" placeholder="Search products...">
    </div>

    <div class="category-container mb-4" id="categoryTabs">
        <button class="btn category-btn active px-4 py-2" data-tab="vegetable">
            <i class="fa-solid fa-leaf me-2 text-success" style="color: inherit !important;"></i>Vegetables
        </button>
        <button class="btn category-btn px-4 py-2" data-tab="precut">
            <i class="fa-solid fa-utensils me-2 text-primary" style="color: inherit !important;"></i>Precut
        </button>
        <button class="btn category-btn px-4 py-2" data-tab="frozen">
            <i class="fa-solid fa-snowflake me-2 text-info" style="color: inherit !important;"></i>Frozen
        </button>
        <button class="btn category-btn px-4 py-2" data-tab="grocery">
            <i class="fa-solid fa-cart-shopping me-2 text-success" style="color: inherit !important;"></i>Grocery
        </button>
    </div>

    <div class="row g-4" id="productsGrid">
        </div>

    <div id="groceryView" style="display: none;" class="flex-column align-items-center mt-3 px-2 w-100">
        
        <button id="addListBtn" class="btn btn-add px-4 py-3 mb-4 w-auto" style="border-radius: 12px; font-size: 1.05rem;">
            <i class="fa-solid fa-plus me-2"></i> Add Product List for Order
        </button>

        <div id="groceryInputCard" class="card shadow-sm p-3 mb-4 w-100 mx-auto" style="display: none; border-radius: 15px; border: 1px solid #ebebeb; max-width: 450px;">
            <label class="form-label fw-bold mb-2" style="color: #333; font-size: 0.95rem;">Add Item to Your List</label>
            <div class="d-flex gap-2 mb-3">
                <input type="text" class="form-control grocery-input py-2" placeholder="Product name" style="flex: 1;">
                <input type="text" class="form-control grocery-input py-2" placeholder="Qty" style="width: 75px; text-align: center;">
            </div>
            <button class="btn btn-add py-2" style="border-radius: 10px;">+ Add Item</button>
        </div>

        <div class="text-muted mt-2 text-center" style="font-size: 0.95rem;">
            Abhi koi item nahi — upar se add karein
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Data List
    const TABS = {
        vegetable: [
            { n: "Adrak", pr: "100/Pao", img: "https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=300&q=80" },
            { n: "Arvi", pr: "80/Pao", img: "https://images.unsplash.com/photo-1568702846914-96b305d2aaeb?w=300&q=80" },
            { n: "Band gobhi", pr: "120/kg", img: "https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=300&q=80" },
            { n: "Bhindi", pr: "150/kg", img: "https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?w=300&q=80" },
            { n: "Kheera", pr: "140/kg", img: "https://images.unsplash.com/photo-1568584711075-3d021a7c3ca3?w=300&q=80" },
            { n: "Gajar", pr: "240/kg", img: "https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=300&q=80" },
            { n: "Palak", pr: "120/kg", img: "https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=300&q=80" },
            { n: "Phool gobhi", pr: "180/kg", img: "https://images.unsplash.com/photo-1568584711075-3d021a7c3ca3?w=300&q=80" }
        ],
        precut: [
            { n: "Precut Band gobhi", pr: "50/pack", img: "https://images.unsplash.com/photo-1594282486552-05b4d80fbb9f?w=300&q=80" },
            { n: "Precut Gajar", pr: "60/pack", img: "https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=300&q=80" },
            { n: "Precut Palak", pr: "80/pack", img: "https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=300&q=80" },
            { n: "Precut Pyaz", pr: "50/pack", img: "https://images.unsplash.com/photo-1605128800972-854bd69e0c2c?w=300&q=80" },
            { n: "Precut Tamatar", pr: "60/pack", img: "https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=300&q=80" }
        ],
        frozen: [
            { n: "Frozen Matar", pr: "120/pack", img: "https://images.unsplash.com/photo-1586201375761-83865001e31c?w=300&q=80" },
            { n: "Frozen Bhutta", pr: "150/pack", img: "https://images.unsplash.com/photo-1551754655-cd27e38d2076?w=300&q=80" },
            { n: "Frozen Mix Sabzi", pr: "180/pack", img: "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300&q=80" },
            { n: "Frozen Samosa", pr: "200/pack", img: "https://images.unsplash.com/photo-1601050690597-df0568f70950?w=300&q=80" }
        ]
    };

    let activeTab = 'vegetable';
    let cartItemCount = 0;

    // Grid ko Render karne ka function
    function renderGrid(tab, query = '') {
        const grid = document.getElementById('productsGrid');
        if (!TABS[tab]) return;
        
        const list = TABS[tab];
        const filteredList = list.filter(p => p.n.toLowerCase().includes(query.toLowerCase()));

        if (filteredList.length === 0) {
            grid.innerHTML = '<div class="no-results"><i class="fa-regular fa-face-frown mb-2" style="font-size:2rem;"></i><br>Koi product nahi mila</div>';
            return;
        }

        grid.innerHTML = filteredList.map(p => `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card shadow-sm p-2">
                    <img src="${p.img}" class="card-img-top product-img" alt="${p.n}">
                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title fs-6 fw-bold mb-1 text-truncate">${p.n}</h5>
                            <p class="card-text mb-3 text-muted" style="font-size: 0.9rem;">Rs. <span class="price-text">${p.pr}</span></p>
                        </div>
                        <button class="btn-add" onclick="addToCart(this)">+ Add to Cart</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // View Switching Logic (Main Fix)
    function switchTab(tabName) {
        activeTab = tabName;
        document.getElementById('searchInput').value = ''; // Search bar clear karein
        
        const gridView = document.getElementById('productsGrid');
        const groceryView = document.getElementById('groceryView');
        const searchBox = document.getElementById('searchBoxContainer');

        if (tabName === 'grocery') {
            // Sirf Grocery view dikhaye
            gridView.style.display = 'none';
            searchBox.style.display = 'none';
            groceryView.style.display = 'flex';
        } else {
            // Baki tabs ke liye Grid view dikhaye
            groceryView.style.display = 'none';
            searchBox.style.display = 'block';
            gridView.style.display = 'flex'; // Restore row flex property
            renderGrid(tabName);
        }
    }

    // Category Button Click Events
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });

    // Grocery "Add List" Button Toggle Logic
    document.getElementById('addListBtn').addEventListener('click', function() {
        const formCard = document.getElementById('groceryInputCard');
        if (formCard.style.display === 'none') {
            formCard.style.display = 'block';
        } else {
            formCard.style.display = 'none';
        }
    });

    // Search bar logic
    document.getElementById('searchInput').addEventListener('input', function() {
        if(activeTab !== 'grocery') {
            renderGrid(activeTab, this.value.trim());
        }
    });

    // Add to Cart Animation Logic
    function addToCart(button) {
        cartItemCount++;
        document.getElementById('cartCount').innerText = cartItemCount;

        const originalText = button.innerText;
        button.innerHTML = '<i class="fa-solid fa-check"></i> Added';
        button.style.backgroundColor = '#198754';
        
        setTimeout(() => {
            button.innerText = originalText;
            button.style.backgroundColor = ''; 
        }, 1200);

        const cartBtn = document.querySelector('.btn-cart');
        cartBtn.style.transform = 'scale(1.15)';
        setTimeout(() => {
            cartBtn.style.transform = 'scale(1)';
        }, 200);
    }

    // Initial load pe sabse pehle Vegetables tab trigger karein
    switchTab('vegetable');

</script>

</body>
</html>
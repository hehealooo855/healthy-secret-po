/* ===========================================================
   SCRIPT.JS - LOGIKA UTAMA (INTEGRASI MENU & CONFIG)
   =========================================================== */

// [1] DEFAULT CONFIG (Data Cadangan jika Google Sheet Gagal/Loading)
// Data ini akan tertimpa otomatis jika koneksi ke Sheet berhasil
let PO_CONFIG = {
    closeDate: "2025-12-30T23:59:00", 
    deliveryText: "Menunggu Update Admin...",
    waAdmin: "62818895488" 
};

// Data Ongkir
const SHIPPING_ZONES = [
    { name: "-- Pilih Metode Kirim --", price: 0 },
    { name: "Ambil Sendiri (Pickup)", price: 0 },
    { name: "Gojek/Grab (Ongkir Bayar di Tempat)", price: 0 }, 
    { name: "Luar Kota (JNE - Cek Admin)", price: 0 }
];

// DATA MENU CADANGAN (Fallback)
let DATABASE_MENU = [
    { id: 1, name: "Nasi Soto", price: 30000, img: "Soto.jpeg", desc: "Ayam suwir vegan, kuah kuning rempah.", stok: 50, category: "berat" },
    { id: 2, name: "Nasi Liwet", price: 30000, img: "Liwet.jpeg", desc: "Nasi gurih santan, tempe orek, sambal.", stok: 20, category: "berat" },
    { id: 5, name: "Onigiri", price: 18000, img: "Onigiri.jpeg", desc: "Nasi kepal ala Jepang isi tuna vegan.", stok: 30, category: "snack" }
];

// ==========================================
// 🔧 FUNGSI INTEGRASI GOOGLE SHEET (DUAL FETCH)
// ==========================================
async function initSystem() {
   // SAYA SUDAH MASUKKAN API DARI SCREENSHOT ANDA:
   const API_ENDPOINT = "https://sheetdb.io/api/v1/kezuoz75thktm"; 
   
   if(!API_ENDPOINT) {
       console.log("API Kosong, menggunakan data lokal.");
       runApp(); 
       return;
   }

   try {
     console.log("Sedang mengambil data dari Google Sheet...");
     
     // Ambil Data Menu & Data Config secara bersamaan
     const [menuRes, configRes] = await Promise.all([
        fetch(`${API_ENDPOINT}?sheet=Menu`),   // Ambil Tab Menu
        fetch(`${API_ENDPOINT}?sheet=Config`)  // Ambil Tab Config
     ]);

     const menuData = await menuRes.json();
     const configData = await configRes.json();

     // 1. Update MENU dari Excel
     if(menuData.length > 0) {
        DATABASE_MENU = menuData.map(item => ({
            id: item.id,
            name: item.name,
            price: parseInt(item.price),
            img: item.img,
            desc: item.desc,
            stok: parseInt(item.stok),
            category: item.category
        }));
     }

     // 2. Update CONFIG (Timer & WA) dari Excel
     if(configData.length > 0) {
        // Ubah format data dari Array ke Object
        const configMap = {};
        configData.forEach(row => { configMap[row.key] = row.value; });

        // Update Config Website
        // Saya tambahkan .replace agar format tanggal dari Excel aman dibaca browser
        if(configMap.closeDate) PO_CONFIG.closeDate = configMap.closeDate.replace(" ", "T"); 
        if(configMap.deliveryText) PO_CONFIG.deliveryText = configMap.deliveryText;
        if(configMap.waAdmin) PO_CONFIG.waAdmin = configMap.waAdmin;
        
        console.log("Config berhasil diupdate:", PO_CONFIG);
     }

   } catch (error) {
     console.error("Gagal koneksi ke SheetDB:", error);
   }

   runApp(); // Jalankan Aplikasi setelah data siap
}

// FUNGSI MENJALANKAN UI (SETELAH DATA SIAP)
function runApp() {
    renderHeroSlides();
    renderGallery();
    renderMainMenu(DATABASE_MENU); 
    renderShippingOptions();
    updateDeliveryInfo(); // Update Teks Pengiriman Baru
    startCountdown();     // Mulai Timer dengan Tanggal Baru
    updateCartUI();
    
    // Sembunyikan Preloader
    setTimeout(() => { document.getElementById('preloader').style.display = 'none'; }, 500);
}

// ==========================================
// 🎮 LOGIKA UI (SEARCH, CART, DLL)
// ==========================================

let cart = JSON.parse(localStorage.getItem('myCart')) || [];
let currentCategory = 'all';

window.addEventListener("load", function() {
  document.getElementById('preloader').style.opacity = '1'; // Show loading awal
  AOS.init({ duration: 1000, once: true, offset: 100 });
  
  // Panggil Fungsi Utama
  initSystem();
  
  // Setup Tombol Scroll Gallery
  const container = document.getElementById('menuScroll');
  const btnL = document.getElementById('btnLeft');
  const btnR = document.getElementById('btnRight');
  if (container) {
      const getScrollAmount = () => Math.round(container.clientWidth * 0.8);
      btnL.addEventListener('click', () => container.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' }));
      btnR.addEventListener('click', () => container.scrollBy({ left: getScrollAmount(), behavior: 'smooth' }));
  }
});

function updateDeliveryInfo() {
  // Update Teks Tanggal Pengiriman di Banner
  const elBanner = document.getElementById('deliveryDate');
  if(elBanner) elBanner.innerText = PO_CONFIG.deliveryText;
}

function setCategory(cat) {
    currentCategory = cat;
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`btn-${cat}`).classList.add('active');
    filterMenu();
}

function filterMenu() {
    const keyword = document.getElementById('searchInput').value.toLowerCase();
    const filtered = DATABASE_MENU.filter(item => {
        const matchSearch = item.name.toLowerCase().includes(keyword);
        const matchCat = currentCategory === 'all' || item.category === currentCategory;
        return matchSearch && matchCat;
    });
    renderMainMenu(filtered);
}

function renderMainMenu(data) {
    const container = document.getElementById('mainMenuGrid');
    container.innerHTML = '';
    
    if(data.length === 0) {
        container.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#888;">Menu tidak ditemukan :(</div>';
        return;
    }

    const now = new Date().getTime();
    const deadline = new Date(PO_CONFIG.closeDate).getTime();
    const isTimeOver = now >= deadline;

    data.forEach((item, idx) => {
        let isSoldOut = item.stok <= 0;
        let isClosed = isTimeOver;
        let badgeClass = "badge-open"; let badgeText = "OPEN PO"; let btnText = "+ Pre-Order"; let cardClass = ""; let isDisabled = false;

        if(isClosed) { badgeClass = "badge-closed"; badgeText = "CLOSED"; btnText = "PO Ditutup"; cardClass = "closed"; isDisabled = true; } 
        else if (isSoldOut) { badgeClass = "badge-warning"; badgeText = "SOLD OUT"; btnText = "Habis"; cardClass = "closed"; isDisabled = true; }

        let fallback = `https://placehold.co/400x300?text=${encodeURIComponent(item.name)}`;
        
        let html = `
          <div class="menu-card ${cardClass}" id="target-${item.id}" data-aos="fade-up">
            <span class="status-badge ${badgeClass}">${badgeText}</span>
            <img src="${item.img}" onerror="this.src='${fallback}'" alt="${item.name}" loading="lazy">
            <div class="card-body">
              <h3>${item.name}</h3> <span class="price">Rp ${item.price.toLocaleString('id-ID')}</span>
              <button class="btn-overview overview-btn" onclick="showPopup('${item.desc}')">Overview</button>
              <div class="action-row">
                <input type="number" id="qty-${item.id}" class="qty-input" value="1" min="1" ${isDisabled ? 'disabled' : ''}>
                <button class="btn-pesan" onclick="addToCart(${item.id})" ${isDisabled ? 'disabled' : ''}>${btnText}</button>
              </div>
            </div>
          </div>`;
        container.innerHTML += html;
    });
}

function renderHeroSlides() {
    const container = document.getElementById('heroSlides');
    container.innerHTML = '';
    DATABASE_MENU.forEach((item, idx) => {
        let img = document.createElement('img');
        img.src = item.img; img.className = 'slide-img'; img.onerror = function() { this.style.display='none'; };
        if(idx === 0) img.style.opacity = 1;
        container.appendChild(img);
    });
    let heroIndex = 0; const heroImages = document.querySelectorAll('.slide-img');
    if(heroImages.length > 0) { setInterval(() => { heroImages.forEach(img => img.style.opacity = 0); heroIndex = (heroIndex + 1) % heroImages.length; heroImages[heroIndex].style.opacity = 1; }, 4000); }
}

function scrollToMenu(elementId) {
    if (currentCategory !== 'all') { setCategory('all'); document.getElementById('searchInput').value = ''; }
    setTimeout(() => {
        const element = document.getElementById(elementId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            element.style.transition = "transform 0.3s, box-shadow 0.3s";
            element.style.transform = "scale(1.05)"; element.style.boxShadow = "0 0 15px rgba(10, 116, 218, 0.5)";
            setTimeout(() => { element.style.transform = "scale(1)"; element.style.boxShadow = "0 10px 20px rgba(0,0,0,0.05)"; }, 1000);
        }
    }, 100);
}

function renderGallery() {
    const container = document.getElementById('menuScroll'); container.innerHTML = '';
    DATABASE_MENU.forEach(item => {
        let fallback = `https://placehold.co/400x300?text=${encodeURIComponent(item.name)}`;
        let html = `
            <div class="slide">
              <img src="${item.img}" onerror="this.src='${fallback}'" alt="${item.name}" loading="lazy">
              <h3>${item.name}</h3> <p>Rp ${item.price.toLocaleString('id-ID')}</p>
              <button class="card-btn" onclick="scrollToMenu('target-${item.id}')">Lihat Detail ➜</button>
            </div>`;
        container.innerHTML += html;
    });
}

function renderShippingOptions() {
    const select = document.getElementById('shipping-zone');
    SHIPPING_ZONES.forEach((zone, idx) => {
        let opt = document.createElement('option'); opt.value = idx; opt.text = zone.name; select.add(opt);
    });
}

function addToCart(id) {
    let product = DATABASE_MENU.find(p => p.id === id);
    let qtyInput = document.getElementById(`qty-${id}`);
    let qty = parseInt(qtyInput.value);

    if(isNaN(qty) || qty < 1) return showToast("⚠️ Minimal pesan 1");
    let itemInCart = cart.find(i => i.id === id);
    let currentQty = itemInCart ? itemInCart.qty : 0;
    
    if (currentQty + qty > product.stok) { return showToast(`⚠️ Stok tidak cukup! Sisa: ${product.stok}`); }

    if (itemInCart) { itemInCart.qty += qty; } else { cart.push({ id: product.id, name: product.name, price: product.price, qty: qty }); }
    saveCart(); updateCartUI(); showToast(`✅ ${product.name} masuk keranjang!`); qtyInput.value = 1;
}

function updateCartUI() {
    let totalItems = cart.reduce((acc, item) => acc + item.qty, 0);
    document.getElementById('cart-count').innerText = totalItems;
    let listContainer = document.getElementById('cartList'); let subtotal = 0;
    
    if(cart.length === 0) { listContainer.innerHTML = '<p style="text-align:center; color:#888; margin-top:20px;">Keranjang PO kosong.</p>'; } else {
        listContainer.innerHTML = '';
        cart.forEach((item, index) => {
            let itemTotal = item.price * item.qty; subtotal += itemTotal;
            listContainer.innerHTML += `
                <div style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding:10px 0;">
                  <div><div style="font-weight:600;">${item.name}</div><div style="font-size:13px; color:#555;">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</div></div>
                  <div style="display:flex; align-items:center; gap:10px;"><span style="font-weight:bold;">Rp ${itemTotal.toLocaleString('id-ID')}</span><span onclick="hapusItem(${index})" style="color:red; cursor:pointer; font-weight:bold; font-size:18px;">×</span></div>
                </div>`;
        });
    }
    let zoneIdx = document.getElementById('shipping-zone').value;
    let shippingInfo = SHIPPING_ZONES[zoneIdx] || SHIPPING_ZONES[0];
    let shippingCost = shippingInfo.price;
    let grandTotal = subtotal + shippingCost;
    let shippingDisplay = (shippingInfo.name.includes("Bayar di Tempat")) ? "Bayar di Tempat" : "Rp " + shippingCost.toLocaleString('id-ID');

    document.getElementById('cartSubtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('cartShipping').innerText = shippingDisplay;
    document.getElementById('cartTotal').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
}

function hapusItem(index) { cart.splice(index, 1); saveCart(); updateCartUI(); }

function checkoutWA() {
    if(cart.length === 0) return showToast("⚠️ Keranjang kosong!");
    let name = document.getElementById('cust-name').value;
    let address = document.getElementById('cust-address').value;
    let zoneIdx = document.getElementById('shipping-zone').value;

    name = name.replace(/[^\w\s]/gi, ''); 

    if(!name || !address) return showToast("⚠️ Mohon isi Nama & Alamat");
    if(zoneIdx == 0) return showToast("⚠️ Mohon pilih Metode Kirim");

    let shippingInfo = SHIPPING_ZONES[zoneIdx];
    let subtotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    
    let msg = `Halo Admin Healthy Secret, mau order PO:%0A%0A`;
    msg += `👤 Nama: ${encodeURIComponent(name)}%0A`;
    msg += `📍 Alamat: ${encodeURIComponent(address)}%0A`;
    msg += `🚚 Pengiriman: ${shippingInfo.name}%0A%0A`;
    msg += `*LIST PESANAN:*%0A`;
    
    cart.forEach(item => { msg += `- ${item.name} (${item.qty}x) : Rp ${(item.price * item.qty).toLocaleString('id-ID')}%0A`; });
    
    msg += `--------------------------------%0A`;
    msg += `Subtotal Menu: Rp ${subtotal.toLocaleString('id-ID')}%0A`;

    if(shippingInfo.name.includes("Bayar di Tempat")) {
        msg += `Ongkir: *Bayar ke Kurir/Driver*%0A`; 
        msg += `*TOTAL TRANSFER: Rp ${subtotal.toLocaleString('id-ID')}* (Hanya Harga Menu)%0A%0A`;
    } else {
        let total = subtotal + shippingInfo.price;
        msg += `Ongkir: Rp ${shippingInfo.price.toLocaleString('id-ID')}%0A`;
        msg += `*TOTAL TRANSFER: Rp ${total.toLocaleString('id-ID')}*%0A%0A`;
    }
    msg += `_Note: Stok akan divalidasi ulang oleh Admin._%0A`;
    msg += `Mohon info rekening ya. Terima kasih!`;
    
    // GUNAKAN NOMOR WA DINAMIS DARI GOOGLE SHEET
    window.open(`https://wa.me/${PO_CONFIG.waAdmin}?text=${msg}`);

    setTimeout(() => {
        cart = []; saveCart(); updateCartUI(); toggleCart();
        document.getElementById('cust-name').value = '';
        document.getElementById('cust-address').value = '';
        showToast("✅ Pesanan dialihkan ke WhatsApp!");
    }, 1500);
}

function saveCart() { localStorage.setItem('myCart', JSON.stringify(cart)); }
function toggleCart() { let modal = document.getElementById('cartModal'); modal.style.display = (modal.style.display === 'block') ? 'none' : 'block'; }
function showPopup(desc) { document.getElementById('popupContent').innerText = desc; document.getElementById('popup').style.display = 'block'; }
function closePopup() { document.getElementById('popup').style.display = 'none'; }
function showToast(msg) { const c = document.getElementById('toast-container'); const el = document.createElement('div'); el.className = 'toast-msg'; el.innerHTML = `<span>🔔</span> ${msg}`; c.appendChild(el); setTimeout(() => el.remove(), 3000); }

function startCountdown() {
  const deadline = new Date(PO_CONFIG.closeDate).getTime();
  const timer = setInterval(() => {
    const now = new Date().getTime(); const diff = deadline - now;
    if (diff <= 0) { 
        clearInterval(timer); 
        document.getElementById('countdown').innerHTML = "<span style='color:#ff6b6b; font-weight:bold;'>PO DITUTUP</span>"; 
        renderMainMenu(DATABASE_MENU); 
        return; 
    }
    document.getElementById('days').innerText = Math.floor(diff / (864e5));
    document.getElementById('hours').innerText = Math.floor((diff % 864e5) / 36e5).toString().padStart(2,'0');
    document.getElementById('minutes').innerText = Math.floor((diff % 36e5) / 6e4).toString().padStart(2,'0');
    document.getElementById('seconds').innerText = Math.floor((diff % 6e4) / 1e3).toString().padStart(2,'0');
  }, 1000);
}

window.addEventListener('scroll', function() { const navbar = document.querySelector('.navbar'); if (window.scrollY > 50) navbar.classList.add('scrolled'); else navbar.classList.remove('scrolled'); });
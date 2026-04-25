<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKU Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { width: 250px; height: 100vh; background: #1e293b; position: fixed; padding: 25px; border-right: 1px solid #334155; }
        .main { margin-left: 250px; padding: 40px; }
        .glass-card { background: #1e293b; border: 1px solid #334155; border-radius: 15px; padding: 25px; overflow-x: auto; }
        .table { color: #cbd5e1; vertical-align: middle; }
        .table th { border-bottom: 2px solid #334155; color: #94a3b8; }
        .table td { border-bottom: 1px solid #334155; }
        .low-stock { background: rgba(239, 68, 68, 0.1); color: #f87171 !important; font-weight: bold; }
        .btn-cyber { background: #3b82f6; border: none; font-weight: 600; color: white; padding: 8px 20px; border-radius: 8px; transition: 0.3s; }
        .btn-cyber:hover { background: #2563eb; }
        
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; margin: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ef4444; transition: .4s; border-radius: 34px; box-shadow: inset 0 0 5px rgba(0,0,0,0.2); }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        input:checked + .slider { background-color: #10b981; }
        input:checked + .slider:before { transform: translateX(24px); }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="fw-bold text-primary mb-5">Healthy Admin</h4>
    <nav class="nav flex-column gap-3">
        <a class="nav-link text-white bg-primary rounded px-3 py-2 fw-bold" href="#" id="tab-sku" onclick="switchTab('sku')">🍔 Manajemen Menu</a>
        <a class="nav-link text-secondary px-3 py-2 fw-bold" href="#" id="tab-orders" onclick="switchTab('orders')">📦 Riwayat Pesanan</a>
        
        <a class="nav-link text-secondary px-3 py-2 mt-3" href="main.php" target="_blank">🌐 Lihat Web Pembeli</a>
        <button class="btn btn-link text-danger text-start px-3 mt-4 text-decoration-none fw-bold" onclick="logout()">🚪 Logout</button>
    </nav>
</div>

<div class="main">
    <div id="section-sku">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0">SKU Inventory Control</h2>
                <p class="text-secondary small">Kelola harga, stok, dan status menu secara real-time.</p>
            </div>
            <button class="btn btn-cyber" onclick="addMenu()">+ Tambah SKU Baru</button>
        </div>

        <div class="glass-card mb-4 d-flex justify-content-between align-items-center" style="border-color: #d4af37;">
            <div>
                <h4 class="fw-bold m-0 text-warning">Status Batch Pre-Order</h4>
                <p class="small text-secondary m-0">Aktifkan sakelar ini untuk menentukan tanggal tutup PO dan membuka pesanan.</p>
            </div>
            <div>
                <label class="switch">
                    <input type="checkbox" id="batchToggle" onchange="toggleBatchStatus()">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="glass-card">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Item & Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Tersedia (Status Menu)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="menuList"></tbody>
            </table>
        </div>
    </div>

    <div id="section-orders" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0 text-warning">Riwayat Pesanan 📦</h2>
                <p class="text-secondary small">Daftar pesanan masuk dari pembeli Anda.</p>
            </div>
            <button class="btn btn-outline-danger btn-sm fw-bold" onclick="clearAllOrders()">🗑️ Bersihkan Semua Riwayat</button>
        </div>

        <div class="glass-card">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID Pesanan</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Total Tagihan</th>
                        <th>Status Pesanan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="orderList"></tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // UPDATE LINK KE PHP
    if(localStorage.getItem('HS_AUTH') !== 'true') window.location.href = 'index.php';

    function logout() {
        localStorage.removeItem('HS_AUTH');
        window.location.href = 'index.php'; // UPDATE LINK KE PHP
    }

    function switchTab(tabName) {
        if(tabName === 'sku') {
            document.getElementById('section-sku').style.display = 'block';
            document.getElementById('section-orders').style.display = 'none';
            document.getElementById('tab-sku').className = 'nav-link text-white bg-primary rounded px-3 py-2 fw-bold';
            document.getElementById('tab-orders').className = 'nav-link text-secondary px-3 py-2 fw-bold';
            render();
        } else {
            document.getElementById('section-sku').style.display = 'none';
            document.getElementById('section-orders').style.display = 'block';
            document.getElementById('tab-sku').className = 'nav-link text-secondary px-3 py-2 fw-bold';
            document.getElementById('tab-orders').className = 'nav-link text-white bg-primary rounded px-3 py-2 fw-bold';
            renderOrders();
        }
    }

    function renderOrders() {
        let orders = JSON.parse(localStorage.getItem('HS_ORDERS_DB')) || [];
        const tbody = document.getElementById('orderList');
        
        if(orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Belum ada pesanan masuk.</td></tr>';
            return;
        }

        orders.sort((a,b) => b.id.localeCompare(a.id));

        tbody.innerHTML = orders.map((o, i) => {
            const colorMap = {
                'Menunggu Pembayaran': 'text-warning',
                'Diproses': 'text-primary',
                'Selesai': 'text-success',
                'Dibatalkan': 'text-danger'
            };
            const colorClass = colorMap[o.status] || 'text-white';

            return `
                <tr>
                    <td class="fw-bold" style="font-family: monospace; font-size:12px;">${o.id}</td>
                    <td class="small text-secondary">${o.date}</td>
                    <td>
                        <b class="text-white">${o.customer}</b><br>
                        <small class="text-secondary">${o.items.length} jenis item dipesan</small>
                    </td>
                    <td class="fw-bold text-success">Rp ${parseInt(o.total).toLocaleString('id-ID')}</td>
                    <td>
                        <select class="form-select form-select-sm fw-bold ${colorClass} bg-dark border-secondary" onchange="updateOrderStatus('${o.id}', this.value)" style="width: 190px;">
                            <option value="Menunggu Pembayaran" ${o.status === 'Menunggu Pembayaran' ? 'selected' : ''}>⏳ Menunggu Pembayaran</option>
                            <option value="Diproses" ${o.status === 'Diproses' ? 'selected' : ''}>🍳 Sedang Diproses</option>
                            <option value="Selesai" ${o.status === 'Selesai' ? 'selected' : ''}>✅ Selesai</option>
                            <option value="Dibatalkan" ${o.status === 'Dibatalkan' ? 'selected' : ''}>❌ Dibatalkan</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info fw-bold py-1 px-2" onclick="viewOrder('${o.id}')">👁️ Detail</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.updateOrderStatus = (id, newStatus) => {
        let orders = JSON.parse(localStorage.getItem('HS_ORDERS_DB')) || [];
        const orderIndex = orders.findIndex(o => o.id === id);
        if (orderIndex > -1) {
            orders[orderIndex].status = newStatus;
            localStorage.setItem('HS_ORDERS_DB', JSON.stringify(orders));
            renderOrders(); 
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Status Diperbarui', showConfirmButton: false, timer: 1500 });
        }
    }

    window.viewOrder = (id) => {
        let orders = JSON.parse(localStorage.getItem('HS_ORDERS_DB')) || [];
        const o = orders.find(x => x.id === id);
        if(!o) return;

        let itemsHtml = o.items.map(item => `<li><b>${item.qty}x</b> ${item.name} <span class="float-end">Rp ${item.subtotal.toLocaleString('id-ID')}</span></li>`).join('');

        Swal.fire({
            title: `Detail Pesanan`,
            background: '#1e293b', color: '#fff',
            html: `
                <div class="text-start small">
                    <p class="mb-1 text-secondary">ID: <b class="text-white">${o.id}</b></p>
                    <p class="mb-1 text-secondary">Nama: <b class="text-white">${o.customer}</b></p>
                    <p class="mb-3 text-secondary">Alamat/Catatan:<br><span class="text-white">${o.address}</span></p>
                    
                    <div class="p-3 bg-dark rounded border border-secondary mb-3">
                        <ul class="list-unstyled m-0" style="font-size:13px;">${itemsHtml}</ul>
                    </div>
                    
                    <h5 class="fw-bold text-warning text-end m-0">Total: Rp ${parseInt(o.total).toLocaleString('id-ID')}</h5>
                </div>
            `,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Tutup'
        });
    }

    window.clearAllOrders = () => {
        Swal.fire({ title: 'Hapus Semua Riwayat?', text: 'Semua data pesanan pembeli akan hilang!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Bersihkan!', background: '#1e293b', color: '#fff' }).then((r) => {
            if (r.isConfirmed) { 
                localStorage.removeItem('HS_ORDERS_DB');
                renderOrders();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Riwayat Pesanan Dibersihkan.', showConfirmButton: false, timer: 1500 });
            }
        });
    }

    let batchStatus = localStorage.getItem('HS_BATCH_STATUS') || 'CLOSED';
    document.getElementById('batchToggle').checked = (batchStatus === 'OPEN');

    async function toggleBatchStatus() {
        const toggle = document.getElementById('batchToggle');
        const isChecked = toggle.checked;

        if (isChecked) {
            const { value: formValues } = await Swal.fire({
                title: 'Buka Batch PO Baru',
                background: '#1e293b', color: '#fff',
                html: `
                    <div class="text-start mb-2">
                        <label class="small text-secondary">Kapan PO ini akan ditutup secara otomatis?</label>
                        <input id="swal-close-date" type="datetime-local" class="swal2-input m-0 w-100">
                    </div>
                    <div class="text-start mt-3">
                        <label class="small text-secondary">Teks Estimasi Pengiriman (Akan tampil di Web)</label>
                        <input id="swal-delivery-text" type="text" class="swal2-input m-0 w-100" placeholder="Contoh: Selasa, 13 Maret 2026">
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Buka Batch Sekarang',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const closeDate = document.getElementById('swal-close-date').value;
                    const deliveryText = document.getElementById('swal-delivery-text').value;
                    if (!closeDate || !deliveryText) {
                        Swal.showValidationMessage('Harap isi Tanggal Tutup dan Teks Pengiriman!');
                        return false;
                    }
                    return { closeDate, deliveryText };
                }
            });

            if (formValues) {
                const newConfig = { closeDate: formValues.closeDate, deliveryText: formValues.deliveryText, waNumber: "62818895488" };
                localStorage.setItem('HS_PO_CONFIG', JSON.stringify(newConfig));
                localStorage.setItem('HS_BATCH_STATUS', 'OPEN');
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Batch PO Dibuka!', showConfirmButton: false, timer: 1500 });
            } else {
                toggle.checked = false;
            }
        } else {
            localStorage.setItem('HS_BATCH_STATUS', 'CLOSED');
            Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Batch PO Ditutup Paksa!', showConfirmButton: false, timer: 1500 });
        }
    }

    const defaultMenus = [
        { id: 1713250000001, name: 'Nasi Soto', price: 30000, stock: 15, img: 'Soto.jpeg', desc: 'Nasi putih, ayam suwir vegan (kedelai), bihun, tauge, kol, tomat.', active: true },
        { id: 1713250000002, name: 'Nasi Liwet', price: 30000, stock: 12, img: 'Liwet.jpeg', desc: 'Nasi gurih dimasak dengan santan & rempah pilihan, tempe orek.', active: true },
        { id: 1713250000003, name: 'Mie Sop Medan', price: 25000, stock: 20, img: 'Misop.jpeg', desc: 'Mie kuning, bihun, bakso jamur kenyal, tahu potong dadu.', active: true },
        { id: 1713250000004, name: 'Nasi Bakar', price: 25000, stock: 10, img: 'NasiBakar.jpeg', desc: 'Nasi gurih berbumbu rempah, isian jamur suwir pedas, kemangi segar.', active: true },
        { id: 1713250000005, name: 'Onigiri', price: 18000, stock: 30, img: 'Onigiri.jpeg', desc: 'Nasi kepal ala Jepang menggunakan beras premium, dibalut nori renyah.', active: true },
        { id: 1713250000006, name: 'Takoyaki', price: 40000, stock: 25, img: 'Takoyaki.jpeg', desc: 'Bola-bola tepung ala Jepang yang lembut, berisi potongan gurita vegan.', active: false },
        { id: 1713250000007, name: 'Nasi Chasio', price: 25000, stock: 18, img: 'VeganChasio.jpeg', desc: 'Nasi putih hangat dengan irisan daging chasio vegan panggang madu.', active: true }
    ];

    let menus = JSON.parse(localStorage.getItem('HS_MENU_DB')) || [];
    if (menus.length < 7) {
        menus = defaultMenus;
        localStorage.setItem('HS_MENU_DB', JSON.stringify(menus));
    }

    function save() { 
        localStorage.setItem('HS_MENU_DB', JSON.stringify(menus)); 
        render(); 
    }

    function render() {
        const body = document.getElementById('menuList');
        if(menus.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data SKU.</td></tr>';
            return;
        }

        body.innerHTML = menus.map((m, i) => `
            <tr class="${m.stock < 5 ? 'low-stock' : ''}">
                <td style="max-width: 250px;">
                    <div class="d-flex align-items-center gap-3">
                        <img src="${m.img}" class="rounded" width="50" height="50" style="object-fit:cover;" onerror="this.src='https://placehold.co/50'"> 
                        <div>
                            <b class="d-block">${m.name}</b>
                            <small class="text-secondary" style="font-size: 11px;">${m.desc.substring(0, 40)}...</small>
                        </div>
                    </div>
                </td>
                <td class="fw-bold">Rp ${parseInt(m.price).toLocaleString('id-ID')}</td>
                <td class="fw-bold">${m.stock} ${m.stock < 5 ? '⚠️' : ''}</td>
                <td>
                    <label class="switch">
                        <input type="checkbox" ${m.active ? 'checked' : ''} onchange="toggleAvail(${i})">
                        <span class="slider"></span>
                    </label>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-info fw-bold py-1 px-2" onclick="editItem(${i})">✏️ Edit</button>
                    <button class="btn btn-sm btn-outline-danger fw-bold py-1 px-2" onclick="delItem(${i})">🗑️ Hapus</button>
                </td>
            </tr>
        `).join('');
    }

    window.toggleAvail = (i) => {
        menus[i].active = !menus[i].active;
        save();
        const status = menus[i].active ? 'Aktif' : 'Disembunyikan';
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${menus[i].name} menjadi ${status}`, showConfirmButton: false, timer: 1500 });
    };

    window.editItem = async (i) => {
        const m = menus[i];
        const { value: v } = await Swal.fire({
            title: 'Edit SKU: ' + m.name,
            background: '#1e293b', color: '#fff',
            html: `
                <div class="text-start mb-2"><label class="small text-secondary">Harga</label><input id="p" type="number" class="swal2-input m-0 w-100" value="${m.price}"></div>
                <div class="text-start mb-2"><label class="small text-secondary">Stok</label><input id="s" type="number" class="swal2-input m-0 w-100" value="${m.stock}"></div>
                <div class="text-start mb-2"><label class="small text-secondary">Nama Gambar</label><input id="g" type="text" class="swal2-input m-0 w-100" value="${m.img}"></div>
                <div class="text-start"><label class="small text-secondary">Deskripsi</label><textarea id="d" class="swal2-textarea m-0 w-100">${m.desc}</textarea></div>
            `,
            focusConfirm: false,
            preConfirm: () => {
                const p = document.getElementById('p').value;
                const s = document.getElementById('s').value;
                const g = document.getElementById('g').value;
                const d = document.getElementById('d').value;
                if(!p || !s || !g) { Swal.showValidationMessage('Semua kolom penting wajib diisi!'); return false; }
                return [p, s, d, g];
            }
        });
        if(v) { 
            menus[i].price = parseInt(v[0]); menus[i].stock = parseInt(v[1]); menus[i].desc = v[2]; menus[i].img = v[3];
            save(); 
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Data berhasil diubah!', showConfirmButton: false, timer: 1500 });
        }
    };

    window.delItem = (i) => {
        Swal.fire({ title: 'Hapus SKU?', text: 'Menu akan hilang dari website pembeli!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!', background: '#1e293b', color: '#fff' }).then((r) => {
            if (r.isConfirmed) { 
                menus.splice(i, 1); save(); 
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Menu dihapus.', showConfirmButton: false, timer: 1500 });
            }
        });
    };

    window.addMenu = async () => {
        const { value: v } = await Swal.fire({
            title: 'Tambah SKU Baru',
            background: '#1e293b', color: '#fff',
            html: `
                <input id="n" class="swal2-input w-100 m-0 mb-3" placeholder="Nama Menu (Misal: Siomay)">
                <input id="p" type="number" class="swal2-input w-100 m-0 mb-3" placeholder="Harga (Misal: 20000)">
                <input id="s" type="number" class="swal2-input w-100 m-0 mb-3" placeholder="Stok (Misal: 50)">
                <input id="g" class="swal2-input w-100 m-0 mb-3" placeholder="File Gambar (Misal: Siomay.jpeg)">
                <textarea id="d" class="swal2-textarea w-100 m-0" placeholder="Deskripsi Singkat..."></textarea>
            `,
            focusConfirm: false,
            preConfirm: () => {
                const n = document.getElementById('n').value; const p = document.getElementById('p').value;
                const s = document.getElementById('s').value; const g = document.getElementById('g').value;
                const d = document.getElementById('d').value;
                if(!n || !p || !s || !g) { Swal.showValidationMessage('Mohon lengkapi Nama, Harga, Stok, dan Gambar!'); return false; }
                return [n, p, s, g, d];
            }
        });
        if (v) { 
            menus.push({ id: Date.now(), name: v[0], price: parseInt(v[1]), stock: parseInt(v[2]), img: v[3], desc: v[4], active: true }); 
            save(); 
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Menu Baru Ditambahkan!', showConfirmButton: false, timer: 1500 });
        }
    };

    render();
</script>
</body>
</html>

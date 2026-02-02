<?php
/**
 * Class Menu: Representasi satu item menu (Prinsip PBO - Encapsulation)
 */
class Menu {
    public $id;
    public $name;
    public $price;
    public $img;
    public $desc;
    public $stok;
    public $category;

    public function __construct($id, $name, $price, $img, $desc, $stok, $category) {
        $this->id = $id;
        $this->name = $name;
        $this->price = (int)$price;
        $this->img = $img;
        $this->desc = $desc;
        $this->stok = (int)$stok;
        $this->category = $category;
    }
}

/**
 * Class MenuRepository: Menangani pembacaan data (Prinsip PBO - Data Handling)
 */
class MenuRepository {
    private $filePath;

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function loadMenu() {
        $menuList = [];
        if (($handle = fopen($this->filePath, "r")) !== FALSE) {
            fgetcsv($handle); // Lewati header
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 7) {
                    $menuList[] = new Menu($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
                }
            }
            fclose($handle);
        }
        return $menuList;
    }
}

// Inisialisasi Data
$repo = new MenuRepository('menu.csv');
$daftarMenu = $repo->loadMenu();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Healthy Secret - Premium PO System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="po-banner">
        <div>📢 <strong>OPEN PO MINGGU INI!</strong> Pesan sekarang sebelum ditutup:</div>
        <div class="timer-box">
            <span id="days">00</span> Hari : <span id="hours">00</span> Jam : <span id="minutes">00</span> Menit : <span id="seconds">00</span> Detik
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#" style="letter-spacing: 2px;">HEALTHY <span class="text-info">SECRET</span></a>
        </div>
    </nav>

    <header class="container my-5 text-center" data-aos="fade-up">
        <h1 class="display-4 fw-bold">Menu Sehat Mingguan</h1>
        <p class="lead text-muted">Bahan premium, rasa bintang lima, harga mahasiswa.</p>
        <hr class="w-25 mx-auto">
    </header>

    <div class="container mb-5">
        <div class="row g-4" id="menu-container">
            <?php foreach ($daftarMenu as $item): ?>
            <div class="col-md-4 col-sm-6" data-aos="zoom-in">
                <div class="card h-100 border-0 shadow-sm position-relative card-menu">
                    <?php if ($item->stok <= 0): ?>
                        <span class="badge bg-danger position-absolute" style="top:10px; right:10px;">HABIS</span>
                    <?php else: ?>
                        <span class="badge bg-success position-absolute" style="top:10px; right:10px;">Tersedia: <?= $item->stok ?></span>
                    <?php endif; ?>

                    <img src="img/<?= $item->img ?>" class="card-img-top p-3" alt="<?= $item->name ?>" onerror="this.src='https://placehold.co/300x200?text=No+Image'">
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-dark"><?= $item->name ?></h5>
                        <p class="card-text text-muted small flex-grow-1"><?= $item->desc ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <h6 class="text-primary fw-bold mb-0">Rp <?= number_format($item->price, 0, ',', '.') ?></h6>
                            <button class="btn btn-outline-primary btn-sm px-3" 
                                    onclick="addToCart('<?= $item->id ?>', '<?= $item->name ?>', <?= $item->price ?>)"
                                    <?= ($item->stok <= 0) ? 'disabled' : '' ?>>
                                <?= ($item->stok <= 0) ? 'Sold Out' : '+ Tambah' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="container mb-5" id="order-section" data-aos="fade-up">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4 shadow border-0">
                    <h4 class="fw-bold mb-4 text-center">Detail Pesanan</h4>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" id="buyer-name" class="form-control" placeholder="Siapa nama Anda?">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat / Wilayah</label>
                        <textarea id="buyer-address" class="form-control" rows="2" placeholder="Contoh: Jl. Mawar No 12 (Medan Johor)"></textarea>
                    </div>

                    <div id="cart-summary" class="alert alert-light border d-none">
                        <h6 class="fw-bold">Item yang dipilih:</h6>
                        <div id="cart-items-list" class="mb-3"></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2">
                            <span>TOTAL TAGIHAN</span>
                            <span id="total-price" class="text-primary">Rp 0</span>
                        </div>
                    </div>

                    <button class="btn btn-success w-100 py-3 fw-bold" onclick="sendWA()">
                        PESAN SEKARANG VIA WHATSAPP
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container" class="position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 1055;"></div>

    <footer class="bg-dark text-white text-center py-4">
        <p class="mb-0 small">© 2026 Healthy Secret Medan. Made with ❤️ for Healthy Life.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="script.js"></script>
    <script>
        AOS.init({ duration: 800 });
        // Inisialisasi countdown jika fungsi ada di script.js
        if (typeof startCountdown === "function") startCountdown();
    </script>
</body>
</html>
<?php
session_start();

// Inisialisasi data peserta dan potongan
if (!isset($_SESSION['peserta'])) {
    $_SESSION['peserta'] = [];
}

$message = '';

// Hapus peserta berdasar NIP dari POST hapus_nip
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_nip'])) {
    $nipHapus = $_POST['hapus_nip'];
    $beforeCount = count($_SESSION['peserta']);
    $_SESSION['peserta'] = array_filter($_SESSION['peserta'], function($p) use ($nipHapus){
        return $p['nip'] !== $nipHapus;
    });
    $_SESSION['peserta'] = array_values($_SESSION['peserta']);
    $afterCount = count($_SESSION['peserta']);
    if ($beforeCount > $afterCount) {
        $message = "Peserta dengan NIP " . htmlspecialchars($nipHapus) . "berhasil dihapus.";
    } else {
        $message = "Peserta dengan NIP " . htmlspecialchars($nipHapus) . "tidak ditemukan.";
    }
    // Redirect supaya mencegah refresh submit form
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=pendaftar&msg=" . urlencode($message));
    exit();
}

// Form pendaftaran peserta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['daftar_peserta'])) {
    $nip = trim($_POST['nip']);
    $nama = trim($_POST['nama']);
    $unit = trim($_POST['unit']);
    if ($nip !== '' && $nama !== '' && $unit !== '') {
        // Cek NIP unik
        $exists = false;
        foreach ($_SESSION['peserta'] as $p) {
            if ($p['nip'] === $nip) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $_SESSION['peserta'][] = [
                'nip' => htmlspecialchars($nip),
                'nama' => htmlspecialchars($nama),
                'unit' => htmlspecialchars($unit),
                'potongan' => null
            ];
            $message = "Peserta berhasil didaftarkan.";
        } else {
            $message = "NIP sudah terdaftar.";
        }
    } else {
        $message = "Semua field wajib diisi.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=pendaftar&msg=" . urlencode($message));
    exit();
}

// Form input potongan gaji
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['input_potongan'])) {
    $nipPotong = $_POST['nip_potongan'] ?? '';
    $nominalPotong = floatval($_POST['potongan_bulanan'] ?? 0);
    if ($nipPotong !== '' && $nominalPotong > 0) {
        foreach ($_SESSION['peserta'] as &$p) {
            if ($p['nip'] === $nipPotong) {
                $p['potongan'] = $nominalPotong;
                // Ubah pesan tanpa tag html <strong>
                $message = "Potongan gaji untuk NIP " . htmlspecialchars($nipPotong) . " berhasil disimpan.";
                break;
            }
        }
        unset($p);
    } else {
        $message = "Mohon pilih peserta dan isi potongan gaji dengan benar.";
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=input-potongan&msg=" . urlencode($message));
    exit();
}


// Hitung total potongan seluruh peserta
$totalPotongan = 0;
foreach ($_SESSION['peserta'] as $p) {
    if ($p['potongan'] !== null) {
        $totalPotongan += $p['potongan'];
    }
}

// Ambil pesan (jika ada) dari parameter GET
if (isset($_GET['msg'])) {
    $message = htmlspecialchars(urldecode($_GET['msg']));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Berqurban Bersama UNP Peduli</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins&display=swap');
    :root {
        --orange: #f37021;
        --orange-light: #ffa56b;
        --blue: #004aad;
        --gray-light: #f5f5f5;
        --gray-medium: #999999;
        --gray-dark: #333333;
        --bg-white: #fff;
    }
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--gray-light);
        color: var(--gray-dark);
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    header {
        background-color: var(--orange);
        padding: 20px 10px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.15);
        color: white;
        text-align: center;
    }
    header h1 {
        margin: 0 0 5px 0;
        font-weight: 700;
        font-size: 1.8rem;
        letter-spacing: 1.2px;
    }
    header p {
        margin: 0 0 12px 0;
        font-weight: 400;
        font-size: 1.1rem;
        opacity: 0.9;
    }
    nav {
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        gap: 30px;
        border-bottom: 3px solid var(--orange-light);
        background: var(--orange);
    }
    nav button {
        background: none;
        border: none;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        padding: 14px 20px;
        transition: background-color 0.3s, color 0.3s;
        border-bottom: 4px solid transparent;
        letter-spacing: 1.1px;
    }
    nav button.active,
    nav button:hover {
        background-color: var(--orange-light);
        color: var(--bg-white);
        border-bottom-color: white;
        border-radius: 4px 4px 0 0;
    }
    main {
        max-width: 900px;
        margin: 30px auto 40px auto;
        padding: 0 20px;
        flex-grow: 1;
    }
    section {
        background: var(--bg-white);
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(243, 112, 33, 0.15);
        display: none;
    }
    section.active {
        display: block;
    }
    section h2 {
        color: var(--orange);
        margin-bottom: 20px;
        font-weight: 700;
        font-size: 1.5rem;
        border-bottom: 3px solid var(--orange-light);
        display: inline-block;
        padding-bottom: 5px;
    }
    .message {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        border-radius: 5px;
        padding: 12px 15px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    form label {
        display: block;
        margin: 15px 0 6px;
        font-weight: 600;
        font-size: 1rem;
    }
    form input[type="text"],
    form input[type="number"],
    form select {
        width: 100%;
        padding: 14px 12px;
        border: 2px solid #ddd;
        border-radius: 7px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    form input[type="text"]:focus,
    form input[type="number"]:focus,
    form select:focus {
        outline: none;
        border-color: var(--orange);
        box-shadow: 0 0 5px var(--orange-light);
    }
    form input[type="submit"], form button.delete-btn {
        margin-top: 25px;
        background-color: var(--orange);
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        padding: 15px;
        width: 100%;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        box-shadow: 0 6px 15px rgba(243, 112, 33, 0.3);
        letter-spacing: 1.4px;
        text-align: center;
    }
    form input[type="submit"]:hover, form button.delete-btn:hover {
        background-color: var(--orange-light);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        font-size: 0.95rem;
    }
    thead {
        background-color: var(--orange);
        color: white;
    }
    thead th {
        text-align: left;
        padding: 14px 16px;
        font-weight: 700;
        letter-spacing: 0.8px;
    }
    tbody tr {
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s ease-in-out;
    }
    tbody tr:hover {
        background-color: var(--gray-light);
    }
    tbody td {
        padding: 12px 16px;
        color: var(--gray-dark);
        vertical-align: middle;
    }
    tfoot td {
        font-weight: 700;
        font-size: 1rem;
        border-top: 3px solid var(--orange-light);
        padding: 14px 16px;
        color: var(--orange);
        text-align: right;
    }
    p.empty-msg {
        color: var(--gray-medium);
        font-style: italic;
        margin-top: 8px;
    }
    form.delete-form {
        display: inline-block;
        margin: 0;
        padding: 0;
    }
    form.delete-form button.delete-btn {
        width: auto;
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
        box-shadow: none;
        letter-spacing: normal;
        border: none;
        background-color: #dc3545;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    form.delete-form button.delete-btn:hover {
        background-color: #c82333;
    }
    @media (max-width: 600px) {
        nav {
            gap: 10px;
        }
        nav button {
            font-size: 0.9rem;
            padding: 12px 10px;
        }
        section h2 {
            font-size: 1.3rem;
        }
        main {
            margin: 15px auto 30px auto;
            padding: 0 10px;
        }
        form input[type="submit"], form button.delete-btn {
            font-size: 1rem;
            padding: 12px;
            width: 100%;
        }
        thead th, tbody td {
            font-size: 0.9rem;
            padding: 10px 12px;
        }
        form.delete-form button.delete-btn {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
    }
</style>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('nav button');
        const sections = document.querySelectorAll('main section');
        function deactivateAll() {
            tabs.forEach(t => t.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
        }
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                deactivateAll();
                tab.classList.add('active');
                const target = tab.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
                history.replaceState(null, '', '?tab=' + target);
            });
        });
        const params = new URLSearchParams(window.location.search);
        let activeTab = params.get('tab');
        if (!activeTab) activeTab = 'pendaftar';
        const btn = Array.from(tabs).find(t => t.getAttribute('data-target') === activeTab);
        if (btn) {
            btn.click();
        } else {
            tabs[0].click();
        }
    });
</script>
</head>
<body>

<header>
    <h1>Berqurban Bersama UNP Peduli</h1>
    <p>Menuju Kebersamaan dan Keberkahan di Idul Adha 1447 H</p>
</header>

<nav>
    <button type="button" data-target="pendaftar">Pendaftar</button>
    <button type="button" data-target="input-potongan">Input Potongan Gaji</button>
    <button type="button" data-target="laporan">Laporan Peserta</button>
</nav>

<main>

<?php if ($message !== ''): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<!-- Menu 1: Pendaftar -->
<section id="pendaftar">
    <h2>Formulir Pendaftaran Peserta Qurban</h2>
    <form method="POST" novalidate>
        <label for="nip">NIP</label>
        <input type="text" name="nip" id="nip" placeholder="Masukkan NIP" required autocomplete="off" />
        
        <label for="nama">Nama Lengkap Dosen/Karyawan</label>
        <input type="text" name="nama" id="nama" placeholder="Masukkan nama lengkap" required autocomplete="off" />
        
        <label for="unit">Unit / Fakultas</label>
        <input type="text" name="unit" id="unit" placeholder="Masukkan unit atau fakultas" required autocomplete="off" />
        
        <input type="submit" name="daftar_peserta" value="Daftar Sekarang" />
    </form>

    <?php if (count($_SESSION['peserta']) > 0): ?>
        <h3 style="margin-top: 30px; color: var(--orange)">Daftar Peserta Terdaftar:</h3>
        <table>
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Unit / Fakultas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($_SESSION['peserta'] as $p): ?>
                <tr>
                    <td><?= $p['nip']; ?></td>
                    <td><?= $p['nama']; ?></td>
                    <td><?= $p['unit']; ?></td>
                    <td>
                        <form method="POST" class="delete-form" onsubmit="return confirm('Hapus peserta <?= addslashes($p['nama']); ?>?');">
                            <input type="hidden" name="hapus_nip" value="<?= $p['nip']; ?>" />
                            <button type="submit" class="delete-btn" title="Hapus Peserta">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-msg">Belum ada peserta yang terdaftar.</p>
    <?php endif; ?>
</section>

<!-- Menu 2: Input Potongan Gaji -->
<section id="input-potongan">
    <h2>Input Data Potongan Gaji per Bulan</h2>
    <?php if (count($_SESSION['peserta']) > 0): ?>
    <form method="POST" novalidate>
        <label for="nip_potongan">Pilih Peserta (NIP - Nama)</label>
        <select name="nip_potongan" id="nip_potongan" required>
            <option value="" disabled selected>-- Pilih Peserta --</option>
            <?php foreach ($_SESSION['peserta'] as $p): ?>
                <option value="<?= $p['nip']; ?>">
                    <?= $p['nip'] . ' - ' . $p['nama']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="potongan_bulanan">Nominal Potongan Gaji per Bulan (Rp)</label>
        <input type="number" name="potongan_bulanan" id="potongan_bulanan" placeholder="Masukkan nominal potongan" min="1" required />

        <input type="submit" name="input_potongan" value="Simpan Potongan" />
    </form>
    <?php else: ?>
        <p class="empty-msg">Belum ada peserta terdaftar. Silakan daftar peserta terlebih dahulu.</p>
    <?php endif; ?>
</section>

<!-- Menu 3: Laporan Peserta -->
<section id="laporan">
    <h2>Laporan Peserta Qurban untuk Lebaran Haji 1447 H</h2>
    <?php
    $adaPotongan = false;
    foreach($_SESSION['peserta'] as $p) {
        if ($p['potongan'] !== null) {
            $adaPotongan = true;
            break;
        }
    }
    ?>
    <?php if ($adaPotongan): ?>
    <table>
        <thead>
            <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Unit / Fakultas</th>
                <th>Potongan Gaji per Bulan (Rp)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['peserta'] as $p): ?>
            <tr>
                <td><?= $p['nip']; ?></td>
                <td><?= $p['nama']; ?></td>
                <td><?= $p['unit']; ?></td>
                <td><?= $p['potongan'] !== null ? number_format($p['potongan'], 0, ',', '.') : '<em style="color:var(--gray-medium)">Belum diinput</em>'; ?></td>
                <td>
                    <form method="POST" class="delete-form" onsubmit="return confirm('Hapus peserta <?= addslashes($p['nama']); ?>?');">
                        <input type="hidden" name="hapus_nip" value="<?= $p['nip']; ?>" />
                        <button type="submit" class="delete-btn" title="Hapus Peserta">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right; font-weight:700; font-size:1.1rem;">Total Potongan Bulanan Seluruh Peserta</td>
                <td style="font-weight:700; font-size:1.1rem; color:var(--orange);">
                    Rp <?= number_format($totalPotongan, 0, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
        <p class="empty-msg">Belum ada data potongan gaji yang diinput. Silakan input data potongan gaji pada menu Input Potongan Gaji.</p>
    <?php endif; ?>
</section>

</main>

</body>
</html>

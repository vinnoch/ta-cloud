# Dokumen Pengaturan Akses Panduan Pengguna (User Guide)

Dokumentasi pengguna berbentuk HTML multi-halaman saat ini berada di `/Users/vinno/Herd/TACLOUD/docs`. Agar dapat diakses melalui browser di alamat `https://tacloud.test/docs/index.html`, Anda memiliki dua pilihan cara setup tanpa perlu melakukan coding yang rumit.

---

## Opsi 1: Pindahkan ke Folder `public` (Sangat Direkomendasikan)

Nginx pada Laravel Herd secara otomatis akan melayani file statis yang berada di dalam folder `public/`. Jika Anda memindahkan folder dokumentasi ke sana, Anda **tidak memerlukan penambahan Route Laravel sama sekali**.

### Langkah-langkah:
1. Pindahkan folder `docs` ke dalam folder `public/`:
   ```bash
   mv /Users/vinno/Herd/TACLOUD/docs /Users/vinno/Herd/TACLOUD/public/docs
   ```
2. Akses langsung melalui browser:
   ```
   https://tacloud.test/docs/index.html
   ```

---

## Opsi 2: Menggunakan Route Laravel (Jika Tetap di Root)

Jika Anda ingin folder `docs/` tetap berada di root project `/Users/vinno/Herd/TACLOUD/docs/`, Anda perlu mendaftarkan route statis di Laravel.

### Langkah-langkah:
Tambahkan kode berikut ke dalam file `routes/web.php` atau `routes/web/global.php` Anda:

```php
use Illuminate\Support\Facades\File;

Route::get('/docs/{file?}', function ($file = 'index.html') {
    $path = base_path('docs/' . $file);
    
    // Jika mengakses sub-folder assets
    if (str_contains($file, 'assets/')) {
        $path = base_path('docs/' . $file);
    }

    if (!File::exists($path)) {
        abort(404);
    }

    $fileContent = File::get($path);
    $type = File::mimeType($path);

    return response($fileContent)->header('Content-Type', $type);
})->where('file', '.*');
```

---

## Verifikasi Tautan Navigasi
Setelah setup selesai menggunakan salah satu opsi di atas, pastikan untuk membuka:
- `https://tacloud.test/docs/index.html` (Halaman Utama)
- `https://tacloud.test/docs/kaprodi.html` (Panduan Kaprodi)
- `https://tacloud.test/docs/dosen.html` (Panduan Dosen)
- `https://tacloud.test/docs/mahasiswa.html` (Panduan Mahasiswa)

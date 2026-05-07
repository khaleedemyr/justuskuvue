# QRIS DP Security Notes (Tanpa Payment Gateway)

## Konteks
- Fitur reservasi akan menerima DP via QRIS.
- Skenario saat ini: QRIS statis, tanpa payment gateway.
- Kekhawatiran utama: QRIS diganti (deface/tampering), bukti bayar palsu, dan mismatch nominal.

## Kesimpulan Singkat
- Tetap bisa dijalankan, tetapi tingkat keamanan di bawah gateway.
- Jangan percaya frontend untuk status bayar.
- Fokus keamanan: backend, storage, kontrol akses, audit, dan SOP verifikasi.

## Rekomendasi Arsitektur Pembayaran (Tanpa Gateway)
1. Saat reservasi dibuat, backend generate invoice DP dengan nominal unik (contoh: 150237).
2. Berikan masa berlaku invoice (misalnya 15-30 menit).
3. Tampilkan QRIS statis resmi.
4. User transfer nominal unik dan upload bukti bayar.
5. Status pembayaran masuk `pending_verification`.
6. Finance verifikasi ke mutasi rekening/e-wallet.
7. Jika valid, ubah ke `paid`; jika tidak valid, `rejected`.

## Keamanan Anti Deface QRIS
1. Simpan file QRIS di storage private (bukan file publik yang mudah ditimpa).
2. Web server hanya read-only terhadap file QRIS aktif.
3. Gunakan versioning file: upload baru -> versi baru, jangan overwrite file lama.
4. Simpan hash SHA-256 file QRIS di database.
5. Saat QRIS di-serve, verifikasi hash; jika mismatch, blok dan kirim alert.
6. Terapkan maker-checker (uploader != approver) untuk aktivasi QRIS.
7. Audit log wajib: siapa, kapan, IP, user-agent, hash lama/baru.
8. Batasi endpoint upload: role khusus, MFA, CSRF, rate limit, validasi MIME/magic bytes.

## State Machine yang Disarankan
- `draft`
- `awaiting_payment`
- `pending_verification`
- `paid`
- `rejected`
- `expired`

Status `paid` hanya boleh diubah oleh backend/admin finance setelah verifikasi.

## Pertanyaan: "Private storage harus server baru?"
Tidak wajib server baru.

### Opsi tanpa server baru
1. Tetap satu server aplikasi, tetapi:
   - simpan QRIS di folder non-public (`storage/app/private/...`),
   - akses lewat endpoint backend (authenticated/signed),
   - batasi permission OS (web user read-only).
2. Gunakan object storage private (S3/MinIO) dari server yang sama aplikasi.

### Kapan perlu server terpisah?
- Jika butuh segmentasi keamanan lebih ketat, compliance, atau traffic tinggi.
- Untuk skala menengah, satu server dengan hardening yang benar sudah cukup sebagai langkah awal.

## Quick Wins (Paling Berdampak)
1. Pindahkan QRIS ke private storage.
2. Tambahkan hash integrity check + alert.
3. Terapkan maker-checker dan audit log.
4. Terapkan nominal unik + expiry invoice.
5. Jangan pernah set `paid` dari aksi frontend.


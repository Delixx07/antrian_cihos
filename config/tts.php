<?php

/*
 * Text-to-Speech Bahasa Indonesia memakai PIPER (offline, gratis).
 *
 * Cara pasang lihat: SUARA-INDONESIA.md
 * Ringkas:
 *   1. Unduh piper Windows dari https://github.com/rhasspy/piper/releases
 *      → ekstrak ke C:\piper
 *   2. Unduh voice id_ID (mis. id_ID-fajri-medium.onnx + .onnx.json)
 *      dari https://huggingface.co/rhasspy/piper-voices/tree/main/id/id_ID
 *      → simpan di C:\piper\voices
 *   3. Isi TTS_BINARY & TTS_MODEL di .env bila lokasinya berbeda.
 *
 * Bila Piper tidak tersedia, layar otomatis kembali memakai Web Speech API
 * browser (kualitas bergantung voice yang terpasang di perangkat).
 */
return [

    /*
     * Mesin suara: 'edge' atau 'piper'.
     *
     *  edge  → Microsoft Edge TTS. Voice Indonesia NEURAL (Gadis/Ardi),
     *          paling natural & SAMA di semua perangkat. Server butuh
     *          internet saat merender; hasil di-cache.
     *  piper → offline sepenuhnya, hanya 1 voice Indonesia, lebih robotik.
     *
     * Bila keduanya tidak tersedia, layar otomatis memakai Web Speech API
     * bawaan browser sebagai cadangan (lihat announceBrowser() di layar).
     */
    'engine' => env('TTS_ENGINE', 'edge'),

    /*
     * Dari mana suara keluar:
     *
     *  local   → LAYAR ITU SENDIRI yang berbunyi begitu ada panggilan.
     *            Tidak perlu halaman/tombol tambahan (default).
     *  central → layar diam; panggilan diantre di server lalu diputar oleh
     *            perangkat yang membuka /display/speaker. Hanya perlu bila
     *            memakai satu sound system pusat untuk seluruh RS.
     *
     * Bisa ditimpa per layar lewat query, mis. /display/kasir?sound=central
     */
    'sound_mode' => env('TTS_SOUND_MODE', 'local'),

    // --- Pengaturan Edge TTS ---
    // Voice: id-ID-GadisNeural (wanita) atau id-ID-ArdiNeural (pria).
    'edge_voice' => env('TTS_EDGE_VOICE', 'id-ID-GadisNeural'),
    // Kecepatan & nada, format persen bertanda. Contoh: '-10%' lebih pelan.
    // Tempo. Tiap angka dipisah koma (agar jelas), sehingga rate dinaikkan
    // sedikit supaya jeda antar angka tidak terasa lama.
    // Naikkan angkanya bila ingin lebih cepat lagi (mis. '+12%').
    'edge_rate'  => env('TTS_EDGE_RATE', '+8%'),
    // Nada sedikit diturunkan → kesan formal/berwibawa ala pengumuman resmi.
    'edge_pitch' => env('TTS_EDGE_PITCH', '-2Hz'),
    // Perintah python (ganti bila memakai path lain / virtualenv).
    'python' => env('TTS_PYTHON', 'python'),

    // --- Pengaturan Piper (dipakai bila engine=piper atau edge gagal) ---
    // Lokasi executable piper.
    'binary' => env('TTS_BINARY', 'C:\\piper\\piper.exe'),

    // Berkas model suara (.onnx). File .onnx.json harus ada di folder sama.
    'model' => env('TTS_MODEL', 'C:\\piper\\id_ID-news_tts-medium.onnx'),

    // Kecepatan bicara: >1 lebih lambat, <1 lebih cepat.
    // 1.15 = tenang & berwibawa ala pengumuman bandara, tapi tidak bertele-tele
    // (pengumuman diputar 2x, jadi total durasi harus tetap wajar).
    'length_scale' => env('TTS_LENGTH_SCALE', '1.15'),

    // Matikan untuk memaksa memakai Web Speech API browser.
    'enabled' => env('TTS_ENABLED', true),

];

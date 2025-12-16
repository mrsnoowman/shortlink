<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AE News | Portal Berita Terkini</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            line-height: 1.6;
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .nav {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .brand-logo {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: radial-gradient(circle at 20% 20%, #22c55e 0, #0ea5e9 40%, #6366f1 80%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 800;
            color: #e5e7eb;
        }
        .brand-text {
            font-weight: 700;
            letter-spacing: 0.04em;
            font-size: 1.1rem;
        }
        .brand-sub {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            font-size: 0.9rem;
            color: #cbd5f5;
        }
        .nav-link {
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            transition: background 0.2s, color 0.2s;
        }
        .nav-link:hover {
            background: rgba(148, 163, 184, 0.18);
            color: #f9fafb;
        }

        .hero {
            background: radial-gradient(circle at top, rgba(56, 189, 248, 0.12), transparent 55%),
                        radial-gradient(circle at bottom, rgba(129, 140, 248, 0.12), transparent 55%);
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }
        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 1.5rem;
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
            gap: 2rem;
        }
        .hero-kicker {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #a5b4fc;
            margin-bottom: 0.8rem;
        }
        .hero-title {
            font-size: 2.3rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 0.8rem;
            color: #f9fafb;
        }
        .hero-highlight {
            background: linear-gradient(120deg, #38bdf8, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .hero-subtitle {
            font-size: 1rem;
            color: #9ca3af;
            max-width: 32rem;
            margin-bottom: 1.6rem;
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: #9ca3af;
        }
        .hero-meta-item span:first-child {
            display: block;
            font-weight: 600;
            color: #e5e7eb;
        }
        .hero-image-wrapper {
            position: relative;
        }
        .hero-image {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(148, 163, 184, 0.4);
        }
        .hero-image img {
            display: block;
            width: 100%;
            height: 260px;
            object-fit: cover;
        }
        .hero-tag {
            position: absolute;
            bottom: 1.2rem;
            left: 1.2rem;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(148, 163, 184, 0.5);
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            flex: 1;
        }
        .main-layout {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.1fr);
            gap: 2rem;
            align-items: flex-start;
        }
        .section-title {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .news-card {
            background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.18), rgba(15, 23, 42, 0.95));
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.75);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .news-card:hover {
            transform: translateY(-4px);
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.95);
        }
        .news-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }
        .news-content {
            padding: 1.2rem 1.3rem 1.1rem;
        }
        .news-category {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(15, 23, 42, 0.7);
            color: #e5e7eb;
            padding: 0.25rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(148, 163, 184, 0.55);
            margin-bottom: 0.75rem;
        }
        .news-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: #f9fafb;
        }
        .news-excerpt {
            color: #9ca3af;
            margin-bottom: 0.9rem;
            font-size: 0.9rem;
        }
        .news-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #6b7280;
            padding-top: 0.8rem;
            border-top: 1px dashed rgba(55, 65, 81, 0.8);
        }
        .news-date {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .sidebar {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            padding: 1.4rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.9);
        }
        .sidebar-title {
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        .sidebar-list {
            list-style: none;
        }
        .sidebar-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.7rem 0;
            border-bottom: 1px solid rgba(31, 41, 55, 0.9);
        }
        .sidebar-item:last-child {
            border-bottom: none;
        }
        .sidebar-rank {
            font-weight: 700;
            color: #6b7280;
            font-size: 0.85rem;
            min-width: 1.3rem;
        }
        .sidebar-text {
            flex: 1;
        }
        .sidebar-text-title {
            font-size: 0.9rem;
            color: #e5e7eb;
            margin-bottom: 0.15rem;
        }
        .sidebar-text-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .footer {
            border-top: 1px solid rgba(31, 41, 55, 0.9);
            padding: 1.1rem 1.5rem 1.4rem;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .footer-links {
            display: flex;
            gap: 1rem;
        }
        .footer-link:hover {
            color: #e5e7eb;
        }

        @media (max-width: 900px) {
            .hero-inner {
                grid-template-columns: 1fr;
            }
            .hero-image img {
                height: 220px;
            }
            .main-layout {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .nav-inner {
                padding-inline: 1rem;
            }
            .hero-inner {
                padding-inline: 1rem;
            }
            .main {
                padding-inline: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <nav class="nav">
            <div class="nav-inner">
                <div class="brand">
                    <div class="brand-logo">N</div>
                    <div>
                        <div class="brand-text">News</div>
                        <div class="brand-sub">Daily Insights</div>
                    </div>
                </div>
                <div class="nav-links">
                    <span class="nav-link">Beranda</span>
                    <span class="nav-link">Teknologi</span>
                    <span class="nav-link">Bisnis</span>
                    <span class="nav-link">Olahraga</span>
                    <span class="nav-link">Gaya Hidup</span>
                </div>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-inner">
                <div>
                    <div class="hero-kicker">Headline Hari Ini</div>
                    <h1 class="hero-title">
                        Tren <span class="hero-highlight">Teknologi & Bisnis</span> yang Mengubah Cara Kita Bekerja
                    </h1>
                    <p class="hero-subtitle">
                        Update harian seputar teknologi, bisnis, dan gaya hidup digital yang dikemas secara ringkas, 
                        profesional, dan mudah dipahami.
                    </p>
                    <div class="hero-meta">
                        <div class="hero-meta-item">
                            <span>Editorial</span>
                            <span>Disusun oleh tim AE News · {{ date('d M Y') }}</span>
                        </div>
                        <div class="hero-meta-item">
                            <span>Topik</span>
                            <span>AI · Startup · Produktivitas · Kesehatan</span>
                        </div>
                    </div>
                </div>
                <div class="hero-image-wrapper">
                    <div class="hero-image">
                        <img
                            src="https://images.unsplash.com/photo-1526498460520-4c246339dccb?w=1200&h=600&fit=crop"
                            alt="Ruang kerja modern dengan teknologi"
                            onerror="this.src='https://via.placeholder.com/1200x600/020617/ffffff?text=AE+News'"
                        >
                    </div>
                    <div class="hero-tag">
                        <span>✨ Pilihan Editor</span>
                        <span>•</span>
                        <span>{{ date('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <main class="main">
            <div class="main-layout">
                <section>
                    <div class="section-title">Berita Utama</div>
                    <div class="news-grid">
            <!-- Berita 1 -->
            <article class="news-card">
                <img 
                    src="https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&h=600&fit=crop" 
                    alt="Teknologi"
                    class="news-image"
                    onerror="this.src='https://via.placeholder.com/800x600/667eea/ffffff?text=Technology'"
                >
                <div class="news-content">
                    <span class="news-category">Teknologi</span>
                    <h2 class="news-title">Perkembangan Teknologi AI di Tahun 2025</h2>
                    <p class="news-excerpt">
                        Artificial Intelligence terus berkembang dengan pesat. Tahun 2025 membawa inovasi baru dalam machine learning dan deep learning yang akan mengubah cara kita bekerja dan hidup.
                    </p>
                    <div class="news-meta">
                        <span class="news-date">📅 {{ date('d M Y') }}</span>
                        <span>👁️ 1.2K views</span>
                    </div>
                </div>
            </article>

            <!-- Berita 2 -->
            <article class="news-card">
                <img 
                    src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop" 
                    alt="Bisnis"
                    class="news-image"
                    onerror="this.src='https://via.placeholder.com/800x600/764ba2/ffffff?text=Business'"
                >
                <div class="news-content">
                    <span class="news-category" style="background: #764ba2;">Bisnis</span>
                    <h2 class="news-title">Strategi Bisnis Digital di Era Modern</h2>
                    <p class="news-excerpt">
                        Transformasi digital menjadi kunci sukses bisnis di era modern. Pelaku usaha perlu mengadopsi teknologi untuk tetap kompetitif di pasar global.
                    </p>
                    <div class="news-meta">
                        <span class="news-date">📅 {{ date('d M Y', strtotime('-1 day')) }}</span>
                        <span>👁️ 890 views</span>
                    </div>
                </div>
            </article>

            <!-- Berita 3 -->
            <article class="news-card">
                <img 
                    src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=800&h=600&fit=crop" 
                    alt="Kesehatan"
                    class="news-image"
                    onerror="this.src='https://via.placeholder.com/800x600/48bb78/ffffff?text=Health'"
                >
                <div class="news-content">
                    <span class="news-category" style="background: #48bb78;">Kesehatan</span>
                    <h2 class="news-title">Tips Menjaga Kesehatan di Musim Hujan</h2>
                    <p class="news-excerpt">
                        Musim hujan membawa tantangan tersendiri untuk kesehatan. Simak tips praktis untuk menjaga daya tahan tubuh dan mencegah penyakit selama musim hujan.
                    </p>
                    <div class="news-meta">
                        <span class="news-date">📅 {{ date('d M Y', strtotime('-2 days')) }}</span>
                        <span>👁️ 2.1K views</span>
                    </div>
                </div>
            </article>

            <!-- Berita 4 -->
            <article class="news-card">
                <img 
                    src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800&h=600&fit=crop" 
                    alt="Olahraga"
                    class="news-image"
                    onerror="this.src='https://via.placeholder.com/800x600/ed8936/ffffff?text=Sports'"
                >
                <div class="news-content">
                    <span class="news-category" style="background: #ed8936;">Olahraga</span>
                    <h2 class="news-title">Prestasi Atlet Indonesia di Ajang Internasional</h2>
                    <p class="news-excerpt">
                        Atlet Indonesia kembali menorehkan prestasi gemilang di berbagai ajang internasional. Pencapaian ini membanggakan dan menginspirasi generasi muda.
                    </p>
                    <div class="news-meta">
                        <span class="news-date">📅 {{ date('d M Y', strtotime('-3 days')) }}</span>
                        <span>👁️ 3.5K views</span>
                    </div>
                </div>
            </article>

            <!-- Berita 5 -->
            <article class="news-card">
                <img 
                    src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&h=600&fit=crop" 
                    alt="Lingkungan"
                    class="news-image"
                    onerror="this.src='https://via.placeholder.com/800x600/38a169/ffffff?text=Environment'"
                >
                <div class="news-content">
                    <span class="news-category" style="background: #38a169;">Lingkungan</span>
                    <h2 class="news-title">Gerakan Peduli Lingkungan untuk Masa Depan</h2>
                    <p class="news-excerpt">
                        Kesadaran akan pentingnya menjaga lingkungan semakin meningkat. Berbagai gerakan peduli lingkungan digalakkan untuk menciptakan masa depan yang lebih baik.
                    </p>
                    <div class="news-meta">
                        <span class="news-date">📅 {{ date('d M Y', strtotime('-4 days')) }}</span>
                        <span>👁️ 1.8K views</span>
                    </div>
                </div>
            </article>

                        <!-- Berita 6 -->
                        <article class="news-card">
                            <img 
                                src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&h=600&fit=crop" 
                                alt="Pendidikan"
                                class="news-image"
                                onerror="this.src='https://via.placeholder.com/800x600/805ad5/ffffff?text=Education'"
                            >
                            <div class="news-content">
                                <span class="news-category" style="border-color:#a855f7;">🎓 Pendidikan</span>
                                <h2 class="news-title">Inovasi dalam Sistem Pendidikan Digital</h2>
                                <p class="news-excerpt">
                                    Pendidikan digital menjadi solusi untuk meningkatkan akses dan kualitas pembelajaran. 
                                    Teknologi membuka peluang baru untuk metode belajar yang lebih interaktif.
                                </p>
                                <div class="news-meta">
                                    <span class="news-date">📅 {{ date('d M Y', strtotime('-5 days')) }}</span>
                                    <span>👁️ 2.3K views</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <aside class="sidebar">
                    <div class="sidebar-title">Trending Minggu Ini</div>
                    <ul class="sidebar-list">
                        <li class="sidebar-item">
                            <span class="sidebar-rank">1</span>
                            <div class="sidebar-text">
                                <div class="sidebar-text-title">5 Tools AI untuk Produktivitas Kerja Harian</div>
                                <div class="sidebar-text-meta">Teknologi · 4.1K views</div>
                            </div>
                        </li>
                        <li class="sidebar-item">
                            <span class="sidebar-rank">2</span>
                            <div class="sidebar-text">
                                <div class="sidebar-text-title">Cara UKM Bertahan di Tengah Ketidakpastian Ekonomi</div>
                                <div class="sidebar-text-meta">Bisnis · 3.7K views</div>
                            </div>
                        </li>
                        <li class="sidebar-item">
                            <span class="sidebar-rank">3</span>
                            <div class="sidebar-text">
                                <div class="sidebar-text-title">Rutinitas Sehat 15 Menit untuk Pekerja Kantoran</div>
                                <div class="sidebar-text-meta">Kesehatan · 2.9K views</div>
                            </div>
                        </li>
                        <li class="sidebar-item">
                            <span class="sidebar-rank">4</span>
                            <div class="sidebar-text">
                                <div class="sidebar-text-title">Tips Mengelola Waktu di Era Hybrid Working</div>
                                <div class="sidebar-text-meta">Gaya Hidup · 2.4K views</div>
                            </div>
                        </li>
                        <li class="sidebar-item">
                            <span class="sidebar-rank">5</span>
                            <div class="sidebar-text">
                                <div class="sidebar-text-title">Tren Startup Lokal yang Mengguncang Pasar Asia</div>
                                <div class="sidebar-text-meta">Startup · 1.9K views</div>
                            </div>
                        </li>
                    </ul>
                </aside>
            </div>
        </main>

        <footer class="footer">
            <div class="footer-inner">
                <div>© {{ date('Y') }} AE News · aelink.click · All rights reserved.</div>
                <div class="footer-links">
                    <span class="footer-link">Tentang Kami</span>
                    <span class="footer-link">Kebijakan Privasi</span>
                    <span class="footer-link">Kontak</span>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>

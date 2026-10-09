<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | Sistem SDM & Absensi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #17345e;
            background:
                radial-gradient(circle at 5% 100%, #e0edff 0, transparent 25%),
                radial-gradient(circle at 100% 5%, #edf5ff 0, transparent 30%),
                #f8fbff;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            padding: 32px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            font-weight: 800;
            font-size: 22px;
            line-height: 1.2;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            color: white;
            font-size: 26px;
            background: linear-gradient(135deg, #55a2ff, #1763e9);
            box-shadow: 0 8px 20px #2478ed30;
        }

        .brand small {
            display: block;
            margin-top: 4px;
            color: #6681aa;
            font-size: 14px;
            font-weight: 500;
        }

        .tagline {
            color: #6681aa;
            text-align: right;
            font-size: 14px;
        }

        .maintenance-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 40px 0 70px;
        }

        .illustration {
            width: 100%;
            max-width: 550px;
            margin: auto;
            display: block;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 18px;
            border-radius: 50px;
            background: #e5f0ff;
            color: #2474ee;
            font-weight: 700;
            margin-bottom: 24px;
        }

        h1 {
            font-size: clamp(36px, 4vw, 60px);
            font-weight: 850;
            letter-spacing: -2px;
            line-height: 1.12;
            margin-bottom: 25px;
        }

        h1 span { color: #2878f5; }

        .description {
            font-size: 18px;
            line-height: 1.8;
            color: #60799f;
            max-width: 540px;
        }

        .estimate {
            margin-top: 30px;
            padding: 24px;
            border-radius: 18px;
            background: #eaf3ff;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .estimate-icon {
            width: 58px;
            height: 58px;
            flex-shrink: 0;
            border: 3px solid #3984f8;
            color: #3984f8;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 27px;
        }

        .estimate small {
            display: block;
            color: #6881a6;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .estimate strong {
            display: block;
            font-size: 21px;
        }

        .estimate p {
            margin: 5px 0 0;
            color: #60799f;
        }

        footer {
            padding: 25px 15px 35px;
            text-align: center;
            color: #2878f5;
            font-size: 17px;
            font-style: italic;
        }

        @media (max-width: 767px) {
            .topbar { padding: 22px 0; }
            .tagline { display: none; }
            .maintenance-content { padding: 20px 0 35px; }
            .illustration { max-width: 330px; margin-bottom: 30px; }
            h1 { font-size: 39px; letter-spacing: -1.5px; }
            .description { font-size: 16px; }
            .estimate { padding: 18px; }
            .estimate strong { font-size: 18px; }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="brand">
                    <div class="brand-icon">✓</div>
                    <div>
                        Sistem SDM & Absensi
                        <small>Daniel Creative School</small>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="maintenance-content">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-md-6 text-center">
                    <svg class="illustration" viewBox="0 0 520 440"
                         xmlns="http://www.w3.org/2000/svg"
                         role="img" aria-label="Ilustrasi perawatan sistem">
                        <ellipse cx="260" cy="398" rx="230" ry="15"
                                 fill="#dceaff"/>

                        <path d="M50 120 Q10 70 100 45 Q170 10 220 50
                                 Q280 0 330 65 Q410 55 440 120
                                 Q500 190 435 250 L80 250 Q10 205 50 120"
                              fill="#e6f0ff"/>

                        <g fill="#c7ddff" opacity=".9">
                            <path d="M100 55h18v-12h14v12h18v14h-18v18h-14V69h-18z"
                                  transform="rotate(20 125 65)"/>
                            <circle cx="440" cy="90" r="24"/>
                            <circle cx="440" cy="90" r="10" fill="#e6f0ff"/>
                        </g>

                        <rect x="145" y="105" width="235" height="190"
                              rx="16" fill="#214777"/>
                        <rect x="155" y="115" width="215" height="168"
                              rx="10" fill="#fff"/>
                        <rect x="170" y="130" width="185" height="140"
                              rx="8" fill="#f2f7ff"/>

                        <g fill="#91add3">
                            <circle cx="262" cy="174" r="24"/>
                            <circle cx="262" cy="174" r="10" fill="#f2f7ff"/>
                            <path d="M258 142h8v12h-8zm0 52h8v12h-8zm-32-24h12v8h-12zm52 0h12v8h-12z"/>
                        </g>

                        <rect x="195" y="220" width="135" height="12"
                              rx="6" fill="#d4e5ff"/>
                        <rect x="195" y="220" width="92" height="12"
                              rx="6" fill="#3684f7"/>

                        <text x="262" y="250" text-anchor="middle"
                              font-size="12" fill="#6681aa"
                              font-weight="700">UNDER MAINTENANCE</text>

                        <path d="M240 295h45l12 35h-69z" fill="#214777"/>
                        <rect x="215" y="328" width="95" height="12"
                              rx="6" fill="#214777"/>

                        <g transform="translate(65 180)">
                            <circle cx="40" cy="20" r="19" fill="#f8bc91"/>
                            <path d="M22 18q0-26 22-20l14 16-5 6-7-13-24 11z"
                                  fill="#17345e"/>
                            <path d="M25 42h30l15 85H10z" fill="#2878f5"/>
                            <path d="M24 49L2 110l10 7 28-52M54 48l27 48-8 8-32-37"
                                  fill="#17345e"/>
                            <path d="M20 120l-8 74h17l16-74m4 0 8 74h17l-4-74"
                                  fill="#214777"/>
                            <path d="M12 190h20v10H5q-8-5 7-10m44 0h17l10 10H58z"
                                  fill="#17345e"/>
                            <path d="M18 5l7-18h35l7 18z" fill="#ffbf38"/>
                            <path d="M15 5h55v7H15z" fill="#e8a51d"/>
                            <path d="M37 18l-5 8 10 10 8-10-5-8"
                                  fill="#f8bc91"/>
                        </g>

                        <path d="M89 310l20-67 20 67z" fill="#ffbf38"/>
                        <path d="M98 280h22l-4 13h-22z" fill="#fff"/>
                        <rect x="85" y="310" width="48" height="9"
                              rx="4" fill="#214777"/>

                        <g transform="translate(335 280)">
                            <rect x="0" y="25" width="92" height="70"
                                  rx="10" fill="#214777"/>
                            <rect x="8" y="15" width="76" height="18"
                                  rx="5" fill="#2f5b93"/>
                            <rect x="18" y="0" width="56" height="22"
                                  rx="5" fill="#17345e"/>
                            <rect x="13" y="35" width="10" height="18"
                                  rx="3" fill="#ffbf38"/>
                            <rect x="69" y="35" width="10" height="18"
                                  rx="3" fill="#ffbf38"/>
                        </g>
                    </svg>
                </div>

                <div class="col-md-6">
                    <div class="status-pill">
                        🔧 Maintenance
                    </div>

                    <h1>
                        Website Sedang<br>
                        <span>Dalam Perawatan</span>
                    </h1>

                    <p class="description">
                        Kami sedang melakukan pemeliharaan sistem untuk
                        meningkatkan kinerja, keamanan, dan memberikan
                        pengalaman yang lebih baik.
                        Mohon kembali beberapa saat lagi.
                    </p>

                    <div class="estimate">
                        <div class="estimate-icon">◷</div>
                        <div>
                            <small>STATUS PEMELIHARAAN</small>
                            <strong>Sistem Sedang Diperbarui</strong>
                            <p>Terima kasih atas pengertian Anda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        — &nbsp; Kami akan segera kembali &nbsp; —
    </footer>
</body>
</html>
 <!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Izin Berhasil</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #e8f5e9, #f4f8ff);
            color: #263238;
        }

        .success-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            padding: 45px 30px;
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f7ed;
            color: #198754;
            border-radius: 50%;
            font-size: 58px;
            font-weight: bold;
        }

        h1 {
            font-size: 27px;
            margin-bottom: 15px;
            color: #198754;
        }

        .description {
            color: #667085;
            line-height: 1.7;
            font-size: 15px;
            margin-bottom: 25px;
        }

        .info {
            padding: 15px;
            background: #f0f7ff;
            border: 1px solid #d7e8ff;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.7;
            text-align: left;
            margin-bottom: 28px;
        }

        .info strong {
            color: #175cd3;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 10px;
            background: #198754;
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #146c43;
        }

        .footer {
            margin-top: 25px;
            color: #98a2b3;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .success-card {
                padding: 35px 22px;
            }

            h1 {
                font-size: 23px;
            }

            .success-icon {
                width: 85px;
                height: 85px;
                font-size: 48px;
            }
        }
    </style>
</head>

<body>

    <div class="success-card">

        <div class="success-icon">
            &#10003;
        </div>

        <h1>Pengajuan Berhasil!</h1>

        <p class="description">
            Terima kasih. Form pengajuan izin kamu berhasil
            dikirim dan tersimpan di sistem.
        </p>

        <div class="info">
            <strong>Informasi:</strong><br>
            Pengajuan izin kamu sedang menunggu proses
            persetujuan dari pimpinan.
        </div>

        <a href="{{ url()->previous() }}" class="btn">
            Kembali ke Halaman Izin
        </a>

    </div>

</body>
</html>
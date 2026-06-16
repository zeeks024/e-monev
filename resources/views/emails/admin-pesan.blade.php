<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pesan->judul }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    @php
        $loginUrl = url('/login');
        $logoPath = public_path('images/logobna.png');
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f4f6; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; background-color:#ffffff; border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="background-color:#1d4ed8; padding:28px 24px;">
                            @if (file_exists($logoPath))
                                <img src="{{ $message->embed($logoPath) }}" alt="Logo E-Monev KIP" width="72" style="display:block; width:72px; height:auto; margin:0 auto 16px; background-color:#ffffff; border-radius:8px; padding:8px;">
                            @endif
                            <p style="margin:0 0 8px; color:#dbeafe; font-size:13px; font-weight:bold; text-transform:uppercase;">
                                E-Monev KIP Banjarnegara
                            </p>
                            <h1 style="margin:0; color:#ffffff; font-size:22px; line-height:1.35; font-weight:bold;">
                                {{ $pesan->judul }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 18px; color:#111827; font-size:16px; line-height:1.6;">
                                Yth. <strong>{{ $namaPenerima }}</strong>,
                            </p>

                            <div style="margin:0 0 24px; color:#374151; font-size:15px; line-height:1.75; white-space:pre-line;">{{ $pesan->isi }}</div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 28px; background-color:#eff6ff; border-left:4px solid #2563eb;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <p style="margin:0; color:#1e40af; font-size:14px; line-height:1.6;">
                                            Silakan masuk ke aplikasi E-Monev KIP untuk melihat detail informasi atau menindaklanjuti pesan ini.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                <tr>
                                    <td align="center" bgcolor="#2563eb" style="border-radius:6px;">
                                        <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 24px; color:#ffffff; font-size:15px; font-weight:bold; text-decoration:none;">
                                            Masuk ke Aplikasi
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;">
                    <tr>
                        <td align="center" style="padding:18px 12px 0;">
                            <p style="margin:0 0 8px; color:#6b7280; font-size:12px; line-height:1.5;">
                                Email ini dikirim secara otomatis oleh sistem <strong>E-Monev KIP Banjarnegara</strong>.<br>
                                Mohon untuk tidak membalas langsung ke alamat email ini.
                            </p>
                            <p style="margin:0; color:#9ca3af; font-size:12px; line-height:1.5;">
                                &copy; {{ date('Y') }} E-Monev KIP Kabupaten Banjarnegara. Hak Cipta Dilindungi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

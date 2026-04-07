<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activación Telegram</title>
</head>
<body style="margin:0; padding:0; background:#eef6ff; font-family: Arial, sans-serif; color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eef6ff; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:620px; background:#ffffff; border-radius:16px; box-shadow:0 10px 30px rgba(31,41,55,0.08); overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 16px 28px; text-align:center; background:linear-gradient(135deg,#f4f9ff 0%,#f3fbf7 55%,#f7fbff 100%);">
                            <img src="{{ asset('images/brand/genesis-email.png') }}" alt="Genesis SST" style="max-width:220px; width:60%; height:auto; display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h2 style="margin:0 0 12px 0; font-size:22px; color:#0f172a;">Activación de Telegram</h2>
                            <p style="margin:0 0 12px 0; font-size:15px; line-height:1.6;">Hola {{ $empleado->nombre }},</p>
                            <p style="margin:0 0 18px 0; font-size:15px; line-height:1.6;">
                                Para activar tu canal de Telegram y recibir notificaciones de Genesis SST, haz clic en el siguiente botón:
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 18px auto;">
                                <tr>
                                    <td align="center" bgcolor="#2563eb" style="border-radius:10px;">
                                        <a href="{{ $link }}" target="_blank" style="display:inline-block; padding:12px 22px; font-size:15px; color:#ffffff; text-decoration:none; font-weight:bold;">
                                            Activar Telegram
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 10px 0; font-size:13px; color:#475569; line-height:1.6;">
                                Si el botón no abre, copia y pega esta URL en tu navegador:
                            </p>
                            <p style="margin:0 0 18px 0; font-size:13px; color:#2563eb; word-break:break-all;">
                                {{ $link }}
                            </p>
                            <p style="margin:0; font-size:14px; color:#0f172a;">Gracias,</p>
                            <p style="margin:4px 0 0 0; font-size:14px; color:#0f172a;">Equipo Genesis SST</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px 28px; font-size:12px; color:#64748b; text-align:center;">
                            Este mensaje fue generado automáticamente. Si no solicitaste esta activación, ignora este correo.
                        </td>
                    </tr>
                </table>
                <div style="height:16px;"></div>
                <div style="font-size:11px; color:#94a3b8;">
                    Genesis SST · Notificaciones
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

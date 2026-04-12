<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GENESIS SST</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#4f8ef7">

    <style>
        :root {
            --gen-blue: #4f8ef7;
            --gen-green: #62d3a7;
            --gen-ink: #111827;
            --gen-muted: #6b7280;
            --card-border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
            background: radial-gradient(1200px 600px at 20% -10%, #eef5ff 0%, #f6f9ff 45%, #f5fbf7 75%, #f7fbff 100%);
            color: var(--gen-ink);
        }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1120px; margin: 0 auto; padding: 32px 24px 64px; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .nav-links { display: none; gap: 24px; font-size: 14px; font-weight: 600; align-items: center; }
        .nav-link { color: #374151; }
        .nav-link:hover { color: #111827; }
        .btn-primary {
            background: linear-gradient(135deg, var(--gen-blue), var(--gen-green));
            color: white; border: 0; padding: 10px 18px; border-radius: 999px; font-weight: 600;
            box-shadow: 0 18px 40px -28px rgba(15, 23, 42, 0.4);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-outline {
            background: white; border: 1px solid var(--card-border); color: #374151;
            padding: 10px 18px; border-radius: 999px; font-weight: 600;
        }
        .hero { margin-top: 48px; display: grid; gap: 40px; }
        .chip { display: inline-flex; gap: 8px; padding: 6px 12px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: 12px; font-weight: 600; }
        .hero h1 { font-size: 40px; line-height: 1.1; margin: 16px 0 0; }
        .hero p { color: var(--gen-muted); font-size: 16px; max-width: 520px; margin: 16px 0 0; }
        .hero-actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 16px; }
        .mockup {
            border: 1px solid var(--card-border); border-radius: 24px; padding: 24px;
            background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
            box-shadow: 0 25px 60px -40px rgba(2, 8, 23, 0.5);
        }
        .mockup-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .card {
            background: #ffffffcc; border: 1px solid var(--card-border); border-radius: 16px; padding: 16px;
            box-shadow: 0 20px 50px -30px rgba(15, 23, 42, 0.35); backdrop-filter: blur(8px);
        }
        .card-title { font-weight: 600; font-size: 14px; }
        .card-text { color: var(--gen-muted); font-size: 13px; margin-top: 8px; }
        .grad-bar { height: 64px; border-radius: 12px; margin-top: 12px; }
        .features { margin-top: 64px; }
        .features h2 { font-size: 24px; margin: 0; }
        .features-grid { display: grid; gap: 20px; margin-top: 24px; }
        .explore { margin-top: 48px; }
        .explore-grid { display: grid; gap: 20px; margin-top: 20px; }
        .explore-list { margin: 12px 0 0; padding: 0; list-style: none; color: var(--gen-muted); font-size: 13px; }
        .explore-list li { margin-bottom: 6px; }
        .benefits { margin-top: 64px; }
        .benefits-grid { display: grid; gap: 20px; margin-top: 24px; }
        .benefit-item { display: grid; gap: 6px; }
        .benefit-title { font-weight: 600; font-size: 14px; }
        .benefit-text { font-size: 13px; color: var(--gen-muted); }
        .benefits-box { display: flex; gap: 24px; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 28px; border-radius: 24px; }
        .plans { margin-top: 64px; }
        .plans-grid { display: grid; gap: 20px; margin-top: 24px; }
        .plan-card { padding: 22px; border-radius: 18px; border: 1px solid var(--card-border); background: #fff; box-shadow: 0 16px 40px -28px rgba(15, 23, 42, 0.35); }
        .plan-name { font-weight: 600; font-size: 16px; }
        .plan-price { font-size: 26px; font-weight: 700; margin-top: 10px; }
        .plan-sub { font-size: 12px; color: var(--gen-muted); }
        .plan-list { margin: 16px 0 0; padding: 0; list-style: none; font-size: 13px; color: var(--gen-muted); }
        .plan-list li { margin-bottom: 8px; }
        .plan-cta { margin-top: 18px; display: inline-flex; }
        .plan-highlight { border: 2px solid #a7f3d0; background: #f7fffb; }
        .cta { margin-top: 56px; text-align: center; color: white; padding: 32px; border-radius: 24px; background: linear-gradient(135deg, var(--gen-blue), var(--gen-green)); }
        .cta p { margin: 8px 0 0; font-size: 14px; color: rgba(255,255,255,0.9); }
        .cta-actions { margin-top: 20px; display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; }
        .footer { margin-top: 64px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; color: var(--gen-muted); font-size: 14px; }

        @media (min-width: 900px) {
            .nav-links { display: flex; }
            .hero { grid-template-columns: 1.1fr 0.9fr; align-items: center; }
            .features-grid { grid-template-columns: repeat(3, 1fr); }
            .explore-grid { grid-template-columns: repeat(2, 1fr); }
            .benefits-grid { grid-template-columns: repeat(3, 1fr); }
            .plans-grid { grid-template-columns: repeat(4, 1fr); }
        }
        @media (max-width: 600px) {
            .hero h1 { font-size: 32px; }
            .mockup-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="nav">
            <div class="logo">
                <img src="{{ asset('images/brand/genesis-email.png') }}" alt="GENESIS" style="height:36px;width:auto;">
            </div>
            <nav class="nav-links">
                <a href="#features" class="nav-link">Features</a>
                <a href="#explorar" class="nav-link">Explorar</a>
                <a href="#beneficios" class="nav-link">Beneficios</a>
                <a href="#planes" class="nav-link">Planes</a>
                <a href="/admin/login" class="btn-primary">Login</a>
            </nav>
        </header>

        <section class="hero">
            <div>
                <div class="chip">Plataforma SST inteligente</div>
                <h1>Desbloquea el potencial de tu equipo con GENESIS</h1>
                <p>La plataforma integral para programas SST, seguimiento de casos, reincorporaciones y pausas activas, con comunicación directa y datos accionables.</p>
                <div class="hero-actions">
                    <a href="/admin/login" class="btn-primary">Entrar al Panel</a>
                    <a href="#explorar" class="btn-outline">Explorar funciones</a>
                </div>
                <div style="margin-top:18px; font-size:13px; color:#6b7280;">Gestión unificada por empresa y planta. Seguimiento en tiempo real.</div>
            </div>
            <div class="mockup">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; font-size:14px; font-weight:600;">
                    <span>Dashboard</span>
                    <span style="font-size:12px; color:#9ca3af;">Últimos 30 días</span>
                </div>
                <div class="mockup-grid">
                    <div class="card">
                        <div style="font-size:12px; color:#6b7280;">Casos activos</div>
                        <div style="font-size:22px; font-weight:600; margin-top:6px;">128</div>
                        <div class="grad-bar" style="background: linear-gradient(90deg,#dbeafe,#bfdbfe);"></div>
                    </div>
                    <div class="card">
                        <div style="font-size:12px; color:#6b7280;">Pausas completadas</div>
                        <div style="font-size:22px; font-weight:600; margin-top:6px;">1,842</div>
                        <div class="grad-bar" style="background: linear-gradient(90deg,#d1fae5,#a7f3d0);"></div>
                    </div>
                    <div class="card" style="grid-column: span 2;">
                        <div style="font-size:12px; color:#6b7280;">Alertas preventivas</div>
                        <div style="font-size:16px; font-weight:600; margin-top:6px;">+24% de detecciones tempranas</div>
                        <div class="grad-bar" style="height:80px;background: linear-gradient(90deg,#e0e7ff,#99f6e4);"></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="features">
            <h2>Features</h2>
            <div class="features-grid">
                <div class="card">
                    <div class="card-title">Programas y casos</div>
                    <div class="card-text">Seguimiento integral de reincorporaciones, incapacidades y evolución del personal.</div>
                </div>
                <div class="card">
                    <div class="card-title">Pausas activas</div>
                    <div class="card-text">Gamificación, tiempos controlados y participación visible por planta.</div>
                </div>
                <div class="card">
                    <div class="card-title">Encuestas inteligentes</div>
                    <div class="card-text">Detección temprana de riesgos con métricas y alertas automatizadas.</div>
                </div>
            </div>
        </section>

        <section id="explorar" class="explore">
            <h2>Explorar funciones</h2>
            <div class="explore-grid">
                <div class="card">
                    <div class="card-title">Gestión clínica y SST</div>
                    <ul class="explore-list">
                        <li>• Reincorporaciones con actas y evidencia</li>
                        <li>• Incapacidades con CIE10 y seguimiento</li>
                        <li>• Casos por programa y estado</li>
                        <li>• Mapas diagnósticos configurables</li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-title">Participación y comunicación</div>
                    <ul class="explore-list">
                        <li>• Pausas activas con temporizador y foco</li>
                        <li>• Encuestas inteligentes por empresa/planta</li>
                        <li>• Envíos masivos programados</li>
                        <li>• Activación Telegram con trazabilidad</li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-title">Reportes y control</div>
                    <ul class="explore-list">
                        <li>• Exportes en Excel por filtro</li>
                        <li>• Indicadores por programa</li>
                        <li>• Historial de participación por persona</li>
                        <li>• Control multiempresa y permisos</li>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-title">Automatización operativa</div>
                    <ul class="explore-list">
                        <li>• Programación de envíos por fecha/hora</li>
                        <li>• Seguimiento de pendientes y completados</li>
                        <li>• Evidencia por PDF y registro de actividad</li>
                        <li>• Comunicación unificada por canal</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="beneficios" class="benefits">
            <h2>Beneficios</h2>
            <div class="benefits-grid">
                <div class="card benefit-item">
                    <div class="benefit-title">Gestión por empresa y planta</div>
                    <div class="benefit-text">Controla accesos, reportes y casos por cada cliente y sede sin cruces de información.</div>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-title">Alertas preventivas</div>
                    <div class="benefit-text">Detecta riesgos temprano con encuestas inteligentes y reglas clínicas configurables.</div>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-title">Ejecución y evidencia</div>
                    <div class="benefit-text">Pausas activas con temporizador, foco y registro automático de participación.</div>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-title">Automatización operativa</div>
                    <div class="benefit-text">Envíos programados por Telegram o correo con seguimiento del estado.</div>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-title">Trazabilidad SST</div>
                    <div class="benefit-text">Historial completo de reincorporaciones, incapacidades y casos con PDFs y evidencias.</div>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-title">Reportes exportables</div>
                    <div class="benefit-text">Descarga en Excel participaciones y métricas por empresa/planta en segundos.</div>
                </div>
            </div>
            <div class="card benefits-box" style="margin-top:20px;">
                <div>
                    <h3 style="margin:0; font-size:22px;">Resultados que se notan</h3>
                    <div style="margin-top:10px; color:#6b7280; font-size:14px;">Reduce tiempo administrativo, aumenta participación y mejora la toma de decisiones con datos actualizados.</div>
                </div>
                <a href="/admin/login" class="btn-primary">Entrar al Panel</a>
            </div>
        </section>

        <section id="planes" class="plans">
            <h2>Planes y precios</h2>
            <div class="plans-grid">
                <div class="plan-card">
                    <div class="plan-name">Prime</div>
                    <div class="plan-price">COP $590.000</div>
                    <div class="plan-sub">Mensual · Hasta 50 empleados</div>
                    <ul class="plan-list">
                        <li>• Programas SST + casos</li>
                        <li>• Reincorporaciones e incapacidades</li>
                        <li>• Reportes básicos</li>
                        <li>• Soporte estándar</li>
                    </ul>
                    <div class="plan-cta"><a href="/admin/login" class="btn-outline">Elegir Prime</a></div>
                </div>
                <div class="plan-card plan-highlight">
                    <div class="plan-name">Plus</div>
                    <div class="plan-price">COP $990.000</div>
                    <div class="plan-sub">Mensual · 51–200 empleados</div>
                    <ul class="plan-list">
                        <li>• Todo Prime + Pausas activas</li>
                        <li>• Encuestas inteligentes</li>
                        <li>• Envíos programados</li>
                        <li>• Dashboards comparativos</li>
                    </ul>
                    <div class="plan-cta"><a href="/admin/login" class="btn-primary">Elegir Plus</a></div>
                </div>
                <div class="plan-card">
                    <div class="plan-name">Pro</div>
                    <div class="plan-price">COP $1.590.000</div>
                    <div class="plan-sub">Mensual · 201–500 empleados</div>
                    <ul class="plan-list">
                        <li>• Todo Plus + automatizaciones</li>
                        <li>• Multiempresa avanzada</li>
                        <li>• Reportes gerenciales</li>
                        <li>• Soporte prioritario</li>
                    </ul>
                    <div class="plan-cta"><a href="/admin/login" class="btn-outline">Elegir Pro</a></div>
                </div>
                <div class="plan-card">
                    <div class="plan-name">Signature</div>
                    <div class="plan-price">Desde COP $2.490.000</div>
                    <div class="plan-sub">Mensual · 500+ empleados</div>
                    <ul class="plan-list">
                        <li>• Cobertura multiempresa avanzada</li>
                        <li>• Integraciones y personalización</li>
                        <li>• Reportes ejecutivos</li>
                        <li>• Soporte dedicado</li>
                    </ul>
                    <div class="plan-cta"><a href="/admin/login" class="btn-outline">Cotizar</a></div>
                </div>
            </div>
        </section>

        <section class="cta">
            <h3 style="margin:0; font-size:22px;">¿Listo para transformar tu operación SST?</h3>
            <p>Centraliza el control, mide el impacto y mejora la salud laboral.</p>
            <div class="cta-actions">
                <a href="/admin/login" class="btn-outline" style="background:#fff;color:#111827;">Entrar al Panel</a>
                <a href="#features" class="btn-outline" style="border-color:rgba(255,255,255,0.6); color:white; background:transparent;">Explorar funciones</a>
            </div>
        </section>

        <footer class="footer">
            <div style="display:flex; align-items:center; gap:12px;">
                <img src="{{ asset('images/brand/genesis-email.png') }}" alt="GENESIS" style="height:28px;width:auto;">
                <span>Genesis SST</span>
            </div>
            <div style="display:flex; gap:18px; flex-wrap:wrap;">
                <span>Producto</span>
                <span>Compañía</span>
                <span>Recursos</span>
            </div>
        </footer>
    </div>
</body>
</html>

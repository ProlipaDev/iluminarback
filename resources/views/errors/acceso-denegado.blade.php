<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Prolipa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
            margin: 20px;
        }
        .error-icon {
            font-size: 120px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            color: #2c3e50;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .error-message {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
        }
        .details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        
        <h1 class="error-title">Acceso Denegado</h1>
        
        <p class="error-message">
            Lo sentimos, no tienes los permisos necesarios para acceder a esta sección del sistema.
            <br><br>
            Esta área está restringida únicamente para administradores con permisos especiales.
        </p>

        @auth
            <div class="details">
                <!-- <h5 class="mb-3">📋 Información de tu cuenta:</h5> -->
                <h5 class="mb-3">📋 Información:</h5>
                
                <!-- <div class="detail-item">
                    <span class="detail-label">👤 Usuario ID:</span>
                    <span class="detail-value">{{ Auth::user()->idusuario ?? 'N/A' }}</span>
                </div> -->
                
                <!-- <div class="detail-item">
                    <span class="detail-label">👥 Grupo:</span>
                    <span class="detail-value">{{ Auth::user()->id_group ?? 'NO_DEFINIDO' }}</span>
                </div> -->
                
                <!-- <div class="detail-item">
                    <span class="detail-label">📧 Email:</span>
                    <span class="detail-value">{{ Auth::user()->email ?? 'N/A' }}</span>
                </div> -->
                
                @php
                    $tienePermisoSuper = \DB::table('permisos_super')
                        ->where('usuario_id', Auth::user()->idusuario)
                        ->where('id_group', Auth::user()->id_group)
                        ->exists();
                @endphp
                
                <div class="detail-item">
                    <span class="detail-label">🔐 Bloqueo:</span>
                    <span class="detail-value">{{ $tienePermisoSuper ? '✅ Activo' : '❌ No autorizado' }}</span>
                </div>
                
                <!-- <div class="detail-item">
                    <span class="detail-label">⚡ Estado:</span>
                    <span class="detail-value">
                        @if(Auth::user()->id_group != 1)
                            ❌ Grupo incorrecto (se requiere grupo 1)
                        @elseif(!$tienePermisoSuper)
                            ❌ Sin permisos especiales
                        @else
                            ✅ Autorizado
                        @endif
                    </span>
                </div> -->
            </div>
        @else
            <div class="details">
                <h5 class="mb-3">⚠️ No has iniciado sesión</h5>
                <p class="text-muted">Para acceder a esta sección, primero debes iniciar sesión con una cuenta autorizada.</p>
            </div>
        @endauth

        <div class="mt-4">
            <a href="/admin/despachados/vista" class="btn-home">Reintentar el acceso</a>
        </div>

        <div class="mt-3">
            <small class="text-muted">
                Si crees que esto es un error, contacta al administrador del sistema.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
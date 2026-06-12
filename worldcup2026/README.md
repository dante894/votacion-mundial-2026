# ⚽ FIFA World Cup 2026 — Plataforma de Predicciones

Plataforma PHP para votar resultados del Mundial 2026.
Compatible con hosting gratuito (InfinityFree, 000webhost, ByetHost).

## Estructura de archivos

```
worldcup2026/
├── index.php                   ← Página principal (pública)
├── login.php                   ← Login con Google OAuth o email
├── .htaccess                   ← Seguridad Apache
├── config/
│   └── oauth.php               ← Credenciales Google OAuth ← EDITAR
├── includes/
│   ├── bootstrap.php           ← Inicialización
│   ├── database.php            ← SQLite + esquema
│   ├── teams_data.php          ← 48 equipos oficiales
│   └── auth.php                ← Sesiones + OAuth
├── auth/
│   ├── google-callback.php     ← Callback de Google
│   └── logout.php
├── api/
│   └── vote.php                ← Registrar votos
├── admin/
│   └── index.php               ← Panel de administración
└── database/
    └── worldcup.db             ← Se crea automáticamente
```

## Instalación

### 1. Subir archivos al hosting
Sube toda la carpeta `worldcup2026/` a la raíz pública de tu hosting
(normalmente `public_html/` o `htdocs/`).

### 2. Configurar Google OAuth (para login con Google)
1. Ir a https://console.cloud.google.com/
2. Crear proyecto → "APIs & Services" → "Credentials"
3. "Create Credentials" → "OAuth 2.0 Client ID"
4. Application type: **Web application**
5. Authorized redirect URIs:
   ```
   https://tudominio.com/worldcup2026/auth/google-callback.php
   ```
6. Copiar **Client ID** y **Client Secret**
7. Editar `config/oauth.php`:
   ```php
   define('GOOGLE_CLIENT_ID',     'tu-client-id.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'tu-client-secret');
   define('GOOGLE_REDIRECT_URI',  'https://tudominio.com/worldcup2026/auth/google-callback.php');
   ```

### 3. Permisos de carpeta (en hosting Linux)
```bash
chmod 755 database/
chmod 644 .htaccess
```
Si no puedes usar SSH, crea la carpeta `database/` manualmente desde el
panel del hosting con permisos 755.

### 4. Acceder al sitio
- **Sitio público:** `https://tudominio.com/worldcup2026/`
- **Panel admin:**  `https://tudominio.com/worldcup2026/admin/`

### 5. Credenciales admin por defecto
```
Email:     admin@worldcup2026.com
Contraseña: admin2026
```
⚠️ **Cambiar la contraseña** en `includes/teams_data.php` función `seedAdmin()` antes de subir.

## Hosting gratuito compatible

| Hosting        | URL                     | PHP | SQLite | Notas |
|---------------|-------------------------|-----|--------|-------|
| InfinityFree  | infinityfree.net        | ✅  | ✅     | Recomendado |
| 000webhost    | 000webhost.com          | ✅  | ✅     | Sin curl en gratis |
| ByetHost      | byet.host               | ✅  | ✅     | Buena velocidad |
| AwardSpace    | awardspace.com          | ✅  | ✅     | |
| Netlify       | netlify.com             | ❌  | —      | Solo estático |

> **Nota sobre cURL:** El login con Google OAuth requiere `curl_exec()`.
> En hostings gratuitos que bloqueen cURL, el login con email/contraseña
> seguirá funcionando. Solo el botón "Continuar con Google" no funcionará.

## Uso del panel admin

1. Ingresar a `/admin/` con las credenciales de admin
2. Ir a **"Crear Partido"**
3. Seleccionar equipo local y visitante (se muestra preview con banderas)
4. Elegir etapa (Grupos, Octavos, Cuartos, etc.)
5. Poner fecha de inicio y cierre de votación
6. El partido aparece automáticamente en la página principal cuando llegue la hora de inicio
7. Se cierra automáticamente al vencer la fecha de cierre

## Tecnologías

- **Backend:** PHP 7.4+ / 8.x
- **Base de datos:** SQLite (sin configuración de MySQL)
- **Banderas:** flagcdn.com (gratuito, sin API key)
- **OAuth:** Google OAuth 2.0
- **Frontend:** HTML/CSS/JS puro (sin frameworks, carga rápida)
- **Fuente datos:** FIFA World Cup 2026 oficial (48 equipos, grupos A-L)

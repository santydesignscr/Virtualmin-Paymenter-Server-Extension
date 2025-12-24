# Virtualmin Extension for Paymenter

Esta extensión permite a Paymenter gestionar servidores virtuales en Virtualmin a través de su API remota.

## Características

- ✅ Crear dominios/servidores virtuales
- ✅ Suspender y reactivar dominios
- ✅ Eliminar dominios
- ✅ Actualizar/cambiar de plan (template)
- ✅ Generar enlaces de inicio de sesión
- ✅ Soporte para templates de Virtualmin

## Requisitos

- Virtualmin GPL o Pro instalado
- Acceso root al servidor Virtualmin
- API remota habilitada en Virtualmin (habilitada por defecto)
- Puerto 10000 accesible (puerto por defecto de Webmin/Virtualmin)

## Instalación

1. Copia el directorio `Virtualmin` a `extensions/Servers/Virtualmin` en tu instalación de Paymenter
2. Ve al panel de administración de Paymenter
3. Navega a Extensiones > Servidores
4. Busca y habilita la extensión "Virtualmin"

## Configuración

### Configuración de la Extensión

Después de habilitar la extensión, configura los siguientes parámetros:

- **Hostname**: La URL completa de tu servidor Virtualmin incluyendo el puerto (ej: `https://server.example.com:10000`)
- **Username**: El nombre de usuario del administrador maestro (generalmente `root`)
- **Password**: La contraseña del administrador maestro
- **Verify SSL Certificate**: Habilitar para verificar certificados SSL. Desactivar para certificados autofirmados (por defecto: desactivado)

### Configuración del Producto

Al crear un producto con esta extensión, podrás seleccionar:

- **Account Plan**: El plan de Virtualmin que se aplicará a los nuevos dominios y definirá las cuotas y límites

### Configuración en el Checkout

Los clientes deberán proporcionar:

- **Domain**: El nombre de dominio para su servidor virtual (ej: `example.com`)

## Comandos de API Utilizados

Esta extensión utiliza los siguientes comandos de la API de Virtualmin:

- `list-plans` - Lista los planes de cuenta disponibles
- `list-domains` - Lista los dominios (usado para verificar la conexión)
- `create-domain` - Crea un nuevo dominio virtual con features básicas (unix, dir, web, dns, mail)
- `disable-domain` - Suspende un dominio con razón
- `enable-domain` - Reactiva un dominio suspendido
- `delete-domain` - Elimina un dominio completamente
- `modify-domain` - Modifica un dominio y aplica nuevo plan con `--apply-plan`
- `create-login-link` - Genera un enlace de inicio de sesión temporal

## Funcionalidad

### Creación de Servidor

Al crear un servicio, la extensión:
1. Genera un nombre de usuario aleatorio (8 caracteres)
2. Genera una contraseña segura aleatoria
3. Crea el dominio virtual en Virtualmin con:
   - Features habilitadas: Unix user, directorio, web, DNS, mail
   - Plan seleccionado (aplica límites automáticamente con `--limits-from-plan`)
   - Email del cliente como contacto
4. Guarda las credenciales en las propiedades del servicio

### Suspensión y Reactivación

- **Suspensión**: Deshabilita el dominio usando `disable-domain` con razón "Suspended by Paymenter"
- **Reactivación**: Reactiva el dominio usando `enable-domain`

### Eliminación

Elimina completamente el dominio virtual y todas sus propiedades asociadas.

### Actualización/Upgrade

Aplica un nuevo plan al dominio existente usando `modify-domain` con `--apply-plan`, lo que actualiza automáticamente las cuotas y límites según el nuevo plan.

### Acciones Disponibles

Los clientes pueden ver:
- Username del servidor virtual
- Password del servidor virtual
- Dominio asignado
- Botón para acceder a Virtualmin (genera un enlace de login temporal)

## Próximas Mejoras

Esta es la versión base de la extensión. Las siguientes mejoras están planificadas:

- [ ] Soporte para funciones adicionales de Virtualmin (email, DNS, bases de datos, etc.)
- [ ] Opciones de configuración de recursos (cuotas de disco, ancho de banda, etc.)
- [ ] Gestión de subdominios y alias
- [ ] Gestión de bases de datos MySQL/PostgreSQL
- [ ] Gestión de cuentas de correo
- [ ] Gestión de registros DNS
- [ ] Instalación de scripts (WordPress, etc.)
- [ ] Soporte para certificados SSL/Let's Encrypt
- [ ] Configuración de versiones de PHP

## Documentación de Referencia

- [Virtualmin Remote API Documentation](https://www.virtualmin.com/documentation/developer/http-api/)
- [Virtualmin Command-line API](https://www.virtualmin.com/documentation/developer/cli/)

## Soporte

Para reportar problemas o sugerencias, por favor abre un issue en el repositorio de Paymenter.

## Licencia

Esta extensión se distribuye bajo la misma licencia que Paymenter.

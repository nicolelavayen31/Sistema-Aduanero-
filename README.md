# Sistema Aduanero

Aplicación de práctica para gestionar clientes, declaraciones aduaneras y el registro de pagos. El frontend está hecho con HTML, CSS y JavaScript nativo; las solicitudes HTTP se centralizan en `resources/js/api.js`. No requiere un framework JavaScript adicional.

## Requisitos

- PHP y Composer instalados.
- Node.js y npm instalados.
- Base de datos y configuración de Laravel preparadas en `.env`.

## Ejecutar en desarrollo

Abre dos terminales desde la raíz del proyecto:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Abre `http://127.0.0.1:8000`. Laravel sirve la interfaz y el frontend llama a las rutas `/api/...` en el mismo origen, por lo que esta configuración no necesita CORS.

El acceso está protegido con la autenticación de sesión de Laravel. Para crear una cuenta administrativa desde la terminal, ejecuta:

```powershell
php artisan app:create-user
```

El comando solicitará nombre, correo y contraseña (mínimo 12 caracteres); no existe una cuenta ni una contraseña predeterminadas. Inicia sesión en `/login` con la cuenta creada. Las rutas de API requieren una sesión válida y las solicitudes de escritura incluyen el token CSRF de Laravel.

Para generar los recursos optimizados:

```powershell
npm run build
```

Después de la compilación, Laravel carga los archivos de `public/build`.

## Funcionalidad

- **Dashboard:** obtiene estadísticas agregadas y las declaraciones recientes desde `GET /api/dashboard/estadisticas`.
- **Clientes:** busca, pagina, consulta y crea registros con `POST /api/clientes`.
- **Declaraciones:** busca y pagina declaraciones con sus clientes y pagos asociados, y registra una declaración y un pago con `POST /api/declaraciones/con-pago`.

Los tipos de declaración, métodos y estados de pago se mantienen en `config/aduanas.php`, se exponen con `GET /api/catalogos` y se validan también en el backend. Los campos personales aplican formato, unicidad y validación del dígito verificador de cédula ecuatoriana. Al registrar una operación, Laravel valida el cliente activo, el formato y la unicidad del número, los valores y las opciones permitidas. La declaración y el pago se crean en una transacción; la declaración se marca pagada solo cuando el total de pagos confirmados cubre el valor de la mercancía. Los pagos parciales, pendientes y rechazados quedan visibles mientras la declaración permanece pendiente. Las estadísticas y respuestas de declaraciones también reconocen como pagadas las declaraciones históricas cuyos pagos acumulados ya cubren el valor.

Los formularios muestran los errores de validación que devuelve Laravel; la validación del servidor es la fuente de verdad. Las listas admiten `search`, `page` y `per_page` como parámetros opcionales; las llamadas sin parámetros conservan la respuesta original como arreglo. El tamaño de página está limitado a 100 registros. Las estadísticas del dashboard se calculan en el servidor y no requieren descargar todas las listas.

Las reglas de entrada, ejemplos y matriz de casos de prueba están en [docs/validaciones.md](./docs/validaciones.md). Las reglas de validación también se prueban con `php artisan test tests/Feature/OperacionesApiTest.php`.

## Rutas de API consumidas

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/api/clientes` | Listar clientes y cargar el selector; admite `search`, `page` y `per_page` opcionales |
| GET | `/api/clientes/{id}` | Consultar el detalle de un cliente |
| POST | `/api/clientes` | Registrar un cliente |
| GET | `/api/catalogos` | Consultar tipos de declaración y métodos de pago permitidos |
| GET | `/api/dashboard/estadisticas` | Consultar totales y declaraciones recientes |
| GET | `/api/declaraciones` | Listar declaraciones con cliente y pagos; admite `search`, `page` y `per_page` opcionales |
| POST | `/api/declaraciones/con-pago` | Registrar declaración y pago asociados |

El frontend está integrado en la vista raíz de Laravel y consume las rutas en el mismo origen, así que no requiere CORS.

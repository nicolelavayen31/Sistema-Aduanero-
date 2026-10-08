# Matriz de pruebas de validación

Las reglas del navegador ayudan a corregir errores antes de enviar el formulario, pero no son una capa de seguridad: cada solicitud directa a la API debe ser validada por Laravel. Para verificar la capa backend, ejecutar:

```powershell
php artisan test tests/Feature/OperacionesApiTest.php
```

La cédula `0923456784` es un ejemplo de prueba que pasa el formato y el dígito verificador ecuatoriano. El ejemplo `0934567890` tiene 10 dígitos, pero no pasa el dígito verificador y debe rechazarse.

| # | Qué se envía | Qué debería ocurrir / mensaje | Frontend bloquea | Laravel rechaza |
|---|---|---|---|---|
| 1 | `nombres=José Luis`, `apellidos=García López` | Aceptar y guardar; al perder el foco se normalizan espacios repetidos. | No | No |
| 2 | `nombres=Ana123` | Rechazar: “Solo se permiten letras y espacios; no se permiten números ni símbolos.” | Sí | Sí |
| 3 | `nombres=Ana😀` | Rechazar: “Este campo no permite emojis.” | Sí | Sí |
| 4 | `apellidos=López@` | Rechazar: “Solo se permiten letras y espacios; no se permiten números ni símbolos.” | Sí | Sí |
| 5 | `cedula=0923456784` | Aceptar si no está duplicada; validar también el dígito verificador ecuatoriano. | No | No |
| 6 | `cedula=09345678AB` | Rechazar: “La cédula solo puede contener números, sin espacios ni símbolos.” | Sí | Sí |
| 7 | `cedula=093456789` | Rechazar: “La cédula debe contener exactamente 10 números.” | Sí | Sí |
| 8 | `cedula=09345678901` | Rechazar: “La cédula debe contener exactamente 10 números.” | Sí | Sí |
| 9 | Repetir `cedula=0923456784` después de registrarla | Rechazar: “Esta cédula ya está registrada.” | No puede saberlo sin enviar; muestra el error que responde la API | Sí |
| 10 | `telefono=0991234567` | Aceptar. El teléfono vacío también es válido porque es opcional. | No | No |
| 11 | `telefono=099ABC4567` | Rechazar: “El teléfono solo puede contener números.” | Sí | Sí |
| 12 | `telefono=099123456😀` | Rechazar: “El teléfono no permite emojis.” | Sí | Sí |
| 13 | `email=ana.martinez@gmail.com` | Aceptar si no está duplicado. | No | No |
| 14 | `email=ana gmail@gmail.com` | Rechazar: “El correo electrónico no puede contener espacios.” | Sí | Sí |
| 15 | `valor_mercancia=-500` | Rechazar: “El valor debe ser mayor que 0.” | Sí | Sí |
| 16 | `valor_mercancia=1500.999` | Rechazar: “Ingresa un número positivo con máximo 2 decimales.” | Sí | Sí |
| 17 | `monto=-500` | Rechazar: “El valor debe ser mayor que 0.” | Sí | Sí |
| 18 | `metodo_pago=cheque` | Rechazar: “Selecciona un método de pago permitido.” Laravel solo acepta las opciones del catálogo. | Sí | Sí |
| 19 | `estado=completado` | Rechazar: “Selecciona un estado de pago permitido.” Los estados válidos son `pendiente`, `pagado` y `rechazado`. | Sí | Sí |

También se valida `dirección` (letras, números, espacios, comas, puntos y guiones; sin emojis), el formato `DA-001`, la unicidad del número de declaración, el tipo del catálogo, el cliente activo y la asignación de propiedades. Los montos aceptan como máximo dos decimales y el máximo compatible con la columna decimal existente (`99999999.99`).

La API de declaración/pago existente conserva el comportamiento anterior cuando no se envía `estado`: el estado del pago por defecto es `pagado`. Si se envía, debe corresponder a un estado permitido. Un pago con estado pendiente o rechazado no se cuenta como pago confirmado.

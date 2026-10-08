const emojiPattern = /[\p{Extended_Pictographic}\p{Regional_Indicator}\uFE0F\u200D\u20E3]/u;
const namePattern = /^(?:\p{Script=Latin}\p{M}*)+(?: (?:\p{Script=Latin}\p{M}*)+)*$/u;
const addressPattern = /^[\p{Script=Latin}\p{M}0-9 ,.-]*$/u;

function hasEmoji(value) {
    return emojiPattern.test(value);
}

function validEcuadorianCedula(value) {
    if (!/^[0-9]{10}$/.test(value)) {
        return false;
    }

    const province = Number(value.slice(0, 2));
    const thirdDigit = Number(value[2]);

    if (!((province >= 1 && province <= 24) || province === 30) || thirdDigit > 5) {
        return false;
    }

    let sum = 0;
    for (let index = 0; index < 9; index += 1) {
        const product = Number(value[index]) * (index % 2 === 0 ? 2 : 1);
        sum += product > 9 ? product - 9 : product;
    }

    return (10 - (sum % 10)) % 10 === Number(value[9]);
}

function positiveMoney(value, label) {
    if (!/^[0-9]+(?:\.[0-9]{1,2})?$/.test(value)) {
        return `${label} debe ser positivo y tener máximo 2 decimales.`;
    }

    const amount = Number(value);
    if (!Number.isFinite(amount) || amount <= 0) {
        return `${label} debe ser mayor que 0.`;
    }

    if (amount > 99999999.99) {
        return `${label} supera el máximo permitido.`;
    }

    return '';
}

function validateClientField(field) {
    const value = field.value;
    const trimmed = value.trim();

    if (['nombres', 'apellidos'].includes(field.name)) {
        if (!trimmed) return 'Este campo es obligatorio.';
        if (hasEmoji(value)) return 'Este campo no permite emojis.';
        if (!namePattern.test(value)) return 'Solo se permiten letras y espacios; no se permiten números ni símbolos.';
        if (value.length > 255) return 'No puede superar los 255 caracteres.';
        return '';
    }

    if (field.name === 'cedula') {
        if (!value) return 'La cédula es obligatoria.';
        if (hasEmoji(value)) return 'La cédula no permite emojis.';
        if (!/^[0-9]+$/.test(value)) return 'La cédula solo puede contener números, sin espacios ni símbolos.';
        if (!/^[0-9]{10}$/.test(value)) return 'La cédula debe contener exactamente 10 números.';
        if (!validEcuadorianCedula(value)) return 'La cédula ecuatoriana no supera la validación del dígito verificador.';
        return '';
    }

    if (field.name === 'telefono') {
        if (!value) return '';
        if (hasEmoji(value)) return 'El teléfono no permite emojis.';
        if (!/^[0-9]+$/.test(value)) return 'El teléfono solo puede contener números.';
        if (!/^[0-9]{10}$/.test(value)) return 'El teléfono debe contener exactamente 10 números.';
        return '';
    }

    if (field.name === 'email') {
        if (!value) return 'El correo electrónico es obligatorio.';
        if (hasEmoji(value)) return 'El correo electrónico no permite emojis.';
        if (/\s/.test(value)) return 'El correo electrónico no puede contener espacios.';
        if (value.length > 255 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            return 'El correo electrónico no tiene un formato válido.';
        }
        return '';
    }

    if (field.name === 'direccion') {
        if (!value) return '';
        if (hasEmoji(value)) return 'La dirección no permite emojis.';
        if (!addressPattern.test(value)) return 'La dirección solo permite letras, números, espacios, comas, puntos y guiones.';
        if (value.length > 255) return 'La dirección no puede superar los 255 caracteres.';
    }

    return '';
}

function validateDeclarationField(field, options) {
    const value = field.value;

    if (field.name === 'cliente_id') {
        return value ? '' : 'Selecciona un cliente.';
    }

    if (field.name === 'numero_declaracion') {
        if (!value) return 'El número de declaración es obligatorio.';
        if (hasEmoji(value)) return 'El número de declaración no permite emojis.';
        if (!/^DA-[0-9]{3}$/.test(value)) return 'Usa el formato DA-001: prefijo DA-, guion y 3 números, sin espacios.';
        return '';
    }

    if (field.name === 'tipo') {
        return options.tipos.has(value) ? '' : 'Selecciona un tipo de declaración permitido.';
    }

    if (['valor_mercancia', 'monto'].includes(field.name)) {
        if (!value) return 'Este campo es obligatorio.';
        if (hasEmoji(value)) return 'Este campo no permite emojis.';
        return positiveMoney(value, field.name === 'monto' ? 'El monto' : 'El valor de mercancía');
    }

    if (field.name === 'metodo_pago') {
        return options.metodos.has(value) ? '' : 'Selecciona un método de pago permitido.';
    }

    if (field.name === 'estado') {
        return options.estados.has(value) ? '' : 'Selecciona un estado de pago permitido.';
    }

    return '';
}

export function attachFieldValidation(form, validateField, { normalizeNames = false } = {}) {
    const fields = [...form.querySelectorAll('[data-validate]')];
    const summary = form.querySelector('[data-form-errors]');

    function showFieldError(field, message) {
        const errorElement = form.querySelector(`[data-error-for="${field.name}"]`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.hidden = !message;
        }

        field.classList.toggle('is-invalid', Boolean(message));
        field.classList.toggle('is-valid', !message && Boolean(field.value));
        field.setAttribute('aria-invalid', String(Boolean(message)));
        if (errorElement?.id) {
            field.setAttribute('aria-describedby', errorElement.id);
        }
    }

    function clearSummary() {
        if (summary) {
            summary.textContent = '';
            summary.hidden = true;
        }
    }

    function normalize(field) {
        if (normalizeNames && ['nombres', 'apellidos'].includes(field.name)) {
            field.value = field.value.normalize('NFC').trim().replace(/ +/g, ' ');
        }
    }

    function validateOne(field) {
        const message = validateField(field);
        showFieldError(field, message);
        return !message;
    }

    fields.forEach((field) => {
        field.addEventListener('input', () => {
            clearSummary();
            validateOne(field);
        });
        field.addEventListener('change', () => {
            clearSummary();
            validateOne(field);
        });
        field.addEventListener('blur', () => {
            normalize(field);
            validateOne(field);
        });
    });

    return {
        validateAll() {
            clearSummary();
            fields.forEach(normalize);
            const firstInvalid = fields.find((field) => !validateOne(field));
            if (firstInvalid) {
                firstInvalid.focus();
                return false;
            }

            return true;
        },
        showServerErrors(errors) {
            clearSummary();
            let firstInvalid = null;
            const unassigned = [];

            Object.entries(errors || {}).forEach(([name, messages]) => {
                const field = fields.find((item) => item.name === name);
                const message = Array.isArray(messages) ? messages[0] : messages;

                if (field) {
                    showFieldError(field, message);
                    firstInvalid ||= field;
                } else if (message) {
                    unassigned.push(message);
                }
            });

            if (unassigned.length && summary) {
                summary.textContent = unassigned.join(' ');
                summary.hidden = false;
            }

            firstInvalid?.focus();
        },
        showSummary(message) {
            if (summary) {
                summary.textContent = message;
                summary.hidden = !message;
            }
        },
    };
}

export function clientFieldValidator(field) {
    return validateClientField(field);
}

export function declarationFieldValidator(options) {
    return (field) => validateDeclarationField(field, options);
}

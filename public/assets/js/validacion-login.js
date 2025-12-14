const formLogin = document.getElementById('formLogin');
const nombre = document.getElementById('nombre');
const contrasena = document.getElementById('contrasena');

// Patrón para validar nombre: solo letras, 3-40 caracteres
const patronNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}$/;

// Patrón para validar contraseña: mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
const patronContrasena = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;

// Validación de nombre: solo letras, 3-40 caracteres
if (nombre) {
    nombre.addEventListener('blur', () => {
        if (nombre.value.trim() && !patronNombre.test(nombre.value)) {
            nombre.classList.add('is-invalid');
            nombre.classList.remove('is-valid');
        } else if (nombre.value.trim()) {
            nombre.classList.remove('is-invalid');
            nombre.classList.add('is-valid');
        } else {
            nombre.classList.remove('is-invalid', 'is-valid');
        }
    });

    nombre.addEventListener('input', () => {
        if (nombre.classList.contains('is-invalid')) {
            if (nombre.value.trim() && patronNombre.test(nombre.value)) {
                nombre.classList.remove('is-invalid');
                nombre.classList.add('is-valid');
            }
        }
    });
}

// Validación de contraseña: mínimo 8 caracteres, mayúscula, minúscula y número
if (contrasena) {
    contrasena.addEventListener('blur', () => {
        if (contrasena.value && !patronContrasena.test(contrasena.value)) {
            contrasena.classList.add('is-invalid');
            contrasena.classList.remove('is-valid');
        } else if (contrasena.value) {
            contrasena.classList.remove('is-invalid');
            contrasena.classList.add('is-valid');
        } else {
            contrasena.classList.remove('is-invalid', 'is-valid');
        }
    });

    contrasena.addEventListener('input', () => {
        if (contrasena.classList.contains('is-invalid')) {
            if (contrasena.value && patronContrasena.test(contrasena.value)) {
                contrasena.classList.remove('is-invalid');
                contrasena.classList.add('is-valid');
            }
        }
    });
}

// Validación del formulario al enviar
if (formLogin) {
    formLogin.addEventListener('submit', (e) => {
        e.preventDefault();

        let esValido = true;

        // Validar nombre
        if (!nombre.value.trim() || !patronNombre.test(nombre.value)) {
            nombre.classList.add('is-invalid');
            nombre.classList.remove('is-valid');
            esValido = false;
        } else {
            nombre.classList.remove('is-invalid');
            nombre.classList.add('is-valid');
        }

        // Validar contraseña
        if (!contrasena.value || !patronContrasena.test(contrasena.value)) {
            contrasena.classList.add('is-invalid');
            contrasena.classList.remove('is-valid');
            esValido = false;
        } else {
            contrasena.classList.remove('is-invalid');
            contrasena.classList.add('is-valid');
        }

        if (esValido) {
            formLogin.submit();
        }
    });
}

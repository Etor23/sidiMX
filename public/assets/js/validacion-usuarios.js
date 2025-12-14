const formUsuario = document.getElementById('formUsuario');
const nombre = document.getElementById('nombre');
const idRoles = document.getElementById('idRoles');
const contrasena = document.getElementById('contrasena');
const contrasenaConfirm = document.getElementById('contrasenaConfirm');

// Patrón para validar contraseña: mínimo 8 caracteres, al menos una mayúscula, una minúscula y un número
const patronContrasena = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;

// Variable para controlar el proceso de validación de nombre
let validandoNombre = false;

// Detectar si estamos en modo edición
const isEdit = formUsuario && formUsuario.getAttribute('data-is-edit') === '1';

// Validación de nombre: solo letras, 3-40 caracteres
if (nombre) {
    nombre.addEventListener('blur', () => {
        const patronNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}$/;
        const feedbackDiv = document.getElementById('feedback_nombre');
        
        // Si está vacío, quitar clases
        if (!nombre.value.trim()) {
            nombre.classList.remove('is-invalid', 'is-valid');
            feedbackDiv.style.display = 'none';
            return;
        }
        
        // Si el patrón no es válido, marcar como inválido
        if (!patronNombre.test(nombre.value)) {
            nombre.classList.add('is-invalid');
            nombre.classList.remove('is-valid');
            feedbackDiv.textContent = 'El nombre debe contener solo letras y tener entre 3 y 40 caracteres.';
            feedbackDiv.style.display = 'block';
            return;
        }
        
        // Si el patrón es válido, ya se encarga validarNombreEnBD
        // No hacer nada aquí, dejar que AJAX determine el estado
    });

    nombre.addEventListener('input', () => {
        const patronNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}$/;
        
        // Si está vacío, quitar clases
        if (!nombre.value.trim()) {
            nombre.classList.remove('is-invalid', 'is-valid');
            return;
        }
        
        // Si el patrón no es válido, marcar como inválido
        if (!patronNombre.test(nombre.value)) {
            nombre.classList.add('is-invalid');
            nombre.classList.remove('is-valid');
            document.getElementById('feedback_nombre').textContent = 'El nombre debe contener solo letras y tener entre 3 y 40 caracteres.';
            document.getElementById('feedback_nombre').style.display = 'block';
        } else {
            // Si el patrón es válido, validar en BD
            nombre.classList.remove('is-invalid', 'is-valid');
            validarNombreEnBD(nombre.value.trim());
        }
    });
}

// Función para validar nombre en la base de datos
function validarNombreEnBD(nombreValor) {
    // Si no hay valor o está validando actualmente, no hacer nada
    if (!nombreValor) {
        return;
    }
    
    // Si ya está en proceso de validación, salir
    if (validandoNombre) {
        return;
    }
    
    validandoNombre = true; // inicia proceso de validación
    
    const idUsuario = formUsuario.querySelector('input[name="id"]')?.value || 0;
    
    fetch("/usuarios/validarNombre", {
        method: "POST",
        headers: {
            "Content-type": "application/json",
        },
        body: JSON.stringify({ nombre: nombreValor, id_usuario: idUsuario }),
    })
        .then((response) => response.json())
        .then((data) => {
            validandoNombre = false; // proceso fetch terminado
            
            // Solo actualizar si el valor no ha cambiado
            if (nombre.value.trim() !== nombreValor) {
                return; // El usuario ya cambió el valor
            }
            
            const feedbackDiv = document.getElementById('feedback_nombre');
            nombre.classList.remove('is-valid', 'is-invalid');
            
            if (data.existe) {
                nombre.classList.add("is-invalid");
                feedbackDiv.textContent = "Este nombre de usuario ya existe";
                feedbackDiv.style.display = 'block';
            } else {
                nombre.classList.add("is-valid");
                feedbackDiv.style.display = 'none';
            }
        })
        .catch((error) => {
            console.error("Error validando nombre:", error);
            validandoNombre = false; // proceso terminado con error
        });
}

// Validación de rol: debe estar seleccionado
if (idRoles) {
    idRoles.addEventListener('change', () => {
        if (idRoles.value === '') {
            idRoles.classList.add('is-invalid');
            idRoles.classList.remove('is-valid');
        } else {
            idRoles.classList.remove('is-invalid');
            idRoles.classList.add('is-valid');
        }
    });
}

// Validación de contraseña: mínimo 8 caracteres, mayúscula, minúscula y número
if (contrasena) {
    contrasena.addEventListener('blur', () => {
        if (contrasena.value && !patronContrasena.test(contrasena.value)) {
            contrasena.classList.add('is-invalid');
            contrasena.classList.remove('is-valid');
        } else if (contrasena.value && patronContrasena.test(contrasena.value)) {
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

// Validación de contraseñas: deben coincidir
function validarContraseñas() {
    if (contrasena.value || contrasenaConfirm.value) {
        if (contrasena.value === contrasenaConfirm.value && contrasena.value && patronContrasena.test(contrasena.value)) {
            contrasena.classList.remove('is-invalid');
            contrasena.classList.add('is-valid');
            contrasenaConfirm.classList.remove('is-invalid');
            contrasenaConfirm.classList.add('is-valid');
        } else {
            if (contrasena.value && !patronContrasena.test(contrasena.value)) {
                contrasena.classList.add('is-invalid');
                contrasena.classList.remove('is-valid');
            } else if (contrasena.value !== contrasenaConfirm.value) {
                contrasenaConfirm.classList.add('is-invalid');
                contrasenaConfirm.classList.remove('is-valid');
            }
        }
    } else {
        contrasena.classList.remove('is-invalid', 'is-valid');
        contrasenaConfirm.classList.remove('is-invalid', 'is-valid');
    }
}

if (contrasena) {
    contrasena.addEventListener('blur', validarContraseñas);
    contrasena.addEventListener('input', validarContraseñas);
}

if (contrasenaConfirm) {
    contrasenaConfirm.addEventListener('blur', validarContraseñas);
    contrasenaConfirm.addEventListener('input', validarContraseñas);
}

// Validación del formulario al enviar
if (formUsuario) {
    formUsuario.addEventListener('submit', (e) => {
        e.preventDefault();

        let esValido = true;

        // Validar nombre
        const patronNombre = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,40}$/;
        if (!nombre.value.trim() || !patronNombre.test(nombre.value)) {
            nombre.classList.add('is-invalid');
            nombre.classList.remove('is-valid');
            esValido = false;
        } else if (nombre.classList.contains('is-invalid')) {
            // Si tiene clase is-invalid significa que ya existe en BD
            esValido = false;
        } else {
            nombre.classList.remove('is-invalid');
            nombre.classList.add('is-valid');
        }

        // Validar rol
        if (idRoles.value === '') {
            idRoles.classList.add('is-invalid');
            idRoles.classList.remove('is-valid');
            esValido = false;
        } else {
            idRoles.classList.remove('is-invalid');
            idRoles.classList.add('is-valid');
        }

        // Validar contraseñas
        if (!contrasena.value && !contrasenaConfirm.value) {
            // Si ambas están vacías y es edición, permitir
            if (!isEdit) {
                contrasena.classList.add('is-invalid');
                contrasena.classList.remove('is-valid');
                esValido = false;
            }
        } else if (!patronContrasena.test(contrasena.value) || contrasena.value !== contrasenaConfirm.value) {
            contrasena.classList.add('is-invalid');
            contrasena.classList.remove('is-valid');
            contrasenaConfirm.classList.add('is-invalid');
            contrasenaConfirm.classList.remove('is-valid');
            esValido = false;
        } else {
            contrasena.classList.remove('is-invalid');
            contrasena.classList.add('is-valid');
            contrasenaConfirm.classList.remove('is-invalid');
            contrasenaConfirm.classList.add('is-valid');
        }

        if (esValido) {
            formUsuario.submit();
        }
    });
}

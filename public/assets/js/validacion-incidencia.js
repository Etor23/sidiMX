// Validación para formulario de incidencias
// Estructura similar a otras validaciones

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formIncidencia');
  if (!form) return;

  const validaciones = {
    pIdTiposIncidencia: {
      msg: 'Seleccione un tipo de incidencia.',
    },
    pDescripcion: {
      regex: /^.{3,200}$/,
      msg: 'La descripción debe tener entre 3 y 200 caracteres.',
    },
  };

  function limpiar(grupo, campo) {
    campo.classList.remove('is-valid', 'is-invalid');
    const fb = grupo.querySelectorAll('.valid-feedback, .invalid-feedback');
    fb.forEach((el) => el.remove());
  }

  function mostrarMensaje(grupo, texto, tipo) {
    const divmsg = document.createElement('div');
    divmsg.className = `${tipo}-feedback d-block`;
    divmsg.textContent = texto;
    grupo.appendChild(divmsg);
  }

  function validar() {
    const campo = this;
    const id = campo.id;
    const config = validaciones[id];
    if (!config) return true;

    const grupo = document.getElementById(`grupo_${id}`) || campo.parentElement;
    limpiar(grupo, campo);

    const valor = campo.value ? campo.value.trim() : '';
    let esValido = false;

    if (config.regex) {
      esValido = valor !== '' && config.regex.test(valor);
    } else {
      esValido = valor !== '';
    }

    const mensaje = config.msg;

    if (esValido) {
      campo.classList.add('is-valid');
    } else {
      campo.classList.add('is-invalid');
      mostrarMensaje(grupo, mensaje, 'invalid');
    }
    return esValido;
  }

  Object.keys(validaciones).forEach((id) => {
    const campo = document.getElementById(id);
    if (campo) {
      campo.addEventListener('change', validar);
      campo.addEventListener('blur', validar);
      if (campo.tagName === 'TEXTAREA') {
        campo.addEventListener('keyup', validar);
      }
    }
  });

  form.addEventListener('submit', (e) => {
    let esValido = true;
    Object.keys(validaciones).forEach((id) => {
      const campo = document.getElementById(id);
      if (campo && !validar.call(campo)) {
        esValido = false;
      }
    });

    if (!esValido) {
      e.preventDefault();
      alert('Corrija los errores antes de enviar la incidencia.');
    }
  });
});

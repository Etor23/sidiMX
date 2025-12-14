// Validación para formulario de POD

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formPOD');
  if (!form) return;

  const campos = {
    pReceptor: {
      regex: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,40}$/,
      mensaje: 'El receptor solo puede contener letras (3 a 40 caracteres).',
    },
    pCondicion: {
      validar: (valor) => valor !== '',
      mensaje: 'Seleccione una condición válida.',
    },
    pObservaciones: {
      validar: (valor) => {
        const longitud = valor.trim().length;
        return longitud >= 0 && longitud <= 200;
      },
      mensaje: 'Las observaciones no pueden exceder 200 caracteres.',
    },
  };

  function limpiarEstado(grupo, campo) {
    campo.classList.remove('is-valid', 'is-invalid');
    const feedback = grupo.querySelectorAll('.valid-feedback, .invalid-feedback');
    feedback.forEach((fb) => fb.remove());
  }

  function mostrarMensaje(grupo, texto, tipo = 'invalid') {
    const div = document.createElement('div');
    div.className = `${tipo}-feedback d-block`;
    div.textContent = texto;
    grupo.appendChild(div);
  }

  function validarCampo(campo) {
    const id = campo.id;
    const cfg = campos[id];
    if (!cfg) return true;

    const grupo = document.getElementById(`grupo_${id}`) || campo.parentElement;
    limpiarEstado(grupo, campo);

    let valor = campo.value || '';

    let esValido = false;
    if (cfg.regex) {
      esValido = cfg.regex.test(valor);
    } else if (typeof cfg.validar === 'function') {
      esValido = cfg.validar(valor);
    }

    if (esValido) {
      campo.classList.add('is-valid');
    } else {
      campo.classList.add('is-invalid');
      mostrarMensaje(grupo, cfg.mensaje);
    }

    return esValido;
  }

  Object.keys(campos).forEach((id) => {
    const campo = document.getElementById(id);
    if (!campo) return;
    const eventos = campo.tagName === 'SELECT' ? ['change', 'blur'] : ['input', 'blur'];
    eventos.forEach((ev) => campo.addEventListener(ev, () => validarCampo(campo)));
  });

  form.addEventListener('submit', (e) => {
    let valido = true;
    Object.keys(campos).forEach((id) => {
      const campo = document.getElementById(id);
      if (campo && !validarCampo(campo)) {
        valido = false;
      }
    });

    if (!valido) {
      e.preventDefault();
      alert('Por favor corrija los errores del formulario de POD.');
    }
  });
});

// Validación para formulario de Retiro

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRetiro');
  if (!form) return;

  const campos = {
    pUnidad: {
      regex: /^[A-Za-z0-9\s\-_./()]{3,20}$/,
      mensaje: 'La unidad debe contener letras, números y signos básicos (3 a 20 caracteres).',
    },
    pPlacas: {
      normalizar: (valor) => valor.toUpperCase().slice(0, 10),
      regex: /^[A-Z0-9\-]{5,10}$/,
      mensaje: 'Las placas deben contener letras mayúsculas, números y guiones (5 a 10 caracteres).',
    },
    pOperador: {
      regex: /^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,40}$/,
      mensaje: 'El conductor solo puede contener letras (3 a 40 caracteres).',
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
    if (typeof cfg.normalizar === 'function') {
      const nuevoValor = cfg.normalizar(valor);
      if (nuevoValor !== campo.value) {
        campo.value = nuevoValor;
      }
      valor = nuevoValor;
    }

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
    campo.addEventListener('input', () => validarCampo(campo));
    campo.addEventListener('blur', () => validarCampo(campo));
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
      alert('Por favor corrija los errores del formulario de retiro.');
    }
  });
});

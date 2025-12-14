// Validación para formulario de Liberación

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formLiberacion');
  if (!form) return;

  const campos = {
    pObservaciones: {
      validar: (valor) => {
        const longitud = valor.trim().length;
        return longitud >= 3 && longitud <= 200;
      },
      mensaje: 'Las observaciones deben tener entre 3 y 200 caracteres.',
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

    const valor = campo.value || '';
    const esValido = cfg.validar(valor);

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
      alert('Por favor corrija los errores del formulario de liberación.');
    }
  });
});

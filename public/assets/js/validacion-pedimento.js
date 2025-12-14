// Validación para formulario de Pedimento

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formPedimento');
  if (!form) return;

  const regimenesValidos = [
    'Importación definitiva',
    'Importación temporal',
    'Devolucion',
  ];

  const campos = {
    pRegimen: {
      validar: (valor) => regimenesValidos.includes(valor),
      mensaje: 'Seleccione un régimen válido.',
    },
    pPatente: {
      regex: /^\d{4}$/,
      mensaje: 'La patente debe tener 4 dígitos numéricos.',
      normalizar: (valor) => valor.replace(/\D/g, '').slice(0, 4),
    },
    pNumero: {
      regex: /^\d{15}$/,
      mensaje: 'El número de pedimento debe tener exactamente 15 dígitos.',
      normalizar: (valor) => valor.replace(/\D/g, '').slice(0, 15),
    },
  };

  function limpiarEstado(grupo, campo) {
    campo.classList.remove('is-valid', 'is-invalid');
    const feedback = grupo.querySelectorAll('.valid-feedback, .invalid-feedback');
    feedback.forEach((n) => n.remove());
  }

  function mostrarMensaje(grupo, texto, tipo = 'invalid') {
    const div = document.createElement('div');
    div.className = `${tipo}-feedback d-block`;
    div.textContent = texto;
    grupo.appendChild(div);
  }

  function validarCampo(campo) {
    const id = campo.id;
    const config = campos[id];
    if (!config) return true;

    const grupo = document.getElementById(`grupo_${id}`) || campo.parentElement;
    limpiarEstado(grupo, campo);

    let valor = campo.value.trim();
    if (typeof config.normalizar === 'function') {
      const nuevoValor = config.normalizar(valor);
      if (nuevoValor !== campo.value) {
        campo.value = nuevoValor;
      }
      valor = nuevoValor;
    }

    let esValido = false;
    if (config.regex) {
      esValido = config.regex.test(valor);
    } else if (typeof config.validar === 'function') {
      esValido = config.validar(valor);
    }

    if (esValido) {
      campo.classList.add('is-valid');
    } else {
      campo.classList.add('is-invalid');
      mostrarMensaje(grupo, config.mensaje, 'invalid');
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
    let formularioValido = true;
    Object.keys(campos).forEach((id) => {
      const campo = document.getElementById(id);
      if (campo && !validarCampo(campo)) {
        formularioValido = false;
      }
    });

    if (!formularioValido) {
      e.preventDefault();
      alert('Por favor corrija los errores del formulario de pedimento.');
    }
  });
});

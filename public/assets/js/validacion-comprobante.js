// Validación para formulario de Comprobante

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formComprobante');
  if (!form) return;

  const campos = {
    pNumero: {
      normalizar: (valor) => valor.replace(/\D/g, '').slice(0, 20),
      regex: /^\d{10,20}$/,
      mensaje: 'El número de cuenta debe tener entre 10 y 20 dígitos.',
    },
    pEmisor: {
      regex: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s&/\-.()]{3,100}$/,
      mensaje: 'El emisor solo puede contener letras, espacios y & / - . () (3 a 100 caracteres).',
    },
    pMoneda: {
      validar: (valor) => valueIsNonEmpty(valor),
      mensaje: 'Seleccione una moneda.',
    },
    pTotal: {
      validar: (valor) => {
        if (valor === '' || valor === null) return false;
        const num = Number(valor);
        return !Number.isNaN(num) && num >= 0 && num <= 999999999.99;
      },
      mensaje: 'Ingrese un total válido (0 - 999,999,999.99).',
    },
  };

  function valueIsNonEmpty(value) {
    return value !== null && value !== undefined && String(value).trim() !== '';
  }

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
      alert('Por favor corrija los errores del formulario de comprobante.');
    }
  });
});

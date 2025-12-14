// Validación para formulario de Recepción
// Estructura similar a validacion-guia.js

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRecepcion');
  if (!form) return;

  // Manejo del checkbox de confirmación
  const check = document.getElementById('confirmCheck');
  const submitBtn = document.getElementById('submitBtn');
  if (check && submitBtn) {
    submitBtn.disabled = !check.checked;
    check.addEventListener('change', () => {
      submitBtn.disabled = !check.checked;
    });
  }

  const validaciones = {
    pCantidad: {
      regex: /^\d+$/,
      msg: 'La cantidad debe ser un número entero mayor que 0.',
    },
    pPesoNeto: {
      regex: /^\d+(\.\d+)?$/,
      msg: 'El peso neto debe ser un número mayor que 0.',
    },
    pAncho: {
      regex: /^\d+(\.\d+)?$/,
      msg: 'El ancho debe ser un número mayor que 0.',
    },
    pAlto: {
      regex: /^\d+(\.\d+)?$/,
      msg: 'El alto debe ser un número mayor que 0.',
    },
    pLargo: {
      regex: /^\d+(\.\d+)?$/,
      msg: 'El largo debe ser un número mayor que 0.',
    },
    pVolumen: {
      regex: /^\d+(\.\d+)?$/,
      msg: 'El volumen debe ser un número mayor que 0.',
    },
  };

  // Helpers
  function mostrarMensaje(grupo, texto, tipo) {
    const divmsg = document.createElement('div');
    divmsg.className = `${tipo}-feedback d-block`;
    divmsg.textContent = texto;
    grupo.appendChild(divmsg);
  }

  function limpiarEstado(grupo, campo) {
    campo.classList.remove('is-valid', 'is-invalid');
    const feedBack = grupo.querySelectorAll('.valid-feedback, .invalid-feedback');
    feedBack.forEach((fb) => fb.remove());
    const textDanger = grupo.querySelector('.text-danger');
    if (textDanger) textDanger.textContent = '';
  }

  function validar() {
    const campo = this;
    if (campo.disabled) return true; // No validar campos deshabilitados

    const id = campo.id;
    const config = validaciones[id];
    if (!config) return true;

    const grupo = document.getElementById(`grupo_${id}`) || campo.parentElement;
    limpiarEstado(grupo, campo);

    let valor = campo.value ? campo.value.trim() : '';

    let esValido = false;
    if (config.regex) {
      esValido = valor !== '' && config.regex.test(valor);
    }

    // Validaciones numéricas adicionales
    if (esValido && (id === 'pCantidad' || id === 'pPesoNeto' || id === 'pVolumen' || id === 'pAncho' || id === 'pAlto' || id === 'pLargo')) {
      const numero = parseFloat(valor);
      if (numero <= 0) {
        esValido = false;
      }

      if (esValido && id === 'pVolumen') {
        const ancho = parseFloat(document.getElementById('pAncho')?.value || 0);
        const alto = parseFloat(document.getElementById('pAlto')?.value || 0);
        const largo = parseFloat(document.getElementById('pLargo')?.value || 0);
        const volumenMaximo = ancho * alto * largo;
        if (numero > volumenMaximo) {
          esValido = false;
          mostrarMensaje(grupo, `El volumen no puede ser mayor que ${volumenMaximo.toFixed(2)} cm³ (Ancho × Alto × Largo).`, 'invalid');
          campo.classList.add('is-invalid');
          return false;
        }
      }
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

  // Habilitar/deshabilitar volumen según dimensiones
  function verificarDimensionesParaVolumen() {
    const pAncho = document.getElementById('pAncho');
    const pAlto = document.getElementById('pAlto');
    const pLargo = document.getElementById('pLargo');
    const pVolumen = document.getElementById('pVolumen');

    if (pAncho && pAlto && pLargo && pVolumen) {
      const anchoValido = pAncho.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pAncho.value.trim()) && parseFloat(pAncho.value) > 0;
      const altoValido = pAlto.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pAlto.value.trim()) && parseFloat(pAlto.value) > 0;
      const largoValido = pLargo.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pLargo.value.trim()) && parseFloat(pLargo.value) > 0;

      if (anchoValido && altoValido && largoValido) {
        pVolumen.disabled = false;
      } else {
        pVolumen.disabled = true;
        pVolumen.value = '';
        pVolumen.classList.remove('is-valid', 'is-invalid');
        const grupoVolumen = document.getElementById('grupo_pVolumen') || pVolumen.parentElement;
        limpiarEstado(grupoVolumen, pVolumen);
      }
    }
  }

  // Asignar listeners a cada campo configurado
  Object.keys(validaciones).forEach((id) => {
    const campo = document.getElementById(id);
    if (campo) {
      campo.addEventListener('keyup', validar);
      campo.addEventListener('blur', validar);
      if (campo.type === 'number') {
        campo.addEventListener('change', validar);
      }
    }
  });

  // Listeners para dimensiones
  ['pAncho', 'pAlto', 'pLargo'].forEach((id) => {
    const campo = document.getElementById(id);
    if (campo) {
      campo.addEventListener('input', verificarDimensionesParaVolumen);
      campo.addEventListener('blur', verificarDimensionesParaVolumen);
    }
  });

  // Verificar al cargar
  verificarDimensionesParaVolumen();

  // Submit del formulario
  form.addEventListener('submit', function(e) {
    let esValido = true;
    Object.keys(validaciones).forEach((id) => {
      const campo = document.getElementById(id);
      if (campo && !validar.call(campo)) {
        esValido = false;
      }
    });

    if (!esValido) {
      e.preventDefault();
      alert('Por favor corrija los errores en el formulario de recepción.');
    }
  });
});

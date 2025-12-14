// Crear el codigo para validacion

// Funciones globales para validar campos dinámicos de permisos
window.validarTipoPermiso = function() {
  const campo = this;
  const valor = campo.value.trim();
  const regex = /^[a-zA-Z0-9\s\.,;:\-]{5,50}$/;
  const grupo = campo.parentElement;
  
  campo.classList.remove('is-valid', 'is-invalid');
  const feedbackDiv = grupo.querySelector('.invalid-feedback');
  if (feedbackDiv) feedbackDiv.textContent = '';
  
  if (valor === '') {
    return true; // Permitir vacío para eliminar
  }
  
  const esValido = regex.test(valor);
  
  if (esValido) {
    campo.classList.add('is-valid');
  } else {
    campo.classList.add('is-invalid');
    if (feedbackDiv) {
      feedbackDiv.textContent = 'El tipo debe tener entre 5 y 50 caracteres, solo letras, números, espacios y signos básicos (.,;:-).';
      feedbackDiv.style.display = 'block';
    }
  }
  
  return esValido;
};

window.validarAutoridadPermiso = function() {
  const campo = this;
  const valor = campo.value.trim();
  const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s&\/\-\.,()]{3,30}$/;
  const grupo = campo.parentElement;
  
  campo.classList.remove('is-valid', 'is-invalid');
  const feedbackDiv = grupo.querySelector('.invalid-feedback');
  if (feedbackDiv) feedbackDiv.textContent = '';
  
  if (valor === '') {
    return true; // Permitir vacío (campo opcional)
  }
  
  const esValido = regex.test(valor);
  
  if (esValido) {
    campo.classList.add('is-valid');
  } else {
    campo.classList.add('is-invalid');
    if (feedbackDiv) {
      feedbackDiv.textContent = 'La autoridad debe tener entre 3 y 30 caracteres, solo letras, espacios y signos (&, /, -, ., ,, ()).';
      feedbackDiv.style.display = 'block';
    }
  }
  
  return esValido;
};

window.validarVigenciaPermiso = function() {
  const campo = this;
  const valor = campo.value.trim();
  const grupo = campo.parentElement;
  
  // Detectar si estamos en modo edición
  const form = document.getElementById('formCrearGuia');
  const isEdit = form && form.getAttribute('data-is-edit') === '1';
  
  campo.classList.remove('is-valid', 'is-invalid');
  const feedbackDiv = grupo.querySelector('.invalid-feedback');
  if (feedbackDiv) feedbackDiv.textContent = '';
  
  if (valor === '') {
    return true; // Permitir vacío (campo opcional)
  }
  
  // Omitir validación de vigencia en modo edición
  if (isEdit) {
    campo.classList.add('is-valid');
    return true;
  }
  
  const fechaSeleccionada = new Date(valor);
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const esValido = fechaSeleccionada > hoy;
  
  if (esValido) {
    campo.classList.add('is-valid');
  } else {
    campo.classList.add('is-invalid');
    if (feedbackDiv) {
      feedbackDiv.textContent = 'Seleccione una fecha posterior a la fecha actual.';
      feedbackDiv.style.display = 'block';
    }
  }
  
  return esValido;
};

// Variable para controlar el proceso de validación de guía externa
let validandoGuia = false;

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formCrearGuia"); // o
  // const form = document.querySelector('form');
  if (!form) {
    return;
  }
  
  // Detectar si estamos en modo edición
  const isEdit = form.getAttribute('data-is-edit') === '1';

  // prepara la configuraicon
  const validaciones = {
    pGuia: {
      regex: /^[A-Z0-9\-]{3,40}$/,
      msg: "El código de la guía debe tener entre 3 y 40 caracteres, solo letras mayúsculas, números y guiones.",
    },
    pMaster: {
      regex: /^[A-Z0-9\-]{3,40}$/,
      msg: "El código Master debe tener entre 3 y 40 caracteres, solo letras mayúsculas, números y guiones.",
    },
    pContenedor: {
      regex: /^[A-Z0-9\-]{3,40}$/,
      msg: "El código del contenedor debe tener entre 3 y 40 caracteres, solo letras mayúsculas, números y guiones.",
    },
    pOrigen: {
      regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ,\s]{3,40}$/,
      msg: "El origen debe tener entre 3 y 40 caracteres, solo letras y coma.",
    },
    pDestino: {
      regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ,\s]{3,40}$/,
      msg: "El destino debe tener entre 3 y 40 caracteres, solo letras y coma.",
    },
    pAduana: {
      regex: /^[a-zA-Z0-9\-\s]{2,20}$/,
      msg: "La aduana debe tener entre 2 y 20 caracteres, solo letras, números, guiones y espacios.",
    },
    pModo: {
      msg: "Seleccione un modo",
    },
    pIncoterm: {
      regex: /^[A-Z]{3}$/,
      msg: "El Incoterm debe tener exactamente 3 letras mayúsculas.",
    },
    pConsignatario: {
      regex: /^.{3,30}$/,
      msg: "El Consignatario debe tener entre 3 y 30 caracteres.",
    },
    pContacto: {
      regex: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
      msg: "Debe tener un formato de correo electrónico válido.",
    },
    pImportadorRfc: {
      regex: /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/,
      msg: "Debe tener un formato de RFC válido (12-13 caracteres).",    },
    pCertTratado: {
      regex: /^[A-Z0-9\s\-\/]{3,50}$/,
      msg: "El tratado debe tener entre 3 y 50 caracteres, solo mayúsculas, números, espacios, guiones y diagonales.",    },
    pCertVigencia: {
      msg: "Seleccione una fecha posterior a la fecha actual.",
    },
    pCantidad: {
      regex: /^\d+$/,
      msg: "La cantidad debe ser un número entero mayor que 0.",
    },
    pPesoNeto: {
      regex: /^\d+(\.\d+)?$/,
      msg: "El peso neto debe ser un número mayor que 0.",
    },
    pVolumen: {
      regex: /^\d+(\.\d+)?$/,
      msg: "El volumen debe ser un número mayor que 0.",
    },
    pAncho: {
      regex: /^\d+(\.\d+)?$/,
      msg: "El ancho debe ser un número mayor que 0.",
    },
    pAlto: {
      regex: /^\d+(\.\d+)?$/,
      msg: "El alto debe ser un número mayor que 0.",
    },
    pLargo: {
      regex: /^\d+(\.\d+)?$/,
      msg: "El largo debe ser un número mayor que 0.",
    },
    pEtd: {
      msg: "Debe ser una fecha válida y anterior a la fecha de entrada a México.",
    },
    pEta: {
      msg: "Debe ser una fecha válida y debe ser hoy o anterior.",
    }
  };

  Object.keys(validaciones).forEach((id) => {
    const campo = document.getElementById(id);

    if (campo) {
      campo.addEventListener("keyup", validar);
      campo.addEventListener("blur", validar);
      // Para campos de fecha, también escuchar evento change
      if (campo.type === "date") {
        campo.addEventListener("change", validar);
      }
    }
  }); // fin de Object
  
  // Agregar listener especial: cuando cambie pEta, revalidar pEtd
  const pEta = document.getElementById('pEta');
  const pEtd = document.getElementById('pEtd');
  if (pEta && pEtd) {
    pEta.addEventListener('change', function() {
      if (pEtd.value) {
        validar.call(pEtd);
      }
    });
  }

  function validarGuiaExterna(campo, valor, grupo) {
    // Si no hay valor o está validando actualmente, no hacer nada
    if (!valor) {
      return;
    }
    
    // Si ya está en proceso de validación o es modo edición, salir
    if (validandoGuia || isEdit) {
      return;
    }
    
    validandoGuia = true; // inicia proceso de validación
    
    const idGuia = form.querySelector('input[name="pIdGuia"]')?.value || 0;
    
    fetch("/guias/validarGuiaExterna", {
      method: "POST",
      headers: {
        "Content-type": "application/json",
      },
      body: JSON.stringify({ guia_externa: valor, id_guia: idGuia }),
    })
      .then((response) => response.json())
      .then((data) => {
        validandoGuia = false; // proceso fetch terminado
        
        // Solo actualizar si el valor no ha cambiado
        if (campo.value.trim().toUpperCase() !== valor) {
          return; // El usuario ya cambió el valor
        }
        
        // Limpiar mensajes previos
        const feedbackDivs = grupo.querySelectorAll('.valid-feedback, .invalid-feedback');
        feedbackDivs.forEach(fb => fb.remove());
        campo.classList.remove('is-valid', 'is-invalid');
        
        if (data.existe) {
          campo.classList.add("is-invalid");
          mostrarMensaje(grupo, "Esta guía externa ya existe", "invalid");
        } else {
          campo.classList.add("is-valid");
          mostrarMensaje(grupo, "Guía disponible", "valid");
        }
      })
      .catch((error) => {
        console.error("Error validando guía:", error);
        validandoGuia = false; // proceso terminado con error
      });
  }

  function validar() {
    const campo = this;
    const id = campo.id;
    const config = validaciones[id];

    if (!config) return true;

    // Normaliza valor (pGuia, pMaster, pContenedor, pIncoterm, pImportadorRfc y pCertTratado en mayúsculas)
    let valor = campo.value ? campo.value.trim() : "";
    if (id === "pGuia" || id === "pMaster" || id === "pContenedor" || id === "pIncoterm" || id === "pImportadorRfc" || id === "pCertTratado") {
      valor = valor.toUpperCase();
      campo.value = valor;
    }

    // Buscar grupo contenedor y limpiar estado previo
    const grupo = document.getElementById(`grupo_${id}`) || campo.parentElement;
    campo.classList.remove("is-valid", "is-invalid");
    const feedBack = grupo.querySelectorAll(".valid-feedback, .invalid-feedback");
    feedBack.forEach((fb) => fb.remove());
    const textDanger = grupo.querySelector(".text-danger");
    if (textDanger) textDanger.textContent = "";

    // Validar contra regex (si existe), validación de campo vacío para selects, o fecha futura
    let esValido = false;
    if (config.regex) {
      esValido = valor !== "" && config.regex.test(valor);
    } else if (id === "pCertVigencia") {
      // Validación especial para fecha futura - omitir en modo edición
      if (isEdit) {
        esValido = true; // No validar vigencia en modo edición
      } else if (valor === "") {
        esValido = false;
      } else {
        const fechaSeleccionada = new Date(valor);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas
        esValido = fechaSeleccionada > hoy;
      }
    } else if (id === "pEta") {
      // Fecha de entrada a México: debe ser hoy o anterior
      if (valor === "") {
        esValido = false;
      } else {
        const fechaEntrada = new Date(valor);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        esValido = fechaEntrada <= hoy;
        if (!esValido) {
          config.msg = "La fecha de entrada a México debe ser hoy o anterior.";
        }
      }
    } else if (id === "pEtd") {
      // Fecha de salida: debe ser anterior a fecha de entrada a México
      if (valor === "") {
        esValido = false;
      } else {
        const fechaSalida = new Date(valor);
        const pEta = document.getElementById('pEta');
        if (pEta && pEta.value) {
          const fechaEntrada = new Date(pEta.value);
          esValido = fechaSalida < fechaEntrada;
          if (!esValido) {
            config.msg = "La fecha de salida debe ser anterior a la fecha de entrada a México.";
          }
        }
      }
    } else {
      // Para selects u otros campos sin regex
      esValido = valor !== "";
    }
    
    // Validaciones adicionales para campos numéricos
    if (esValido && (id === "pCantidad" || id === "pPesoNeto" || id === "pVolumen" || id === "pAncho" || id === "pAlto" || id === "pLargo")) {
      const numero = parseFloat(valor);
      if (numero <= 0) {
        esValido = false;
        config.msg = config.msg.replace("válido", "mayor que 0");
      }
      
      // Validación específica para Volumen: debe ser <= (Ancho × Alto × Largo)
      if (esValido && id === "pVolumen") {
        const ancho = parseFloat(document.getElementById('pAncho')?.value || 0);
        const alto = parseFloat(document.getElementById('pAlto')?.value || 0);
        const largo = parseFloat(document.getElementById('pLargo')?.value || 0);
        const volumenMaximo = ancho * alto * largo;
        
        if (numero > volumenMaximo) {
          esValido = false;
          config.msg = `El volumen no puede ser mayor que ${volumenMaximo.toFixed(2)} cm³ (Ancho × Alto × Largo) o menor que 1.`;
        }
      }
    }
    
    const mensaje = config.msg;

    if (esValido) {
      campo.classList.add("is-valid");
      
      // Si el campo es pGuia y pasó el regex, hacer validación de duplicado
      if (id === "pGuia") {
        validarGuiaExterna(campo, valor, grupo);
      }
    } else {
      campo.classList.add("is-invalid");
      mostrarMensaje(grupo, mensaje, "invalid");
    }
    return esValido; // uso en submit
  }

  // funcion mostarMensaje
  function mostrarMensaje(grupo, texto, tipo) {
    const divmsg = document.createElement("div");
    divmsg.className = `${tipo}-feedback d-block`;
    divmsg.textContent = texto;
    grupo.appendChild(divmsg);
  }

  // Lógica para habilitar/deshabilitar campo Volumen según Ancho, Alto y Largo
  function verificarDimensionesParaVolumen() {
    const pAncho = document.getElementById('pAncho');
    const pAlto = document.getElementById('pAlto');
    const pLargo = document.getElementById('pLargo');
    const pVolumen = document.getElementById('pVolumen');
    
    if (pAncho && pAlto && pLargo && pVolumen) {
      const anchoValido = pAncho.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pAncho.value.trim());
      const altoValido = pAlto.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pAlto.value.trim());
      const largoValido = pLargo.value.trim() !== '' && /^\d+(\.\d+)?$/.test(pLargo.value.trim());
      
      if (anchoValido && altoValido && largoValido) {
        pVolumen.disabled = false;
      } else {
        pVolumen.disabled = true;
        pVolumen.value = '';
        pVolumen.classList.remove('is-valid', 'is-invalid');
      }
    }
  }
  
  // Agregar listeners a los campos de dimensiones
  ['pAncho', 'pAlto', 'pLargo'].forEach(id => {
    const campo = document.getElementById(id);
    if (campo) {
      campo.addEventListener('input', verificarDimensionesParaVolumen);
      campo.addEventListener('blur', verificarDimensionesParaVolumen);
    }
  });
  
  // Verificar al cargar la página
  verificarDimensionesParaVolumen();


     // Evento submit del formulario ========================> CUANDO SE ENVIA EL Formulario <================
     // en el form de create agregar el argumento ==> novalidate <==   <form action... method=.. etc novalidate>
    form.addEventListener('submit', function(e) {
      e.preventDefault(); // omite propagacion

      let esValido = true;
      Object.keys(validaciones).forEach((id) => {
        const campo = document.getElementById(id);
        if (campo && !validar.call(campo)) {esValido = false}; // aqui el uso especial de call
      });

      if (esValido) {
        form.submit();
      } else {
        alert('Por favor corrija los errores en el formulario antes de enviarlo.');
      }
    });
});
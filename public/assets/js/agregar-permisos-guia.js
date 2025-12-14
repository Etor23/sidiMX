// Manejo de permisos dinámicos en formulario de guía
document.addEventListener('DOMContentLoaded', () => {
  const permisosContainer = document.getElementById('permisosContainer');
  const addBtn = document.getElementById('addPermisoBtn');
  const permisosInput = document.getElementById('pPermisosJson');
  const form = document.getElementById('formCrearGuia');

  if (!permisosContainer || !addBtn || !permisosInput || !form) {
    return; // Si no existen los elementos, salir
  }

  function createPermisoRow(tipo='', autoridad='', vigencia=''){
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 permiso-row';
    row.innerHTML = `
      <div class="col-md-4">
        <input class="form-control permiso-tipo" placeholder="Tipo" data-key="tipo" value="${tipo}">
        <div class="invalid-feedback"></div>
      </div>
      <div class="col-md-4">
        <input class="form-control permiso-autoridad" placeholder="Autoridad" data-key="autoridad" value="${autoridad}">
        <div class="invalid-feedback"></div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-text">Vigencia</span>
          <input type="date" class="form-control permiso-vigencia" data-key="vigencia" value="${vigencia}">
        </div>
        <div class="invalid-feedback"></div>
      </div>
      <div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-danger btn-sm remove-perm" aria-label="Eliminar permiso">X</button></div>
    `;
    permisosContainer.appendChild(row);
    row.querySelector('.remove-perm').addEventListener('click', ()=> row.remove());
    
    // Agregar validación al campo tipo usando la función global
    const campoTipo = row.querySelector('.permiso-tipo');
    if (window.validarTipoPermiso) {
      campoTipo.addEventListener('blur', window.validarTipoPermiso);
      campoTipo.addEventListener('keyup', window.validarTipoPermiso);
    }
    
    // Agregar validación al campo autoridad usando la función global
    const campoAutoridad = row.querySelector('.permiso-autoridad');
    if (window.validarAutoridadPermiso) {
      campoAutoridad.addEventListener('blur', window.validarAutoridadPermiso);
      campoAutoridad.addEventListener('keyup', window.validarAutoridadPermiso);
    }
    
    // Agregar validación al campo vigencia usando la función global
    const campoVigencia = row.querySelector('.permiso-vigencia');
    if (window.validarVigenciaPermiso) {
      campoVigencia.addEventListener('blur', window.validarVigenciaPermiso);
      campoVigencia.addEventListener('change', window.validarVigenciaPermiso);
    }
  }

  addBtn.addEventListener('click', ()=> createPermisoRow());

  // Si hay permisos ya en el campo oculto, precargar filas
  (function preloadPermisos(){
    try{
      const raw = permisosInput.value;
      if(raw){
        const arr = JSON.parse(raw);
        if(Array.isArray(arr)){
          arr.forEach(p=> createPermisoRow(p.tipo || '', p.autoridad || '', p.vigencia || ''));
        }
      }
    }catch(e){
      console.warn('No se pudo parsear permisos JSON', e);
    }
  })();

  form.addEventListener('submit', function(e){
    // Validar todos los campos tipo, autoridad y vigencia antes de serializar
    let todosValidos = true;
    
    const camposTipo = permisosContainer.querySelectorAll('.permiso-tipo');
    if (window.validarTipoPermiso) {
      camposTipo.forEach(campo => {
        if (campo.value.trim() !== '' && !window.validarTipoPermiso.call(campo)) {
          todosValidos = false;
        }
      });
    }
    
    const camposAutoridad = permisosContainer.querySelectorAll('.permiso-autoridad');
    if (window.validarAutoridadPermiso) {
      camposAutoridad.forEach(campo => {
        if (campo.value.trim() !== '' && !window.validarAutoridadPermiso.call(campo)) {
          todosValidos = false;
        }
      });
    }
    
    const camposVigencia = permisosContainer.querySelectorAll('.permiso-vigencia');
    if (window.validarVigenciaPermiso) {
      camposVigencia.forEach(campo => {
        if (campo.value.trim() !== '' && !window.validarVigenciaPermiso.call(campo)) {
          todosValidos = false;
        }
      });
    }
    
    if (!todosValidos) {
      e.preventDefault();
      alert('Por favor corrija los errores en los permisos antes de enviar.');
      return;
    }
    
    // serializar permisos
    const rows = permisosContainer.querySelectorAll('.permiso-row');
    const arr = [];
    rows.forEach(r=>{
      const tipo = r.querySelector('[data-key="tipo"]').value.trim();
      const autoridad = r.querySelector('[data-key="autoridad"]').value.trim();
      const vigencia = r.querySelector('[data-key="vigencia"]').value;
      if(tipo !== ''){
        arr.push({tipo: tipo, autoridad: autoridad || null, vigencia: vigencia || null});
      }
    });
    permisosInput.value = arr.length ? JSON.stringify(arr) : '';
  });
});

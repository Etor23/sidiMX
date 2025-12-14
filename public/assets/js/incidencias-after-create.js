document.getElementById('btn-no').addEventListener('click', function(e){
    e.preventDefault();
    const idGuia = this.getAttribute('data-guia-id');
    const urlRoot = this.getAttribute('data-url-root');
    
    if (!confirm('Al terminar se cambiará el estado de la guía a "ordenDePago". ¿Continuar?')) return;

    fetch(`${urlRoot}/incidencias/marcarOrdenPago/${idGuia}`)
        .then(r => r.json())
        .then(js => {
            if (js.ok) {
                window.location.href = `${urlRoot}/guias`;
            } else {
                alert('No se pudo actualizar el estado: ' + (js.msg || 'error'));
            }
        })
        .catch(err => { alert('Error: ' + err); });
});

document.querySelectorAll('.confirm-action').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.confirm))event.preventDefault();}));
document.querySelectorAll('.status-form').forEach(form=>form.addEventListener('submit',event=>{if(form.querySelector('select').value==='cancelado'&&!confirm('Cancelar este atendimento?'))event.preventDefault();}));

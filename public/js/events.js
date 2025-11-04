// JS para inicializar FullCalendar y cargar eventos desde /index.php?route=eventos/list
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  if (!calendarEl) return;

  window.EVENT_CALENDAR = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    selectable: true,
    select: function(info) {
      // abrir modal con fecha prellenada
      var modalEl = document.getElementById('eventModal');
      if (!modalEl) return;
      var modal = new bootstrap.Modal(modalEl);
      // rellenar fecha
      var form = document.getElementById('eventModalForm');
      form.reset();
      form.elements['fecha'].value = info.startStr;
      document.getElementById('eventModalLabel').innerText = 'Crear Evento';
      modal.show();
    },
    events: function(info, successCallback, failureCallback) {
      var url = './index.php?route=eventos/list&start=' + info.startStr + '&end=' + info.endStr;
      fetch(url)
        .then(function(res){ return res.json(); })
        .then(function(data){ successCallback(data); })
        .catch(function(err){ console.error('Error cargando eventos', err); failureCallback(err); });
    },
    eventClick: function(info) {
      var event = info.event;
      var canEdit = (window.USER_ROLE === 1 || window.USER_ROLE === 2);
      if (canEdit) {
        // abrir el modal en modo edición redirigiendo a edit page (simple) or we could load via AJAX
        window.location.href = './index.php?route=eventos/edit/' + event.id;
      } else {
        // Mostrar modal con info y opción RSVP
        var modalEl = document.getElementById('viewEventModal');
        if (!modalEl) {
          var details = 'Título: ' + event.title + '\n';
          if (event.extendedProps && event.extendedProps.descripcion) details += '\n' + event.extendedProps.descripcion;
          alert(details);
          return;
        }
        // rellenar campos
        document.getElementById('viewEventTitle').innerText = event.title;
        document.getElementById('viewEventDesc').innerText = event.extendedProps.descripcion || '';
        document.getElementById('viewEventWhen').innerText = (new Date(event.start)).toLocaleString();
        document.getElementById('viewEventLocation').innerText = event.extendedProps.ubicacion || '';
        document.getElementById('viewEventId').value = event.id;
        // mostrar modal
        var viewModal = new bootstrap.Modal(modalEl);
        viewModal.show();
      }
    }
  });

    // RSVP handler for view modal
    var rsvpBtn = document.getElementById('rsvpBtn');
    if (rsvpBtn) {
      rsvpBtn.addEventListener('click', function() {
        var id = document.getElementById('viewEventId').value;
        if (!id) return alert('Evento inválido');
        var form = new FormData();
        form.append('id_evento', id);
        // añadir CSRF
        if (window.CSRF_TOKEN) form.append('csrf_token', window.CSRF_TOKEN);

        fetch('./index.php?route=eventos/rsvp', {
          method: 'POST',
          body: form,
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json'
          }
        }).then(function(res){ return res.json(); }).then(function(json){
          if (json && json.success) {
            alert(json.attending ? 'Confirmaste asistencia.' : 'Se removió tu asistencia.');
            var modalEl = document.getElementById('viewEventModal');
            var m = bootstrap.Modal.getInstance(modalEl);
            if (m) m.hide();
          } else {
            alert((json && json.message) ? json.message : 'Error procesando RSVP');
          }
        }).catch(function(err){ console.error('Error RSVP', err); alert('Error interno al confirmar asistencia.'); });
      });
    }

  window.EVENT_CALENDAR.render();

  // Modal submit via AJAX
  var submitBtn = document.getElementById('eventModalSubmit');
  if (submitBtn) {
    submitBtn.addEventListener('click', function() {
      var form = document.getElementById('eventModalForm');
      var formData = new FormData(form);
      // add CSRF if not present
      if (!formData.get('csrf_token') && window.CSRF_TOKEN) formData.set('csrf_token', window.CSRF_TOKEN);

      fetch('./index.php?route=eventos/create', {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json'
        }
      }).then(function(res){ return res.json(); })
      .then(function(json){
        if (json && json.success) {
          // cerrar modal y refrescar calendario
          var modalEl = document.getElementById('eventModal');
          var modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
          if (window.EVENT_CALENDAR) window.EVENT_CALENDAR.refetchEvents();
        } else {
          var msg = (json && json.message) ? json.message : 'Error al crear evento.';
          alert(msg);
        }
      }).catch(function(err){ console.error('Error en creación AJAX', err); alert('Error interno al crear evento.'); });
    });
  }
});

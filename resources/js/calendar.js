// Practitioner booking calendar (loaded only on that page).
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import luxonPlugin from '@fullcalendar/luxon3';

const el = document.getElementById('fc');
if (el) {
    const narrow = window.matchMedia('(max-width: 768px)').matches;
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, luxonPlugin],
        timeZone: el.dataset.timezone || 'local',
        initialView: narrow ? 'listWeek' : 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: narrow ? 'listWeek,dayGridMonth' : 'timeGridWeek,dayGridMonth,listWeek' },
        height: 'auto',
        slotMinTime: '07:00:00',
        slotMaxTime: '23:00:00',
        nowIndicator: true,
        allDaySlot: false,
        firstDay: 1,
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        events: { url: el.dataset.feed, method: 'GET' },
    });
    calendar.render();
}

// --- Pizarra Virtual CrossFit ---

// Tabs
const tabResults = document.getElementById('tab-results');
const tabCoach = document.getElementById('tab-coach');
const resultsTabContent = document.getElementById('results-tab-content');
const coachTabContent = document.getElementById('coach-tab-content');

// Helper para saber si el usuario es coach/admin
// Obtener parámetros de la URL
function getBoardParams() {
    const params = new URLSearchParams(window.location.search);
    return {
        board_id: params.get('board_id'),
        board_name: params.get('board_name'),
        board_role: params.get('board_role')
    };
}

const { board_id, board_name, board_role } = getBoardParams();
// Obtener user_id desde variable global inyectada por PHP (o -1 si no existe)
if (typeof window.user_id === 'undefined') {
    window.user_id = -1;
}
if (!board_id) {
    alert('Error: No se ha encontrado la pizarra activa (board_id) en la URL.');
    throw new Error('No board_id in URL');
}
// Poner el nombre de la pizarra en el título
const boardTitle = document.getElementById('board-title');
if (boardTitle) {
    boardTitle.textContent = board_name ? decodeURIComponent(board_name) : 'Pizarra Virtual';
}

function isCoach() {
    return board_role === 'admin' || board_role === 'coach';
}


function showTab(tab) {
    if (tab === 'results') {
        resultsTabContent.style.display = '';
        if (coachTabContent) coachTabContent.style.display = 'none';
        tabResults.setAttribute('aria-selected', 'true');
        if (tabCoach) tabCoach.setAttribute('aria-selected', 'false');
    } else {
        resultsTabContent.style.display = 'none';
        if (coachTabContent) coachTabContent.style.display = '';
        tabResults.setAttribute('aria-selected', 'false');
        if (tabCoach) tabCoach.setAttribute('aria-selected', 'true');
    }
}
tabResults.addEventListener('click', () => showTab('results'));
if (tabCoach) tabCoach.addEventListener('click', () => showTab('coach'));
showTab('results');

// Elementos principales
const wodText = document.getElementById('wod-text');
const saveWodBtn = document.getElementById('save-wod');
const resultForm = document.getElementById('result-form');
const athleteResult = document.getElementById('athlete-result'); // Solo resultado, nombre lo pone el backend
const resultsList = document.getElementById('results-list');
const wodView = document.getElementById('wod-view');

let wodData = {}; // Estructura: { 'YYYY-MM-DD': { wod: '', results: [] }, ... }
let currentDate = getToday();

function getToday() {
    const d = new Date();
    return d.toISOString().slice(0,10);
}
function getWeekDates() {
    const today = new Date(currentDate);
    const dayOfWeek = today.getDay() === 0 ? 6 : today.getDay() - 1; // Lunes=0
    const week = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() - dayOfWeek + i);
        week.push(d.toISOString().slice(0,10));
    }
    return week;
}
function renderCalendarBar() {
    const bar = document.getElementById('calendar-bar');
    bar.innerHTML = '';
    const week = getWeekDates();
    week.forEach(date => {
        const btn = document.createElement('button');
        btn.className = 'calendar-day';
        btn.textContent = date.slice(8,10) + '/' + date.slice(5,7);
        btn.dataset.date = date;
        if (date === getToday()) btn.classList.add('today');
        if (date === currentDate) btn.classList.add('selected');
        btn.onclick = () => {
            currentDate = date;
            renderCalendarBar();
            renderWodAndResults();
        };
        bar.appendChild(btn);
    });
}

// Cargar todos los WODs y resultados
function loadWod() {
    fetch('load.php?board_id=' + encodeURIComponent(board_id), { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            wodData = data;
            renderCalendarBar();
            renderWodAndResults();
        });
}

// Mostrar WOD y resultados del día seleccionado
defaultWodResults = { wod: '', results: [] };
function renderWodAndResults() {
    const dayData = wodData[currentDate] || defaultWodResults;
    // Coach tab
    wodText.value = dayData.wod || '';
    // Resultados tab
    wodView.textContent = dayData.wod || '';
    renderResults(dayData.results || []);
}


// Guardar el WOD (solo coach)
if (saveWodBtn) {
    saveWodBtn.addEventListener('click', () => {
        if (!isCoach()) return; // Solo coach puede guardar
        const wod_text = wodText.value;
        fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            board_id,
            date: currentDate,
            wod_text
        })
    })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'success') {
                alert('Error al guardar el WOD: ' + (res.message || ''));
                return;
            }
            loadWod();
        })
        .catch(() => alert('Error de conexión al guardar el WOD.'));
    });
}

// Añadir resultado (atleta)
resultForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const result = athleteResult.value.trim();
    if (!result) return;
    fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            board_id,
            date: currentDate,
            result_text: result
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status !== 'success') {
            alert('Error al guardar el resultado: ' + (res.message || '')); 
            return;
        }
        athleteResult.value = '';
        loadWod();
    })
    .catch(() => alert('Error de conexión al guardar el resultado.'));
});

// Guardar en el backend
// saveWodData ya no es necesario, la lógica está en los listeners de arriba

// Mostrar resultados en la lista
function renderResults(resultsArr) {
    resultsList.innerHTML = '';
    if (!Array.isArray(resultsArr)) return;
    resultsArr.forEach((res, idx) => {
        const li = document.createElement('li');
        li.innerHTML = `<span class="name">${escapeHtml(res.name)}</span>: <span class="mark">${escapeHtml(res.result)}</span>`;
        // Solo mostrar botón si el resultado es del usuario actual
        if (res.user_id && typeof user_id !== 'undefined' && res.user_id == user_id) {
            const delBtn = document.createElement('button');
            delBtn.className = 'delete-result-btn';
            delBtn.title = 'Eliminar mi resultado';
            delBtn.style.marginLeft = '12px';
            delBtn.style.background = 'none';
            delBtn.style.border = 'none';
            delBtn.style.cursor = 'pointer';
            delBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><path d="M6 7V15C6 15.5523 6.44772 16 7 16H13C13.5523 16 14 15.5523 14 15V7" stroke="#ffbaba" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 7H16" stroke="#ffbaba" stroke-width="1.4" stroke-linecap="round"/><path d="M9 10V13" stroke="#ffbaba" stroke-width="1.4" stroke-linecap="round"/><path d="M11 10V13" stroke="#ffbaba" stroke-width="1.4" stroke-linecap="round"/><path d="M8 7V5C8 4.44772 8.44772 4 9 4H11C11.5523 4 12 4.44772 12 5V7" stroke="#ffbaba" stroke-width="1.4" stroke-linecap="round"/></svg>`;
            delBtn.onclick = function() {
                if (confirm('¿Seguro que quieres eliminar tu resultado?')) {
                    deleteResult(idx);
                }
            };
            li.appendChild(delBtn);
        }
        resultsList.appendChild(li);
    });
}

// Elimina el resultado del usuario de la lista local y del backend
function deleteResult(idx) {
    const dayData = wodData[currentDate] || defaultWodResults;
    if (!Array.isArray(dayData.results)) return;
    const result = dayData.results[idx];
    // Eliminar en backend primero
    fetch('save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            board_id,
            date: currentDate,
            delete_result: true,
            result_text: result.result
        })
    }).then(() => {
        // Eliminar en frontend solo si el backend responde
        dayData.results.splice(idx, 1);
        renderResults(dayData.results);
    });
}

// Evitar XSS
function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'})[m];
    });
}

// Limpiar datos locales de WOD/resultados/fecha al cargar la app
// Inicializar
loadWod();

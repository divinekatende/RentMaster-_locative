<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-app: #f8fafc;
            --surface: #ffffff;
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-soft: #eff6ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            
            --danger: #ef4444;
            --danger-soft: #fef2f2;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height:100vh; display:flex; }
        
        /* SIDEBAR */
        aside {
            width:260px; background: var(--surface);
            border-right:1px solid var(--border-color); padding:24px 16px; position:fixed; height:100%;
            display: flex; flex-direction: column; transition:.3s ease; z-index: 99;
        }
        .logo { text-align:center; margin-bottom:32px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .logo img { width:70px; height:70px; border-radius:50%; border:3px solid var(--primary-soft); object-fit: cover; }
        .logo h2 { color:var(--primary); font-size: 1.25rem; font-weight:700; letter-spacing: -0.02em; }
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside a {
            display:flex; align-items:center; gap:12px; padding:12px 14px;
            border-radius:var(--radius-md); text-decoration:none; color:var(--text-muted); font-weight:500; font-size: 14px; transition:.2s ease;
        }
        aside a:hover { background: var(--primary-soft); color: var(--primary); }
        aside a.active { background:var(--primary); color:white; font-weight:600; }
        aside a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer a { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer a:hover { background: var(--danger); color: white; }

        /* CONTENT */
        .content { margin-left:260px; width:100%; padding:40px; display: flex; flex-direction: column; min-height: 100vh; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
        .top h1 { color:var(--text-main); font-size:24px; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        .menu-btn { display:none; width:40px; height:40px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--surface); color:var(--text-main); font-size:16px; cursor: pointer; }

        /* CALENDAR COMPONENT CARD */
        .calendar-card {
            background: var(--surface); padding: 32px; border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); 
        }

        /* CONTROLS */
        .calendar-controls {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);
        }
        #monthYear { font-size: 18px; font-weight: 700; color: var(--text-main); text-transform: capitalize; }
        
        .btn-ghost {
            background: var(--surface); color: var(--text-main); border: 1px solid var(--border-color);
            padding: 8px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s ease;
        }
        .btn-ghost:hover { background: var(--bg-app); border-color: var(--text-muted); }

        /* GRID SYSTEM */
        .weekdays, .calendar {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center;
        }
        .weekdays div {
            font-weight: 600; font-size: 13px; padding: 12px 0; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;
        }
        
        /* CALENDAR DAYS */
        .day {
            aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 500; color: var(--text-main);
            background: var(--bg-app); border-radius: var(--radius-md); 
            cursor: pointer; transition: all 0.15s ease; border: 1px solid transparent;
        }
        .day:hover { background: var(--primary-soft); color: var(--primary); border-color: #bfdbfe; }
        
        /* EMPTY CELL DAYS */
        .empty-day { background: transparent; cursor: default; }

        /* SELECTED STATE */
        .day.selected {
            background: var(--primary); color: var(--surface); font-weight: 600;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.25);
        }
        .day.selected:hover { background: var(--primary-light); color: var(--surface); border-color: transparent; }

        footer { text-align:center; padding: 24px 0; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: auto; }

        @media(max-width:900px){
            aside { left:-280px; }
            aside.show { left:0; box-shadow: var(--shadow-md); }
            .content { margin-left:0; padding:24px 16px; }
            .menu-btn { display:flex; align-items: center; justify-content: center; }
            .top h1 { font-size:22px; }
            .calendar-card { padding: 20px; }
            #monthYear { font-size: 15px; }
            .btn-ghost { padding: 6px 12px; font-size: 12px; }
            .weekdays div { font-size: 11px; }
            .day { font-size: 13px; }
        }
    </style>
</head>
<body>

<aside id="sidebar">
    <div class="logo">
        <img src="../../../public/assets/images/logo.png" alt="logo">
        <h2>RentMaster</h2>
    </div>
    <nav>
        <a href="dashboard_locataire.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fas fa-receipt"></i> Reçus</a>
        <a href="historique.php"><i class="fas fa-history"></i> Historique</a>
        <a href="calendrier.html" class="active"><i class="fas fa-calendar-alt"></i> Calendrier</a>
        <a href="profil_locataire.php"><i class="fas fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="content">
    <div class="top">
        <h1><i class="fas fa-calendar-alt" style="color: var(--primary)"></i> Calendrier</h1>
        <button class="menu-btn" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="calendar-card">
        <div class="calendar-controls">
            <button class="btn-ghost" onclick="prevMonth()"><i class="fas fa-chevron-left"></i> Précédent</button>
            <span id="monthYear"></span>
            <button class="btn-ghost" onclick="nextMonth()">Suivant <i class="fas fa-chevron-right"></i></button>
        </div>

        <div class="weekdays">
            <div>Lun</div><div>Mar</div><div>Mer</div><div>Jeu</div><div>Ven</div><div>Sam</div><div>Dim</div>
        </div>

        <div id="calendar" class="calendar"></div>
    </div>

    <footer>
        &copy; 2026 RentMaster - Tous droits réservés
    </footer>
</div>

<script>
let selectedDates = JSON.parse(localStorage.getItem("selectedDates")) || [];
let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

function renderCalendar(month, year){
    const calendar = document.getElementById("calendar");
    calendar.innerHTML = "";
    
    document.getElementById("monthYear").innerText = new Intl.DateTimeFormat('fr-FR', {
        month: 'long', 
        year: 'numeric'
    }).format(new Date(year, month));

    let firstDay = new Date(year, month, 1).getDay();
    // Ajustement de la grille pour commencer le lundi (0=Dimanche, donc si 0 devient 6, sinon index - 1)
    firstDay = firstDay === 0 ? 6 : firstDay - 1;
    let daysInMonth = new Date(year, month + 1, 0).getDate();

    // Remplissage des cellules vides pour le début du mois
    for(let i = 0; i < firstDay; i++){
        let empty = document.createElement("div");
        empty.className = "empty-day";
        calendar.appendChild(empty);
    }

    // Génération des jours opérationnels du mois
    for(let d = 1; d <= daysInMonth; d++){
        let day = document.createElement("div");
        day.className = "day";
        day.innerText = d;

        let fullDate = `${year}-${month + 1}-${d}`;
        if(selectedDates.includes(fullDate)) day.classList.add("selected");

        day.onclick = () => {
            if(selectedDates.includes(fullDate)){
                selectedDates = selectedDates.filter(dt => dt !== fullDate);
                day.classList.remove("selected");
            } else {
                selectedDates.push(fullDate);
                day.classList.add("selected");
            }
            localStorage.setItem("selectedDates", JSON.stringify(selectedDates));
        };

        calendar.appendChild(day);
    }
}

function prevMonth(){
    currentMonth--;
    if(currentMonth < 0){
        currentMonth = 11;
        currentYear--;
    } 
    renderCalendar(currentMonth, currentYear);
}

function nextMonth(){
    currentMonth++;
    if(currentMonth > 11){
        currentMonth = 0;
        currentYear++;
    } 
    renderCalendar(currentMonth, currentYear);
}

function toggleMenu(){
    document.getElementById("sidebar").classList.toggle("show");
}

window.onload = () => {
    renderCalendar(currentMonth, currentYear);
}
</script>
</body>
</html>
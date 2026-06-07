<?php
session_start();
require_once('../../config/database.php');
require_once('../../controllers/CalendrierController.php');

$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) die("Accès refusé");

$ctrl = new CalendrierController();
$erreur = null;
$succes = null;

// Suppression d'un événement
if (isset($_GET['delete'])) {
    $ctrl->delete((int)$_GET['delete'], $id_bailleur);
    header("Location: calendrier.php");
    exit;
}

// Ajout d'un événement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_bailleur'    => $id_bailleur,
        'id_locataire'   => $_POST['id_locataire'] ?? null,
        'titre'          => $_POST['titre'] ?? '',
        'description'    => $_POST['description'] ?? '',
        'date_evenement' => $_POST['date_evenement'] ?? '',
    ];

    if (!empty($data['titre']) && !empty($data['date_evenement'])) {
        $result = $ctrl->create($data);
        if ($result['ok']) $succes = $result['msg'];
        else $erreur = $result['msg'];
    } else {
        $erreur = "Le titre et la date sont obligatoires.";
    }
}

// Récupération des données
$evenements = $ctrl->index($id_bailleur);
$locataires = $ctrl->getLocataires($id_bailleur);

// Formatage des événements pour l'injecter proprement dans le JavaScript
$eventsJson = [];
foreach ($evenements as $e) {
    // Normalisation de la date au format Y-m-d (sans zéros superflus pour correspondre au JS)
    $ts = strtotime($e['date_evenement']);
    $key = date('Y-n-j', $ts); // Format : "2026-3-8"
    $eventsJson[$key][] = [
        'id'          => $e['id_evenement'],
        'titre'       => $e['titre'],
        'description' => $e['description'],
        'locataire'   => $e['locataire_nom'] ?? 'Aucun'
    ];
}
$eventsJson = json_encode($eventsJson);
?>
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
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            --danger: #ef4444;
            --danger-soft: #fef2f2;
            --success: #10b981;
            --success-soft: #ecfdf5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-app); color: var(--text-main); padding: 20px 10px; }
        
        .container {
            max-width: 900px; margin: 40px auto; padding: 30px;
            background: var(--surface); border-radius: var(--radius-lg);
            border: 1px solid var(--border-color); box-shadow: var(--shadow-md);
        }
        
        .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .top-actions h1 { font-size: 24px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        
        /* Boutons */
        .btn { padding: 10px 18px; border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s ease; }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15); }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }
        .btn-ghost { background: var(--surface); border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-ghost:hover { background: var(--bg-app); }
        .btn-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid #fca5a5; padding: 6px 10px; font-size: 12px; border-radius: 6px; text-decoration: none; }
        .btn-danger:hover { background: var(--danger); color: white; }

        /* Alertes */
        .alert { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-error  { background: var(--danger-soft); border: 1px solid #fca5a5; color: #b91c1c; }
        .alert-succes { background: var(--success-soft); border: 1px solid #6ee7b7; color: #047857; }

        /* Calendrier Controls */
        .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: var(--bg-app); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); }
        #monthYear { font-size: 18px; font-weight: 700; color: var(--text-main); text-transform: capitalize; }

        /* Grille Calendrier */
        .weekdays, .calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .weekdays div { font-weight: 600; padding: 10px 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase; text-align: center; }
        
        .day {
            aspect-ratio: 1; padding: 10px; background: var(--surface);
            border: 1px solid var(--border-color); border-radius: var(--radius-sm);
            position: relative; cursor: pointer; transition: all 0.2s ease;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .day:hover { background: var(--primary-soft); border-color: var(--primary-light); }
        .day .day-num { font-weight: 600; font-size: 14px; color: var(--text-main); }
        
        /* Badges d'évènements internes à la case */
        .day .dots-container { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; }
        .day .dot { width: 6px; height: 6px; background-color: var(--primary-light); border-radius: 50%; }
        
        /* Case sélectionnée ou active */
        .day.selected { background: var(--primary-soft); border-color: var(--primary); box-shadow: inset 0 0 0 2px var(--primary); }
        .day.empty-day { background: transparent; border: none; cursor: default; }

        /* Modale d'évènements */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal { background: var(--surface); border-radius: var(--radius-lg); width: 100%; max-width: 500px; max-height: 85vh; overflow-y: auto; box-shadow: var(--shadow-md); border: 1px solid var(--border-color); padding: 24px; animation: slideUp 0.25s ease; }
        
        @keyframes slideUp { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color); }
        .modal-header h3 { font-size: 18px; font-weight: 700; color: var(--text-main); }
        .close-modal { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; }

        /* Éléments de formulaire */
        form label { display: block; margin-top: 12px; font-size: 13px; font-weight: 600; color: var(--text-main); }
        form input, form select, form textarea { width: 100%; padding: 10px 14px; margin-top: 6px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); outline: none; font-size: 14px; background: var(--bg-app); transition: 0.2s; }
        form input:focus, form select:focus, form textarea:focus { border-color: var(--primary-light); background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        /* Liste des évènements du jour */
        .event-list { margin-bottom: 20px; max-height: 200px; overflow-y: auto; }
        .event-item { background: var(--bg-app); border-left: 4px solid var(--primary); padding: 12px; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .event-item-info h4 { font-size: 14px; font-weight: 600; margin-bottom: 3px; }
        .event-item-info p { font-size: 12px; color: var(--text-muted); }

        @media(max-width:600px){
            .container { padding: 15px; margin: 20px auto; }
            .top-actions { flex-direction: column; align-items: flex-start; gap: 12px; }
            .calendar-controls { flex-direction: row; font-size: 12px; }
            #monthYear { font-size: 15px; }
            .day { padding: 5px; }
            .day .day-num { font-size: 12px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-actions">
        <h1><i class="fa fa-calendar"></i> Calendrier</h1>
        <button class="btn btn-ghost" onclick="window.location='dashboard.php'">
            <i class="fa fa-arrow-left"></i> Retour au Dashboard
        </button>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-error"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($succes): ?>
        <div class="alert alert-succes"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <div class="calendar-controls">
        <button class="btn btn-ghost" onclick="prevMonth()"><i class="fa fa-chevron-left"></i></button>
        <span id="monthYear"></span>
        <button class="btn btn-ghost" onclick="nextMonth()"><i class="fa fa-chevron-right"></i></button>
    </div>

    <div class="weekdays">
        <div>Lun</div><div>Mar</div><div>Mer</div><div>Jeu</div><div>Ven</div><div>Sam</div><div>Dim</div>
    </div>

    <div id="calendar" class="calendar"></div>
</div>

<div id="eventModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalDateTitle">Événements</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>

        <div id="dayEventsContainer">
            <div class="event-list" id="dayEventsList"></div>
        </div>

        <form method="POST" action="calendrier.php">
            <h3 style="font-size: 15px; margin-top: 15px; color: var(--primary); border-top: 1px solid var(--border-color); padding-top: 15px;"><i class="fa fa-plus"></i> Ajouter un événement</h3>
            
            <input type="hidden" name="date_evenement" id="formDateInput">

            <label for="titre">Titre de l'événement *</label>
            <input type="text" name="titre" id="titre" required placeholder="Ex: État des lieux, Visite, Rappel">

            <label for="id_locataire">Locataire concerné (Optionnel)</label>
            <select name="id_locataire" id="id_locataire">
                <option value="">-- Aucun ou général --</option>
                <?php foreach ($locataires as $l): ?>
                    <option value="<?= $l['id_locataire'] ?>"><?= htmlspecialchars($l['nom'].' '.$l['prenom']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="description">Description / Notes</label>
            <textarea name="description" id="description" rows="3" placeholder="Détails supplémentaires..."></textarea>

            <div class="btn-group" style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-ghost" style="flex:1; justify-content: center;" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content: center;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
// Injection des événements issus de la BDD
const dbEvents = <?= $eventsJson ?>;

let today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

function renderCalendar(month, year) {
    const calendar = document.getElementById("calendar");
    calendar.innerHTML = "";
    
    // Titre supérieur
    document.getElementById("monthYear").innerText = new Intl.DateTimeFormat('fr-FR', {month: 'long', year: 'numeric'}).format(new Date(year, month));

    // Décalage pour le premier jour du mois (lundi par défaut en FR)
    let firstDay = new Date(year, month, 1).getDay();
    firstDay = firstDay === 0 ? 6 : firstDay - 1;
    let daysInMonth = new Date(year, month + 1, 0).getDate();

    // Remplissage des cases vides du début
    for (let i = 0; i < firstDay; i++) {
        let emptyCell = document.createElement("div");
        emptyCell.className = "day empty-day";
        calendar.appendChild(emptyCell);
    }

    // Création des cases des jours du mois
    for (let d = 1; d <= daysInMonth; d++) {
        let dayCell = document.createElement("div");
        dayCell.className = "day";
        
        let dayNumSpan = document.createElement("span");
        dayNumSpan.className = "day-num";
        dayNumSpan.innerText = d;
        dayCell.appendChild(dayNumSpan);

        // Clé unique pour vérifier la présence d'événements : Y-M-D (ex: 2026-6-8)
        let dateKey = `${year}-${month + 1}-${d}`;
        
        // Ajout des petites pastilles bleues (dots) si des événements existent ce jour-là
        if (dbEvents[dateKey] && dbEvents[dateKey].length > 0) {
            let dotsContainer = document.createElement("div");
            dotsContainer.className = "dots-container";
            
            dbEvents[dateKey].forEach(() => {
                let dot = document.createElement("div");
                dot.className = "dot";
                dotsContainer.appendChild(dot);
            });
            dayCell.appendChild(dotsContainer);
        }

        // Action au clic sur une cellule
        dayCell.onclick = () => {
            openModal(dateKey, year, month + 1, d);
        };

        calendar.appendChild(dayCell);
    }
}

function openModal(dateKey, year, month, day) {
    // Formater la date lisible pour le titre de la modale
    let formattedDate = new Date(year, month - 1, day).toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById("modalDateTitle").innerText = formattedDate;
    
    // Injecter la valeur brute dans le input hidden du formulaire (format SQL Y-m-d requis)
    let monthString = month < 10 ? '0' + month : month;
    let dayString = day < 10 ? '0' + day : day;
    document.getElementById("formDateInput").value = `${year}-${monthString}-${dayString}`;

    // Nettoyer la liste des événements précédents
    const listContainer = document.getElementById("dayEventsList");
    listContainer.innerHTML = "";

    // Charger les événements correspondants à ce jour
    if (dbEvents[dateKey] && dbEvents[dateKey].length > 0) {
        dbEvents[dateKey].forEach(ev => {
            let item = document.createElement("div");
            item.className = "event-item";
            item.innerHTML = `
                <div class="event-item-info">
                    <h4>${ev.titre}</h4>
                    <p><strong>Locataire :</strong> ${ev.locataire}</p>
                    ${ev.description ? `<p>${ev.description}</p>` : ''}
                </div>
                <a href="calendrier.php?delete=${ev.id}" class="btn-danger" onclick="return confirm('Supprimer cet événement ?')">
                    <i class="fa fa-trash"></i>
                </a>
            `;
            listContainer.appendChild(item);
        });
    } else {
        listContainer.innerHTML = "<p style='font-size:13px; color:var(--text-muted); font-style:italic;'>Aucun événement de planifié ce jour.</p>";
    }

    document.getElementById("eventModal").classList.add("show");
}

function closeModal() {
    document.getElementById("eventModal").classList.remove("show");
}

function prevMonth() { currentMonth--; if (currentMonth < 0) { currentMonth = 11; currentYear--; } renderCalendar(currentMonth, currentYear); }
function nextMonth() { currentMonth++; if (currentMonth > 11) { currentMonth = 0; currentYear++; } renderCalendar(currentMonth, currentYear); }

window.onload = () => { renderCalendar(currentMonth, currentYear); }

// Fermeture du modal au clic à l'extérieur du panneau blanc
document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
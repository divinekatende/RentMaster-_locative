<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Utilisateur - RentMaster</title>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; padding: 24px; display: flex; flex-direction: column; }

        /* HEADER ÉPURÉ ET PROFESSIONNEL */
        .header {
            padding: 10px 0;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header p {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* NAVIGATION BAR STANDARD */
        nav { 
            background: var(--surface); 
            padding: 14px 28px; 
            border-radius: var(--radius-md); 
            box-shadow: var(--shadow-sm); 
            display: flex; 
            gap: 28px; 
            margin-bottom: 30px; 
            align-items: center; 
            border: 1px solid var(--border-color); 
        }
        nav a { 
            text-decoration: none; 
            color: var(--text-muted); 
            font-weight: 500; 
            font-size: 14px; 
            transition: all 0.2s ease; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        nav a:hover, nav a.active { 
            color: var(--primary); 
            font-weight: 600; 
        }

        /* GRID CONTAINER SUR UN SEUL BLOC */
        .container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            flex: 1;
        }

        /* CARDS AVEC LE LOOK & FEEL RENTMASTER */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        /* CONTENEUR DE L'ICÔNE */
        .icon {
            width: 46px;
            height: 46px;
            border-radius: var(--radius-sm);
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .card h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.01em;
        }

        .card p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        /* FOOTER ÉPURÉ */
        footer {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
        }

        /* RESPONSIVE */
        @media(max-width: 700px){
            body { padding: 16px; }
            nav { flex-direction: column; align-items: flex-start; gap: 16px; padding: 18px; }
            .container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- EN-TÊTE -->
    <div class="header">
        <h1><i class="fa-solid fa-book-bookmark" style="color: var(--primary);"></i> Guide Utilisateur</h1>
        <p>Découvrez toutes les fonctionnalités et optimisez votre gestion sur RentMaster.</p>
    </div>

    <!-- NAVIGATION HARMONISÉE -->
    <nav>
        <a href="dashboard_locataire.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrats</a>
        <a href="notification_locataire.php"><i class="fas fa-bell"></i> Notifications</a>
        <a href="message_locataire.php"><i class="fas fa-comment"></i> Messages</a>
    </nav>

    <!-- GRILLE DE DOCUMENTATION -->
    <div class="container">

        <div class="card">
            <div class="icon"><i class="fas fa-chart-pie"></i></div>
            <h3>Dashboard</h3>
            <p>Consultez rapidement le montant de votre loyer, l'état de vos soldes et la date de votre prochaine échéance réglementaire.</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fas fa-credit-card"></i></div>
            <h3>Paiements</h3>
            <p>Visualisez l'historique complet de vos règlements, téléchargez vos quittances et suivez vos transactions financières.</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fas fa-file-signature"></i></div>
            <h3>Contrats</h3>
            <p>Accédez instantanément aux clauses de votre bail, vos états des lieux et téléchargez l'ensemble de vos documents administratifs.</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fas fa-envelope"></i></div>
            <h3>Messages</h3>
            <p>Échangez facilement et de manière sécurisée avec votre propriétaire ou l'équipe de gestion directement via la messagerie.</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fas fa-bell"></i></div>
            <h3>Notifications</h3>
            <p>Restez informé en temps réel des appels de loyers, rappels d'échéances et alertes importantes liées à votre logement.</p>
        </div>

        <div class="card">
            <div class="icon"><i class="fas fa-shield-halved"></i></div>
            <h3>Sécurité</h3>
            <p>Vos données personnelles, vos documents justificatifs et vos identifiants sont cryptés et protégés en totale conformité.</p>
        </div>

    </div>

    <!-- PIED DE PAGE -->
    <footer>
        &copy; 2026 RentMaster - Outil de gestion locative professionnelle. Tous droits réservés.
    </footer>

</body>
</html>
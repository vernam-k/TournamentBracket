</main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">A comprehensive tournament bracket management system.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="index.php?page=tournaments" class="text-white">Tournaments</a></li>
                        <li><a href="index.php?page=statistics" class="text-white">Statistics</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Account</h5>
                    <ul class="list-unstyled">
                        <?php if (is_logged_in()): ?>
                            <li><a href="index.php?page=profile" class="text-white">Profile</a></li>
                            <li><a href="index.php?page=predictions" class="text-white">Predictions</a></li>
                            <li><a href="index.php?page=logout" class="text-white">Logout</a></li>
                        <?php else: ?>
                            <li><a href="index.php?page=login" class="text-white">Login</a></li>
                            <li><a href="index.php?page=register" class="text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="https://github.com/vernam-k/TournamentBracket" class="text-white" target="_blank" title="GitHub Repository">
                        <i class="fab fa-github"></i> GitHub Repository
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo get_asset_url('js/main.js'); ?>"></script>
    
    <!-- Real-time updates -->
    <script>
        // Polling interval (in milliseconds)
        const POLLING_INTERVAL = <?php echo POLLING_INTERVAL; ?>;
        
        // Last update timestamp
        let lastUpdate = Date.now();
        
        // Current page
        const currentPage = '<?php echo $current_page; ?>';
        
        // Tournament ID (if on a tournament page)
        const tournamentId = '<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>';
        
        // Start polling for updates
        if (['tournaments', 'tournament', 'admin_tournaments', 'admin_tournament'].includes(currentPage)) {
            setInterval(checkForUpdates, POLLING_INTERVAL);
        }
        
        /**
         * Check for updates
         */
        function checkForUpdates() {
            // Different endpoints based on the current page
            let endpoint = '';
            let params = `timestamp=${lastUpdate}`;
            
            if (currentPage === 'tournament' || currentPage === 'admin_tournament') {
                if (!tournamentId) return;
                endpoint = 'tournaments';
                params += `&action=get_bracket_data&id=${tournamentId}`;
            } else if (currentPage === 'tournaments' || currentPage === 'admin_tournaments') {
                endpoint = 'tournaments';
                params += '&action=list';
            }
            
            if (!endpoint) return;
            
            // Make AJAX request
            $.ajax({
                url: `api/index.php?endpoint=${endpoint}&${params}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Update the last update timestamp
                    lastUpdate = Date.now();
                    
                    // Handle the response based on the current page
                    if (currentPage === 'tournament' || currentPage === 'admin_tournament') {
                        updateTournamentBracket(response.bracket_data);
                    } else if (currentPage === 'tournaments' || currentPage === 'admin_tournaments') {
                        updateTournamentsList(response.tournaments);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking for updates:', error);
                }
            });
        }
        
        /**
         * Update tournament bracket
         * 
         * @param {Object} bracketData Bracket data
         */
        function updateTournamentBracket(bracketData) {
            if (!bracketData) return;
            
            // Update bracket visualization
            // This will be implemented in the tournament.js file
            if (typeof updateBracketVisualization === 'function') {
                updateBracketVisualization(bracketData);
            }
        }
        
        /**
         * Update tournaments list
         * 
         * @param {Array} tournaments Tournaments data
         */
        function updateTournamentsList(tournaments) {
            if (!tournaments) return;
            
            // Update tournaments list
            // This will be implemented in the tournaments.js file
            if (typeof updateTournamentsList === 'function') {
                updateTournamentsList(tournaments);
            }
        }
    </script>
    
    <?php
    // Include page-specific JavaScript
    $js_file = '';
    
    switch ($current_page) {
        case 'tournament':
        case 'admin_tournament':
            $js_file = 'tournament.js';
            break;
        case 'tournaments':
        case 'admin_tournaments':
            $js_file = 'tournaments.js';
            break;
        case 'predictions':
            $js_file = 'predictions.js';
            break;
        case 'statistics':
            $js_file = 'statistics.js';
            break;
    }
    
    if ($js_file) {
        echo '<script src="' . get_asset_url('js/' . $js_file) . '"></script>';
    }
    ?>
</body>
</html>
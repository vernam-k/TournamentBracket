<?php
/**
 * Database functions for JSON data storage
 */

/**
 * Initialize a JSON file with default structure if it doesn't exist
 * 
 * @param string $file_path Path to the JSON file
 * @param array $default_data Default data structure
 * @return bool True if successful, false otherwise
 */
function init_json_file($file_path, $default_data) {
    if (!file_exists($file_path)) {
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($file_path, json_encode($default_data, JSON_PRETTY_PRINT));
    }
    return true;
}

/**
 * Read data from a JSON file
 * 
 * @param string $file_path Path to the JSON file
 * @return array|null JSON data as array or null on failure
 */
function read_json_file($file_path) {
    if (!file_exists($file_path)) {
        return null;
    }
    
    $json_data = file_get_contents($file_path);
    if ($json_data === false) {
        return null;
    }
    
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return null;
    }
    
    return $data;
}

/**
 * Write data to a JSON file
 * 
 * @param string $file_path Path to the JSON file
 * @param array $data Data to write
 * @return bool True if successful, false otherwise
 */
function write_json_file($file_path, $data) {
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Create a temporary file
    $temp_file = $file_path . '.tmp';
    
    // Write to the temporary file
    $success = file_put_contents(
        $temp_file,
        json_encode($data, JSON_PRETTY_PRINT),
        LOCK_EX
    );
    
    if ($success === false) {
        error_log('Failed to write to temporary file: ' . $temp_file);
        return false;
    }
    
    // Rename the temporary file to the actual file (atomic operation)
    if (!rename($temp_file, $file_path)) {
        error_log('Failed to rename temporary file to: ' . $file_path);
        unlink($temp_file); // Clean up the temporary file
        return false;
    }
    
    return true;
}

/**
 * Generate a unique ID
 * 
 * @param string $prefix Optional prefix for the ID
 * @return string Unique ID
 */
function generate_id($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(4));
}

/**
 * Initialize all required JSON files with default structures
 */
function init_database() {
    // Default tournament structure
    $tournaments_default = [
        'tournaments' => []
    ];
    
    // Default matches structure
    $matches_default = [
        'matches' => []
    ];
    
    // Default users structure
    $users_default = [
        'users' => []
    ];
    
    // Default participants structure
    $participants_default = [
        'participants' => []
    ];
    
    // Default predictions structure
    $predictions_default = [
        'predictions' => []
    ];
    
    // Default statistics structure
    $statistics_default = [
        'statistics' => []
    ];
    
    // Initialize all JSON files
    init_json_file(TOURNAMENTS_FILE, $tournaments_default);
    init_json_file(MATCHES_FILE, $matches_default);
    init_json_file(USERS_FILE, $users_default);
    init_json_file(PARTICIPANTS_FILE, $participants_default);
    init_json_file(PREDICTIONS_FILE, $predictions_default);
    init_json_file(STATISTICS_FILE, $statistics_default);
}

/**
 * Get a specific tournament by ID
 * 
 * @param string $tournament_id Tournament ID
 * @return array|null Tournament data or null if not found
 */
function get_tournament($tournament_id) {
    $tournaments = read_json_file(TOURNAMENTS_FILE);
    if (!$tournaments) {
        return null;
    }
    
    foreach ($tournaments['tournaments'] as $tournament) {
        if ($tournament['id'] === $tournament_id) {
            return $tournament;
        }
    }
    
    return null;
}

/**
 * Save a tournament
 * 
 * @param array $tournament Tournament data
 * @return bool True if successful, false otherwise
 */
function save_tournament($tournament) {
    $tournaments = read_json_file(TOURNAMENTS_FILE);
    if (!$tournaments) {
        $tournaments = ['tournaments' => []];
    }
    
    $found = false;
    foreach ($tournaments['tournaments'] as $key => $existing_tournament) {
        if ($existing_tournament['id'] === $tournament['id']) {
            $tournaments['tournaments'][$key] = $tournament;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $tournaments['tournaments'][] = $tournament;
    }
    
    return write_json_file(TOURNAMENTS_FILE, $tournaments);
}

/**
 * Delete a tournament
 * 
 * @param string $tournament_id Tournament ID
 * @return bool True if successful, false otherwise
 */
function delete_tournament($tournament_id) {
    $tournaments = read_json_file(TOURNAMENTS_FILE);
    if (!$tournaments) {
        return false;
    }
    
    foreach ($tournaments['tournaments'] as $key => $tournament) {
        if ($tournament['id'] === $tournament_id) {
            unset($tournaments['tournaments'][$key]);
            $tournaments['tournaments'] = array_values($tournaments['tournaments']);
            return write_json_file(TOURNAMENTS_FILE, $tournaments);
        }
    }
    
    return false;
}

/**
 * Get all tournaments
 * 
 * @return array Array of tournaments
 */
function get_all_tournaments() {
    $tournaments = read_json_file(TOURNAMENTS_FILE);
    if (!$tournaments) {
        return [];
    }
    
    return $tournaments['tournaments'];
}

/**
 * Get matches for a tournament
 * 
 * @param string $tournament_id Tournament ID
 * @return array Array of matches
 */
function get_tournament_matches($tournament_id) {
    $matches = read_json_file(MATCHES_FILE);
    if (!$matches) {
        return [];
    }
    
    $tournament_matches = [];
    foreach ($matches['matches'] as $match) {
        if ($match['tournament_id'] === $tournament_id) {
            $tournament_matches[] = $match;
        }
    }
    
    return $tournament_matches;
}

/**
 * Get a specific match by ID
 * 
 * @param string $match_id Match ID
 * @return array|null Match data or null if not found
 */
function get_match($match_id) {
    $matches = read_json_file(MATCHES_FILE);
    if (!$matches) {
        return null;
    }
    
    foreach ($matches['matches'] as $match) {
        if ($match['id'] === $match_id) {
            return $match;
        }
    }
    
    return null;
}

/**
 * Save a match
 * 
 * @param array $match Match data
 * @return bool True if successful, false otherwise
 */
function save_match($match) {
    $matches = read_json_file(MATCHES_FILE);
    if (!$matches) {
        $matches = ['matches' => []];
    }
    
    $found = false;
    foreach ($matches['matches'] as $key => $existing_match) {
        if ($existing_match['id'] === $match['id']) {
            $matches['matches'][$key] = $match;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $matches['matches'][] = $match;
    }
    
    return write_json_file(MATCHES_FILE, $matches);
}

/**
 * Get all participants
 * 
 * @return array Array of participants
 */
function get_all_participants() {
    $participants = read_json_file(PARTICIPANTS_FILE);
    if (!$participants) {
        return [];
    }
    
    return $participants['participants'];
}

/**
 * Get a specific participant by ID
 * 
 * @param string $participant_id Participant ID
 * @return array|null Participant data or null if not found
 */
function get_participant($participant_id) {
    $participants = read_json_file(PARTICIPANTS_FILE);
    if (!$participants) {
        return null;
    }
    
    foreach ($participants['participants'] as $participant) {
        if ($participant['id'] === $participant_id) {
            return $participant;
        }
    }
    
    return null;
}

/**
 * Save a participant
 * 
 * @param array $participant Participant data
 * @return bool True if successful, false otherwise
 */
function save_participant($participant) {
    $participants = read_json_file(PARTICIPANTS_FILE);
    if (!$participants) {
        $participants = ['participants' => []];
    }
    
    $found = false;
    foreach ($participants['participants'] as $key => $existing_participant) {
        if ($existing_participant['id'] === $participant['id']) {
            $participants['participants'][$key] = $participant;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $participants['participants'][] = $participant;
    }
    
    return write_json_file(PARTICIPANTS_FILE, $participants);
}

/**
 * Delete a participant
 * 
 * @param string $participant_id Participant ID
 * @return bool True if successful, false otherwise
 */
function delete_participant($participant_id) {
    $participants = read_json_file(PARTICIPANTS_FILE);
    if (!$participants) {
        return false;
    }
    
    foreach ($participants['participants'] as $key => $participant) {
        if ($participant['id'] === $participant_id) {
            unset($participants['participants'][$key]);
            $participants['participants'] = array_values($participants['participants']);
            return write_json_file(PARTICIPANTS_FILE, $participants);
        }
    }
    
    return false;
}

/**
 * Get all users
 * 
 * @return array Array of users
 */
function get_all_users() {
    $users = read_json_file(USERS_FILE);
    if (!$users) {
        return [];
    }
    
    return $users['users'];
}

/**
 * Get a specific user by username
 * 
 * @param string $username Username
 * @return array|null User data or null if not found
 */
function get_user_by_username($username) {
    $users = read_json_file(USERS_FILE);
    if (!$users) {
        return null;
    }
    
    foreach ($users['users'] as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    
    return null;
}

/**
 * Save a user
 * 
 * @param array $user User data
 * @return bool True if successful, false otherwise
 */
function save_user($user) {
    $users = read_json_file(USERS_FILE);
    if (!$users) {
        $users = ['users' => []];
    }
    
    $found = false;
    foreach ($users['users'] as $key => $existing_user) {
        if ($existing_user['id'] === $user['id']) {
            $users['users'][$key] = $user;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $users['users'][] = $user;
    }
    
    return write_json_file(USERS_FILE, $users);
}
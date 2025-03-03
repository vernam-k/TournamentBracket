<?php
/**
 * Tournament functions
 */

require_once INCLUDES_PATH . '/database.php';

/**
 * Create a new tournament
 * 
 * @param string $name Tournament name
 * @param string $type Tournament type (single_elimination, double_elimination, round_robin)
 * @param string $created_by Username of creator
 * @return string|bool Tournament ID if successful, false otherwise
 */
function create_tournament($name, $type, $created_by) {
    $tournament_id = generate_id('t');
    
    $tournament = [
        'id' => $tournament_id,
        'name' => $name,
        'type' => $type,
        'status' => 'setup', // setup, active, completed
        'created_at' => date('Y-m-d\TH:i:s\Z'),
        'created_by' => $created_by,
        'participants' => [],
        'rounds' => [],
        'current_round' => 0,
        'winner' => null
    ];
    
    if (save_tournament($tournament)) {
        return $tournament_id;
    }
    
    return false;
}

/**
 * Add a participant to a tournament
 * 
 * @param string $tournament_id Tournament ID
 * @param string $participant_id Participant ID
 * @return bool True if successful, false otherwise
 */
function add_tournament_participant($tournament_id, $participant_id) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament) {
        return false;
    }
    
    // Check if tournament is in setup status
    if ($tournament['status'] !== 'setup') {
        return false;
    }
    
    // Check if participant already exists in tournament
    if (in_array($participant_id, $tournament['participants'])) {
        return true; // Already added
    }
    
    // Add participant
    $tournament['participants'][] = $participant_id;
    
    return save_tournament($tournament);
}

/**
 * Remove a participant from a tournament
 * 
 * @param string $tournament_id Tournament ID
 * @param string $participant_id Participant ID
 * @return bool True if successful, false otherwise
 */
function remove_tournament_participant($tournament_id, $participant_id) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament) {
        return false;
    }
    
    // Check if tournament is in setup status
    if ($tournament['status'] !== 'setup') {
        return false;
    }
    
    // Find and remove participant
    $key = array_search($participant_id, $tournament['participants']);
    if ($key !== false) {
        unset($tournament['participants'][$key]);
        $tournament['participants'] = array_values($tournament['participants']);
        return save_tournament($tournament);
    }
    
    return true; // Participant not found, nothing to remove
}

/**
 * Start a tournament
 * 
 * @param string $tournament_id Tournament ID
 * @return bool True if successful, false otherwise
 */
function start_tournament($tournament_id) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament) {
        return false;
    }
    
    // Check if tournament is in setup status
    if ($tournament['status'] !== 'setup') {
        return false;
    }
    
    // Check if there are enough participants
    if (count($tournament['participants']) < 2) {
        return false;
    }
    
    // Generate brackets based on tournament type
    switch ($tournament['type']) {
        case 'single_elimination':
            $success = generate_single_elimination_brackets($tournament);
            break;
        case 'double_elimination':
            $success = generate_double_elimination_brackets($tournament);
            break;
        case 'round_robin':
            $success = generate_round_robin_brackets($tournament);
            break;
        default:
            return false;
    }
    
    if (!$success) {
        return false;
    }
    
    // Update tournament status
    $tournament['status'] = 'active';
    $tournament['current_round'] = 1;
    
    return save_tournament($tournament);
}

/**
 * Generate single elimination tournament brackets
 * 
 * @param array $tournament Tournament data
 * @return bool True if successful, false otherwise
 */
function generate_single_elimination_brackets(&$tournament) {
    $participants = $tournament['participants'];
    $participant_count = count($participants);
    
    // Shuffle participants for random seeding
    shuffle($participants);
    
    // Calculate number of rounds needed
    $rounds_needed = ceil(log($participant_count, 2));
    
    // Calculate total number of matches needed
    $total_matches = pow(2, $rounds_needed) - 1;
    
    // Calculate number of byes needed
    $perfect_bracket_size = pow(2, $rounds_needed);
    $byes_needed = $perfect_bracket_size - $participant_count;
    
    // Initialize rounds array
    $tournament['rounds'] = [];
    
    // Generate first round matches
    $first_round_matches = [];
    $match_count = $participant_count - $byes_needed;
    $match_count = $match_count / 2;
    
    for ($i = 0; $i < $match_count; $i++) {
        $match_id = generate_id('m');
        
        $match = [
            'id' => $match_id,
            'tournament_id' => $tournament['id'],
            'round' => 1,
            'match_number' => $i + 1,
            'participant1' => $participants[$i * 2],
            'participant2' => $participants[$i * 2 + 1],
            'score1' => 0,
            'score2' => 0,
            'winner' => null,
            'status' => 'pending',
            'next_match' => null
        ];
        
        save_match($match);
        $first_round_matches[] = $match_id;
    }
    
    // Add byes directly to second round
    $second_round_matches = [];
    $bye_participants = array_slice($participants, $match_count * 2);
    
    // Create first round in tournament
    $tournament['rounds'][] = [
        'round_number' => 1,
        'matches' => $first_round_matches
    ];
    
    // Generate subsequent rounds
    $current_round_matches = $first_round_matches;
    $bye_index = 0;
    
    for ($round = 2; $round <= $rounds_needed; $round++) {
        $next_round_matches = [];
        $matches_in_round = ceil(count($current_round_matches) / 2) + floor($byes_needed / pow(2, $round - 2));
        
        for ($i = 0; $i < $matches_in_round; $i++) {
            $match_id = generate_id('m');
            
            // Determine participants
            $participant1 = null;
            $participant2 = null;
            
            // If there are byes for this round
            if ($bye_index < count($bye_participants) && $round == 2) {
                $participant1 = $bye_participants[$bye_index];
                $bye_index++;
            }
            
            $match = [
                'id' => $match_id,
                'tournament_id' => $tournament['id'],
                'round' => $round,
                'match_number' => $i + 1,
                'participant1' => $participant1,
                'participant2' => $participant2,
                'score1' => 0,
                'score2' => 0,
                'winner' => null,
                'status' => 'pending',
                'next_match' => null
            ];
            
            save_match($match);
            $next_round_matches[] = $match_id;
            
            // Update next_match for previous round matches
            if ($round > 1 && isset($current_round_matches[$i * 2])) {
                $prev_match = get_match($current_round_matches[$i * 2]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
            
            if ($round > 1 && isset($current_round_matches[$i * 2 + 1])) {
                $prev_match = get_match($current_round_matches[$i * 2 + 1]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
        }
        
        // Add round to tournament
        $tournament['rounds'][] = [
            'round_number' => $round,
            'matches' => $next_round_matches
        ];
        
        $current_round_matches = $next_round_matches;
    }
    
    return true;
}

/**
 * Generate double elimination tournament brackets
 * 
 * @param array $tournament Tournament data
 * @return bool True if successful, false otherwise
 */
function generate_double_elimination_brackets(&$tournament) {
    $participants = $tournament['participants'];
    $participant_count = count($participants);
    
    // Shuffle participants for random seeding
    shuffle($participants);
    
    // Calculate number of rounds needed for winners bracket
    $winners_rounds_needed = ceil(log($participant_count, 2));
    
    // Initialize rounds array
    $tournament['rounds'] = [];
    
    // Generate winners bracket (similar to single elimination)
    $winners_bracket = [];
    $losers_bracket = [];
    
    // Generate first round matches for winners bracket
    $first_round_matches = [];
    $perfect_bracket_size = pow(2, $winners_rounds_needed);
    $byes_needed = $perfect_bracket_size - $participant_count;
    $match_count = $participant_count - $byes_needed;
    $match_count = $match_count / 2;
    
    for ($i = 0; $i < $match_count; $i++) {
        $match_id = generate_id('m');
        
        $match = [
            'id' => $match_id,
            'tournament_id' => $tournament['id'],
            'round' => 1,
            'bracket' => 'winners',
            'match_number' => $i + 1,
            'participant1' => $participants[$i * 2],
            'participant2' => $participants[$i * 2 + 1],
            'score1' => 0,
            'score2' => 0,
            'winner' => null,
            'loser' => null,
            'status' => 'pending',
            'next_match' => null,
            'next_loser_match' => null
        ];
        
        save_match($match);
        $first_round_matches[] = $match_id;
        $winners_bracket[1][] = $match_id;
    }
    
    // Add byes directly to second round
    $bye_participants = array_slice($participants, $match_count * 2);
    
    // Create first round in tournament
    $tournament['rounds'][] = [
        'round_number' => 1,
        'bracket' => 'winners',
        'matches' => $first_round_matches
    ];
    
    // Generate subsequent rounds for winners bracket
    $current_round_matches = $first_round_matches;
    $bye_index = 0;
    
    for ($round = 2; $round <= $winners_rounds_needed; $round++) {
        $next_round_matches = [];
        $matches_in_round = ceil(count($current_round_matches) / 2) + floor($byes_needed / pow(2, $round - 2));
        
        for ($i = 0; $i < $matches_in_round; $i++) {
            $match_id = generate_id('m');
            
            // Determine participants
            $participant1 = null;
            $participant2 = null;
            
            // If there are byes for this round
            if ($bye_index < count($bye_participants) && $round == 2) {
                $participant1 = $bye_participants[$bye_index];
                $bye_index++;
            }
            
            $match = [
                'id' => $match_id,
                'tournament_id' => $tournament['id'],
                'round' => $round,
                'bracket' => 'winners',
                'match_number' => $i + 1,
                'participant1' => $participant1,
                'participant2' => $participant2,
                'score1' => 0,
                'score2' => 0,
                'winner' => null,
                'loser' => null,
                'status' => 'pending',
                'next_match' => null,
                'next_loser_match' => null
            ];
            
            save_match($match);
            $next_round_matches[] = $match_id;
            $winners_bracket[$round][] = $match_id;
            
            // Update next_match for previous round matches
            if ($round > 1 && isset($current_round_matches[$i * 2])) {
                $prev_match = get_match($current_round_matches[$i * 2]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
            
            if ($round > 1 && isset($current_round_matches[$i * 2 + 1])) {
                $prev_match = get_match($current_round_matches[$i * 2 + 1]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
        }
        
        // Add round to tournament
        $tournament['rounds'][] = [
            'round_number' => $round,
            'bracket' => 'winners',
            'matches' => $next_round_matches
        ];
        
        $current_round_matches = $next_round_matches;
    }
    
    // Generate losers bracket
    $losers_rounds_needed = 2 * $winners_rounds_needed - 1;
    $current_losers_round = 1;
    
    // In double elimination, losers from winners bracket round N go to losers bracket round 2N-1
    for ($winners_round = 1; $winners_round < $winners_rounds_needed; $winners_round++) {
        $losers_round = 2 * $winners_round - 1;
        $losers_bracket[$losers_round] = [];
        
        // Create matches for losers coming from winners bracket
        $losers_matches = [];
        $matches_in_round = ceil(count($winners_bracket[$winners_round]) / 2);
        
        for ($i = 0; $i < $matches_in_round; $i++) {
            $match_id = generate_id('m');
            
            $match = [
                'id' => $match_id,
                'tournament_id' => $tournament['id'],
                'round' => $losers_round,
                'bracket' => 'losers',
                'match_number' => $i + 1,
                'participant1' => null,
                'participant2' => null,
                'score1' => 0,
                'score2' => 0,
                'winner' => null,
                'loser' => null,
                'status' => 'pending',
                'next_match' => null
            ];
            
            save_match($match);
            $losers_matches[] = $match_id;
            $losers_bracket[$losers_round][] = $match_id;
            
            // Update next_loser_match for winners bracket matches
            if (isset($winners_bracket[$winners_round][$i * 2])) {
                $winners_match = get_match($winners_bracket[$winners_round][$i * 2]);
                if ($winners_match) {
                    $winners_match['next_loser_match'] = $match_id;
                    save_match($winners_match);
                }
            }
            
            if (isset($winners_bracket[$winners_round][$i * 2 + 1])) {
                $winners_match = get_match($winners_bracket[$winners_round][$i * 2 + 1]);
                if ($winners_match) {
                    $winners_match['next_loser_match'] = $match_id;
                    save_match($winners_match);
                }
            }
        }
        
        // Add round to tournament
        $tournament['rounds'][] = [
            'round_number' => $losers_round,
            'bracket' => 'losers',
            'matches' => $losers_matches
        ];
        
        // Create the next round in losers bracket (consolidation round)
        $losers_round = 2 * $winners_round;
        $losers_bracket[$losers_round] = [];
        $consolidation_matches = [];
        $matches_in_round = floor(count($losers_matches) / 2);
        
        for ($i = 0; $i < $matches_in_round; $i++) {
            $match_id = generate_id('m');
            
            $match = [
                'id' => $match_id,
                'tournament_id' => $tournament['id'],
                'round' => $losers_round,
                'bracket' => 'losers',
                'match_number' => $i + 1,
                'participant1' => null,
                'participant2' => null,
                'score1' => 0,
                'score2' => 0,
                'winner' => null,
                'loser' => null,
                'status' => 'pending',
                'next_match' => null
            ];
            
            save_match($match);
            $consolidation_matches[] = $match_id;
            $losers_bracket[$losers_round][] = $match_id;
            
            // Update next_match for previous losers round matches
            if (isset($losers_matches[$i * 2])) {
                $prev_match = get_match($losers_matches[$i * 2]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
            
            if (isset($losers_matches[$i * 2 + 1])) {
                $prev_match = get_match($losers_matches[$i * 2 + 1]);
                if ($prev_match) {
                    $prev_match['next_match'] = $match_id;
                    save_match($prev_match);
                }
            }
        }
        
        // Add round to tournament
        $tournament['rounds'][] = [
            'round_number' => $losers_round,
            'bracket' => 'losers',
            'matches' => $consolidation_matches
        ];
    }
    
    // Create final match (winners bracket champion vs losers bracket champion)
    $final_match_id = generate_id('m');
    $final_match = [
        'id' => $final_match_id,
        'tournament_id' => $tournament['id'],
        'round' => $winners_rounds_needed + 1,
        'bracket' => 'finals',
        'match_number' => 1,
        'participant1' => null, // Winners bracket champion
        'participant2' => null, // Losers bracket champion
        'score1' => 0,
        'score2' => 0,
        'winner' => null,
        'loser' => null,
        'status' => 'pending',
        'next_match' => null
    ];
    
    save_match($final_match);
    
    // Add finals round to tournament
    $tournament['rounds'][] = [
        'round_number' => $winners_rounds_needed + 1,
        'bracket' => 'finals',
        'matches' => [$final_match_id]
    ];
    
    // Update next_match for winners bracket final and losers bracket final
    if (isset($winners_bracket[$winners_rounds_needed][0])) {
        $winners_final = get_match($winners_bracket[$winners_rounds_needed][0]);
        if ($winners_final) {
            $winners_final['next_match'] = $final_match_id;
            save_match($winners_final);
        }
    }
    
    $last_losers_round = 2 * ($winners_rounds_needed - 1);
    if (isset($losers_bracket[$last_losers_round][0])) {
        $losers_final = get_match($losers_bracket[$last_losers_round][0]);
        if ($losers_final) {
            $losers_final['next_match'] = $final_match_id;
            save_match($losers_final);
        }
    }
    
    return true;
}

/**
 * Generate round robin tournament brackets
 * 
 * @param array $tournament Tournament data
 * @return bool True if successful, false otherwise
 */
function generate_round_robin_brackets(&$tournament) {
    $participants = $tournament['participants'];
    $participant_count = count($participants);
    
    // If odd number of participants, add a "bye" participant
    $has_bye = false;
    if ($participant_count % 2 != 0) {
        $has_bye = true;
        $participants[] = 'bye';
        $participant_count++;
    }
    
    // Number of rounds needed for round robin
    $rounds_needed = $participant_count - 1;
    
    // Number of matches per round
    $matches_per_round = $participant_count / 2;
    
    // Initialize rounds array
    $tournament['rounds'] = [];
    
    // Generate schedule using circle method
    $fixed_participant = $participants[0];
    $rotating_participants = array_slice($participants, 1);
    
    for ($round = 1; $round <= $rounds_needed; $round++) {
        $round_matches = [];
        
        // First match is always fixed_participant vs rotating_participants[0]
        if ($fixed_participant !== 'bye' && $rotating_participants[0] !== 'bye') {
            $match_id = generate_id('m');
            
            $match = [
                'id' => $match_id,
                'tournament_id' => $tournament['id'],
                'round' => $round,
                'match_number' => 1,
                'participant1' => $fixed_participant,
                'participant2' => $rotating_participants[0],
                'score1' => 0,
                'score2' => 0,
                'winner' => null,
                'status' => 'pending',
                'next_match' => null
            ];
            
            save_match($match);
            $round_matches[] = $match_id;
        }
        
        // Generate other matches for this round
        for ($match = 1; $match < $matches_per_round; $match++) {
            $participant1 = $rotating_participants[$match];
            $participant2 = $rotating_participants[$participant_count - 2 - $match];
            
            if ($participant1 !== 'bye' && $participant2 !== 'bye') {
                $match_id = generate_id('m');
                
                $match = [
                    'id' => $match_id,
                    'tournament_id' => $tournament['id'],
                    'round' => $round,
                    'match_number' => $match + 1,
                    'participant1' => $participant1,
                    'participant2' => $participant2,
                    'score1' => 0,
                    'score2' => 0,
                    'winner' => null,
                    'status' => 'pending',
                    'next_match' => null
                ];
                
                save_match($match);
                $round_matches[] = $match_id;
            }
        }
        
        // Add round to tournament
        $tournament['rounds'][] = [
            'round_number' => $round,
            'matches' => $round_matches
        ];
        
        // Rotate participants for next round
        array_unshift($rotating_participants, array_pop($rotating_participants));
    }
    
    return true;
}

/**
 * Update match score
 * 
 * @param string $match_id Match ID
 * @param int $score1 Score for participant 1
 * @param int $score2 Score for participant 2
 * @return bool True if successful, false otherwise
 */
function update_match_score($match_id, $score1, $score2) {
    $match = get_match($match_id);
    if (!$match) {
        return false;
    }
    
    // Update scores
    $match['score1'] = $score1;
    $match['score2'] = $score2;
    
    // Determine winner
    if ($score1 > $score2) {
        $match['winner'] = $match['participant1'];
        if (isset($match['loser'])) {
            $match['loser'] = $match['participant2'];
        }
    } elseif ($score2 > $score1) {
        $match['winner'] = $match['participant2'];
        if (isset($match['loser'])) {
            $match['loser'] = $match['participant1'];
        }
    } else {
        $match['winner'] = null; // Tie
        if (isset($match['loser'])) {
            $match['loser'] = null;
        }
    }
    
    // Update match status
    $match['status'] = 'completed';
    
    // Save match
    if (!save_match($match)) {
        return false;
    }
    
    // If there's a next match, update it with the winner
    if ($match['next_match']) {
        $next_match = get_match($match['next_match']);
        if ($next_match) {
            // Determine which participant slot to update
            if ($next_match['participant1'] === null) {
                $next_match['participant1'] = $match['winner'];
            } else {
                $next_match['participant2'] = $match['winner'];
            }
            
            save_match($next_match);
        }
    }
    
    // If there's a next loser match, update it with the loser
    if (isset($match['next_loser_match']) && $match['next_loser_match']) {
        $next_loser_match = get_match($match['next_loser_match']);
        if ($next_loser_match) {
            // Determine which participant slot to update
            if ($next_loser_match['participant1'] === null) {
                $next_loser_match['participant1'] = $match['loser'];
            } else {
                $next_loser_match['participant2'] = $match['loser'];
            }
            
            save_match($next_loser_match);
        }
    }
    
    // Check if round is complete and advance tournament if needed
    check_round_completion($match['tournament_id'], $match['round']);
    
    return true;
}

/**
 * Check if a round is complete and advance tournament if needed
 * 
 * @param string $tournament_id Tournament ID
 * @param int $round_number Round number
 * @return bool True if round is complete, false otherwise
 */
function check_round_completion($tournament_id, $round_number) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament) {
        return false;
    }
    
    // Find the round
    $round = null;
    foreach ($tournament['rounds'] as $r) {
        if ($r['round_number'] == $round_number) {
            $round = $r;
            break;
        }
    }
    
    if (!$round) {
        return false;
    }
    
    // Check if all matches in the round are completed
    $all_completed = true;
    foreach ($round['matches'] as $match_id) {
        $match = get_match($match_id);
        if (!$match || $match['status'] !== 'completed') {
            $all_completed = false;
            break;
        }
    }
    
    if (!$all_completed) {
        return false;
    }
    
    // If this is the final round, determine the tournament winner
    $is_final_round = true;
    foreach ($tournament['rounds'] as $r) {
        if ($r['round_number'] > $round_number) {
            $is_final_round = false;
            break;
        }
    }
    
    if ($is_final_round) {
        // Get the final match
        $final_match = get_match($round['matches'][0]);
        if ($final_match && $final_match['winner']) {
            $tournament['winner'] = $final_match['winner'];
            $tournament['status'] = 'completed';
            save_tournament($tournament);
        }
    } else {
        // Advance to next round
        $tournament['current_round'] = $round_number + 1;
        save_tournament($tournament);
    }
    
    return true;
}

/**
 * Get tournament standings for round robin
 * 
 * @param string $tournament_id Tournament ID
 * @return array Array of participant standings
 */
function get_round_robin_standings($tournament_id) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament || $tournament['type'] !== 'round_robin') {
        return [];
    }
    
    $matches = get_tournament_matches($tournament_id);
    $participants = [];
    
    // Initialize standings for each participant
    foreach ($tournament['participants'] as $participant_id) {
        $participant = get_participant($participant_id);
        $participants[$participant_id] = [
            'id' => $participant_id,
            'name' => $participant ? $participant['name'] : 'Unknown',
            'wins' => 0,
            'losses' => 0,
            'ties' => 0,
            'points' => 0,
            'points_against' => 0,
            'point_differential' => 0
        ];
    }
    
    // Calculate standings based on completed matches
    foreach ($matches as $match) {
        if ($match['status'] === 'completed') {
            $participant1 = $match['participant1'];
            $participant2 = $match['participant2'];
            $score1 = $match['score1'];
            $score2 = $match['score2'];
            
            // Update participant 1 stats
            if (isset($participants[$participant1])) {
                $participants[$participant1]['points'] += $score1;
                $participants[$participant1]['points_against'] += $score2;
                
                if ($score1 > $score2) {
                    $participants[$participant1]['wins']++;
                } elseif ($score1 < $score2) {
                    $participants[$participant1]['losses']++;
                } else {
                    $participants[$participant1]['ties']++;
                }
            }
            
            // Update participant 2 stats
            if (isset($participants[$participant2])) {
                $participants[$participant2]['points'] += $score2;
                $participants[$participant2]['points_against'] += $score1;
                
                if ($score2 > $score1) {
                    $participants[$participant2]['wins']++;
                } elseif ($score2 < $score1) {
                    $participants[$participant2]['losses']++;
                } else {
                    $participants[$participant2]['ties']++;
                }
            }
        }
    }
    
    // Calculate point differential
    foreach ($participants as &$participant) {
        $participant['point_differential'] = $participant['points'] - $participant['points_against'];
    }
    
    // Sort standings by wins, then point differential
    usort($participants, function($a, $b) {
        if ($a['wins'] != $b['wins']) {
            return $b['wins'] - $a['wins']; // Sort by wins (descending)
        }
        return $b['point_differential'] - $a['point_differential']; // Then by point differential (descending)
    });
    
    return array_values($participants);
}

/**
 * Get tournament bracket data for visualization
 * 
 * @param string $tournament_id Tournament ID
 * @return array Array of bracket data
 */
function get_tournament_bracket_data($tournament_id) {
    $tournament = get_tournament($tournament_id);
    if (!$tournament) {
        return [];
    }
    
    $matches = get_tournament_matches($tournament_id);
    $participants = [];
    
    // Get participant details
    foreach ($tournament['participants'] as $participant_id) {
        $participant = get_participant($participant_id);
        $participants[$participant_id] = $participant ? $participant['name'] : 'Unknown';
    }
    
    // Organize matches by round
    $rounds = [];
    foreach ($matches as $match) {
        $round = $match['round'];
        
        if (!isset($rounds[$round])) {
            $rounds[$round] = [];
        }
        
        // Get participant names
        $participant1_name = isset($participants[$match['participant1']]) ? $participants[$match['participant1']] : '';
        $participant2_name = isset($participants[$match['participant2']]) ? $participants[$match['participant2']] : '';
        
        $rounds[$round][] = [
            'id' => $match['id'],
            'match_number' => $match['match_number'],
            'participant1' => [
                'id' => $match['participant1'],
                'name' => $participant1_name,
                'score' => $match['score1']
            ],
            'participant2' => [
                'id' => $match['participant2'],
                'name' => $participant2_name,
                'score' => $match['score2']
            ],
            'winner' => $match['winner'],
            'status' => $match['status'],
            'next_match' => $match['next_match']
        ];
    }
    
    // Sort matches within each round
    foreach ($rounds as &$round_matches) {
        usort($round_matches, function($a, $b) {
            return $a['match_number'] - $b['match_number'];
        });
    }
    
    // For double elimination, separate winners and losers brackets
    if ($tournament['type'] === 'double_elimination') {
        $bracket_data = [
            'winners' => [],
            'losers' => [],
            'finals' => []
        ];
        
        foreach ($matches as $match) {
            if (isset($match['bracket'])) {
                $bracket = $match['bracket'];
                $round = $match['round'];
                
                if (!isset($bracket_data[$bracket][$round])) {
                    $bracket_data[$bracket][$round] = [];
                }
                
                // Get participant names
                $participant1_name = isset($participants[$match['participant1']]) ? $participants[$match['participant1']] : '';
                $participant2_name = isset($participants[$match['participant2']]) ? $participants[$match['participant2']] : '';
                
                $bracket_data[$bracket][$round][] = [
                    'id' => $match['id'],
                    'match_number' => $match['match_number'],
                    'participant1' => [
                        'id' => $match['participant1'],
                        'name' => $participant1_name,
                        'score' => $match['score1']
                    ],
                    'participant2' => [
                        'id' => $match['participant2'],
                        'name' => $participant2_name,
                        'score' => $match['score2']
                    ],
                    'winner' => $match['winner'],
                    'status' => $match['status'],
                    'next_match' => $match['next_match'],
                    'next_loser_match' => isset($match['next_loser_match']) ? $match['next_loser_match'] : null
                ];
            }
        }
        
        // Sort matches within each round
        foreach ($bracket_data as &$bracket) {
            foreach ($bracket as &$round_matches) {
                usort($round_matches, function($a, $b) {
                    return $a['match_number'] - $b['match_number'];
                });
            }
        }
        
        return [
            'type' => 'double_elimination',
            'brackets' => $bracket_data,
            'current_round' => $tournament['current_round'],
            'status' => $tournament['status'],
            'winner' => $tournament['winner'] ? $participants[$tournament['winner']] : null
        ];
    }
    
    return [
        'type' => $tournament['type'],
        'rounds' => $rounds,
        'current_round' => $tournament['current_round'],
        'status' => $tournament['status'],
        'winner' => $tournament['winner'] ? $participants[$tournament['winner']] : null
    ];
}
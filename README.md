# Tournament Bracket System

A comprehensive PHP-based tournament bracket management system that uses JSON for data storage.

## Features

- **Multiple Tournament Types**: Support for single elimination, double elimination, and round robin tournaments
- **Admin Dashboard**: Create tournaments, manage participants, and log match scores
- **Real-time Updates**: AJAX polling for live updates without page refresh
- **Responsive Design**: Optimized for both desktop and mobile devices
- **User Registration**: Optional user accounts with prediction capabilities
- **Statistics**: Comprehensive tournament and participant statistics
- **Print Functionality**: Print-friendly tournament brackets

## Requirements

- PHP 8.2 or higher
- Web server with URL rewriting (Apache, Nginx, etc.)
- Modern web browser with JavaScript enabled

## Installation

1. Clone or download this repository to your web server
2. Ensure the `data` directory is writable by the web server
3. Configure your web server to serve the application
4. Access the application through your web browser
5. Log in as admin using the default credentials (see below)

## Default Admin Credentials

- **Username**: admin
- **Password**: password123

**Important**: Change the default admin password in `config.php` before deploying to production.

## Directory Structure

```
/
├── index.php                 # Main entry point
├── config.php                # Configuration (includes admin credentials)
├── api/                      # API endpoints for AJAX
│   ├── tournaments.php
│   ├── matches.php
│   ├── users.php
│   └── predictions.php
├── includes/                 # Core PHP functionality
│   ├── auth.php              # Authentication
│   ├── database.php          # JSON data handling
│   ├── tournament.php        # Tournament logic
│   └── utils.php             # Helper functions
├── data/                     # JSON storage (with .htaccess protection)
│   ├── tournaments.json
│   ├── matches.json
│   ├── users.json
│   └── predictions.json
├── assets/                   # Frontend resources
│   ├── css/
│   ├── js/
│   └── images/
└── templates/                # HTML templates
    ├── header.php
    ├── footer.php
    ├── tournament.php
    └── admin/
```

## Usage

### Admin

1. Log in as admin
2. Create participants in the admin dashboard
3. Create a new tournament
4. Add participants to the tournament
5. Start the tournament to generate brackets
6. Update match scores as games are played

### Users

1. Register for an account (optional)
2. View active tournaments
3. Make predictions on upcoming matches
4. View tournament statistics and leaderboards

## Tournament Types

### Single Elimination

In a single elimination tournament, participants who lose a match are immediately eliminated from the tournament. Each round reduces the number of participants by half, until only one participant remains as the winner.

### Double Elimination

In a double elimination tournament, participants must lose two matches before being eliminated. The tournament consists of a winners bracket and a losers bracket. Participants who lose in the winners bracket move to the losers bracket for a second chance.

### Round Robin

In a round robin tournament, each participant plays against every other participant once. The winner is determined by the participant with the best overall record (most wins, or highest point total).

## JSON Data Structure

The application uses JSON files for data storage:

- **tournaments.json**: Tournament metadata and structure
- **matches.json**: Match results and scheduling
- **participants.json**: Participant information
- **users.json**: User accounts and preferences
- **predictions.json**: User predictions and betting
- **statistics.json**: Tournament and player statistics

## Customization

You can customize the application by:

1. Modifying the CSS files in the `assets/css` directory
2. Updating the templates in the `templates` directory
3. Adjusting configuration settings in `config.php`

## Security

- JSON files are protected from direct access via .htaccess
- Admin authentication is required for all administrative actions
- CSRF protection is implemented for all form submissions
- Input validation and sanitization is performed on all user inputs

## License

This project is licensed under The Unlicense - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the responsive UI framework
- Font Awesome for the icons
- jQuery for DOM manipulation and AJAX requests
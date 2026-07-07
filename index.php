<?php
session_start();

$page = $_GET['page'] ?? 'splash';

const DB_HOST = 'localhost';
const DB_NAME = 'tripnovaa_db';
const DB_USER = 'root';
const DB_PASS = '';

$allowedPages = [
    'splash',
    'get-started',
    'role-selection',
    'user-register',
    'user-login',
    'captain-register',
    'captain-login',
    'admin-login',
    'otp',
    'user-dashboard',
    'book-ride',
    'available-captains',
    'ride-confirm',
    'ride-tracking',
    'ride-success',
    'feedback',
    'feedback-success',
    'payment',
    'payment-success',
    'payment-failed',
    'hotel-search',
    'hotel-list',
    'hotel-book',
    'hotel-success',
    'train-search',
    'train-list',
    'train-book',
    'train-success',
    'bus-search',
    'bus-list',
    'bus-book',
    'bus-success',
    'restaurant-search',
    'restaurant-list',
    'restaurant-book',
    'restaurant-success',
    'plan-trip',
    'plan-trip-transport',
    'plan-trip-options',
    'plan-trip-detail',
    'plan-trip-captain',
    'plan-trip-arrival',
    'plan-trip-accepted',
    'plan-trip-deposit',
    'plan-trip-guide',
    'plan-trip-complete',
    'plan-trip-reminder',
    'group-tours',
    'group-tour-details',
    'group-tour-captain',
    'group-tour-seats',
    'group-tour-advance',
    'group-tour-confirmed',
    'group-tour-booking',
    'group-tour-itinerary',
    'group-tour-during',
    'group-tour-remaining',
    'group-tour-completed',
    'group-tour-more',
    'tour-ticket-search',
    'tour-ticket-results',
    'tour-ticket-book',
    'ticket-success',
    'rewards-offers',
    'apply-offer',
    'post-new-trip',
    'my-trips-posted',
    'driver-offers',
    'saved-trips',
    'trip-messages',
    'driver-chat',
    'my-bookings',
    'user-profile',
    'captain-dashboard',
    'captain-ride-requests',
    'captain-trip-details',
    'captain-accept-trip',
    'captain-advance-payment',
    'captain-navigation',
    'captain-trip-progress',
    'captain-trip-earnings',
    'captain-passenger-details',
    'captain-trip-chat',
    'captain-trip-history',
    'captain-wallet',
    'captain-rewards',
    'captain-profile',
    'captain-earnings-analytics',
    'captain-current-trips',
    'captain-completed-trips',
    'captain-earnings',
    'captain-offers',
    'admin-dashboard',
    'admin-users',
    'admin-captains',
    'admin-rides',
    'admin-hotel-bookings',
    'admin-train-bookings',
    'admin-bus-bookings',
    'admin-restaurant-bookings',
    'admin-ticket-bookings',
    'admin-payments',
    'admin-offers',
    'admin-feedback',
    'logout',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'splash';
}

function db(): ?PDO
{
    static $pdo = null;
    static $failed = false;
    global $dbError;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if ($failed) {
        return null;
    }

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (Throwable $e) {
        $failed = true;
        $dbError = $e->getMessage();
        return null;
    }
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Basic CSRF protection for this single-file app. Every POST form receives this token automatically.
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf_token(): bool
{
    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    return $sessionToken !== '' && $postedToken !== '' && hash_equals($sessionToken, $postedToken);
}

function inject_csrf_tokens(string $html): string
{
    if (stripos($html, '<form') === false) {
        return $html;
    }

    $updated = preg_replace_callback(
        '/<form\b(?=[^>]*\bmethod\s*=\s*["\']?post["\']?)[^>]*>/i',
        static fn(array $match): string => $match[0] . csrf_input(),
        $html
    );

    return $updated ?? $html;
}

ob_start('inject_csrf_tokens');

function post_text(string $key, int $maxLength = 255): string
{
    $value = trim((string) ($_POST[$key] ?? ''));

    return substr($value, 0, $maxLength);
}

function post_email(string $key): string
{
    return strtolower(post_text($key, 190));
}

function is_positive_number($value): bool
{
    return is_numeric($value) && (float) $value > 0;
}

function is_non_negative_number($value): bool
{
    return is_numeric($value) && (float) $value >= 0;
}

function is_positive_integer_value($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

function is_valid_date_ymd(string $date): bool
{
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);

    return $dateObject instanceof DateTime && $dateObject->format('Y-m-d') === $date;
}

function phone_country_code_options(): array
{
    return [
        ['code' => '+92', 'label' => '+92 PK'],
        ['code' => '+91', 'label' => '+91 IN'],
        ['code' => '+1', 'label' => '+1 US/CA'],
        ['code' => '+44', 'label' => '+44 UK'],
        ['code' => '+971', 'label' => '+971 AE'],
        ['code' => '+966', 'label' => '+966 SA'],
        ['code' => '+974', 'label' => '+974 QA'],
        ['code' => '+965', 'label' => '+965 KW'],
        ['code' => '+973', 'label' => '+973 BH'],
        ['code' => '+968', 'label' => '+968 OM'],
        ['code' => '+93', 'label' => '+93 AF'],
        ['code' => '+880', 'label' => '+880 BD'],
        ['code' => '+977', 'label' => '+977 NP'],
        ['code' => '+94', 'label' => '+94 LK'],
        ['code' => '+86', 'label' => '+86 CN'],
        ['code' => '+81', 'label' => '+81 JP'],
        ['code' => '+82', 'label' => '+82 KR'],
        ['code' => '+60', 'label' => '+60 MY'],
        ['code' => '+65', 'label' => '+65 SG'],
        ['code' => '+66', 'label' => '+66 TH'],
        ['code' => '+62', 'label' => '+62 ID'],
        ['code' => '+63', 'label' => '+63 PH'],
        ['code' => '+84', 'label' => '+84 VN'],
        ['code' => '+90', 'label' => '+90 TR'],
        ['code' => '+98', 'label' => '+98 IR'],
        ['code' => '+964', 'label' => '+964 IQ'],
        ['code' => '+962', 'label' => '+962 JO'],
        ['code' => '+961', 'label' => '+961 LB'],
        ['code' => '+20', 'label' => '+20 EG'],
        ['code' => '+27', 'label' => '+27 ZA'],
        ['code' => '+234', 'label' => '+234 NG'],
        ['code' => '+254', 'label' => '+254 KE'],
        ['code' => '+255', 'label' => '+255 TZ'],
        ['code' => '+256', 'label' => '+256 UG'],
        ['code' => '+212', 'label' => '+212 MA'],
        ['code' => '+213', 'label' => '+213 DZ'],
        ['code' => '+216', 'label' => '+216 TN'],
        ['code' => '+218', 'label' => '+218 LY'],
        ['code' => '+251', 'label' => '+251 ET'],
        ['code' => '+233', 'label' => '+233 GH'],
        ['code' => '+33', 'label' => '+33 FR'],
        ['code' => '+49', 'label' => '+49 DE'],
        ['code' => '+39', 'label' => '+39 IT'],
        ['code' => '+34', 'label' => '+34 ES'],
        ['code' => '+31', 'label' => '+31 NL'],
        ['code' => '+32', 'label' => '+32 BE'],
        ['code' => '+41', 'label' => '+41 CH'],
        ['code' => '+43', 'label' => '+43 AT'],
        ['code' => '+46', 'label' => '+46 SE'],
        ['code' => '+47', 'label' => '+47 NO'],
        ['code' => '+45', 'label' => '+45 DK'],
        ['code' => '+358', 'label' => '+358 FI'],
        ['code' => '+48', 'label' => '+48 PL'],
        ['code' => '+351', 'label' => '+351 PT'],
        ['code' => '+353', 'label' => '+353 IE'],
        ['code' => '+30', 'label' => '+30 GR'],
        ['code' => '+7', 'label' => '+7 RU/KZ'],
        ['code' => '+380', 'label' => '+380 UA'],
        ['code' => '+994', 'label' => '+994 AZ'],
        ['code' => '+995', 'label' => '+995 GE'],
        ['code' => '+374', 'label' => '+374 AM'],
        ['code' => '+61', 'label' => '+61 AU'],
        ['code' => '+64', 'label' => '+64 NZ'],
        ['code' => '+55', 'label' => '+55 BR'],
        ['code' => '+52', 'label' => '+52 MX'],
        ['code' => '+54', 'label' => '+54 AR'],
        ['code' => '+56', 'label' => '+56 CL'],
        ['code' => '+57', 'label' => '+57 CO'],
        ['code' => '+51', 'label' => '+51 PE'],
        ['code' => '+58', 'label' => '+58 VE'],
        ['code' => '+593', 'label' => '+593 EC'],
        ['code' => '+595', 'label' => '+595 PY'],
        ['code' => '+598', 'label' => '+598 UY'],
        ['code' => '+1242', 'label' => '+1242 BS'],
        ['code' => '+1246', 'label' => '+1246 BB'],
        ['code' => '+1876', 'label' => '+1876 JM'],
        ['code' => '+1868', 'label' => '+1868 TT'],
    ];
}

function allowed_phone_country_codes(): array
{
    return array_values(array_unique(array_column(phone_country_code_options(), 'code')));
}

function redirect_to(string $targetPage, array $params = []): void
{
    $query = array_merge(['page' => $targetPage], $params);
    header('Location: index.php?' . http_build_query($query));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function render_alerts(): void
{
    $alerts = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    global $dbError;
    if (!empty($dbError)) {
        $alerts[] = [
            'type' => 'warning',
            'message' => 'Database connection issue: ' . $dbError,
        ];
    }

    foreach ($alerts as $alert) {
        $type = $alert['type'] ?? 'info';
        $message = $alert['message'] ?? '';
        echo '<div class="alert alert-' . h($type) . '">' . h($message) . '</div>';
    }
}

function auth(): ?array
{
    return $_SESSION['auth'] ?? null;
}

function is_logged_in(?string $role = null): bool
{
    $auth = auth();

    if (!$auth) {
        if (!isset($_SESSION['role'])) {
            return false;
        }

        return $role === null || $_SESSION['role'] === $role;
    }

    return $role === null || ($auth['role'] ?? '') === $role;
}

function require_role(string $role, string $loginPage): void
{
    if (!is_logged_in($role)) {
        set_flash('warning', 'Please login first to continue.');
        redirect_to($loginPage);
    }
}

function current_user_id(): ?int
{
    $auth = auth();
    if (($auth['role'] ?? '') === 'user') {
        return (int) $auth['id'];
    }

    if (($_SESSION['role'] ?? '') === 'user' && isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    return null;
}

function current_user_name(): string
{
    $auth = auth();
    return $auth['name'] ?? ($_SESSION['user_name'] ?? ($_SESSION['captain_name'] ?? ($_SESSION['admin_name'] ?? 'Traveler')));
}

function current_user_profile(): array
{
    $fallback = [
        'full_name' => current_user_name(),
        'email' => 'user@tripnovaa.com',
        'phone' => 'Not added',
        'city' => 'Peshawar',
        'reward_points' => 0,
        'created_at' => null,
    ];

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        return $fallback;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT full_name, email, phone, city, reward_points, created_at
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);
        $profile = $stmt->fetch();

        return $profile ? array_merge($fallback, $profile) : $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
}

function ride_type_options(): array
{
    return [
        'bike' => ['label' => 'Bike', 'fare' => 300, 'icon' => '🏍'],
        'car' => ['label' => 'Car', 'fare' => 600, 'icon' => '🚗'],
        'premium_car' => ['label' => 'Premium Car', 'fare' => 1000, 'icon' => '🚘'],
        'mini_bus' => ['label' => 'Mini Bus', 'fare' => 1500, 'icon' => '🚐'],
    ];
}

function ride_type_label(string $rideType): string
{
    $options = ride_type_options();
    return $options[$rideType]['label'] ?? ucwords(str_replace('_', ' ', $rideType));
}

function ride_status_label(string $status): string
{
    return match ($status) {
        'captain_selected' => 'Request sent',
        'pending' => 'Waiting for captain',
        'accepted' => 'Accepted',
        'ongoing' => 'In progress',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function ride_type_fare(string $rideType): int
{
    $options = ride_type_options();
    return (int) ($options[$rideType]['fare'] ?? 0);
}

function table_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName,
    ]);
    $row = $stmt->fetch();

    return (int) ($row['total'] ?? 0) > 0;
}

function ensure_ride_table_ready(PDO $pdo): void
{
    $columnExists = function (string $columnName) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = "rides"
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([':column_name' => $columnName]);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0) > 0;
    };

    if (!$columnExists('travel_date')) {
        $pdo->exec('ALTER TABLE rides ADD COLUMN travel_date DATE DEFAULT NULL AFTER ride_type');
    }

    if (!$columnExists('travel_time')) {
        $pdo->exec('ALTER TABLE rides ADD COLUMN travel_time TIME DEFAULT NULL AFTER travel_date');
    }

    if (!$columnExists('requested_at')) {
        $pdo->exec('ALTER TABLE rides ADD COLUMN requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status');
    }

    $pdo->exec("ALTER TABLE rides MODIFY ride_type ENUM('bike', 'car', 'premium_car', 'mini_bus', 'auto', 'mini', 'sedan', 'suv') NOT NULL DEFAULT 'car'");
    $pdo->exec("ALTER TABLE rides MODIFY status ENUM('pending', 'captain_selected', 'accepted', 'rejected', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function ensure_captain_table_ready(PDO $pdo): void
{
    try {
        if (!table_column_exists($pdo, 'captains', 'city')) {
            $pdo->exec("ALTER TABLE captains ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT 'Lahore' AFTER password_hash");
        }

        if (!table_column_exists($pdo, 'captains', 'id_card_type')) {
            $pdo->exec("ALTER TABLE captains ADD COLUMN id_card_type ENUM('aadhar', 'pan') DEFAULT NULL AFTER license_number");
        }

        if (!table_column_exists($pdo, 'captains', 'id_card_number')) {
            $pdo->exec('ALTER TABLE captains ADD COLUMN id_card_number VARCHAR(80) DEFAULT NULL AFTER id_card_type');
        }

        $pdo->exec("ALTER TABLE captains MODIFY account_status ENUM('pending', 'active', 'approved', 'inactive', 'blocked') NOT NULL DEFAULT 'active'");
    } catch (Throwable $e) {
        // Fresh imports already match the expected structure; this only supports older local databases.
    }
}

function ensure_ride_messages_table_ready(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ride_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ride_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            captain_id INT UNSIGNED NOT NULL,
            sender_role ENUM("user", "captain") NOT NULL,
            message_body TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ride_messages_ride (ride_id, created_at),
            INDEX idx_ride_messages_user (user_id),
            INDEX idx_ride_messages_captain (captain_id),
            CONSTRAINT fk_ride_messages_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
            CONSTRAINT fk_ride_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_ride_messages_captain FOREIGN KEY (captain_id) REFERENCES captains(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function get_user_ride(int $rideId): ?array
{
    $pdo = db();
    $userId = current_user_id();

    if (!$pdo || !$userId) {
        return null;
    }

    try {
        ensure_captain_table_ready($pdo);
        $captainCityExpr = table_column_exists($pdo, 'captains', 'city') ? 'c.city' : "'Lahore'";
        $stmt = $pdo->prepare(
            'SELECT r.*, c.full_name AS captain_name, c.phone AS captain_phone, c.vehicle_type AS captain_vehicle_type,
                    c.vehicle_number AS captain_vehicle_number, ' . $captainCityExpr . ' AS captain_city
             FROM rides r
             LEFT JOIN captains c ON c.id = r.captain_id
             WHERE r.id = :ride_id AND r.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
        ]);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function fetch_user_chat_ride(int $rideId = 0): ?array
{
    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        return null;
    }

    try {
        ensure_ride_table_ready($pdo);
        ensure_captain_table_ready($pdo);

        $rideFilter = $rideId > 0 ? ' AND r.id = :ride_id' : '';
        $params = [':user_id' => $userId];
        if ($rideId > 0) {
            $params[':ride_id'] = $rideId;
        }

        $stmt = $pdo->prepare(
            'SELECT r.*, c.full_name AS captain_name, c.phone AS captain_phone,
                    c.vehicle_type AS captain_vehicle_type, c.vehicle_number AS captain_vehicle_number
             FROM rides r
             LEFT JOIN captains c ON c.id = r.captain_id
             WHERE r.user_id = :user_id' . $rideFilter . '
             ORDER BY
                CASE r.status
                    WHEN "accepted" THEN 1
                    WHEN "ongoing" THEN 2
                    WHEN "captain_selected" THEN 3
                    WHEN "pending" THEN 4
                    ELSE 5
                END,
                r.created_at DESC,
                r.id DESC
             LIMIT 1'
        );
        $stmt->execute($params);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function fetch_captain_chat_ride(int $rideId = 0): ?array
{
    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId) {
        return null;
    }

    try {
        ensure_ride_table_ready($pdo);

        $rideFilter = $rideId > 0 ? ' AND r.id = :ride_id' : '';
        $params = [':captain_id' => $captainId];
        if ($rideId > 0) {
            $params[':ride_id'] = $rideId;
        }

        $stmt = $pdo->prepare(
            'SELECT r.*, u.full_name AS user_name, u.phone AS user_phone, u.email AS user_email, u.city AS user_city
             FROM rides r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.captain_id = :captain_id' . $rideFilter . '
             ORDER BY
                CASE r.status
                    WHEN "pending" THEN 1
                    WHEN "captain_selected" THEN 1
                    WHEN "accepted" THEN 2
                    WHEN "ongoing" THEN 3
                    WHEN "completed" THEN 4
                    ELSE 5
                END,
                r.created_at DESC,
                r.id DESC
             LIMIT 1'
        );
        $stmt->execute($params);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function user_driver_chat_context(): array
{
    $context = [
        'ride_id' => 0,
        'user_id' => current_user_id() ?? 0,
        'captain_id' => 0,
        'captain_name' => 'Captain',
        'captain_vehicle' => 'TripNovaa ride',
        'captain_number' => 'Not assigned',
        'pickup' => 'Pickup location',
        'drop' => 'Drop location',
        'status' => 'Waiting',
        'eta' => 'Select a captain first',
        'can_message' => false,
        'message_hint' => 'Book a ride and select a captain to start messaging.',
        'action_url' => 'index.php?page=book-ride',
        'action_label' => 'Book Ride',
    ];

    $ride = fetch_user_chat_ride((int) ($_GET['ride_id'] ?? 0));
    if (!$ride) {
        return $context;
    }

    $captainId = (int) ($ride['captain_id'] ?? 0);
    $canMessage = $captainId > 0;

    return [
        'ride_id' => (int) ($ride['id'] ?? 0),
        'user_id' => (int) ($ride['user_id'] ?? 0),
        'captain_id' => $captainId,
        'captain_name' => ($ride['captain_name'] ?? '') ?: 'Assigned Captain',
        'captain_vehicle' => ride_type_label((string) (($ride['captain_vehicle_type'] ?? '') ?: ($ride['ride_type'] ?? 'car'))),
        'captain_number' => ($ride['captain_vehicle_number'] ?? '') ?: 'Vehicle pending',
        'pickup' => ($ride['pickup_location'] ?? '') ?: $context['pickup'],
        'drop' => ($ride['drop_location'] ?? '') ?: $context['drop'],
        'status' => ride_status_label((string) (($ride['status'] ?? '') ?: 'pending')),
        'eta' => $canMessage ? 'Local chat active' : 'Captain not assigned yet',
        'can_message' => $canMessage,
        'message_hint' => $canMessage ? 'Messages save to localhost MySQL.' : 'Select a captain before sending messages.',
        'action_url' => $canMessage ? '' : 'index.php?page=available-captains&ride_id=' . (int) ($ride['id'] ?? 0),
        'action_label' => $canMessage ? '' : 'Select Captain',
    ];
}

function fetch_ride_messages(int $rideId, int $userId, int $captainId): array
{
    $pdo = db();
    if (!$pdo || $rideId <= 0 || $userId <= 0 || $captainId <= 0) {
        return [];
    }

    try {
        ensure_ride_messages_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT id, sender_role, message_body, created_at
             FROM ride_messages
             WHERE ride_id = :ride_id
               AND user_id = :user_id
               AND captain_id = :captain_id
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
            ':captain_id' => $captainId,
        ]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_captain_message_threads(): array
{
    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId) {
        return [];
    }

    try {
        ensure_ride_table_ready($pdo);
        ensure_ride_messages_table_ready($pdo);

        $stmt = $pdo->prepare(
            'SELECT r.id, r.pickup_location, r.drop_location, r.status, r.created_at,
                    u.full_name AS user_name, u.phone AS user_phone,
                    (
                        SELECT m.message_body
                        FROM ride_messages m
                        WHERE m.ride_id = r.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message,
                    (
                        SELECT m.created_at
                        FROM ride_messages m
                        WHERE m.ride_id = r.id
                        ORDER BY m.created_at DESC, m.id DESC
                        LIMIT 1
                    ) AS last_message_at,
                    (
                        SELECT COUNT(*)
                        FROM ride_messages m
                        WHERE m.ride_id = r.id
                    ) AS message_count
             FROM rides r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.captain_id = :captain_id
             ORDER BY COALESCE(last_message_at, r.created_at) DESC, r.id DESC
             LIMIT 20'
        );
        $stmt->execute([':captain_id' => $captainId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function chat_time_label(?string $value): string
{
    $time = $value ? strtotime($value) : false;
    return $time ? date('h:i A', $time) : date('h:i A');
}

function render_ride_messages(array $messages, string $viewerRole): void
{
    if (!$messages) {
        ?>
        <div class="chat-empty-state">
            <strong>No messages yet</strong>
            <span>Send the first message and it will appear here for both accounts.</span>
        </div>
        <?php
        return;
    }

    foreach ($messages as $message) {
        $senderRole = (string) ($message['sender_role'] ?? '');
        $isMine = $senderRole === $viewerRole;
        $bubbleClass = $isMine ? 'user' : 'driver';
        $senderLabel = $isMine ? 'You' : ($senderRole === 'captain' ? 'Captain' : 'Passenger');
        ?>
        <div class="chat-bubble <?php echo h($bubbleClass); ?>">
            <p><?php echo h($message['message_body'] ?? ''); ?></p>
            <span><?php echo h($senderLabel . ' - ' . chat_time_label($message['created_at'] ?? null)); ?></span>
        </div>
        <?php
    }
}

function get_completed_ride_for_feedback(int $rideId, int $userId): ?array
{
    $pdo = db();
    if (!$pdo || $rideId <= 0 || $userId <= 0) {
        return null;
    }

    try {
        ensure_ride_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT r.*, c.full_name AS captain_name
             FROM rides r
             LEFT JOIN captains c ON c.id = r.captain_id
             WHERE r.id = :ride_id
               AND r.user_id = :user_id
               AND r.status = "completed"
             LIMIT 1'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
        ]);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function user_feedback_exists(string $bookingType, int $bookingId, int $userId): bool
{
    $pdo = db();
    if (!$pdo || $bookingType === '' || $bookingId <= 0 || $userId <= 0) {
        return false;
    }

    try {
        if ($bookingType === 'ride') {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM feedback
                 WHERE user_id = :user_id
                   AND feedback_type = "ride"
                   AND ride_id = :booking_id'
            );
        } else {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM feedback
                 WHERE user_id = :user_id
                   AND feedback_type = :booking_type
                   AND ride_id IS NULL'
            );
        }

        $params = [
            ':user_id' => $userId,
        ];
        if ($bookingType === 'ride') {
            $params[':booking_id'] = $bookingId;
        } else {
            $params[':booking_type'] = $bookingType;
        }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function captain_feedback_average(int $captainId): array
{
    $pdo = db();
    if (!$pdo || $captainId <= 0) {
        return ['average' => 0.0, 'count' => 0];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT AVG(f.rating) AS average_rating, COUNT(*) AS total
             FROM feedback f
             INNER JOIN rides r ON r.id = f.ride_id
             WHERE r.captain_id = :captain_id
               AND r.status = "completed"
               AND f.feedback_type = "ride"
               AND f.status = "visible"'
        );
        $stmt->execute([':captain_id' => $captainId]);
        $row = $stmt->fetch();

        return [
            'average' => (float) ($row['average_rating'] ?? 0),
            'count' => (int) ($row['total'] ?? 0),
        ];
    } catch (Throwable $e) {
        return ['average' => 0.0, 'count' => 0];
    }
}

function get_latest_user_ride_id(): int
{
    $pdo = db();
    $userId = current_user_id();

    if (!$pdo || !$userId) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM rides WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();

        return (int) ($row['id'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function supported_booking_types(): array
{
    return ['ride', 'hotel', 'train', 'bus', 'restaurant', 'ticket'];
}

function env_value(string $key, string $fallback = ''): string
{
    $value = getenv($key);
    return $value === false ? $fallback : trim((string) $value);
}

function app_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/TRIPNOVAA/index.php';

    return $scheme . '://' . $host . $script;
}

function cashfree_config(): array
{
    $mode = strtolower(env_value('CASHFREE_MODE', 'sandbox'));
    if (!in_array($mode, ['sandbox', 'production'], true)) {
        $mode = 'sandbox';
    }

    $appId = env_value('CASHFREE_APP_ID', 'CASHFREE_TEST_APP_ID_PLACEHOLDER');
    $secretKey = env_value('CASHFREE_SECRET_KEY', 'CASHFREE_TEST_SECRET_KEY_PLACEHOLDER');
    $configured = !in_array($appId, ['', 'CASHFREE_TEST_APP_ID_PLACEHOLDER'], true)
        && !in_array($secretKey, ['', 'CASHFREE_TEST_SECRET_KEY_PLACEHOLDER'], true);

    return [
        'mode' => $mode,
        'app_id' => $appId,
        'secret_key' => $secretKey,
        'api_base' => $mode === 'production' ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg',
        'api_version' => env_value('CASHFREE_API_VERSION', '2023-08-01'),
        'currency' => env_value('CASHFREE_CURRENCY', 'INR'),
        'configured' => $configured,
    ];
}

function cashfree_demo_config(): array
{
    return cashfree_config();
}

function cashfree_api_request(string $method, string $path, ?array $payload = null): array
{
    $config = cashfree_config();
    if (!$config['configured']) {
        throw new RuntimeException('Cashfree credentials are not configured.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL extension is required for Cashfree API calls.');
    }

    $curl = curl_init();
    $url = rtrim($config['api_base'], '/') . '/' . ltrim($path, '/');
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-version: ' . $config['api_version'],
        'x-client-id: ' . $config['app_id'],
        'x-client-secret: ' . $config['secret_key'],
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false) {
        throw new RuntimeException('Cashfree API request failed: ' . $error);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Cashfree API returned an invalid response.');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $data['message'] ?? $data['error_description'] ?? $data['type'] ?? 'Cashfree API error.';
        throw new RuntimeException($message);
    }

    return $data;
}

function create_cashfree_order(string $bookingType, int $bookingId, float $amount, array $customer): array
{
    $config = cashfree_config();
    $orderId = 'TN_' . strtoupper($bookingType) . '_' . $bookingId . '_' . time();
    $customerId = 'TN_USER_' . (current_user_id() ?? 0);
    $customerPhone = preg_replace('/\D+/', '', (string) ($customer['phone'] ?? '9999999999'));
    if (strlen($customerPhone) < 10) {
        $customerPhone = '9999999999';
    }

    if (!$config['configured']) {
        return [
            'order_id' => $orderId,
            'payment_session_id' => 'DEMO_SESSION_' . bin2hex(random_bytes(6)),
            'order_status' => 'DEMO_SUCCESS',
            'environment' => 'demo-fallback',
            'amount' => $amount,
            'currency' => $config['currency'],
            'live' => false,
        ];
    }

    $payload = [
        'order_id' => $orderId,
        'order_amount' => round($amount, 2),
        'order_currency' => $config['currency'],
        'customer_details' => [
            'customer_id' => $customerId,
            'customer_name' => (string) ($customer['full_name'] ?? current_user_name()),
            'customer_email' => (string) ($customer['email'] ?? 'customer@tripnovaa.local'),
            'customer_phone' => $customerPhone,
        ],
        'order_meta' => [
            'return_url' => app_base_url() . '?page=payment&action=cashfree_return&order_id={order_id}',
        ],
        'order_note' => 'TripNovaa ' . $bookingType . ' booking #' . $bookingId,
    ];

    $order = cashfree_api_request('POST', '/orders', $payload);
    $order['live'] = true;
    $order['environment'] = $config['mode'];

    return $order;
}

function create_cashfree_demo_order(string $bookingType, int $bookingId, float $amount, array $customer): array
{
    return create_cashfree_order($bookingType, $bookingId, $amount, $customer);
}

function verify_cashfree_order(string $orderId): array
{
    $order = cashfree_api_request('GET', '/orders/' . rawurlencode($orderId));
    $order['live'] = true;

    return $order;
}

function get_current_customer(): ?array
{
    $pdo = db();
    $userId = current_user_id();

    if (!$pdo || !$userId) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function booking_payment_mapping(string $bookingType): ?array
{
    $map = [
        'ride' => ['table' => 'rides', 'id_column' => 'ride_id', 'source_type' => 'ride'],
        'hotel' => ['table' => 'hotel_bookings', 'id_column' => 'hotel_booking_id', 'source_type' => 'hotel'],
        'train' => ['table' => 'train_bookings', 'id_column' => 'train_booking_id', 'source_type' => 'train'],
        'bus' => ['table' => 'bus_bookings', 'id_column' => 'bus_booking_id', 'source_type' => 'bus'],
        'restaurant' => ['table' => 'restaurant_bookings', 'id_column' => 'restaurant_booking_id', 'source_type' => 'restaurant'],
        'ticket' => ['table' => 'ticket_bookings', 'id_column' => 'ticket_booking_id', 'source_type' => 'ticket'],
    ];

    return $map[$bookingType] ?? null;
}

function get_booking_payment_details(string $bookingType, int $bookingId, float $fallbackAmount): ?array
{
    $pdo = db();
    $userId = current_user_id();
    $mapping = booking_payment_mapping($bookingType);

    if (!$pdo || !$userId || !$mapping || $bookingId <= 0) {
        return null;
    }

    try {
        if ($bookingType === 'ride') {
            $stmt = $pdo->prepare(
                'SELECT id, pickup_location AS title, drop_location AS subtitle, fare AS amount, status, payment_status
                 FROM rides
                 WHERE id = :booking_id AND user_id = :user_id
                 LIMIT 1'
            );
        } else {
            $titleColumn = match ($bookingType) {
                'hotel' => 'hotel_name',
                'train' => 'train_name',
                'bus' => 'bus_name',
                'restaurant' => 'restaurant_name',
                'ticket' => 'event_name',
                default => 'status',
            };
            $stmt = $pdo->prepare(
                "SELECT id, {$titleColumn} AS title, status, amount
                 FROM {$mapping['table']}
                 WHERE id = :booking_id AND user_id = :user_id
                 LIMIT 1"
            );
        }

        $stmt->execute([
            ':booking_id' => $bookingId,
            ':user_id' => $userId,
        ]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return null;
        }

        $booking['amount'] = (float) ($booking['amount'] ?? $fallbackAmount);
        if ($booking['amount'] <= 0 && $fallbackAmount > 0) {
            $booking['amount'] = $fallbackAmount;
        }

        return $booking;
    } catch (Throwable $e) {
        return null;
    }
}

function update_booking_after_payment(PDO $pdo, string $bookingType, int $bookingId, int $userId): void
{
    $mapping = booking_payment_mapping($bookingType);
    if (!$mapping) {
        return;
    }

    if ($bookingType === 'ride') {
        $stmt = $pdo->prepare(
            'UPDATE rides
             SET payment_status = "paid",
                 status = "completed",
                 completed_at = COALESCE(completed_at, NOW())
             WHERE id = :booking_id AND user_id = :user_id'
        );
    } else {
        $stmt = $pdo->prepare("UPDATE {$mapping['table']} SET status = 'confirmed' WHERE id = :booking_id AND user_id = :user_id");
    }

    $stmt->execute([
        ':booking_id' => $bookingId,
        ':user_id' => $userId,
    ]);
}

function get_user_payment(int $paymentId): ?array
{
    $pdo = db();
    $userId = current_user_id();

    if (!$pdo || !$userId || $paymentId <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :payment_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':user_id' => $userId,
        ]);
        $payment = $stmt->fetch();

        return $payment ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function format_offer_discount(array $offer): string
{
    $value = (float) ($offer['discount_value'] ?? 0);
    if (($offer['discount_type'] ?? '') === 'percentage') {
        return rtrim(rtrim(number_format($value, 2), '0'), '.') . '% off';
    }

    return 'Rs ' . number_format($value, 0) . ' off';
}

function get_offer_by_code(PDO $pdo, string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM offers WHERE UPPER(code) = :code LIMIT 1');
    $stmt->execute([':code' => $code]);
    $offer = $stmt->fetch();

    return $offer ?: null;
}

function calculate_offer_result(string $couponCode, float $amount): array
{
    $amount = max(0, $amount);
    $result = [
        'valid' => false,
        'offer' => null,
        'code' => strtoupper(trim($couponCode)),
        'original_amount' => $amount,
        'discount_amount' => 0.0,
        'final_amount' => $amount,
        'message' => 'Enter a coupon code to apply an offer.',
    ];

    if ($result['code'] === '') {
        return $result;
    }

    $pdo = db();
    if (!$pdo) {
        $result['message'] = 'Database is not connected.';
        return $result;
    }

    try {
        ensure_default_offers($pdo);
        $offer = get_offer_by_code($pdo, $result['code']);
        if (!$offer) {
            $result['message'] = 'Coupon code was not found.';
            return $result;
        }

        $today = date('Y-m-d');
        if (($offer['status'] ?? '') !== 'active') {
            $result['offer'] = $offer;
            $result['message'] = 'This coupon is not active.';
            return $result;
        }

        if (($offer['valid_from'] ?? $today) > $today || ($offer['valid_to'] ?? $today) < $today) {
            $result['offer'] = $offer;
            $result['message'] = 'This coupon is outside its valid date range.';
            return $result;
        }

        $minimumAmount = (float) ($offer['min_booking_amount'] ?? 0);
        if ($amount < $minimumAmount) {
            $result['offer'] = $offer;
            $result['message'] = 'Minimum booking amount is Rs ' . number_format($minimumAmount, 2) . '.';
            return $result;
        }

        $discountValue = (float) ($offer['discount_value'] ?? 0);
        $discountAmount = (($offer['discount_type'] ?? '') === 'percentage')
            ? ($amount * $discountValue / 100)
            : $discountValue;
        $discountAmount = min($amount, max(0, $discountAmount));

        $result['valid'] = true;
        $result['offer'] = $offer;
        $result['discount_amount'] = $discountAmount;
        $result['final_amount'] = max(0, $amount - $discountAmount);
        $result['message'] = 'Coupon applied: ' . format_offer_discount($offer) . '.';

        return $result;
    } catch (Throwable $e) {
        $result['message'] = 'Coupon validation failed: ' . $e->getMessage();
        return $result;
    }
}

function ensure_default_offers(PDO $pdo): void
{
    $offers = [
        ['Trip Ride Saver', '10% off on ride bookings.', 'TRIP10', 'percentage', 10.00, 300.00],
        ['Hotel Comfort Deal', '20% off on hotel bookings.', 'HOTEL20', 'percentage', 20.00, 2000.00],
        ['Bus Seat Discount', 'Rs. 50 off on bus bookings.', 'BUS50', 'flat', 50.00, 300.00],
        ['Ticket Explorer Deal', '15% off on tour and ticket bookings.', 'TICKET15', 'percentage', 15.00, 500.00],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO offers
         (title, description, code, discount_type, discount_value, min_booking_amount, valid_from, valid_to, status)
         SELECT :title, :description, :code, :discount_type, :discount_value, :min_booking_amount, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), "active"
         WHERE NOT EXISTS (SELECT 1 FROM offers WHERE code = :code_check)'
    );

    foreach ($offers as $offer) {
        $stmt->execute([
            ':title' => $offer[0],
            ':description' => $offer[1],
            ':code' => $offer[2],
            ':discount_type' => $offer[3],
            ':discount_value' => $offer[4],
            ':min_booking_amount' => $offer[5],
            ':code_check' => $offer[2],
        ]);
    }
}

function fetch_available_offers(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    try {
        ensure_default_offers($pdo);
        $stmt = $pdo->prepare(
            'SELECT * FROM offers
             ORDER BY status = "active" DESC, valid_to ASC, id DESC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function user_reward_points_total(int $userId): int
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(points), 0) AS total FROM rewards WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function fetch_user_ride_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_ride_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT r.id, r.pickup_location, r.drop_location, r.ride_type, r.travel_date, r.travel_time,
                    r.fare AS amount, r.status, r.payment_status AS ride_payment_status, r.created_at,
                    c.full_name AS captain_name,
                    (SELECT p.payment_status FROM payments p WHERE p.ride_id = r.id AND p.user_id = r.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status,
                    (SELECT COUNT(*) FROM feedback f WHERE f.ride_id = r.id AND f.user_id = r.user_id AND f.feedback_type = "ride") AS feedback_count
             FROM rides r
             LEFT JOIN captains c ON c.id = r.captain_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC, r.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_hotel_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_hotel_booking_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT h.id, h.hotel_name AS title, h.city, h.check_in_date, h.check_out_date,
                    h.room_type, h.guests, h.rooms, h.amount, h.status, h.created_at,
                    (SELECT p.payment_status FROM payments p WHERE p.hotel_booking_id = h.id AND p.user_id = h.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM hotel_bookings h
             WHERE h.user_id = :user_id
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_train_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_train_booking_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT t.id, t.train_name AS title, t.train_number, t.origin, t.destination,
                    t.travel_date, t.seat_class, t.passengers, t.amount, t.status, t.created_at,
                    (SELECT p.payment_status FROM payments p WHERE p.train_booking_id = t.id AND p.user_id = t.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM train_bookings t
             WHERE t.user_id = :user_id
             ORDER BY t.created_at DESC, t.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_bus_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_bus_booking_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT b.id, b.bus_name AS title, b.bus_number, b.origin, b.destination,
                    b.travel_date, b.bus_type, b.seat_no, b.seats, b.amount, b.status, b.created_at,
                    (SELECT p.payment_status FROM payments p WHERE p.bus_booking_id = b.id AND p.user_id = b.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM bus_bookings b
             WHERE b.user_id = :user_id
             ORDER BY b.created_at DESC, b.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_restaurant_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_restaurant_booking_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT r.id, r.restaurant_name AS title, r.city, r.booking_date, r.booking_time,
                    r.guests, r.amount, r.status, r.created_at,
                    (SELECT p.payment_status FROM payments p WHERE p.restaurant_booking_id = r.id AND p.user_id = r.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM restaurant_bookings r
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC, r.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_ticket_bookings(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        ensure_ticket_booking_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT t.id, t.event_name AS title, COALESCE(t.location, t.city) AS location, t.event_date,
                    t.ticket_type, t.quantity, t.tickets, t.price, t.amount, t.status, t.api_reference, t.created_at,
                    (SELECT p.payment_status FROM payments p WHERE p.ticket_booking_id = t.id AND p.user_id = t.user_id ORDER BY p.id DESC LIMIT 1) AS payment_status
             FROM ticket_bookings t
             WHERE t.user_id = :user_id
             ORDER BY t.created_at DESC, t.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function fetch_user_payments(int $userId): array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, booking_type, amount, currency, payment_provider, payment_method,
                    transaction_id, cashfree_order_id, payment_status, paid_at, created_at
             FROM payments
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function display_booking_date(?string $value): string
{
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return 'Not set';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return strlen($value) <= 10 ? date('M d, Y', $timestamp) : date('M d, Y h:i A', $timestamp);
}

function booking_payment_label(array $booking, string $type): string
{
    $paymentStatus = strtolower((string) ($booking['payment_status'] ?? ''));
    $status = strtolower((string) ($booking['status'] ?? ''));
    $ridePaymentStatus = strtolower((string) ($booking['ride_payment_status'] ?? ''));

    if (in_array($paymentStatus, ['success', 'demo_success'], true)) {
        return 'Paid';
    }

    if ($type === 'ride' && $ridePaymentStatus === 'paid') {
        return 'Paid';
    }

    if (in_array($status, ['confirmed', 'completed'], true)) {
        return 'Paid';
    }

    return 'Unpaid';
}

function booking_is_paid(array $booking, string $type): bool
{
    return booking_payment_label($booking, $type) === 'Paid';
}

function booking_payment_url(string $type, int $bookingId, float $amount): string
{
    return 'index.php?' . http_build_query([
        'page' => 'payment',
        'booking_type' => $type,
        'booking_id' => $bookingId,
        'amount' => $amount,
    ]);
}

function hotel_catalog(): array
{
    return [
        'luxury' => [
            'name' => 'TripNovaa Luxury Hotel',
            'rating' => '4.9',
            'price' => 18000,
            'room_type' => 'Deluxe Suite',
            'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80',
        ],
        'mountain' => [
            'name' => 'Mountain View Resort',
            'rating' => '4.8',
            'price' => 14500,
            'room_type' => 'Mountain View Room',
            'image' => 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80',
        ],
        'comfort' => [
            'name' => 'City Comfort Inn',
            'rating' => '4.5',
            'price' => 8500,
            'room_type' => 'Standard King Room',
            'image' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=900&q=80',
        ],
        'royal' => [
            'name' => 'Royal Stay Hotel',
            'rating' => '4.7',
            'price' => 12500,
            'room_type' => 'Executive Room',
            'image' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=900&q=80',
        ],
    ];
}

function get_hotel_from_catalog(string $hotelKey): ?array
{
    $catalog = hotel_catalog();
    return $catalog[$hotelKey] ?? null;
}

function ensure_hotel_booking_table_ready(PDO $pdo): void
{
    if (!table_column_exists($pdo, 'hotel_bookings', 'room_type')) {
        $pdo->exec('ALTER TABLE hotel_bookings ADD COLUMN room_type VARCHAR(100) DEFAULT NULL AFTER rooms');
    }

    $pdo->exec("ALTER TABLE hotel_bookings MODIFY status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function train_catalog(): array
{
    return [
        'green-line' => [
            'name' => 'Green Line Express',
            'train_number' => 'GL-101',
            'departure' => '08:30 AM',
            'arrival' => '02:45 PM',
            'seat_type' => 'AC Business',
            'price' => 6500,
            'accent' => 'green',
        ],
        'khyber-mail' => [
            'name' => 'Khyber Mail',
            'train_number' => 'KM-303',
            'departure' => '07:10 AM',
            'arrival' => '06:30 PM',
            'seat_type' => 'Economy Plus',
            'price' => 4200,
            'accent' => 'blue',
        ],
        'tripnovaa-express' => [
            'name' => 'TripNovaa Express',
            'train_number' => 'TN-707',
            'departure' => '10:00 AM',
            'arrival' => '03:15 PM',
            'seat_type' => 'Executive',
            'price' => 7200,
            'accent' => 'orange',
        ],
        'business-train' => [
            'name' => 'Business Train',
            'train_number' => 'BT-909',
            'departure' => '04:20 PM',
            'arrival' => '09:45 PM',
            'seat_type' => 'Business Class',
            'price' => 8500,
            'accent' => 'dark',
        ],
    ];
}

function get_train_from_catalog(string $trainKey): ?array
{
    $catalog = train_catalog();
    return $catalog[$trainKey] ?? null;
}

function ensure_train_booking_table_ready(PDO $pdo): void
{
    $pdo->exec("ALTER TABLE train_bookings MODIFY status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function bus_catalog(): array
{
    return [
        'daewoo' => [
            'name' => 'Daewoo Express',
            'bus_number' => 'DW-501',
            'bus_type' => 'Luxury Coach',
            'departure' => '09:00 AM',
            'arrival' => '01:30 PM',
            'available_seats' => 18,
            'price' => 2800,
            'accent' => 'blue',
        ],
        'faisal' => [
            'name' => 'Faisal Movers',
            'bus_number' => 'FM-702',
            'bus_type' => 'Executive',
            'departure' => '11:30 AM',
            'arrival' => '04:00 PM',
            'available_seats' => 12,
            'price' => 2500,
            'accent' => 'green',
        ],
        'skyways' => [
            'name' => 'Skyways',
            'bus_number' => 'SW-414',
            'bus_type' => 'Business Class',
            'departure' => '02:15 PM',
            'arrival' => '06:45 PM',
            'available_seats' => 9,
            'price' => 2300,
            'accent' => 'orange',
        ],
        'tripnovaa-bus' => [
            'name' => 'TripNovaa Bus',
            'bus_number' => 'TN-BUS-88',
            'bus_type' => 'Premium Sleeper',
            'departure' => '08:45 PM',
            'arrival' => '01:20 AM',
            'available_seats' => 6,
            'price' => 3600,
            'accent' => 'dark',
        ],
    ];
}

function get_bus_from_catalog(string $busKey): ?array
{
    $catalog = bus_catalog();
    return $catalog[$busKey] ?? null;
}

function ensure_bus_booking_table_ready(PDO $pdo): void
{
    if (!table_column_exists($pdo, 'bus_bookings', 'seat_no')) {
        $pdo->exec('ALTER TABLE bus_bookings ADD COLUMN seat_no VARCHAR(40) DEFAULT NULL AFTER bus_type');
    }

    $pdo->exec("ALTER TABLE bus_bookings MODIFY status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function restaurant_catalog(): array
{
    return [
        'tripnovaa-cafe' => [
            'name' => 'TripNovaa Cafe',
            'city' => 'Lahore',
            'cuisine' => 'Continental, Coffee, Desserts',
            'rating' => '4.8',
            'price' => 2200,
            'accent' => 'orange',
        ],
        'mountain-grill' => [
            'name' => 'Mountain Grill',
            'city' => 'Murree',
            'cuisine' => 'BBQ, Pakistani, Steaks',
            'rating' => '4.7',
            'price' => 3200,
            'accent' => 'green',
        ],
        'city-food-lounge' => [
            'name' => 'City Food Lounge',
            'city' => 'Islamabad',
            'cuisine' => 'Fast Food, Chinese, Local',
            'rating' => '4.5',
            'price' => 1800,
            'accent' => 'blue',
        ],
        'royal-restaurant' => [
            'name' => 'Royal Restaurant',
            'city' => 'Karachi',
            'cuisine' => 'Fine Dining, Seafood, Pakistani',
            'rating' => '4.9',
            'price' => 4200,
            'accent' => 'dark',
        ],
    ];
}

function get_restaurant_from_catalog(string $restaurantKey): ?array
{
    $catalog = restaurant_catalog();
    return $catalog[$restaurantKey] ?? null;
}

function ensure_restaurant_booking_table_ready(PDO $pdo): void
{
    if (!table_column_exists($pdo, 'restaurant_bookings', 'special_request')) {
        $pdo->exec('ALTER TABLE restaurant_bookings ADD COLUMN special_request TEXT DEFAULT NULL AFTER guests');
    }

    $pdo->exec("ALTER TABLE restaurant_bookings MODIFY status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function ticket_api_config(): array
{
    $apiKey = env_value('TICKETMASTER_API_KEY', 'YOUR_TICKETMASTER_API_KEY');

    return [
        'provider' => 'Ticketmaster Discovery API',
        'api_key' => $apiKey,
        'api_base' => 'https://app.ticketmaster.com/discovery/v2/events.json',
        'country_code' => env_value('TICKETMASTER_COUNTRY_CODE', ''),
        'configured' => !in_array($apiKey, ['', 'YOUR_TICKETMASTER_API_KEY'], true),
    ];
}

function fetchTicketApiResults(string $keyword, string $location, string $date = ''): array
{
    $keyword = trim($keyword) !== '' ? trim($keyword) : 'travel';
    $location = trim($location) !== '' ? trim($location) : 'Lahore';
    $config = ticket_api_config();

    $query = [
        'apikey' => $config['api_key'],
        'keyword' => $keyword,
        'city' => $location,
        'size' => 6,
        'sort' => 'date,asc',
    ];

    if ($config['country_code'] !== '') {
        $query['countryCode'] = $config['country_code'];
    }

    if (is_valid_date_ymd($date)) {
        $query['startDateTime'] = $date . 'T00:00:00Z';
        $query['endDateTime'] = date('Y-m-d', strtotime($date . ' +30 days')) . 'T23:59:59Z';
    }

    $apiUrl = $config['api_base'] . '?' . http_build_query($query);

    if ($config['configured'] && function_exists('curl_init')) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $payload = json_decode($response, true);
            $events = $payload['_embedded']['events'] ?? [];
            $apiResults = [];

            foreach ($events as $event) {
                $priceRange = $event['priceRanges'][0] ?? [];
                $venue = $event['_embedded']['venues'][0] ?? [];
                $eventDate = trim(($event['dates']['start']['localDate'] ?? date('Y-m-d')) . ' ' . ($event['dates']['start']['localTime'] ?? '19:00:00'));
                $apiResults[] = [
                    'event_name' => $event['name'] ?? 'Ticketmaster Event',
                    'location' => $venue['city']['name'] ?? $location,
                    'event_date' => $eventDate,
                    'ticket_type' => $event['classifications'][0]['segment']['name'] ?? 'General Admission',
                    'price' => (float) ($priceRange['min'] ?? 2500),
                    'api_reference' => $event['id'] ?? ('TM-' . strtoupper(substr(md5($eventDate), 0, 8))),
                    'api_source' => $config['provider'],
                    'api_url' => $event['url'] ?? '',
                ];
            }

            if ($apiResults) {
                return $apiResults;
            }
        }
    }

    $baseKeyword = ucwords($keyword);
    return [
        [
            'event_name' => "{$baseKeyword} Heritage Walk",
            'location' => $location,
            'event_date' => date('Y-m-d H:i:s', strtotime('+5 days 10:00')),
            'ticket_type' => 'Guided Tour',
            'price' => 1800,
            'api_reference' => 'DEMO-TICKET-API-' . strtoupper(substr(md5($keyword . $location . 'walk'), 0, 8)),
            'api_source' => 'Demo fallback',
            'api_url' => '',
        ],
        [
            'event_name' => "{$baseKeyword} Adventure Pass",
            'location' => $location,
            'event_date' => date('Y-m-d H:i:s', strtotime('+9 days 09:30')),
            'ticket_type' => 'Adventure Pass',
            'price' => 5500,
            'api_reference' => 'DEMO-TICKET-API-' . strtoupper(substr(md5($keyword . $location . 'adventure'), 0, 8)),
            'api_source' => 'Demo fallback',
            'api_url' => '',
        ],
        [
            'event_name' => "{$baseKeyword} Food Festival",
            'location' => $location,
            'event_date' => date('Y-m-d H:i:s', strtotime('+12 days 18:00')),
            'ticket_type' => 'Event Entry',
            'price' => 2500,
            'api_reference' => 'DEMO-TICKET-API-' . strtoupper(substr(md5($keyword . $location . 'food'), 0, 8)),
            'api_source' => 'Demo fallback',
            'api_url' => '',
        ],
        [
            'event_name' => "{$baseKeyword} Family Show",
            'location' => $location,
            'event_date' => date('Y-m-d H:i:s', strtotime('+16 days 17:30')),
            'ticket_type' => 'Family Ticket',
            'price' => 4200,
            'api_reference' => 'DEMO-TICKET-API-' . strtoupper(substr(md5($keyword . $location . 'family'), 0, 8)),
            'api_source' => 'Demo fallback',
            'api_url' => '',
        ],
    ];
}

function ensure_ticket_booking_table_ready(PDO $pdo): void
{
    if (!table_column_exists($pdo, 'ticket_bookings', 'location')) {
        $pdo->exec('ALTER TABLE ticket_bookings ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER event_name');
    }

    if (!table_column_exists($pdo, 'ticket_bookings', 'ticket_type')) {
        $pdo->exec('ALTER TABLE ticket_bookings ADD COLUMN ticket_type VARCHAR(100) DEFAULT NULL AFTER event_date');
    }

    if (!table_column_exists($pdo, 'ticket_bookings', 'quantity')) {
        $pdo->exec('ALTER TABLE ticket_bookings ADD COLUMN quantity INT UNSIGNED NOT NULL DEFAULT 1 AFTER ticket_type');
    }

    if (!table_column_exists($pdo, 'ticket_bookings', 'price')) {
        $pdo->exec('ALTER TABLE ticket_bookings ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity');
    }

    $pdo->exec("ALTER TABLE ticket_bookings MODIFY status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
}

function current_captain_id(): ?int
{
    $auth = auth();
    if (($auth['role'] ?? '') === 'captain') {
        return (int) $auth['id'];
    }

    if (($_SESSION['role'] ?? '') === 'captain' && isset($_SESSION['captain_id'])) {
        return (int) $_SESSION['captain_id'];
    }

    return null;
}

function captain_ride_count(string $status, int $captainId): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    try {
        if ($status === 'pending') {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total
                 FROM rides
                 WHERE captain_id = :captain_id
                   AND status IN ("pending", "captain_selected")'
            );
            $stmt->execute([':captain_id' => $captainId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM rides WHERE captain_id = :captain_id AND status = :status');
            $stmt->execute([
                ':captain_id' => $captainId,
                ':status' => $status,
            ]);
        }
        $row = $stmt->fetch();

        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function captain_total_earnings(int $captainId): float
{
    $pdo = db();
    if (!$pdo) {
        return 0.0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(fare), 0) AS total FROM rides WHERE captain_id = :captain_id AND status = "completed"');
        $stmt->execute([':captain_id' => $captainId]);
        $row = $stmt->fetch();

        return (float) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0.0;
    }
}

function fetch_captain_rides(int $captainId, string $status): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    try {
        ensure_ride_table_ready($pdo);
        if ($status === 'pending') {
            $stmt = $pdo->prepare(
                'SELECT r.*, u.full_name AS user_name, u.phone AS user_phone
                 FROM rides r
                 INNER JOIN users u ON u.id = r.user_id
                 WHERE r.captain_id = :captain_id
                   AND r.status IN ("pending", "captain_selected")
                 ORDER BY r.requested_at DESC, r.id DESC'
            );
            $stmt->execute([':captain_id' => $captainId]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT r.*, u.full_name AS user_name, u.phone AS user_phone
                 FROM rides r
                 INNER JOIN users u ON u.id = r.user_id
                 WHERE r.captain_id = :captain_id AND r.status = :status
                 ORDER BY r.id DESC'
            );
            $stmt->execute([
                ':captain_id' => $captainId,
                ':status' => $status,
            ]);
        }

        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function current_captain_profile(): array
{
    $fallback = [
        'full_name' => current_user_name(),
        'email' => 'captain@tripnovaa.com',
        'phone' => 'Not added',
        'city' => 'Vijayawada',
        'vehicle_type' => 'car',
        'vehicle_number' => 'AP 39 TN 4521',
        'license_number' => 'DL-TRIPNOVAA',
        'id_card_type' => 'aadhar',
        'id_card_number' => 'Verified',
        'availability_status' => 'available',
        'rating' => '4.8',
    ];

    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId) {
        return $fallback;
    }

    try {
        ensure_captain_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT full_name, email, phone, city, vehicle_type, vehicle_number, license_number,
                    id_card_type, id_card_number, availability_status, rating
             FROM captains
             WHERE id = :captain_id
             LIMIT 1'
        );
        $stmt->execute([':captain_id' => $captainId]);
        $profile = $stmt->fetch();

        return $profile ? array_merge($fallback, $profile) : $fallback;
    } catch (Throwable $e) {
        return $fallback;
    }
}

function get_captain_ride_by_id(int $rideId): ?array
{
    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId || $rideId <= 0) {
        return null;
    }

    try {
        ensure_ride_table_ready($pdo);
        $stmt = $pdo->prepare(
            'SELECT r.*, u.full_name AS user_name, u.phone AS user_phone, u.email AS user_email, u.city AS user_city
             FROM rides r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.id = :ride_id AND r.captain_id = :captain_id
             LIMIT 1'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':captain_id' => $captainId,
        ]);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function fetch_latest_captain_ride(array $statuses = []): ?array
{
    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId) {
        return null;
    }

    try {
        ensure_ride_table_ready($pdo);
        $statusFilter = '';
        $params = [':captain_id' => $captainId];
        if ($statuses) {
            $placeholders = [];
            foreach (array_values($statuses) as $index => $status) {
                $key = ':status_' . $index;
                $placeholders[] = $key;
                $params[$key] = $status;
            }
            $statusFilter = ' AND r.status IN (' . implode(', ', $placeholders) . ')';
        }

        $stmt = $pdo->prepare(
            'SELECT r.*, u.full_name AS user_name, u.phone AS user_phone, u.email AS user_email, u.city AS user_city
             FROM rides r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.captain_id = :captain_id' . $statusFilter . '
             ORDER BY
                CASE r.status
                    WHEN "pending" THEN 1
                    WHEN "accepted" THEN 2
                    WHEN "ongoing" THEN 3
                    WHEN "completed" THEN 4
                    ELSE 5
                END,
                r.created_at DESC,
                r.id DESC
             LIMIT 1'
        );
        $stmt->execute($params);
        $ride = $stmt->fetch();

        return $ride ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function captain_demo_trip(): array
{
    return [
        'id' => 0,
        'user_name' => 'No passenger selected',
        'user_phone' => '',
        'user_email' => '',
        'user_city' => '',
        'pickup_location' => 'No pickup selected',
        'drop_location' => 'No trip selected',
        'ride_type' => 'bike',
        'travel_date' => date('Y-m-d'),
        'travel_time' => '',
        'distance_km' => 0,
        'fare' => 0,
        'payment_status' => 'pending',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ];
}

function captain_trip_context(?array $ride = null): array
{
    $ride = $ride ?: fetch_latest_captain_ride(['pending', 'captain_selected', 'accepted', 'ongoing', 'completed']) ?: captain_demo_trip();
    $fare = (float) ($ride['fare'] ?? 0);
    $advance = round($fare * 0.05, 2);
    $date = (string) ($ride['travel_date'] ?? date('Y-m-d'));
    $time = substr((string) ($ride['travel_time'] ?? ''), 0, 5);
    $pickup = (string) ($ride['pickup_location'] ?? 'No pickup selected');
    $drop = (string) ($ride['drop_location'] ?? 'No trip selected');
    $rawStatus = (string) ($ride['status'] ?? 'pending');

    return [
        'ride' => $ride,
        'ride_id' => (int) ($ride['id'] ?? 0),
        'title' => trim((string) ($ride['drop_location'] ?? 'No trip selected')) ?: 'No trip selected',
        'route' => $pickup . ' to ' . $drop,
        'pickup' => $pickup,
        'drop' => $drop,
        'date' => display_booking_date($date),
        'time' => $time,
        'travelers' => 5,
        'distance' => number_format((float) ($ride['distance_km'] ?? 0), 0) . ' km',
        'fare' => $fare,
        'advance' => $advance,
        'remaining' => max(0, $fare - $advance),
        'customer' => (string) ($ride['user_name'] ?? 'Passenger'),
        'phone' => (string) ($ride['user_phone'] ?? ''),
        'status' => ride_status_label($rawStatus),
        'raw_status' => $rawStatus,
        'vehicle' => ride_type_label((string) ($ride['ride_type'] ?? 'bike')),
    ];
}

function set_auth(string $role, array $account): void
{
    session_regenerate_id(true);
    $_SESSION['role'] = $role;

    // Store a shared auth object plus role-specific session keys for the assignment requirements.
    $_SESSION['auth'] = [
        'role' => $role,
        'id' => (int) $account['id'],
        'name' => $account['full_name'] ?? 'TripNovaa User',
        'email' => $account['email'] ?? '',
    ];

    if ($role === 'user') {
        $_SESSION['user_id'] = (int) $account['id'];
        $_SESSION['user_name'] = $account['full_name'] ?? 'TripNovaa User';
    } elseif ($role === 'captain') {
        $_SESSION['captain_id'] = (int) $account['id'];
        $_SESSION['captain_name'] = $account['full_name'] ?? 'TripNovaa Captain';
    } elseif ($role === 'admin') {
        $_SESSION['admin_id'] = (int) $account['id'];
        $_SESSION['admin_name'] = $account['full_name'] ?? 'TripNovaa Admin';
    }
}

function create_demo_otp(string $role, int $accountId, string $phone): void
{
    $otp = '123456';
    $_SESSION['pending_otp'] = [
        'role' => $role,
        'id' => $accountId,
        'phone' => $phone,
        'otp' => $otp,
    ];

    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO otp_verifications
             (user_id, captain_id, role_type, phone, otp_code, purpose, is_verified, expires_at)
             VALUES (:user_id, :captain_id, :role_type, :phone, :otp_code, :purpose, 0, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
        );
        $stmt->execute([
            ':user_id' => $role === 'user' ? $accountId : null,
            ':captain_id' => $role === 'captain' ? $accountId : null,
            ':role_type' => $role,
            ':phone' => $phone,
            ':otp_code' => $otp,
            ':purpose' => 'login',
        ]);
    } catch (Throwable $e) {
        set_flash('warning', 'OTP was created in session, but could not be saved to database.');
    }
}

function get_account_by_id(string $role, int $id): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $tables = [
        'user' => 'users',
        'captain' => 'captains',
    ];

    if (!isset($tables[$role])) {
        return null;
    }

    $table = $tables[$role];
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $account = $stmt->fetch();

    return $account ?: null;
}

function handle_user_register(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('user-register');
    }

    $name = post_text('full_name', 120);
    $email = post_email('email');
    $phone = post_text('phone', 30);
    $city = post_text('city', 100);
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
        set_flash('danger', 'Please fill all required fields.');
        redirect_to('user-register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address.');
        redirect_to('user-register');
    }

    if (strlen($password) < 6) {
        set_flash('danger', 'Password must be at least 6 characters.');
        redirect_to('user-register');
    }

    if ($password !== $confirmPassword) {
        set_flash('danger', 'Password and confirm password do not match.');
        redirect_to('user-register');
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            set_flash('danger', 'This email is already registered. Please login instead.');
            redirect_to('user-register');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);

        if ($stmt->fetch()) {
            set_flash('danger', 'This phone number is already registered. Please login instead.');
            redirect_to('user-register');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, city, reward_points, otp_verified, status)
             VALUES (:full_name, :email, :phone, :password_hash, :city, 100, 0, "active")'
        );
        $stmt->execute([
            ':full_name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':city' => $city,
        ]);

        create_demo_otp('user', (int) $pdo->lastInsertId(), $phone);
        set_flash('success', 'Account created. Enter the demo OTP to continue.');
        redirect_to('otp');
    } catch (Throwable $e) {
        set_flash('danger', 'Registration failed: ' . $e->getMessage());
        redirect_to('user-register');
    }
}

function handle_user_login(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('user-login');
    }

    $login = post_text('login', 190);
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        set_flash('danger', 'Please enter email/phone and password.');
        redirect_to('user-login');
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email_login OR phone = :phone_login LIMIT 1');
        $stmt->execute([
            ':email_login' => $login,
            ':phone_login' => $login,
        ]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            set_flash('danger', 'Invalid user login details.');
            redirect_to('user-login');
        }

        if (($account['status'] ?? '') !== 'active') {
            set_flash('danger', 'This user account is not active.');
            redirect_to('user-login');
        }

        create_demo_otp('user', (int) $account['id'], $account['phone']);
        set_flash('success', 'Login accepted. Verify demo OTP 123456.');
        redirect_to('otp');
    } catch (Throwable $e) {
        set_flash('danger', 'Login failed: ' . $e->getMessage());
        redirect_to('user-login');
    }
}

function handle_captain_register(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('captain-register');
    }

    ensure_captain_table_ready($pdo);

    $name = post_text('full_name', 120);
    $email = post_email('email');
    $countryCode = post_text('phone_country_code', 8) ?: '+92';
    $phoneInput = post_text('phone', 30);
    $phoneDigits = preg_replace('/\D+/', '', $phoneInput);
    $localPhoneDigits = ltrim($phoneDigits, '0');
    $countryCodeDigits = ltrim($countryCode, '+');
    $phone = substr($phoneDigits, 0, strlen($countryCodeDigits)) === $countryCodeDigits ? '+' . $phoneDigits : $countryCode . $localPhoneDigits;
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $vehicleType = 'bike';
    $vehicleNumber = 'Pending';
    $licenseNumber = post_text('license_number', 60);
    $idCardType = post_text('id_card_type', 20);
    $idCardNumber = post_text('id_card_number', 80);
    $city = 'Not specified';

    if ($name === '' || $email === '' || $phoneDigits === '' || $password === '' || $confirmPassword === '' || $licenseNumber === '' || $idCardNumber === '') {
        set_flash('danger', 'Please fill all required captain fields.');
        redirect_to('captain-register');
    }

    if (!in_array($countryCode, allowed_phone_country_codes(), true) || strlen($phoneDigits) < 7) {
        set_flash('danger', 'Please select a valid country code and mobile number.');
        redirect_to('captain-register');
    }

    if (!in_array($idCardType, ['aadhar', 'pan'], true)) {
        set_flash('danger', 'Please select Aadhar Card or PAN Card.');
        redirect_to('captain-register');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid email address.');
        redirect_to('captain-register');
    }

    if (strlen($password) < 6) {
        set_flash('danger', 'Password must be at least 6 characters.');
        redirect_to('captain-register');
    }

    if ($password !== $confirmPassword) {
        set_flash('danger', 'Password and confirm password do not match.');
        redirect_to('captain-register');
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM captains WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            set_flash('danger', 'This captain email is already registered. Please login instead.');
            redirect_to('captain-register');
        }

        $stmt = $pdo->prepare('SELECT id FROM captains WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);

        if ($stmt->fetch()) {
            set_flash('danger', 'This captain phone number is already registered. Please login instead.');
            redirect_to('captain-register');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO captains
             (full_name, email, phone, password_hash, vehicle_type, vehicle_number, license_number, id_card_type, id_card_number, city, availability_status, account_status)
             VALUES (:full_name, :email, :phone, :password_hash, :vehicle_type, :vehicle_number, :license_number, :id_card_type, :id_card_number, :city, "available", "active")'
        );
        $stmt->execute([
            ':full_name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':vehicle_type' => $vehicleType,
            ':vehicle_number' => $vehicleNumber,
            ':license_number' => $licenseNumber,
            ':id_card_type' => $idCardType,
            ':id_card_number' => $idCardNumber,
            ':city' => $city,
        ]);

        create_demo_otp('captain', (int) $pdo->lastInsertId(), $phone);
        set_flash('success', 'Captain account created. Enter the demo OTP to continue.');
        redirect_to('otp');
    } catch (Throwable $e) {
        set_flash('danger', 'Captain registration failed: ' . $e->getMessage());
        redirect_to('captain-register');
    }
}

function handle_captain_login(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('captain-login');
    }

    $login = post_text('login', 190);
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        set_flash('danger', 'Please enter email/phone and password.');
        redirect_to('captain-login');
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM captains WHERE email = :email_login OR phone = :phone_login LIMIT 1');
        $stmt->execute([
            ':email_login' => $login,
            ':phone_login' => $login,
        ]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            set_flash('danger', 'Invalid captain login details.');
            redirect_to('captain-login');
        }

        if (!in_array(($account['account_status'] ?? ''), ['active', 'approved'], true)) {
            set_flash('danger', 'This captain account is pending or blocked.');
            redirect_to('captain-login');
        }

        create_demo_otp('captain', (int) $account['id'], $account['phone']);
        set_flash('success', 'Login accepted. Verify demo OTP 123456.');
        redirect_to('otp');
    } catch (Throwable $e) {
        set_flash('danger', 'Captain login failed: ' . $e->getMessage());
        redirect_to('captain-login');
    }
}

function handle_admin_login(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('admin-login');
    }

    $email = post_email('email');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        set_flash('danger', 'Please enter admin email and password.');
        redirect_to('admin-login');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please enter a valid admin email address.');
        redirect_to('admin-login');
    }

    try {
        // Admin accounts are seeded in the admins table; there is no public admin registration.
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email AND status = "active" LIMIT 1');
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            set_flash('danger', 'Invalid admin login details.');
            redirect_to('admin-login');
        }

        set_auth('admin', $account);
        set_flash('success', 'Welcome to the TripNovaa admin panel.');
        redirect_to('admin-dashboard');
    } catch (Throwable $e) {
        set_flash('danger', 'Admin login failed: ' . $e->getMessage());
        redirect_to('admin-login');
    }
}

function social_demo_accounts(): array
{
    return [
        'google' => [
            'label' => 'Google',
            'full_name' => 'Google Traveler',
            'email' => 'google.demo@tripnovaa.com',
            'phone' => '03001234001',
            'city' => 'Lahore',
        ],
        'apple' => [
            'label' => 'Apple',
            'full_name' => 'Apple Traveler',
            'email' => 'apple.demo@tripnovaa.com',
            'phone' => '03001234002',
            'city' => 'Islamabad',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'full_name' => 'Facebook Traveler',
            'email' => 'facebook.demo@tripnovaa.com',
            'phone' => '03001234003',
            'city' => 'Karachi',
        ],
    ];
}

function handle_social_demo_login(): void
{
    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('role-selection');
    }

    $provider = strtolower(post_text('provider', 20));
    $accounts = social_demo_accounts();
    if (!isset($accounts[$provider])) {
        set_flash('danger', 'Please choose a valid social login provider.');
        redirect_to('role-selection');
    }

    $demo = $accounts[$provider];

    try {
        // Demo OAuth flow: create or reuse a customer account, then continue with the existing OTP verification.
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $demo['email']]);
        $account = $stmt->fetch();

        if (!$account) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = :phone LIMIT 1');
            $stmt->execute([':phone' => $demo['phone']]);
            $account = $stmt->fetch();

            if (!$account) {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, phone, password_hash, city, reward_points, otp_verified, status)
                     VALUES (:full_name, :email, :phone, :password_hash, :city, 100, 0, "active")'
                );
                $stmt->execute([
                    ':full_name' => $demo['full_name'],
                    ':email' => $demo['email'],
                    ':phone' => $demo['phone'],
                    ':password_hash' => password_hash('social-demo-' . $provider . '-' . bin2hex(random_bytes(6)), PASSWORD_DEFAULT),
                    ':city' => $demo['city'],
                ]);

                $account = [
                    'id' => (int) $pdo->lastInsertId(),
                    'full_name' => $demo['full_name'],
                    'email' => $demo['email'],
                    'phone' => $demo['phone'],
                    'city' => $demo['city'],
                    'status' => 'active',
                ];
            }
        }

        if (($account['status'] ?? '') !== 'active') {
            set_flash('danger', $demo['label'] . ' demo account is not active.');
            redirect_to('role-selection');
        }

        create_demo_otp('user', (int) $account['id'], (string) $account['phone']);
        set_flash('success', $demo['label'] . ' demo login selected. Verify OTP 123456.');
        redirect_to('otp');
    } catch (Throwable $e) {
        set_flash('danger', $demo['label'] . ' demo login failed: ' . $e->getMessage());
        redirect_to('role-selection');
    }
}

function handle_otp_verify(): void
{
    $pending = $_SESSION['pending_otp'] ?? null;
    $otp = post_text('otp', 6);

    if (!$pending) {
        set_flash('warning', 'No OTP request found. Please login again.');
        redirect_to('role-selection');
    }

    if ($otp !== ($pending['otp'] ?? '123456')) {
        set_flash('danger', 'Invalid OTP. Use demo OTP 123456.');
        redirect_to('otp');
    }

    // Demo OTP verification completes login and then creates the final role session.
    $role = $pending['role'];
    $accountId = (int) $pending['id'];
    $account = get_account_by_id($role, $accountId);

    if (!$account) {
        set_flash('danger', 'Account not found for OTP verification.');
        redirect_to('role-selection');
    }

    $pdo = db();
    if ($pdo) {
        try {
            if ($role === 'user') {
                $stmt = $pdo->prepare('UPDATE users SET otp_verified = 1 WHERE id = :id');
                $stmt->execute([':id' => $accountId]);
                $stmt = $pdo->prepare('UPDATE otp_verifications SET is_verified = 1 WHERE user_id = :id AND role_type = "user"');
                $stmt->execute([':id' => $accountId]);
            } else {
                $stmt = $pdo->prepare('UPDATE otp_verifications SET is_verified = 1 WHERE captain_id = :id AND role_type = "captain"');
                $stmt->execute([':id' => $accountId]);
            }
        } catch (Throwable $e) {
            set_flash('warning', 'OTP verified, but database verification status could not be updated.');
        }
    }

    unset($_SESSION['pending_otp']);
    set_auth($role, $account);
    set_flash('success', 'OTP verified successfully.');
    redirect_to($role === 'captain' ? 'captain-dashboard' : 'user-dashboard');
}

function handle_book_ride(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('book-ride');
    }

    try {
        ensure_ride_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Ride table update failed. Re-import tripnovaa.sql or add travel_date/travel_time columns. Details: ' . $e->getMessage());
        redirect_to('book-ride');
    }

    $pickup = post_text('pickup_location', 180);
    $drop = post_text('drop_location', 180);
    $rideType = post_text('ride_type', 30);
    $travelDate = post_text('travel_date', 10);
    $travelTime = post_text('travel_time', 5);

    if ($pickup === '' || $drop === '' || $rideType === '' || $travelDate === '' || $travelTime === '') {
        set_flash('danger', 'Please fill pickup, drop, ride type, date, and time.');
        redirect_to('book-ride');
    }

    if (!array_key_exists($rideType, ride_type_options())) {
        set_flash('danger', 'Please choose a valid ride type.');
        redirect_to('book-ride');
    }

    if (!is_valid_date_ymd($travelDate)) {
        set_flash('danger', 'Please choose a valid travel date.');
        redirect_to('book-ride');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $travelTime)) {
        set_flash('danger', 'Please choose a valid travel time.');
        redirect_to('book-ride');
    }

    $fare = ride_type_fare($rideType);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO rides
             (user_id, captain_id, pickup_location, drop_location, pickup_lat, pickup_lng, drop_lat, drop_lng,
              ride_type, travel_date, travel_time, fare, payment_status, status)
             VALUES
             (:user_id, NULL, :pickup_location, :drop_location, :pickup_lat, :pickup_lng, :drop_lat, :drop_lng,
              :ride_type, :travel_date, :travel_time, :fare, "unpaid", "pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':pickup_location' => $pickup,
            ':drop_location' => $drop,
            ':pickup_lat' => 34.0151,
            ':pickup_lng' => 71.5249,
            ':drop_lat' => 33.6844,
            ':drop_lng' => 73.0479,
            ':ride_type' => $rideType,
            ':travel_date' => $travelDate,
            ':travel_time' => $travelTime . ':00',
            ':fare' => $fare,
        ]);

        $rideId = (int) $pdo->lastInsertId();

        if ($rideId <= 0) {
            $rideId = get_latest_user_ride_id();
        }

        $_SESSION['last_ride_id'] = $rideId;
        set_flash('success', 'Ride saved. Select an available captain.');
        redirect_to('available-captains', ['ride_id' => $rideId]);
    } catch (Throwable $e) {
        set_flash('danger', 'Ride booking failed: ' . $e->getMessage());
        redirect_to('book-ride');
    }
}

function handle_select_captain(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to('book-ride');
    }

    $rideId = (int) ($_POST['ride_id'] ?? 0);
    $captainId = (int) ($_POST['captain_id'] ?? 0);

    if ($rideId <= 0 || $captainId <= 0) {
        set_flash('danger', 'Please select a valid captain.');
        redirect_to('available-captains', ['ride_id' => $rideId]);
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM rides WHERE id = :ride_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
        ]);
        if (!$stmt->fetch()) {
            set_flash('danger', 'Ride not found for this customer.');
            redirect_to('book-ride');
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM captains
             WHERE id = :captain_id
               AND availability_status = "available"
               AND account_status IN ("active", "approved")
             LIMIT 1'
        );
        $stmt->execute([':captain_id' => $captainId]);
        if (!$stmt->fetch()) {
            set_flash('danger', 'Selected captain is not available.');
            redirect_to('available-captains', ['ride_id' => $rideId]);
        }

        $stmt = $pdo->prepare(
            'UPDATE rides
             SET captain_id = :captain_id, status = "captain_selected", requested_at = NOW()
             WHERE id = :ride_id AND user_id = :user_id'
        );
        $stmt->execute([
            ':captain_id' => $captainId,
            ':ride_id' => $rideId,
            ':user_id' => $userId,
        ]);

        set_flash('success', 'Trip request sent to the captain. It will show in their Trip Requests until they accept.');
        redirect_to('ride-tracking', ['ride_id' => $rideId]);
    } catch (Throwable $e) {
        set_flash('danger', 'Captain selection failed: ' . $e->getMessage());
        redirect_to('available-captains', ['ride_id' => $rideId]);
    }
}

function handle_captain_ride_action(): void
{
    require_role('captain', 'captain-login');

    $pdo = db();
    $captainId = current_captain_id();
    if (!$pdo || !$captainId) {
        set_flash('danger', 'Database is not connected or captain session expired.');
        redirect_to('captain-login');
    }

    $rideId = (int) ($_POST['ride_id'] ?? 0);
    $rideAction = post_text('ride_action', 20);
    $allowedActions = ['accept', 'reject', 'complete'];

    if ($rideId <= 0 || !in_array($rideAction, $allowedActions, true)) {
        set_flash('danger', 'Invalid ride action.');
        redirect_to('captain-dashboard');
    }

    try {
        ensure_ride_table_ready($pdo);

        $stmt = $pdo->prepare('SELECT * FROM rides WHERE id = :ride_id AND captain_id = :captain_id LIMIT 1');
        $stmt->execute([
            ':ride_id' => $rideId,
            ':captain_id' => $captainId,
        ]);
        $ride = $stmt->fetch();

        if (!$ride) {
            set_flash('danger', 'Ride not found for this captain.');
            redirect_to('captain-dashboard');
        }

        if ($rideAction === 'accept') {
            if (!in_array(($ride['status'] ?? ''), ['pending', 'captain_selected'], true)) {
                set_flash('warning', 'Only pending trip requests can be accepted.');
                redirect_to('captain-ride-requests');
            }

            $stmt = $pdo->prepare('UPDATE rides SET status = "accepted", accepted_at = NOW() WHERE id = :ride_id AND captain_id = :captain_id');
            $stmt->execute([
                ':ride_id' => $rideId,
                ':captain_id' => $captainId,
            ]);
            $stmt = $pdo->prepare('UPDATE captains SET availability_status = "busy" WHERE id = :captain_id');
            $stmt->execute([':captain_id' => $captainId]);

            set_flash('success', 'Ride accepted. Review advance payment status to confirm booking.');
            redirect_to('captain-advance-payment', ['ride_id' => $rideId]);
        }

        if ($rideAction === 'reject') {
            if (!in_array(($ride['status'] ?? ''), ['pending', 'captain_selected'], true)) {
                set_flash('warning', 'Only pending trip requests can be rejected.');
                redirect_to('captain-ride-requests');
            }

            $stmt = $pdo->prepare('UPDATE rides SET status = "rejected" WHERE id = :ride_id AND captain_id = :captain_id');
            $stmt->execute([
                ':ride_id' => $rideId,
                ':captain_id' => $captainId,
            ]);

            set_flash('success', 'Ride rejected.');
            redirect_to('captain-ride-requests');
        }

        if (!in_array(($ride['status'] ?? ''), ['accepted', 'ongoing'], true)) {
            set_flash('warning', 'Only accepted or ongoing rides can be completed.');
            redirect_to('captain-current-trips');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'UPDATE rides
             SET status = "completed", completed_at = NOW(), payment_status = "paid"
             WHERE id = :ride_id AND captain_id = :captain_id AND status IN ("accepted", "ongoing")'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':captain_id' => $captainId,
        ]);

        $stmt = $pdo->prepare('UPDATE users SET reward_points = reward_points + 25 WHERE id = :user_id');
        $stmt->execute([':user_id' => (int) $ride['user_id']]);

        $stmt = $pdo->prepare(
            'INSERT INTO rewards (user_id, points, source_type, source_id, description)
             VALUES (:user_id, 25, "ride", :ride_id, "Reward points for completed TripNovaa ride")'
        );
        $stmt->execute([
            ':user_id' => (int) $ride['user_id'],
            ':ride_id' => $rideId,
        ]);

        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM payments WHERE booking_type = "ride" AND ride_id = :ride_id');
        $stmt->execute([':ride_id' => $rideId]);
        $paymentExists = (int) (($stmt->fetch()['total'] ?? 0)) > 0;

        if (!$paymentExists) {
            $transactionId = 'CAPTAIN-RIDE-' . $rideId . '-' . time();
            $stmt = $pdo->prepare(
                'INSERT INTO payments
                 (user_id, booking_type, ride_id, amount, currency, payment_provider, payment_method, transaction_id, payment_status, paid_at)
                 VALUES (:user_id, "ride", :ride_id, :amount, "PKR", "cash", "Captain completed demo ride", :transaction_id, "demo_success", NOW())'
            );
            $stmt->execute([
                ':user_id' => (int) $ride['user_id'],
                ':ride_id' => $rideId,
                ':amount' => (float) $ride['fare'],
                ':transaction_id' => $transactionId,
            ]);
        }

        $stmt = $pdo->prepare('UPDATE captains SET availability_status = "available" WHERE id = :captain_id');
        $stmt->execute([':captain_id' => $captainId]);

        $pdo->commit();

        set_flash('success', 'Ride completed. Customer earned 25 reward points.');
        redirect_to('captain-completed-trips');
    } catch (Throwable $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_flash('danger', 'Ride action failed: ' . $e->getMessage());
        redirect_to('captain-dashboard');
    }
}

function handle_send_ride_message(): void
{
    $authRole = (string) ((auth()['role'] ?? '') ?: ($_SESSION['role'] ?? ''));
    if (!in_array($authRole, ['user', 'captain'], true)) {
        set_flash('warning', 'Please login before sending a message.');
        redirect_to('role-selection');
    }

    require_role($authRole, $authRole === 'captain' ? 'captain-login' : 'user-login');

    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected. Import tripnovaa.sql first.');
        redirect_to($authRole === 'captain' ? 'captain-trip-chat' : 'driver-chat');
    }

    $rideId = (int) ($_POST['ride_id'] ?? 0);
    $message = post_text('message', 1000);
    $returnPage = $authRole === 'captain' ? 'captain-trip-chat' : 'driver-chat';
    $returnParams = $rideId > 0 ? ['ride_id' => $rideId] : [];

    if ($rideId <= 0) {
        set_flash('danger', 'Please open a valid ride chat first.');
        redirect_to($returnPage, $returnParams);
    }

    if ($message === '') {
        set_flash('warning', 'Type a message before sending.');
        redirect_to($returnPage, $returnParams);
    }

    try {
        ensure_ride_messages_table_ready($pdo);

        if ($authRole === 'user') {
            $userId = current_user_id() ?? 0;
            $stmt = $pdo->prepare(
                'SELECT id, user_id, captain_id
                 FROM rides
                 WHERE id = :ride_id AND user_id = :user_id
                 LIMIT 1'
            );
            $stmt->execute([
                ':ride_id' => $rideId,
                ':user_id' => $userId,
            ]);
        } else {
            $captainId = current_captain_id() ?? 0;
            $stmt = $pdo->prepare(
                'SELECT id, user_id, captain_id
                 FROM rides
                 WHERE id = :ride_id AND captain_id = :captain_id
                 LIMIT 1'
            );
            $stmt->execute([
                ':ride_id' => $rideId,
                ':captain_id' => $captainId,
            ]);
        }

        $ride = $stmt->fetch();
        if (!$ride) {
            set_flash('danger', 'This chat is not available for your account.');
            redirect_to($returnPage, $returnParams);
        }

        $captainId = (int) ($ride['captain_id'] ?? 0);
        $userId = (int) ($ride['user_id'] ?? 0);
        if ($captainId <= 0 || $userId <= 0) {
            set_flash('warning', 'A captain must be selected before messaging.');
            redirect_to($returnPage, $returnParams);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO ride_messages (ride_id, user_id, captain_id, sender_role, message_body)
             VALUES (:ride_id, :user_id, :captain_id, :sender_role, :message_body)'
        );
        $stmt->execute([
            ':ride_id' => $rideId,
            ':user_id' => $userId,
            ':captain_id' => $captainId,
            ':sender_role' => $authRole,
            ':message_body' => $message,
        ]);

        redirect_to($returnPage, $returnParams);
    } catch (Throwable $e) {
        set_flash('danger', 'Message could not be sent: ' . $e->getMessage());
        redirect_to($returnPage, $returnParams);
    }
}

function save_successful_cashfree_payment(
    PDO $pdo,
    int $userId,
    string $bookingType,
    int $bookingId,
    float $amount,
    array $cashfreeOrder,
    string $couponCode = '',
    float $discountAmount = 0.0,
    string $transactionId = '',
    string $paymentMethod = 'Cashfree Web Checkout',
    string $paymentStatus = 'success'
): array {
    $mapping = booking_payment_mapping($bookingType);
    if (!$mapping) {
        throw new RuntimeException('Unsupported booking type.');
    }

    $pdo->beginTransaction();

    $bookingColumns = [
        'ride_id' => null,
        'hotel_booking_id' => null,
        'train_booking_id' => null,
        'bus_booking_id' => null,
        'restaurant_booking_id' => null,
        'ticket_booking_id' => null,
    ];
    $bookingColumns[$mapping['id_column']] = $bookingId;

    $transactionId = $transactionId !== ''
        ? $transactionId
        : (($cashfreeOrder['cf_order_id'] ?? '') ?: ('CF_' . strtoupper($bookingType) . '_' . $bookingId . '_' . time()));

    $stmt = $pdo->prepare(
        'INSERT INTO payments
         (user_id, booking_type, ride_id, hotel_booking_id, train_booking_id, bus_booking_id,
          restaurant_booking_id, ticket_booking_id, amount, currency, payment_provider,
          payment_method, cashfree_order_id, transaction_id, payment_status, paid_at)
         VALUES
         (:user_id, :booking_type, :ride_id, :hotel_booking_id, :train_booking_id, :bus_booking_id,
          :restaurant_booking_id, :ticket_booking_id, :amount, :currency, "cashfree",
          :payment_method, :cashfree_order_id, :transaction_id, :payment_status, NOW())'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':booking_type' => $bookingType,
        ':ride_id' => $bookingColumns['ride_id'],
        ':hotel_booking_id' => $bookingColumns['hotel_booking_id'],
        ':train_booking_id' => $bookingColumns['train_booking_id'],
        ':bus_booking_id' => $bookingColumns['bus_booking_id'],
        ':restaurant_booking_id' => $bookingColumns['restaurant_booking_id'],
        ':ticket_booking_id' => $bookingColumns['ticket_booking_id'],
        ':amount' => $amount,
        ':currency' => (string) (($cashfreeOrder['order_currency'] ?? $cashfreeOrder['currency'] ?? 'INR') ?: 'INR'),
        ':payment_method' => $paymentMethod,
        ':cashfree_order_id' => (string) ($cashfreeOrder['order_id'] ?? ''),
        ':transaction_id' => $transactionId,
        ':payment_status' => $paymentStatus,
    ]);

    $paymentId = (int) $pdo->lastInsertId();
    update_booking_after_payment($pdo, $bookingType, $bookingId, $userId);

    $rewardPoints = max(10, (int) floor(($amount / 100) * 10));
    $stmt = $pdo->prepare('UPDATE users SET reward_points = reward_points + :points WHERE id = :user_id');
    $stmt->execute([
        ':points' => $rewardPoints,
        ':user_id' => $userId,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO rewards (user_id, points, source_type, source_id, description)
         VALUES (:user_id, :points, :source_type, :source_id, :description)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':points' => $rewardPoints,
        ':source_type' => $mapping['source_type'],
        ':source_id' => $bookingId,
        ':description' => 'Cashfree payment reward for TripNovaa ' . $bookingType . ($couponCode !== '' ? ' using coupon ' . $couponCode : ''),
    ]);

    $pdo->commit();

    $message = 'Cashfree payment completed successfully. You earned ' . $rewardPoints . ' reward points.';
    if ($couponCode !== '' && $discountAmount > 0) {
        $message .= ' Coupon ' . $couponCode . ' saved Rs ' . number_format($discountAmount, 2) . '.';
    }

    return [
        'payment_id' => $paymentId,
        'reward_points' => $rewardPoints,
        'message' => $message,
    ];
}

function redirect_after_successful_payment(string $bookingType, int $bookingId, int $paymentId): void
{
    if ($bookingType === 'ride') {
        $_SESSION['last_ride_id'] = $bookingId;
        redirect_to('ride-success', ['ride_id' => $bookingId, 'payment_id' => $paymentId]);
    }

    redirect_to('payment-success', ['payment_id' => $paymentId]);
}

function handle_cashfree_return(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    $orderId = trim((string) ($_GET['order_id'] ?? ''));

    if (!$pdo || !$userId || $orderId === '') {
        set_flash('danger', 'Cashfree return data is missing.');
        redirect_to('payment-failed');
    }

    $pending = $_SESSION['cashfree_pending'][$orderId] ?? null;
    if (!is_array($pending) || (int) ($pending['user_id'] ?? 0) !== $userId) {
        set_flash('danger', 'Cashfree order session expired. Please try again.');
        redirect_to('payment-failed');
    }

    try {
        $verifiedOrder = verify_cashfree_order($orderId);
        $orderStatus = strtoupper((string) ($verifiedOrder['order_status'] ?? ''));

        if ($orderStatus !== 'PAID') {
            set_flash('danger', 'Cashfree payment is not completed. Current status: ' . ($orderStatus ?: 'UNKNOWN') . '.');
            redirect_to('payment-failed', [
                'booking_type' => $pending['booking_type'],
                'booking_id' => $pending['booking_id'],
                'amount' => $pending['original_amount'] ?? $pending['amount'],
            ]);
        }

        $saved = save_successful_cashfree_payment(
            $pdo,
            $userId,
            (string) $pending['booking_type'],
            (int) $pending['booking_id'],
            (float) $pending['amount'],
            array_merge((array) ($pending['order'] ?? []), $verifiedOrder),
            (string) ($pending['coupon_code'] ?? ''),
            (float) ($pending['discount_amount'] ?? 0),
            (string) (($verifiedOrder['cf_order_id'] ?? '') ?: $orderId),
            'Cashfree Web Checkout',
            'success'
        );

        unset($_SESSION['cashfree_pending'][$orderId]);
        set_flash('success', $saved['message']);
        redirect_after_successful_payment((string) $pending['booking_type'], (int) $pending['booking_id'], (int) $saved['payment_id']);
    } catch (Throwable $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_flash('danger', 'Cashfree verification failed: ' . $e->getMessage());
        redirect_to('payment-failed', [
            'booking_type' => $pending['booking_type'] ?? '',
            'booking_id' => $pending['booking_id'] ?? 0,
            'amount' => $pending['original_amount'] ?? ($pending['amount'] ?? 0),
        ]);
    }
}

function handle_demo_cashfree_payment(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    $bookingType = post_text('booking_type', 30);
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $rawPostedAmount = $_POST['amount'] ?? null;
    $postedAmount = is_positive_number($rawPostedAmount) ? (float) $rawPostedAmount : 0.0;
    $couponCode = strtoupper(post_text('coupon_code', 40));

    if (!in_array($bookingType, supported_booking_types(), true) || $bookingId <= 0 || $postedAmount <= 0) {
        set_flash('danger', 'Invalid payment request.');
        redirect_to('payment-failed');
    }

    $booking = get_booking_payment_details($bookingType, $bookingId, $postedAmount);
    if (!$booking) {
        set_flash('danger', 'Booking not found for this customer.');
        redirect_to('payment-failed');
    }

    $amount = (float) $booking['amount'];
    if ($amount <= 0) {
        $amount = $postedAmount;
    }

    $originalAmount = $amount;
    $offerResult = calculate_offer_result($couponCode, $originalAmount);
    if ($couponCode !== '' && !$offerResult['valid']) {
        set_flash('danger', $offerResult['message']);
        redirect_to('payment', [
            'booking_type' => $bookingType,
            'booking_id' => $bookingId,
            'amount' => $originalAmount,
            'coupon_code' => $couponCode,
        ]);
    }

    $discountAmount = (float) $offerResult['discount_amount'];
    $amount = (float) $offerResult['final_amount'];
    $customer = get_current_customer() ?? [];

    try {
        $cashfreeOrder = create_cashfree_order($bookingType, $bookingId, $amount, $customer);

        if (!empty($cashfreeOrder['live'])) {
            $orderId = (string) ($cashfreeOrder['order_id'] ?? '');
            if ($orderId === '' || empty($cashfreeOrder['payment_session_id'])) {
                throw new RuntimeException('Cashfree did not return a payment session.');
            }

            $_SESSION['cashfree_pending'][$orderId] = [
                'user_id' => $userId,
                'booking_type' => $bookingType,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'original_amount' => $originalAmount,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'order' => $cashfreeOrder,
                'created_at' => time(),
            ];

            redirect_to('payment', [
                'booking_type' => $bookingType,
                'booking_id' => $bookingId,
                'amount' => $originalAmount,
                'coupon_code' => $couponCode,
                'cashfree_checkout' => 1,
                'cf_order_id' => $orderId,
            ]);
        }

        $saved = save_successful_cashfree_payment(
            $pdo,
            $userId,
            $bookingType,
            $bookingId,
            $amount,
            $cashfreeOrder,
            $couponCode,
            $discountAmount,
            'CF_DEMO_' . strtoupper($bookingType) . '_' . $bookingId . '_' . time(),
            'Cashfree Demo Fallback',
            'demo_success'
        );

        set_flash('success', $saved['message'] . ' Demo fallback was used because real Cashfree keys are not configured.');
        redirect_after_successful_payment($bookingType, $bookingId, (int) $saved['payment_id']);
    } catch (Throwable $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_flash('danger', 'Payment failed: ' . $e->getMessage());
        redirect_to('payment-failed', [
            'booking_type' => $bookingType,
            'booking_id' => $bookingId,
            'amount' => $amount,
        ]);
    }
}

function handle_hotel_booking(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    try {
        ensure_hotel_booking_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Hotel booking table update failed: ' . $e->getMessage());
        redirect_to('hotel-search');
    }

    $hotelName = post_text('hotel_name', 180);
    $city = post_text('city', 100);
    $checkIn = post_text('check_in', 10);
    $checkOut = post_text('check_out', 10);
    $roomType = post_text('room_type', 80);
    $guestsRaw = $_POST['guests'] ?? null;
    $roomsRaw = $_POST['rooms'] ?? null;
    $priceRaw = $_POST['price'] ?? null;
    $guests = is_positive_integer_value($guestsRaw) ? (int) $guestsRaw : 0;
    $rooms = is_positive_integer_value($roomsRaw) ? (int) $roomsRaw : 1;
    $price = is_positive_number($priceRaw) ? (float) $priceRaw : 0.0;

    if ($hotelName === '' || $city === '' || $checkIn === '' || $checkOut === '' || $roomType === '' || $guests <= 0 || $rooms <= 0 || $price <= 0) {
        set_flash('danger', 'Please complete all hotel booking fields.');
        redirect_to('hotel-search');
    }

    $checkInDate = DateTime::createFromFormat('Y-m-d', $checkIn);
    $checkOutDate = DateTime::createFromFormat('Y-m-d', $checkOut);
    if (!is_valid_date_ymd($checkIn) || !is_valid_date_ymd($checkOut) || !$checkInDate || !$checkOutDate || $checkOutDate <= $checkInDate) {
        set_flash('danger', 'Check-out date must be after check-in date.');
        redirect_to('hotel-search');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO hotel_bookings
             (user_id, hotel_id, hotel_name, city, check_in_date, check_out_date, guests, rooms, room_type, amount, status)
             VALUES
             (:user_id, NULL, :hotel_name, :city, :check_in_date, :check_out_date, :guests, :rooms, :room_type, :amount, "payment_pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':hotel_name' => $hotelName,
            ':city' => $city,
            ':check_in_date' => $checkIn,
            ':check_out_date' => $checkOut,
            ':guests' => $guests,
            ':rooms' => $rooms,
            ':room_type' => $roomType,
            ':amount' => $price,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        set_flash('success', 'Hotel booking saved. Complete payment to confirm your stay.');
        redirect_to('payment', [
            'booking_type' => 'hotel',
            'booking_id' => $bookingId,
            'amount' => $price,
        ]);
    } catch (Throwable $e) {
        set_flash('danger', 'Hotel booking failed: ' . $e->getMessage());
        redirect_to('hotel-search');
    }
}

function handle_train_booking(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    try {
        ensure_train_booking_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Train booking table update failed: ' . $e->getMessage());
        redirect_to('train-search');
    }

    $fromCity = post_text('from_city', 100);
    $toCity = post_text('to_city', 100);
    $travelDate = post_text('travel_date', 10);
    $trainName = post_text('train_name', 160);
    $trainNumber = post_text('train_number', 40);
    $seatType = post_text('seat_type', 80);
    $passengersRaw = $_POST['passengers'] ?? null;
    $priceRaw = $_POST['price'] ?? null;
    $passengers = is_positive_integer_value($passengersRaw) ? (int) $passengersRaw : 0;
    $price = is_positive_number($priceRaw) ? (float) $priceRaw : 0.0;

    if ($fromCity === '' || $toCity === '' || $travelDate === '' || $trainName === '' || $trainNumber === '' || $seatType === '' || $passengers <= 0 || $price <= 0) {
        set_flash('danger', 'Please complete all train booking fields.');
        redirect_to('train-search');
    }

    if (!is_valid_date_ymd($travelDate)) {
        set_flash('danger', 'Please choose a valid travel date.');
        redirect_to('train-search');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO train_bookings
             (user_id, train_id, train_name, train_number, origin, destination, travel_date, seat_class, passengers, amount, status)
             VALUES
             (:user_id, NULL, :train_name, :train_number, :origin, :destination, :travel_date, :seat_class, :passengers, :amount, "payment_pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':train_name' => $trainName,
            ':train_number' => $trainNumber,
            ':origin' => $fromCity,
            ':destination' => $toCity,
            ':travel_date' => $travelDate,
            ':seat_class' => $seatType,
            ':passengers' => $passengers,
            ':amount' => $price,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        set_flash('success', 'Train booking saved. Complete payment to confirm your seats.');
        redirect_to('payment', [
            'booking_type' => 'train',
            'booking_id' => $bookingId,
            'amount' => $price,
        ]);
    } catch (Throwable $e) {
        set_flash('danger', 'Train booking failed: ' . $e->getMessage());
        redirect_to('train-search');
    }
}

function handle_bus_booking(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    try {
        ensure_bus_booking_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Bus booking table update failed: ' . $e->getMessage());
        redirect_to('bus-search');
    }

    $fromCity = post_text('from_city', 100);
    $toCity = post_text('to_city', 100);
    $travelDate = post_text('travel_date', 10);
    $busName = post_text('bus_name', 160);
    $busNumber = post_text('bus_number', 40);
    $busType = post_text('bus_type', 80);
    $seatNo = post_text('seat_no', 30);
    $passengersRaw = $_POST['passengers'] ?? null;
    $priceRaw = $_POST['price'] ?? null;
    $passengers = is_positive_integer_value($passengersRaw) ? (int) $passengersRaw : 0;
    $price = is_positive_number($priceRaw) ? (float) $priceRaw : 0.0;

    if ($fromCity === '' || $toCity === '' || $travelDate === '' || $busName === '' || $busNumber === '' || $busType === '' || $seatNo === '' || $passengers <= 0 || $price <= 0) {
        set_flash('danger', 'Please complete all bus booking fields.');
        redirect_to('bus-search');
    }

    if (!is_valid_date_ymd($travelDate)) {
        set_flash('danger', 'Please choose a valid travel date.');
        redirect_to('bus-search');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO bus_bookings
             (user_id, bus_id, bus_name, bus_number, origin, destination, travel_date, bus_type, seat_no, seats, amount, status)
             VALUES
             (:user_id, NULL, :bus_name, :bus_number, :origin, :destination, :travel_date, :bus_type, :seat_no, :seats, :amount, "payment_pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':bus_name' => $busName,
            ':bus_number' => $busNumber,
            ':origin' => $fromCity,
            ':destination' => $toCity,
            ':travel_date' => $travelDate,
            ':bus_type' => $busType,
            ':seat_no' => $seatNo,
            ':seats' => $passengers,
            ':amount' => $price,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        set_flash('success', 'Bus booking saved. Complete payment to confirm your seats.');
        redirect_to('payment', [
            'booking_type' => 'bus',
            'booking_id' => $bookingId,
            'amount' => $price,
        ]);
    } catch (Throwable $e) {
        set_flash('danger', 'Bus booking failed: ' . $e->getMessage());
        redirect_to('bus-search');
    }
}

function handle_restaurant_booking(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    try {
        ensure_restaurant_booking_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Restaurant booking table update failed: ' . $e->getMessage());
        redirect_to('restaurant-search');
    }

    $restaurantName = post_text('restaurant_name', 180);
    $city = post_text('city', 100);
    $bookingDate = post_text('booking_date', 10);
    $bookingTime = post_text('booking_time', 5);
    $guestsRaw = $_POST['guests'] ?? null;
    $priceRaw = $_POST['price'] ?? null;
    $guests = is_positive_integer_value($guestsRaw) ? (int) $guestsRaw : 0;
    $specialRequest = post_text('special_request', 500);
    $price = is_positive_number($priceRaw) ? (float) $priceRaw : 0.0;

    if ($restaurantName === '' || $city === '' || $bookingDate === '' || $bookingTime === '' || $guests <= 0 || $price <= 0) {
        set_flash('danger', 'Please complete all restaurant booking fields.');
        redirect_to('restaurant-search');
    }

    if (!is_valid_date_ymd($bookingDate)) {
        set_flash('danger', 'Please choose a valid booking date.');
        redirect_to('restaurant-search');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO restaurant_bookings
             (user_id, restaurant_id, restaurant_name, city, booking_date, booking_time, guests, special_request, amount, status)
             VALUES
             (:user_id, NULL, :restaurant_name, :city, :booking_date, :booking_time, :guests, :special_request, :amount, "payment_pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':restaurant_name' => $restaurantName,
            ':city' => $city,
            ':booking_date' => $bookingDate,
            ':booking_time' => $bookingTime,
            ':guests' => $guests,
            ':special_request' => $specialRequest,
            ':amount' => $price,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        set_flash('success', 'Restaurant reservation saved. Complete payment to confirm your table.');
        redirect_to('payment', [
            'booking_type' => 'restaurant',
            'booking_id' => $bookingId,
            'amount' => $price,
        ]);
    } catch (Throwable $e) {
        set_flash('danger', 'Restaurant booking failed: ' . $e->getMessage());
        redirect_to('restaurant-search');
    }
}

function handle_ticket_booking(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    try {
        ensure_ticket_booking_table_ready($pdo);
    } catch (Throwable $e) {
        set_flash('danger', 'Ticket booking table update failed: ' . $e->getMessage());
        redirect_to('tour-ticket-search');
    }

    $eventName = post_text('event_name', 180);
    $location = post_text('location', 140);
    $eventDate = post_text('event_date', 40);
    $ticketType = post_text('ticket_type', 80);
    $quantityRaw = $_POST['quantity'] ?? null;
    $priceRaw = $_POST['price'] ?? null;
    $quantity = is_positive_integer_value($quantityRaw) ? (int) $quantityRaw : 0;
    $price = is_positive_number($priceRaw) ? (float) $priceRaw : 0.0;
    $apiReference = post_text('api_reference', 120);

    if ($eventName === '' || $location === '' || $eventDate === '' || $ticketType === '' || $quantity <= 0 || $price <= 0 || $apiReference === '') {
        set_flash('danger', 'Please complete all ticket booking fields.');
        redirect_to('tour-ticket-search');
    }

    $eventTimestamp = strtotime($eventDate);
    if ($eventTimestamp === false) {
        set_flash('danger', 'Please choose a valid event date.');
        redirect_to('tour-ticket-search');
    }

    $formattedEventDate = date('Y-m-d H:i:s', $eventTimestamp);
    $totalAmount = $price * $quantity;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO ticket_bookings
             (user_id, ticket_event_id, event_name, location, city, event_date, ticket_type, quantity, price, tickets, amount, api_reference, status)
             VALUES
             (:user_id, NULL, :event_name, :location, :city, :event_date, :ticket_type, :quantity, :price, :tickets, :amount, :api_reference, "payment_pending")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':event_name' => $eventName,
            ':location' => $location,
            ':city' => $location,
            ':event_date' => $formattedEventDate,
            ':ticket_type' => $ticketType,
            ':quantity' => $quantity,
            ':price' => $price,
            ':tickets' => $quantity,
            ':amount' => $totalAmount,
            ':api_reference' => $apiReference,
        ]);

        $bookingId = (int) $pdo->lastInsertId();
        set_flash('success', 'Ticket booking saved. Complete payment to confirm your tickets.');
        redirect_to('payment', [
            'booking_type' => 'ticket',
            'booking_id' => $bookingId,
            'amount' => $totalAmount,
        ]);
    } catch (Throwable $e) {
        set_flash('danger', 'Ticket booking failed: ' . $e->getMessage());
        redirect_to('tour-ticket-search');
    }
}

function handle_feedback_submit(): void
{
    require_role('user', 'user-login');

    $pdo = db();
    $userId = current_user_id();
    if (!$pdo || !$userId) {
        set_flash('danger', 'Database is not connected or user session expired.');
        redirect_to('user-login');
    }

    $bookingType = post_text('booking_type', 30);
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $ratingRaw = $_POST['rating'] ?? null;
    $rating = is_positive_integer_value($ratingRaw) ? (int) $ratingRaw : 0;
    $comment = post_text('comment', 1000);

    if (!in_array($bookingType, ['ride'], true) || $bookingId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
        set_flash('danger', 'Please submit a completed ride, rating from 1 to 5, and comment.');
        redirect_to('feedback', ['booking_type' => $bookingType, 'booking_id' => $bookingId]);
    }

    $ride = get_completed_ride_for_feedback($bookingId, $userId);
    if (!$ride) {
        set_flash('danger', 'Feedback is available only after your ride is completed.');
        redirect_to('my-bookings');
    }

    if (user_feedback_exists('ride', $bookingId, $userId)) {
        set_flash('warning', 'Feedback for this ride has already been submitted.');
        redirect_to('my-bookings');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO feedback (user_id, captain_id, ride_id, feedback_type, rating, comments, status)
             VALUES (:user_id, :captain_id, :ride_id, "ride", :rating, :comments, "visible")'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':captain_id' => $ride['captain_id'] ?? null,
            ':ride_id' => $bookingId,
            ':rating' => $rating,
            ':comments' => $comment,
        ]);

        if (!empty($ride['captain_id'])) {
            $statsStmt = $pdo->prepare(
                'SELECT AVG(f.rating) AS average_rating
                 FROM feedback f
                 INNER JOIN rides r ON r.id = f.ride_id
                 WHERE r.captain_id = :captain_id
                   AND r.status = "completed"
                   AND f.feedback_type = "ride"
                   AND f.status = "visible"'
            );
            $statsStmt->execute([':captain_id' => (int) $ride['captain_id']]);
            $average = (float) (($statsStmt->fetch()['average_rating'] ?? 0) ?: 0);

            $updateCaptain = $pdo->prepare('UPDATE captains SET rating = :rating WHERE id = :captain_id');
            $updateCaptain->execute([
                ':rating' => $average > 0 ? round($average, 2) : 5.00,
                ':captain_id' => (int) $ride['captain_id'],
            ]);
        }

        $pdo->commit();
        set_flash('success', 'Thank you. Your ride feedback has been submitted.');
        redirect_to('feedback-success', ['booking_type' => 'ride', 'booking_id' => $bookingId]);
    } catch (Throwable $e) {
        if ($pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        set_flash('danger', 'Feedback save failed: ' . $e->getMessage());
        redirect_to('feedback', ['booking_type' => 'ride', 'booking_id' => $bookingId]);
    }
}

function plan_trip_default_state(): array
{
    return [
        'from_city' => 'Bangalore',
        'to_city' => 'Manali, Himachal Pradesh',
        'trip_date' => date('Y-m-d', strtotime('+7 days')),
        'travelers' => 2,
        'budget' => '15000-25000',
        'interests' => ['Solang Valley', 'Rohtang Pass', 'Local sightseeing'],
        'transport' => 'bus',
        'selected_option_id' => 'bus_hrtc_volvo',
        'package_total' => 19800,
        'deposit_paid' => false,
        'deposit_reference' => '',
        'trip_feedback_rating' => 0,
        'trip_feedback_comment' => '',
    ];
}

function plan_trip_state(): array
{
    $state = $_SESSION['plan_trip'] ?? [];
    if (!is_array($state)) {
        $state = [];
    }

    return array_merge(plan_trip_default_state(), $state);
}

function save_plan_trip_state(array $updates): void
{
    $_SESSION['plan_trip'] = array_merge(plan_trip_state(), $updates);
}

function plan_trip_transport_options(?string $transport = null): array
{
    $options = [
        'bus' => [
            [
                'id' => 'bus_hrtc_volvo',
                'name' => 'HRTC Volvo',
                'route' => 'Bangalore to Manali',
                'time' => '20 May - 8:00 PM',
                'seat' => 'AC Sleeper',
                'price' => 2450,
                'rating' => '4.8',
            ],
            [
                'id' => 'bus_srs_travels',
                'name' => 'SRS Travels',
                'route' => 'Bangalore to Manali',
                'time' => '20 May - 9:30 PM',
                'seat' => 'AC Sleeper',
                'price' => 2150,
                'rating' => '4.5',
            ],
            [
                'id' => 'bus_zingbus',
                'name' => 'Zingbus Plus',
                'route' => 'Bangalore to Manali',
                'time' => '20 May - 7:00 PM',
                'seat' => 'AC Semi Sleeper',
                'price' => 2350,
                'rating' => '4.6',
            ],
            [
                'id' => 'bus_lalada',
                'name' => 'Lalada Travels',
                'route' => 'Bangalore to Manali',
                'time' => '20 May - 10:00 PM',
                'seat' => 'AC Sleeper',
                'price' => 2650,
                'rating' => '4.3',
            ],
        ],
        'train' => [
            [
                'id' => 'train_green_line',
                'name' => 'Green Line Express',
                'route' => 'Bangalore to Chandigarh',
                'time' => '20 May - 6:40 AM',
                'seat' => 'AC Chair + Cab',
                'price' => 1850,
                'rating' => '4.7',
            ],
            [
                'id' => 'train_tripnovaa',
                'name' => 'TripNovaa Express',
                'route' => 'Bangalore to Pathankot',
                'time' => '20 May - 8:15 AM',
                'seat' => 'Sleeper + Shuttle',
                'price' => 1650,
                'rating' => '4.4',
            ],
        ],
        'flight' => [
            [
                'id' => 'flight_indigo',
                'name' => 'IndiGo Saver',
                'route' => 'Bangalore to Kullu',
                'time' => '20 May - 9:10 AM',
                'seat' => 'Economy',
                'price' => 7250,
                'rating' => '4.8',
            ],
            [
                'id' => 'flight_tripnovaa_air',
                'name' => 'TripNovaa Air',
                'route' => 'Bangalore to Delhi + Cab',
                'time' => '20 May - 11:30 AM',
                'seat' => 'Economy + Transfer',
                'price' => 6450,
                'rating' => '4.5',
            ],
        ],
    ];

    return $transport === null ? $options : ($options[$transport] ?? $options['bus']);
}

function selected_plan_trip_option(): array
{
    $state = plan_trip_state();
    $options = plan_trip_transport_options((string) $state['transport']);
    foreach ($options as $option) {
        if (($option['id'] ?? '') === ($state['selected_option_id'] ?? '')) {
            return $option;
        }
    }

    return $options[0];
}

function plan_trip_deposit_amount(): int
{
    $state = plan_trip_state();
    return max(1, (int) round(((float) $state['package_total']) * 0.05));
}

function handle_plan_trip_start(): void
{
    require_role('user', 'user-login');

    $fromCity = post_text('from_city', 100);
    $toCity = post_text('to_city', 140);
    $tripDate = post_text('trip_date', 10);
    $travelersRaw = $_POST['travelers'] ?? null;
    $travelers = is_positive_integer_value($travelersRaw) ? (int) $travelersRaw : 0;
    $budget = post_text('budget', 40);
    $interests = [];

    foreach ((array) ($_POST['interests'] ?? []) as $interest) {
        $interest = substr(trim((string) $interest), 0, 80);
        if ($interest !== '') {
            $interests[] = $interest;
        }
    }

    if ($fromCity === '' || $toCity === '' || $tripDate === '' || $travelers <= 0 || $budget === '') {
        set_flash('danger', 'Please complete your trip origin, destination, date, travelers, and budget.');
        redirect_to('plan-trip');
    }

    if (!is_valid_date_ymd($tripDate)) {
        set_flash('danger', 'Please select a valid trip date.');
        redirect_to('plan-trip');
    }

    save_plan_trip_state([
        'from_city' => $fromCity,
        'to_city' => $toCity,
        'trip_date' => $tripDate,
        'travelers' => min($travelers, 12),
        'budget' => $budget,
        'interests' => $interests ?: ['Solang Valley', 'Rohtang Pass', 'Local sightseeing'],
        'transport' => 'bus',
        'selected_option_id' => 'bus_hrtc_volvo',
        'deposit_paid' => false,
        'deposit_reference' => '',
        'trip_feedback_rating' => 0,
        'trip_feedback_comment' => '',
    ]);

    set_flash('success', 'Trip details saved. Choose your public transport.');
    redirect_to('plan-trip-transport');
}

function handle_post_new_trip(): void
{
    require_role('captain', 'captain-login');

    $fromCity = post_text('from_city', 100);
    $toCity = post_text('to_city', 140);
    $startDate = post_text('start_date', 10);
    $endDate = post_text('end_date', 10);
    $travelersRaw = $_POST['travelers'] ?? null;
    $travelers = is_positive_integer_value($travelersRaw) ? (int) $travelersRaw : 0;
    $budget = post_text('budget', 40);
    $tripType = post_text('trip_type', 40);
    $notes = post_text('notes', 500);

    if ($fromCity === '' || $toCity === '' || $startDate === '' || $endDate === '' || $travelers <= 0 || $budget === '' || $tripType === '') {
        set_flash('danger', 'Please complete from, to, dates, travelers, budget, and trip type.');
        redirect_to('post-new-trip');
    }

    if (!is_valid_date_ymd($startDate) || !is_valid_date_ymd($endDate) || strtotime($endDate) < strtotime($startDate)) {
        set_flash('danger', 'Please choose valid trip dates.');
        redirect_to('post-new-trip');
    }

    $tripId = 'TRP' . random_int(1200, 9999);
    $dateText = date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate));
    $newTrip = [
        'id' => $tripId,
        'image' => 'trip-img-manali',
        'route' => $fromCity . ' -> ' . $toCity,
        'date' => $dateText,
        'travelers' => min($travelers, 12),
        'status' => 'Open',
        'badge' => 'Open',
        'budget' => $budget,
        'trip_type' => $tripType,
        'notes' => $notes,
    ];

    $postedTrips = $_SESSION['posted_trips'] ?? [];
    if (!is_array($postedTrips)) {
        $postedTrips = [];
    }
    array_unshift($postedTrips, $newTrip);
    $_SESSION['posted_trips'] = array_slice($postedTrips, 0, 10);

    save_plan_trip_state([
        'from_city' => $fromCity,
        'to_city' => $toCity,
        'trip_date' => $startDate,
        'travelers' => min($travelers, 12),
        'budget' => $budget,
        'interests' => [$tripType],
    ]);

    set_flash('success', 'Trip posted successfully. Drivers can now send offers.');
    redirect_to('my-trips-posted');
}

function handle_plan_trip_choose_transport(): void
{
    require_role('user', 'user-login');

    $transport = post_text('transport', 20);
    $allOptions = plan_trip_transport_options();
    if (!isset($allOptions[$transport])) {
        set_flash('danger', 'Please choose bus, train, or flight.');
        redirect_to('plan-trip-transport');
    }

    save_plan_trip_state([
        'transport' => $transport,
        'selected_option_id' => $allOptions[$transport][0]['id'],
    ]);

    redirect_to('plan-trip-options');
}

function handle_plan_trip_select_option(): void
{
    require_role('user', 'user-login');

    $state = plan_trip_state();
    $optionId = post_text('option_id', 80);
    $selected = null;

    foreach (plan_trip_transport_options((string) $state['transport']) as $option) {
        if (($option['id'] ?? '') === $optionId) {
            $selected = $option;
            break;
        }
    }

    if (!$selected) {
        set_flash('danger', 'Please select a valid travel option.');
        redirect_to('plan-trip-options');
    }

    save_plan_trip_state(['selected_option_id' => $optionId]);
    set_flash('success', 'Transport option selected. Review your Manali trip plan.');
    redirect_to('plan-trip-detail');
}

function handle_plan_trip_deposit(): void
{
    require_role('user', 'user-login');

    $deposit = plan_trip_deposit_amount();
    save_plan_trip_state([
        'deposit_paid' => true,
        'deposit_reference' => 'TNPLAN5-' . time(),
    ]);

    set_flash('success', 'Demo 5% trip deposit paid: Rs ' . $deposit . '. Your guide trip is unlocked.');
    redirect_to('plan-trip-guide');
}

function handle_plan_trip_feedback(): void
{
    require_role('user', 'user-login');

    $ratingRaw = $_POST['rating'] ?? null;
    $rating = is_positive_integer_value($ratingRaw) ? (int) $ratingRaw : 0;
    $comment = post_text('comment', 500);

    if ($rating < 1 || $rating > 5) {
        set_flash('danger', 'Please select a rating from 1 to 5.');
        redirect_to('plan-trip-complete');
    }

    save_plan_trip_state([
        'trip_feedback_rating' => $rating,
        'trip_feedback_comment' => $comment,
    ]);

    set_flash('success', 'Trip feedback submitted.');
    redirect_to('plan-trip-reminder');
}

function group_tour_default_booking(): array
{
    return [
        'tour_id' => 'vijayawada-kasi',
        'selected_seats' => ['10', '11'],
        'members' => 2,
        'amount_per_person' => 12999,
        'advance_paid' => false,
        'remaining_paid' => false,
        'rating' => 0,
        'feedback' => '',
        'pnr' => 'TN12345678',
    ];
}

function group_tour_booking_state(): array
{
    $state = $_SESSION['group_tour_booking'] ?? [];
    if (!is_array($state)) {
        $state = [];
    }

    return array_merge(group_tour_default_booking(), $state);
}

function save_group_tour_booking(array $updates): void
{
    $_SESSION['group_tour_booking'] = array_merge(group_tour_booking_state(), $updates);
}

function group_tour_catalog(): array
{
    return [
        'vijayawada-kasi' => [
            'id' => 'vijayawada-kasi',
            'title' => 'Vijayawada to Kasi',
            'place' => 'Varanasi',
            'dates' => '20 May - 29 May 2024',
            'duration' => '10 Days / 9 Nights',
            'bus' => 'AC Luxury Bus',
            'food' => 'Breakfast & Dinner',
            'captain' => 'Ramesh Kumar',
            'rating' => '4.8',
            'vehicle' => 'AC Luxury Bus - 50 Seater',
            'price' => 12999,
            'seats_left' => 50,
            'badge' => 'Upcoming',
            'highlights' => ['Kashi Vishwanath Temple', 'Ganga Aarti', 'Sarnath Visit', 'Prayagraj Sangam', 'Ayodhya Darshan', 'Bodh Gaya'],
        ],
        'hyderabad-tirupati' => [
            'id' => 'hyderabad-tirupati',
            'title' => 'Hyderabad to Tirupati',
            'place' => 'Tirupati',
            'dates' => '3 Days / 2 Nights',
            'duration' => '3 Days / 2 Nights',
            'bus' => 'AC Bus',
            'food' => 'Breakfast',
            'captain' => 'Rohit Sharma',
            'rating' => '4.7',
            'vehicle' => 'AC Coach - 35 Seater',
            'price' => 4999,
            'seats_left' => 25,
            'badge' => 'Few Seats Left',
            'highlights' => ['Tirumala Darshan', 'Padmavathi Temple', 'Local Guide'],
        ],
        'chennai-rameswaram' => [
            'id' => 'chennai-rameswaram',
            'title' => 'Chennai to Rameswaram',
            'place' => 'Rameswaram',
            'dates' => '2 Days / 1 Night',
            'duration' => '2 Days / 1 Night',
            'bus' => 'Pushback Coach',
            'food' => 'Breakfast',
            'captain' => 'Ajay Kumar',
            'rating' => '4.6',
            'vehicle' => 'AC Mini Bus - 28 Seater',
            'price' => 2999,
            'seats_left' => 45,
            'badge' => 'Available',
            'highlights' => ['Ramanathaswamy Temple', 'Dhanushkodi', 'Pamban Bridge'],
        ],
    ];
}

function current_group_tour(): array
{
    $booking = group_tour_booking_state();
    $catalog = group_tour_catalog();
    $tourId = (string) ($_GET['tour_id'] ?? $booking['tour_id'] ?? 'vijayawada-kasi');
    if (!isset($catalog[$tourId])) {
        $tourId = 'vijayawada-kasi';
    }

    save_group_tour_booking(['tour_id' => $tourId]);
    return $catalog[$tourId];
}

function group_tour_total_amount(): int
{
    $booking = group_tour_booking_state();
    return (int) $booking['members'] * (int) $booking['amount_per_person'];
}

function group_tour_advance_amount(): int
{
    return max(1, (int) round(group_tour_total_amount() * 0.05));
}

function handle_group_tour_select_seats(): void
{
    require_role('user', 'user-login');

    $tourId = post_text('tour_id', 80);
    $seats = [];
    foreach ((array) ($_POST['selected_seats'] ?? []) as $seat) {
        $seat = preg_replace('/[^0-9]/', '', (string) $seat);
        if ($seat !== '') {
            $seats[] = $seat;
        }
    }
    $seats = array_values(array_unique(array_slice($seats, 0, 6)));

    if (!$seats) {
        set_flash('danger', 'Please select at least one seat.');
        redirect_to('group-tour-seats', ['tour_id' => $tourId]);
    }

    $tour = group_tour_catalog()[$tourId] ?? group_tour_catalog()['vijayawada-kasi'];
    save_group_tour_booking([
        'tour_id' => $tour['id'],
        'selected_seats' => $seats,
        'members' => count($seats),
        'amount_per_person' => (int) $tour['price'],
    ]);

    set_flash('success', 'Seats selected. Pay 5% advance to reserve them.');
    redirect_to('group-tour-advance');
}

function handle_group_tour_pay_advance(): void
{
    require_role('user', 'user-login');

    save_group_tour_booking(['advance_paid' => true]);
    set_flash('success', 'Advance payment received. Your group tour seats are reserved.');
    redirect_to('group-tour-confirmed');
}

function handle_group_tour_pay_remaining(): void
{
    require_role('user', 'user-login');

    save_group_tour_booking(['remaining_paid' => true]);
    set_flash('success', 'Remaining payment completed. Enjoy your group tour.');
    redirect_to('group-tour-completed');
}

function handle_group_tour_feedback(): void
{
    require_role('user', 'user-login');

    $ratingRaw = $_POST['rating'] ?? null;
    $rating = is_positive_integer_value($ratingRaw) ? (int) $ratingRaw : 0;
    $feedback = post_text('feedback', 500);

    if ($rating < 1 || $rating > 5) {
        set_flash('danger', 'Please choose a rating from 1 to 5.');
        redirect_to('group-tour-completed');
    }

    save_group_tour_booking(['rating' => $rating, 'feedback' => $feedback]);
    set_flash('success', 'Thanks for rating your group tour.');
    redirect_to('group-tour-more');
}

function handle_admin_add_offer(): void
{
    require_role('admin', 'admin-login');

    $pdo = db();
    if (!$pdo) {
        set_flash('danger', 'Database is not connected.');
        redirect_to('admin-offers');
    }

    $title = post_text('title', 150);
    $description = post_text('description', 500);
    $code = strtoupper(post_text('code', 40));
    $discountType = post_text('discount_type', 20);
    $discountValueRaw = $_POST['discount_value'] ?? null;
    $minBookingAmountRaw = $_POST['min_booking_amount'] ?? 0;
    $discountValue = is_positive_number($discountValueRaw) ? (float) $discountValueRaw : 0.0;
    $minBookingAmount = is_non_negative_number($minBookingAmountRaw) ? (float) $minBookingAmountRaw : -1.0;
    $validFrom = post_text('valid_from', 10) ?: date('Y-m-d');
    $validTo = post_text('valid_to', 10) ?: date('Y-m-d', strtotime('+30 days'));
    $status = post_text('status', 20);

    if ($title === '' || $code === '' || !in_array($discountType, ['flat', 'percentage'], true) || $discountValue <= 0 || $minBookingAmount < 0 || !in_array($status, ['active', 'inactive', 'expired'], true)) {
        set_flash('danger', 'Please enter a valid offer title, code, discount, and status.');
        redirect_to('admin-offers');
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO offers
             (title, description, code, discount_type, discount_value, min_booking_amount, valid_from, valid_to, status)
             VALUES (:title, :description, :code, :discount_type, :discount_value, :min_booking_amount, :valid_from, :valid_to, :status)'
        );
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':code' => $code,
            ':discount_type' => $discountType,
            ':discount_value' => $discountValue,
            ':min_booking_amount' => $minBookingAmount,
            ':valid_from' => $validFrom,
            ':valid_to' => $validTo,
            ':status' => $status,
        ]);

        set_flash('success', 'Offer added successfully.');
    } catch (Throwable $e) {
        set_flash('danger', 'Offer add failed: ' . $e->getMessage());
    }

    redirect_to('admin-offers');
}

function handle_admin_update_offer_status(): void
{
    require_role('admin', 'admin-login');

    $pdo = db();
    $offerId = (int) ($_POST['offer_id'] ?? 0);
    $status = post_text('status', 20);
    if (!$pdo || $offerId <= 0 || !in_array($status, ['active', 'inactive', 'expired'], true)) {
        set_flash('danger', 'Invalid offer status update.');
        redirect_to('admin-offers');
    }

    try {
        $stmt = $pdo->prepare('UPDATE offers SET status = :status WHERE id = :offer_id');
        $stmt->execute([':status' => $status, ':offer_id' => $offerId]);
        set_flash('success', 'Offer status updated.');
    } catch (Throwable $e) {
        set_flash('danger', 'Offer update failed: ' . $e->getMessage());
    }

    redirect_to('admin-offers');
}

function handle_admin_update_captain_status(): void
{
    require_role('admin', 'admin-login');

    $pdo = db();
    $captainId = (int) ($_POST['captain_id'] ?? 0);
    $status = post_text('account_status', 20);
    if (!$pdo || $captainId <= 0 || !in_array($status, ['pending', 'active', 'approved', 'inactive', 'blocked'], true)) {
        set_flash('danger', 'Invalid captain status update.');
        redirect_to('admin-captains');
    }

    try {
        ensure_captain_table_ready($pdo);
        $availability = $status === 'inactive' || $status === 'blocked' ? 'offline' : 'available';
        $stmt = $pdo->prepare('UPDATE captains SET account_status = :status, availability_status = :availability WHERE id = :captain_id');
        $stmt->execute([
            ':status' => $status,
            ':availability' => $availability,
            ':captain_id' => $captainId,
        ]);
        set_flash('success', 'Captain status updated.');
    } catch (Throwable $e) {
        set_flash('danger', 'Captain update failed: ' . $e->getMessage());
    }

    redirect_to('admin-captains');
}

function handle_admin_update_ride_status(): void
{
    require_role('admin', 'admin-login');

    $pdo = db();
    $rideId = (int) ($_POST['ride_id'] ?? 0);
    $status = post_text('status', 20);
    $allowed = ['pending', 'captain_selected', 'accepted', 'rejected', 'ongoing', 'completed', 'cancelled'];
    if (!$pdo || $rideId <= 0 || !in_array($status, $allowed, true)) {
        set_flash('danger', 'Invalid ride status update.');
        redirect_to('admin-rides');
    }

    try {
        ensure_ride_table_ready($pdo);
        $stmt = $pdo->prepare(
            'UPDATE rides
             SET status = :status,
                 accepted_at = CASE WHEN :accepted_status = "accepted" THEN COALESCE(accepted_at, NOW()) ELSE accepted_at END,
                 completed_at = CASE WHEN :completed_status = "completed" THEN COALESCE(completed_at, NOW()) ELSE completed_at END
             WHERE id = :ride_id'
        );
        $stmt->execute([
            ':status' => $status,
            ':accepted_status' => $status,
            ':completed_status' => $status,
            ':ride_id' => $rideId,
        ]);
        set_flash('success', 'Ride status updated.');
    } catch (Throwable $e) {
        set_flash('danger', 'Ride update failed: ' . $e->getMessage());
    }

    redirect_to('admin-rides');
}

function handle_post(): void
{
    global $page;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    // All state-changing forms must include the session CSRF token injected by inject_csrf_tokens().
    if (!verify_csrf_token()) {
        $params = $_GET;
        unset($params['page']);
        set_flash('danger', 'Security token expired. Please submit the form again.');
        redirect_to($page ?: 'role-selection', $params);
    }

    $action = post_text('action', 80);

    match ($action) {
        'user_register' => handle_user_register(),
        'user_login' => handle_user_login(),
        'captain_register' => handle_captain_register(),
        'captain_login' => handle_captain_login(),
        'admin_login' => handle_admin_login(),
        'social_demo_login' => handle_social_demo_login(),
        'verify_otp' => handle_otp_verify(),
        'book_ride' => handle_book_ride(),
        'select_captain' => handle_select_captain(),
        'captain_ride_action' => handle_captain_ride_action(),
        'send_ride_message' => handle_send_ride_message(),
        'cashfree_demo_payment' => handle_demo_cashfree_payment(),
        'hotel_booking' => handle_hotel_booking(),
        'train_booking' => handle_train_booking(),
        'bus_booking' => handle_bus_booking(),
        'restaurant_booking' => handle_restaurant_booking(),
        'ticket_booking' => handle_ticket_booking(),
        'plan_trip_start' => handle_plan_trip_start(),
        'post_new_trip' => handle_post_new_trip(),
        'plan_trip_choose_transport' => handle_plan_trip_choose_transport(),
        'plan_trip_select_option' => handle_plan_trip_select_option(),
        'plan_trip_deposit' => handle_plan_trip_deposit(),
        'plan_trip_feedback' => handle_plan_trip_feedback(),
        'group_tour_select_seats' => handle_group_tour_select_seats(),
        'group_tour_pay_advance' => handle_group_tour_pay_advance(),
        'group_tour_pay_remaining' => handle_group_tour_pay_remaining(),
        'group_tour_feedback' => handle_group_tour_feedback(),
        'feedback_submit' => handle_feedback_submit(),
        'admin_add_offer' => handle_admin_add_offer(),
        'admin_update_offer_status' => handle_admin_update_offer_status(),
        'admin_update_captain_status' => handle_admin_update_captain_status(),
        'admin_update_ride_status' => handle_admin_update_ride_status(),
        default => set_flash('warning', 'Unknown form action.'),
    };
}

function reset_login_session(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    set_flash('success', 'You have been logged out.');
    redirect_to('role-selection');
}

function protect_route(string $page): void
{
    // Protected pages redirect to the correct login screen when the active role is missing.
    $protectedRoutes = [
        'user-dashboard' => ['role' => 'user', 'login' => 'user-login'],
        'book-ride' => ['role' => 'user', 'login' => 'user-login'],
        'available-captains' => ['role' => 'user', 'login' => 'user-login'],
        'ride-confirm' => ['role' => 'user', 'login' => 'user-login'],
        'ride-tracking' => ['role' => 'user', 'login' => 'user-login'],
        'ride-success' => ['role' => 'user', 'login' => 'user-login'],
        'feedback' => ['role' => 'user', 'login' => 'user-login'],
        'feedback-success' => ['role' => 'user', 'login' => 'user-login'],
        'payment' => ['role' => 'user', 'login' => 'user-login'],
        'payment-success' => ['role' => 'user', 'login' => 'user-login'],
        'payment-failed' => ['role' => 'user', 'login' => 'user-login'],
        'hotel-search' => ['role' => 'user', 'login' => 'user-login'],
        'hotel-list' => ['role' => 'user', 'login' => 'user-login'],
        'hotel-book' => ['role' => 'user', 'login' => 'user-login'],
        'hotel-success' => ['role' => 'user', 'login' => 'user-login'],
        'train-search' => ['role' => 'user', 'login' => 'user-login'],
        'train-list' => ['role' => 'user', 'login' => 'user-login'],
        'train-book' => ['role' => 'user', 'login' => 'user-login'],
        'train-success' => ['role' => 'user', 'login' => 'user-login'],
        'bus-search' => ['role' => 'user', 'login' => 'user-login'],
        'bus-list' => ['role' => 'user', 'login' => 'user-login'],
        'bus-book' => ['role' => 'user', 'login' => 'user-login'],
        'bus-success' => ['role' => 'user', 'login' => 'user-login'],
        'restaurant-search' => ['role' => 'user', 'login' => 'user-login'],
        'restaurant-list' => ['role' => 'user', 'login' => 'user-login'],
        'restaurant-book' => ['role' => 'user', 'login' => 'user-login'],
        'restaurant-success' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-transport' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-options' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-detail' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-captain' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-arrival' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-accepted' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-deposit' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-guide' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-complete' => ['role' => 'user', 'login' => 'user-login'],
        'plan-trip-reminder' => ['role' => 'user', 'login' => 'user-login'],
        'group-tours' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-details' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-captain' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-seats' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-advance' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-confirmed' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-booking' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-itinerary' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-during' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-remaining' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-completed' => ['role' => 'user', 'login' => 'user-login'],
        'group-tour-more' => ['role' => 'user', 'login' => 'user-login'],
        'tour-ticket-search' => ['role' => 'user', 'login' => 'user-login'],
        'tour-ticket-results' => ['role' => 'user', 'login' => 'user-login'],
        'tour-ticket-book' => ['role' => 'user', 'login' => 'user-login'],
        'ticket-success' => ['role' => 'user', 'login' => 'user-login'],
        'rewards-offers' => ['role' => 'user', 'login' => 'user-login'],
        'apply-offer' => ['role' => 'user', 'login' => 'user-login'],
        'driver-chat' => ['role' => 'user', 'login' => 'user-login'],
        'my-bookings' => ['role' => 'user', 'login' => 'user-login'],
        'user-profile' => ['role' => 'user', 'login' => 'user-login'],
        'post-new-trip' => ['role' => 'captain', 'login' => 'captain-login'],
        'my-trips-posted' => ['role' => 'captain', 'login' => 'captain-login'],
        'saved-trips' => ['role' => 'captain', 'login' => 'captain-login'],
        'trip-messages' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-dashboard' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-ride-requests' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-trip-details' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-accept-trip' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-advance-payment' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-navigation' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-trip-progress' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-trip-earnings' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-passenger-details' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-trip-chat' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-trip-history' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-wallet' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-rewards' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-profile' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-earnings-analytics' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-current-trips' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-completed-trips' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-earnings' => ['role' => 'captain', 'login' => 'captain-login'],
        'captain-offers' => ['role' => 'captain', 'login' => 'captain-login'],
        'admin-dashboard' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-users' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-captains' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-rides' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-hotel-bookings' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-train-bookings' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-bus-bookings' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-restaurant-bookings' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-ticket-bookings' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-payments' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-offers' => ['role' => 'admin', 'login' => 'admin-login'],
        'admin-feedback' => ['role' => 'admin', 'login' => 'admin-login'],
    ];

    if (!isset($protectedRoutes[$page])) {
        return;
    }

    $route = $protectedRoutes[$page];
    if (!is_logged_in($route['role'])) {
        set_flash('warning', 'Please login with the correct role to continue.');
        redirect_to($route['login']);
    }
}

handle_post();

if ($page === 'logout') {
    reset_login_session();
}

protect_route($page);

function table_count(string $table): int
{
    $allowed = [
        'users',
        'captains',
        'rides',
        'hotel_bookings',
        'train_bookings',
        'bus_bookings',
        'restaurant_bookings',
        'ticket_bookings',
        'payments',
        'offers',
        'rewards',
        'feedback',
    ];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$table}");
        $stmt->execute();
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function user_table_count(string $table, int $userId): int
{
    $allowed = [
        'rides',
        'hotel_bookings',
        'train_bookings',
        'bus_bookings',
        'restaurant_bookings',
        'ticket_bookings',
        'payments',
        'rewards',
    ];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function captain_metric(string $metric, int $captainId): string
{
    $pdo = db();
    if (!$pdo) {
        return $metric === 'earnings' ? '0' : '0';
    }

    try {
        if ($metric === 'earnings') {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(fare), 0) AS total FROM rides WHERE captain_id = :captain_id AND status = "completed"');
        } elseif ($metric === 'requests') {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM rides WHERE (captain_id = :captain_id OR captain_id IS NULL) AND status IN ("pending", "captain_selected")');
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM rides WHERE captain_id = :captain_id');
        }

        $stmt->execute([':captain_id' => $captainId]);
        $row = $stmt->fetch();
        return (string) (int) ($row['total'] ?? 0);
    } catch (Throwable $e) {
        return '0';
    }
}

function app_header(string $title = 'TripNovaa', bool $showTopbar = true, string $screenClass = ''): void
{
    global $page;
    $auth = auth();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo h($title); ?> | TripNovaa</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <style>
            :root {
                --blue: #0b74e5;
                --blue-dark: #084f9e;
                --orange: #ff8a1d;
                --orange-soft: #fff1e4;
                --ink: #14213d;
                --muted: #667085;
                --line: #e6ebf2;
                --bg: #edf4fb;
                --card: #ffffff;
                --danger: #d92d20;
                --success: #027a48;
                --warning: #b54708;
                --shadow: 0 24px 70px rgba(8, 79, 158, 0.16);
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                background:
                    radial-gradient(circle at 20% 20%, rgba(255, 138, 29, 0.14), transparent 28%),
                    linear-gradient(145deg, #e7f1ff 0%, #f9fbff 46%, #fff3e8 100%);
                color: var(--ink);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            button,
            input,
            select {
                font: inherit;
            }

            .app-shell {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .phone {
                position: relative;
                width: min(100%, 430px);
                min-height: min(860px, calc(100vh - 48px));
                max-height: calc(100vh - 48px);
                overflow: hidden;
                display: flex;
                flex-direction: column;
                background: #fbfdff;
                border: 1px solid rgba(255, 255, 255, 0.7);
                border-radius: 34px;
                box-shadow: var(--shadow);
            }

            .screen {
                flex: 1;
                display: flex;
                flex-direction: column;
                min-height: 0;
            }

            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                padding: 18px 20px 10px;
            }

            .brand-lockup {
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
            }

            .logo-mark {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                border-radius: 15px;
                color: #ffffff;
                font-weight: 900;
                letter-spacing: 0;
                background: linear-gradient(145deg, var(--blue), var(--orange));
                box-shadow: 0 14px 28px rgba(11, 116, 229, 0.24);
            }

            .topbar h1 {
                margin: 0;
                font-size: 18px;
                line-height: 1.15;
            }

            .topbar p {
                margin: 2px 0 0;
                color: var(--muted);
                font-size: 12px;
            }

            .status-pill {
                max-width: 116px;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                border-radius: 999px;
                border: 1px solid var(--line);
                background: #ffffff;
                color: var(--muted);
                padding: 8px 10px;
                font-size: 12px;
            }

            .content {
                flex: 1;
                min-height: 0;
                overflow: auto;
                padding: 16px 20px 24px;
            }

            .with-bottom-nav .content {
                padding-bottom: 104px;
            }

            .splash-screen {
                position: relative;
                background:
                    linear-gradient(165deg, rgba(6, 74, 148, 0.98) 0%, rgba(11, 116, 229, 0.95) 46%, rgba(255, 138, 29, 0.96) 100%),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                color: #ffffff;
            }

            .splash-screen::before,
            .splash-screen::after {
                content: "";
                position: absolute;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.13);
            }

            .splash-screen::before {
                width: 190px;
                height: 190px;
                right: -58px;
                top: -44px;
            }

            .splash-screen::after {
                width: 150px;
                height: 150px;
                left: -42px;
                bottom: 72px;
            }

            .splash-screen .content {
                position: relative;
                z-index: 1;
                display: grid;
                place-items: center;
                padding: 34px;
                overflow: hidden;
            }

            .splash-card {
                text-align: center;
            }

            .splash-brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
            }

            .travel-mark {
                position: relative;
                width: 126px;
                height: 126px;
                display: grid;
                place-items: center;
                margin: 0 auto;
                border-radius: 36px;
                background: rgba(255, 255, 255, 0.15);
                border: 1px solid rgba(255, 255, 255, 0.32);
                box-shadow: 0 24px 55px rgba(0, 0, 0, 0.2);
                backdrop-filter: blur(12px);
            }

            .travel-mark::before {
                content: "";
                width: 58px;
                height: 58px;
                border-radius: 50% 50% 50% 8px;
                transform: rotate(-45deg);
                background: #ffffff;
                box-shadow: 0 16px 30px rgba(0, 0, 0, 0.12);
            }

            .pin-core {
                position: absolute;
                width: 28px;
                height: 28px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: linear-gradient(135deg, var(--blue), var(--orange));
                color: #ffffff;
                font-size: 12px;
                font-weight: 900;
            }

            .paper-plane {
                position: absolute;
                right: 10px;
                top: 11px;
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.22);
                color: #ffffff;
                font-size: 23px;
                transform: rotate(-12deg);
            }

            .splash-card h2 {
                margin: 0;
                font-size: 42px;
                line-height: 1;
                letter-spacing: 0;
            }

            .splash-card p {
                margin: 10px auto 24px;
                max-width: 285px;
                color: rgba(255, 255, 255, 0.88);
                line-height: 1.55;
            }

            .splash-tag {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 12px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.14);
                padding: 9px 13px;
                color: rgba(255, 255, 255, 0.94);
                font-size: 13px;
                font-weight: 800;
            }

            .loader {
                width: 172px;
                height: 6px;
                overflow: hidden;
                margin: 0 auto;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.25);
            }

            .loader span {
                display: block;
                width: 42%;
                height: 100%;
                border-radius: inherit;
                background: #ffffff;
                animation: load 1.25s infinite ease-in-out;
            }

            @keyframes load {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(250%); }
            }

            .hero-title {
                margin: 0 0 8px;
                font-size: 28px;
                line-height: 1.08;
                letter-spacing: 0;
            }

            .muted {
                color: var(--muted);
            }

            .lead {
                margin: 0 0 18px;
                color: var(--muted);
                line-height: 1.55;
                font-size: 15px;
            }

            .onboarding {
                min-height: 100%;
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .onboarding-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
            }

            .mini-brand {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: var(--blue);
                font-size: 14px;
                font-weight: 900;
            }

            .mini-brand span {
                width: 32px;
                height: 32px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                color: #ffffff;
                background: linear-gradient(135deg, var(--blue), var(--orange));
            }

            .skip-link {
                border-radius: 999px;
                background: #ffffff;
                border: 1px solid var(--line);
                color: var(--muted);
                padding: 9px 13px;
                font-size: 13px;
                font-weight: 900;
            }

            .slide-window {
                position: relative;
                min-height: 430px;
                overflow: hidden;
                border-radius: 32px;
                background: #dceafd;
                box-shadow: 0 22px 50px rgba(20, 33, 61, 0.13);
            }

            .slide {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: flex-end;
                padding: 24px;
                opacity: 0;
                pointer-events: none;
                transform: scale(1.025);
                transition: opacity 0.55s ease, transform 0.55s ease;
                background-size: cover;
                background-position: center;
            }

            .slide.active {
                opacity: 1;
                pointer-events: auto;
                transform: scale(1);
            }

            .slide::before {
                content: "";
                position: absolute;
                inset: 0;
                background:
                    linear-gradient(180deg, rgba(8, 31, 68, 0.02) 0%, rgba(8, 31, 68, 0.48) 46%, rgba(8, 31, 68, 0.88) 100%),
                    radial-gradient(circle at 78% 18%, rgba(255, 138, 29, 0.38), transparent 28%);
            }

            .slide-copy {
                position: relative;
                color: #ffffff;
            }

            .slide-chip {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 13px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.24);
                padding: 8px 11px;
                color: #ffffff;
                font-size: 12px;
                font-weight: 900;
            }

            .slide-copy h2 {
                margin: 0 0 8px;
                font-size: 34px;
                line-height: 1.04;
            }

            .slide-copy p {
                margin: 0;
                color: rgba(255, 255, 255, 0.82);
                line-height: 1.5;
            }

            .dots {
                display: flex;
                gap: 8px;
                justify-content: center;
            }

            .dot {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                border: 0;
                background: #c7d7eb;
                transition: width 0.25s ease, background 0.25s ease;
            }

            .dot.active {
                width: 26px;
                background: var(--blue);
            }

            .onboarding-actions {
                width: 100%;
                margin-top: auto;
                padding: 0 0 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                box-sizing: border-box;
            }

            .login-strip {
                color: #9ca3af;
                font-size: 12px;
                text-align: center;
            }

            .login-strip .tiny-link {
                color: #0b56d9;
                font-weight: 700;
                text-decoration: none;
            }

            .login-strip .tiny-link:hover {
                text-decoration: underline;
            }

            .card {
                border: 1px solid var(--line);
                border-radius: 22px;
                background: var(--card);
                box-shadow: 0 16px 40px rgba(20, 33, 61, 0.07);
                padding: 18px;
            }

            .card + .card {
                margin-top: 14px;
            }

            .role-grid,
            .tile-grid,
            .stat-grid {
                display: grid;
                gap: 12px;
            }

            .role-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                min-height: 92px;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 16px;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .role-card:hover {
                transform: translateY(-2px);
                border-color: rgba(11, 116, 229, 0.35);
                box-shadow: 0 16px 35px rgba(11, 116, 229, 0.12);
            }

            .role-card-stack {
                align-items: stretch;
                flex-direction: column;
            }

            .role-main {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
            }

            .role-card strong {
                display: block;
                margin-bottom: 4px;
                font-size: 16px;
            }

            .role-card span {
                color: var(--muted);
                font-size: 13px;
                line-height: 1.35;
            }

            .circle-icon {
                width: 44px;
                height: 44px;
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                border-radius: 16px;
                background: var(--orange-soft);
                color: var(--orange);
                font-weight: 900;
            }

            .role-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 9px;
            }

            .role-actions.single {
                grid-template-columns: 1fr;
            }

            .mini-action {
                min-height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 15px;
                border: 1px solid var(--line);
                background: #ffffff;
                color: var(--blue);
                font-size: 13px;
                font-weight: 900;
            }

            .mini-action.primary {
                border-color: transparent;
                background: linear-gradient(135deg, var(--blue), #0861c4);
                color: #ffffff;
                box-shadow: 0 12px 24px rgba(11, 116, 229, 0.18);
            }

            .form {
                display: grid;
                gap: 12px;
            }

            .field label {
                display: block;
                margin: 0 0 7px;
                color: #344054;
                font-size: 13px;
                font-weight: 700;
            }

            .field input,
            .field select,
            .field textarea {
                width: 100%;
                min-height: 48px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #ffffff;
                color: var(--ink);
                outline: none;
                padding: 0 14px;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .field textarea {
                min-height: 92px;
                padding: 12px 14px;
                resize: vertical;
            }

            .field input:focus,
            .field select:focus,
            .field textarea:focus {
                border-color: rgba(11, 116, 229, 0.55);
                box-shadow: 0 0 0 4px rgba(11, 116, 229, 0.1);
            }

            .row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .btn {
                width: 100%;
                min-height: 50px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border: 0;
                border-radius: 17px;
                cursor: pointer;
                color: #ffffff;
                font-weight: 800;
                background: linear-gradient(135deg, var(--blue), #0861c4);
                box-shadow: 0 14px 28px rgba(11, 116, 229, 0.22);
            }

            .btn:hover {
                filter: brightness(1.03);
            }

            .btn-orange {
                background: linear-gradient(135deg, var(--orange), #ff6a00);
                box-shadow: 0 14px 28px rgba(255, 138, 29, 0.22);
            }

            .btn-light {
                border: 1px solid var(--line);
                color: var(--ink);
                background: #ffffff;
                box-shadow: none;
            }

            .btn-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-top: auto;
            }

            .tiny-link {
                color: var(--blue);
                font-size: 13px;
                font-weight: 800;
            }

            .form-note {
                margin: 4px 0 0;
                color: var(--muted);
                text-align: center;
                font-size: 13px;
                line-height: 1.5;
            }

            .auth-hero {
                position: relative;
                overflow: hidden;
                margin-bottom: 14px;
                border-radius: 26px;
                background:
                    linear-gradient(135deg, rgba(11, 116, 229, 0.94), rgba(255, 138, 29, 0.9)),
                    url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80') center/cover;
                color: #ffffff;
                padding: 22px;
                box-shadow: 0 18px 42px rgba(11, 116, 229, 0.18);
            }

            .auth-hero::after {
                content: "";
                position: absolute;
                width: 132px;
                height: 132px;
                right: -34px;
                top: -42px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.18);
            }

            .auth-hero h2 {
                position: relative;
                margin: 0 0 8px;
                font-size: 26px;
                line-height: 1.08;
            }

            .auth-hero p {
                position: relative;
                max-width: 290px;
                margin: 0;
                color: rgba(255, 255, 255, 0.86);
                font-size: 14px;
                line-height: 1.5;
            }

            .auth-badge {
                position: relative;
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 14px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.16);
                padding: 8px 11px;
                font-size: 12px;
                font-weight: 900;
            }

            .auth-card {
                border-radius: 24px;
                padding: 18px;
            }

            .auth-switch {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-top: 12px;
            }

            .otp-card {
                text-align: center;
            }

            .otp-icon {
                width: 74px;
                height: 74px;
                display: grid;
                place-items: center;
                margin: 0 auto 13px;
                border-radius: 24px;
                background: linear-gradient(135deg, #eaf3ff, var(--orange-soft));
                color: var(--blue);
                font-size: 28px;
            }

            .otp-input {
                text-align: center;
                font-size: 24px;
                font-weight: 900;
                letter-spacing: 0.24em;
            }

            .splash-screen {
                background: #ffffff;
                color: var(--ink);
            }

            .splash-screen::before,
            .splash-screen::after {
                display: none;
            }

            .splash-screen .content {
                padding: 22px 22px 18px;
                background:
                    radial-gradient(circle at 86% 17%, rgba(11, 116, 229, 0.07), transparent 18%),
                    linear-gradient(180deg, #ffffff 0%, #ffffff 62%, #eef6ff 100%);
            }

            .splash-launch {
                position: relative;
                width: 100%;
                min-height: 100%;
                display: grid;
                align-content: center;
                justify-items: center;
                gap: 10px;
                overflow: hidden;
                padding-bottom: 190px;
                text-align: center;
            }

            .splash-launch .welcome-logo {
                margin-top: 34px;
                transform: scale(1.24);
                transform-origin: center;
            }

            .splash-launch .welcome-logo strong {
                font-size: 31px;
            }

            .splash-launch p {
                position: relative;
                z-index: 2;
                margin: 12px 0 0;
                color: #7a889b;
                font-size: 11px;
                font-weight: 800;
            }

            .splash-sky,
            .splash-landscape {
                position: absolute;
                inset: 0;
                pointer-events: none;
            }

            .splash-cloud {
                position: absolute;
                width: 38px;
                height: 15px;
                border-radius: 999px;
                background: rgba(216, 232, 252, 0.75);
                box-shadow:
                    12px -7px 0 rgba(216, 232, 252, 0.62),
                    24px 0 0 rgba(216, 232, 252, 0.48);
            }

            .splash-cloud.one {
                right: 52px;
                top: 84px;
            }

            .splash-cloud.two {
                left: 48px;
                top: 156px;
                transform: scale(0.75);
            }

            .splash-route {
                position: absolute;
                left: 40px;
                top: 110px;
                width: 138px;
                height: 88px;
                border-top: 2px dashed rgba(151, 171, 199, 0.35);
                border-radius: 50%;
                transform: rotate(62deg);
            }

            .splash-landscape {
                top: auto;
                height: 230px;
                overflow: hidden;
            }

            .splash-mountain {
                position: absolute;
                bottom: 36px;
                width: 260px;
                height: 132px;
                clip-path: polygon(0 100%, 22% 54%, 34% 72%, 52% 26%, 70% 70%, 84% 44%, 100% 100%);
                background: linear-gradient(180deg, #e9f2ff, #cfe1fb);
            }

            .splash-mountain.back {
                left: -54px;
                opacity: 0.72;
            }

            .splash-mountain.front {
                right: -58px;
                bottom: 24px;
                transform: scale(1.08);
                opacity: 0.92;
            }

            .splash-city {
                position: absolute;
                left: 42%;
                bottom: 42px;
                width: 130px;
                height: 82px;
                opacity: 0.18;
                background:
                    linear-gradient(90deg, transparent 0 6px, #0b74e5 6px 16px, transparent 16px 24px, #0b74e5 24px 42px, transparent 42px 52px, #0b74e5 52px 68px, transparent 68px 76px, #0b74e5 76px 98px, transparent 98px),
                    linear-gradient(180deg, transparent 0 14px, #0b74e5 14px);
                clip-path: polygon(0 100%, 0 42%, 12% 42%, 12% 20%, 28% 20%, 28% 58%, 45% 58%, 45% 8%, 61% 8%, 61% 48%, 78% 48%, 78% 24%, 100% 24%, 100% 100%);
            }

            .splash-bus,
            .splash-car {
                position: absolute;
                bottom: 42px;
                border-radius: 12px 12px 7px 7px;
                background: rgba(11, 116, 229, 0.24);
            }

            .splash-bus {
                left: 42%;
                width: 70px;
                height: 30px;
            }

            .splash-car {
                left: 86px;
                width: 50px;
                height: 20px;
            }

            .splash-bus::after,
            .splash-car::after {
                content: "";
                position: absolute;
                left: 9px;
                right: 9px;
                bottom: -5px;
                height: 8px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 12px 50%, #8aa7cf 0 4px, transparent 5px),
                    radial-gradient(circle at calc(100% - 12px) 50%, #8aa7cf 0 4px, transparent 5px);
            }

            .splash-location-pin {
                position: absolute;
                right: 70px;
                bottom: 54px;
                width: 24px;
                height: 24px;
                border-radius: 50% 50% 50% 6px;
                background: rgba(11, 116, 229, 0.26);
                transform: rotate(-45deg);
            }

            .splash-launch .loader {
                position: absolute;
                left: 50%;
                bottom: 26px;
                width: 128px;
                height: 5px;
                transform: translateX(-50%);
                background: #e4edf8;
            }

            .splash-launch .loader span {
                background: linear-gradient(90deg, var(--blue), var(--orange));
            }

            .onboarding-screen .content {
                padding: 18px 22px 0;
                background: #ffffff;
                overflow: hidden;
            }

            .onboarding-story {
                min-height: 100%;
                gap: 12px;
            }

            .onboarding-story .onboarding-head {
                min-height: 24px;
            }

            .onboarding-story .skip-link {
                border: 0;
                background: transparent;
                color: #1436c8;
                padding: 0;
                box-shadow: none;
                font-size: 12px;
            }

            .onboarding-title {
                text-align: center;
            }

            .onboarding-title h2 {
                margin: 0;
                color: #14213d;
                font-size: 28px;
                line-height: 1.04;
            }

            .onboarding-title h2 span {
                color: #ff7a00;
            }

            .onboarding-title p {
                max-width: 280px;
                margin: 10px auto 0;
                color: #667085;
                font-size: 12px;
                line-height: 1.45;
                font-weight: 700;
            }

            .journey-carousel {
                position: relative;
                height: 350px;
                margin: 0 -6px;
            }

            .journey-slide {
                position: absolute;
                left: 50%;
                top: 22px;
                width: 58%;
                height: 298px;
                overflow: hidden;
                display: grid;
                align-content: end;
                justify-items: center;
                border: 1px solid rgba(255, 255, 255, 0.72);
                border-radius: 18px;
                padding: 18px 14px;
                color: #ffffff;
                text-align: center;
                background-size: cover;
                background-position: center;
                box-shadow: 0 20px 36px rgba(20, 33, 61, 0.2);
                opacity: 0;
                transform: translateX(-50%) scale(0.82);
                transition: transform 0.5s ease, opacity 0.5s ease, filter 0.5s ease;
            }

            .journey-slide::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, transparent 38%, rgba(7, 21, 46, 0.78) 100%);
            }

            .journey-slide span,
            .journey-slide p {
                position: relative;
                z-index: 1;
            }

            .journey-slide span {
                font-size: 14px;
                font-weight: 900;
            }

            .journey-slide p {
                margin: 6px 0 0;
                max-width: 145px;
                color: rgba(255, 255, 255, 0.86);
                font-size: 10px;
                line-height: 1.35;
                font-weight: 700;
            }

            .journey-slide.active {
                z-index: 3;
                opacity: 1;
                filter: saturate(1.05);
                transform: translateX(-50%) scale(1);
            }

            .journey-slide.previous {
                z-index: 2;
                opacity: 0.78;
                filter: saturate(0.9);
                transform: translateX(-116%) rotate(-8deg) scale(0.86);
            }

            .journey-slide.next {
                z-index: 2;
                opacity: 0.78;
                filter: saturate(0.9);
                transform: translateX(16%) rotate(8deg) scale(0.86);
            }

            .journey-dots {
                margin-top: -10px;
            }

            .journey-dot {
                width: 7px;
                height: 7px;
                border: 0;
                border-radius: 999px;
                background: #d6e0ee;
                padding: 0;
                cursor: pointer;
            }

            .journey-dot.active {
                width: 22px;
                background: #1436c8;
            }

            .onboarding-primary {
                width: 100%;
                height: 54px;
                min-height: 54px;
                position: relative;
                justify-content: center;
                border-radius: 16px;
                background: #0757d8;
                color: #ffffff;
                font-size: 15px;
                font-weight: 700;
                text-decoration: none;
                box-shadow: 0 12px 24px rgba(7, 87, 216, 0.25);
            }

            .onboarding-primary:hover {
                background: #064cc0;
            }

            .onboarding-primary span {
                margin-left: 0;
            }

            .onboarding-primary .start-arrow {
                position: absolute;
                right: 22px;
                font-size: 21px;
                font-weight: 500;
            }

            .auth-mobile-screen .content {
                position: relative;
                padding: 0;
                overflow-y: auto;
                background:
                    linear-gradient(180deg, rgba(223, 241, 255, 0.96) 0, rgba(247, 251, 255, 0.98) 196px, #ffffff 196px);
                scrollbar-width: none;
            }

            .auth-mobile-screen .content::-webkit-scrollbar {
                width: 0;
            }

            .auth-mobile-page {
                position: relative;
                min-height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                overflow: hidden;
                padding: 16px 18px 24px;
            }

            .auth-mobile-page .auth-scenery {
                position: absolute;
                inset: 0 0 auto;
                height: 230px;
                overflow: hidden;
                pointer-events: none;
            }

            .auth-scenery .scene-cloud-one {
                left: 30px;
                top: 48px;
            }

            .auth-scenery .scene-cloud-two {
                right: 34px;
                top: 72px;
            }

            .auth-scenery .scene-building {
                bottom: auto;
                top: 116px;
            }

            .auth-scenery .scene-car {
                bottom: auto;
                top: 154px;
            }

            .auth-back-link {
                position: absolute;
                left: 18px;
                top: 18px;
                z-index: 4;
                width: 34px;
                height: 34px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                border: 1px solid #e2ecf7;
                box-shadow: 0 10px 22px rgba(20, 33, 61, 0.08);
            }

            .auth-mobile-page .welcome-logo {
                z-index: 2;
                margin-top: 16px;
                margin-bottom: 4px;
            }

            .auth-mobile-page .welcome-pin {
                width: 56px;
                height: 70px;
            }

            .auth-mobile-page .welcome-logo strong {
                font-size: 27px;
            }

            .auth-mobile-copy {
                position: relative;
                z-index: 2;
                width: 100%;
                display: grid;
                justify-items: center;
                gap: 4px;
                text-align: center;
            }

            .auth-mobile-copy h2 {
                margin: 0;
                color: #14213d;
                font-size: 24px;
                line-height: 1.08;
            }

            .auth-mobile-copy p {
                max-width: 270px;
                margin: 0;
                color: #667085;
                font-size: 12px;
                line-height: 1.45;
                font-weight: 700;
            }

            .auth-person-visual {
                width: 116px;
                height: 104px;
                margin-top: 2px;
                border-radius: 22px;
                background:
                    radial-gradient(circle at 80% 15%, rgba(255, 138, 29, 0.18), transparent 28%),
                    linear-gradient(145deg, #edf7ff, #ffffff);
                box-shadow: inset 0 0 0 1px rgba(226, 236, 247, 0.9);
            }

            .auth-mobile-form {
                position: relative;
                z-index: 2;
                width: 100%;
                display: grid;
                gap: 10px;
                margin-top: 12px;
                border: 1px solid rgba(226, 236, 247, 0.94);
                border-radius: 22px;
                background: rgba(255, 255, 255, 0.97);
                padding: 14px;
                box-shadow: 0 18px 40px rgba(20, 33, 61, 0.08);
            }

            .auth-mobile-form .field {
                position: relative;
                display: grid;
                gap: 5px;
            }

            .auth-mobile-form .field > label {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0 0 0 0);
                white-space: nowrap;
            }

            .auth-mobile-form .input-shell {
                min-height: 48px;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.045);
            }

            .auth-mobile-form input::placeholder {
                color: #7d8da3;
                font-weight: 800;
            }

            .input-icon-user::before,
            .input-icon-user::after,
            .input-icon-mail::before,
            .input-icon-mail::after,
            .input-icon-lock::before,
            .input-icon-lock::after,
            .input-eye::before {
                content: "";
                position: absolute;
            }

            .input-icon-user::before {
                left: 7px;
                top: 5px;
                width: 8px;
                height: 8px;
                border: 2px solid #667085;
                border-radius: 999px;
            }

            .input-icon-user::after {
                left: 5px;
                top: 15px;
                width: 12px;
                height: 7px;
                border: 2px solid #667085;
                border-bottom: 0;
                border-radius: 10px 10px 0 0;
            }

            .input-icon-mail::before {
                left: 4px;
                top: 6px;
                width: 14px;
                height: 10px;
                border: 2px solid #667085;
                border-radius: 3px;
            }

            .input-icon-mail::after {
                left: 6px;
                top: 8px;
                width: 11px;
                height: 8px;
                border-left: 2px solid #667085;
                border-bottom: 2px solid #667085;
                transform: rotate(-45deg);
                transform-origin: center;
            }

            .input-icon-lock::before {
                left: 5px;
                top: 10px;
                width: 13px;
                height: 10px;
                border: 2px solid #667085;
                border-radius: 3px;
            }

            .input-icon-lock::after {
                left: 8px;
                top: 4px;
                width: 7px;
                height: 8px;
                border: 2px solid #667085;
                border-bottom: 0;
                border-radius: 8px 8px 0 0;
            }

            .input-eye {
                position: relative;
                width: 22px;
                height: 22px;
                display: inline-block;
                border-radius: 999px;
            }

            .input-eye::before {
                left: 3px;
                top: 7px;
                width: 16px;
                height: 8px;
                border: 2px solid #98a2b3;
                border-radius: 50%;
            }

            .forgot-link {
                justify-self: end;
                color: #1436c8;
                font-size: 10px;
                font-weight: 900;
            }

            .auth-login-btn {
                min-height: 50px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                border-radius: 13px;
                font-weight: 900;
            }

            .user-auth-btn {
                background: linear-gradient(135deg, #1436c8, #0b74e5);
            }

            .captain-auth-btn {
                background: linear-gradient(135deg, #ff8a1d, #f05a16);
            }

            .shield-mark {
                position: relative;
                width: 18px;
                height: 20px;
                display: inline-block;
                border-radius: 9px 9px 12px 12px;
                background: rgba(255, 255, 255, 0.24);
            }

            .password-rules {
                display: grid;
                gap: 5px;
                color: #667085;
                font-size: 10px;
                font-weight: 800;
            }

            .password-rules span {
                position: relative;
                padding-left: 16px;
            }

            .password-rules span::before {
                content: "";
                position: absolute;
                left: 0;
                top: 3px;
                width: 9px;
                height: 5px;
                border-left: 2px solid #0b74e5;
                border-bottom: 2px solid #0b74e5;
                transform: rotate(-45deg);
            }

            .auth-demo-note {
                margin: 0;
                color: #667085;
                text-align: center;
                font-size: 10px;
            }

            .auth-mobile-form .login-strip {
                font-size: 11px;
            }

            .auth-social-options {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
            }

            .auth-social-options span {
                min-height: 36px;
                display: grid;
                place-items: center;
                border: 1px solid #e6ebf2;
                border-radius: 11px;
                background: #ffffff;
                color: #344054;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 8px 16px rgba(20, 33, 61, 0.05);
            }

            .captain-login-screen .auth-mobile-page {
                padding-top: 10px;
            }

            .captain-login-screen .auth-person-visual {
                display: none;
            }

            .captain-login-screen .auth-mobile-copy h2 {
                font-size: 25px;
            }

            .captain-login-screen .auth-mobile-form {
                margin-top: 10px;
            }

            .otp-mobile-page {
                justify-content: center;
                gap: 14px;
            }

            .otp-lock-visual {
                position: relative;
                width: 74px;
                height: 74px;
                display: grid;
                place-items: center;
                border-radius: 24px;
                background: linear-gradient(135deg, #eaf3ff, #fff1e4);
                box-shadow: inset 0 0 0 1px rgba(226, 236, 247, 0.9);
            }

            .otp-lock-visual::before {
                content: "";
                width: 28px;
                height: 24px;
                border: 3px solid #1436c8;
                border-radius: 7px;
                transform: translateY(6px);
            }

            .otp-lock-visual::after {
                content: "";
                position: absolute;
                top: 18px;
                width: 24px;
                height: 20px;
                border: 3px solid #1436c8;
                border-bottom: 0;
                border-radius: 16px 16px 0 0;
            }

            .otp-demo-strip {
                position: relative;
                z-index: 2;
                width: 100%;
                display: flex;
                justify-content: space-between;
                gap: 10px;
                border: 1px solid #d8e9ff;
                border-radius: 16px;
                background: #eef7ff;
                padding: 12px;
                color: #1436c8;
                font-size: 11px;
                font-weight: 900;
            }

            .otp-mobile-form {
                margin-top: 0;
            }

            .otp-mobile-form .otp-input {
                width: 100%;
                min-height: 56px;
                border: 1px solid #d8e9ff;
                border-radius: 16px;
                background: #ffffff;
                color: #14213d;
                letter-spacing: 0.28em;
            }

            .alert {
                margin: 0 0 12px;
                border-radius: 16px;
                padding: 12px 14px;
                font-size: 13px;
                line-height: 1.45;
                border: 1px solid transparent;
            }

            .alert-success {
                color: var(--success);
                background: #ecfdf3;
                border-color: #abefc6;
            }

            .alert-danger {
                color: var(--danger);
                background: #fef3f2;
                border-color: #fecdca;
            }

            .alert-warning {
                color: var(--warning);
                background: #fffaeb;
                border-color: #fedf89;
            }

            .alert-info {
                color: var(--blue-dark);
                background: #eff8ff;
                border-color: #b2ddff;
            }

            .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stat-card {
                display: block;
                min-height: 106px;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: #ffffff;
                padding: 15px;
                text-decoration: none;
            }

            .stat-card strong {
                display: block;
                margin-bottom: 6px;
                color: var(--blue);
                font-size: 26px;
                line-height: 1;
            }

            .stat-card span {
                color: var(--muted);
                font-size: 13px;
                line-height: 1.35;
            }

            .tile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tile {
                min-height: 118px;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 15px;
            }

            .tile small {
                display: block;
                margin-bottom: 10px;
                color: var(--orange);
                font-weight: 900;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.05em;
            }

            .tile strong {
                display: block;
                margin-bottom: 7px;
                font-size: 15px;
            }

            .tile p {
                margin: 0;
                color: var(--muted);
                font-size: 12px;
                line-height: 1.4;
            }

            .section-title {
                margin: 22px 0 10px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .section-title h2 {
                margin: 0;
                font-size: 18px;
            }

            .placeholder-list {
                display: grid;
                gap: 10px;
            }

            .placeholder-row {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                padding: 13px;
            }

            .placeholder-row strong {
                display: block;
                margin-bottom: 4px;
                font-size: 14px;
            }

            .placeholder-row span {
                color: var(--muted);
                font-size: 12px;
            }

            .badge {
                align-self: flex-start;
                white-space: nowrap;
                border-radius: 999px;
                background: var(--orange-soft);
                color: var(--orange);
                padding: 7px 9px;
                font-size: 11px;
                font-weight: 900;
            }

            .bottom-nav {
                position: absolute;
                left: 18px;
                right: 18px;
                bottom: 16px;
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 6px;
                border: 1px solid rgba(230, 235, 242, 0.88);
                border-radius: 24px;
                background: rgba(255, 255, 255, 0.9);
                padding: 8px;
                box-shadow: 0 20px 45px rgba(20, 33, 61, 0.12);
                backdrop-filter: blur(16px);
            }

            .bottom-nav a {
                min-height: 52px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 4px;
                border-radius: 17px;
                color: var(--muted);
                font-size: 11px;
                font-weight: 800;
            }

            .bottom-nav a.active {
                background: #eaf3ff;
                color: var(--blue);
            }

            .nav-symbol {
                width: 18px;
                height: 18px;
                display: grid;
                place-items: center;
                border-radius: 7px;
                background: currentColor;
                color: #ffffff;
                font-size: 10px;
                line-height: 1;
            }

            .demo-box {
                border-radius: 18px;
                background: linear-gradient(135deg, #eaf3ff, #fff4e8);
                padding: 14px;
                color: #344054;
                font-size: 13px;
                line-height: 1.5;
            }

            .dashboard-hero {
                position: relative;
                overflow: hidden;
                border-radius: 28px;
                background:
                    linear-gradient(135deg, rgba(8, 79, 158, 0.94), rgba(11, 116, 229, 0.78) 48%, rgba(255, 138, 29, 0.92)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                color: #ffffff;
                padding: 22px;
                box-shadow: 0 20px 44px rgba(11, 116, 229, 0.18);
            }

            .dashboard-hero::after {
                content: "";
                position: absolute;
                width: 154px;
                height: 154px;
                right: -48px;
                top: -52px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.17);
            }

            .dashboard-hero > * {
                position: relative;
                z-index: 1;
            }

            .dashboard-kicker {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 14px;
                border: 1px solid rgba(255, 255, 255, 0.24);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.16);
                padding: 8px 11px;
                font-size: 12px;
                font-weight: 900;
            }

            .dashboard-hero h2 {
                margin: 0 0 8px;
                font-size: 28px;
                line-height: 1.08;
            }

            .dashboard-hero p {
                max-width: 292px;
                margin: 0;
                color: rgba(255, 255, 255, 0.86);
                font-size: 14px;
                line-height: 1.5;
            }

            .search-panel {
                margin-top: -18px;
                position: relative;
                z-index: 2;
                border: 1px solid rgba(230, 235, 242, 0.9);
                border-radius: 22px;
                background: rgba(255, 255, 255, 0.94);
                padding: 12px;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.1);
                backdrop-filter: blur(14px);
            }

            .search-field {
                display: flex;
                align-items: center;
                gap: 10px;
                min-height: 50px;
                border-radius: 17px;
                background: #edf4ff;
                padding: 0 13px;
            }

            .search-field span {
                color: var(--blue);
                font-size: 18px;
            }

            .search-field input {
                width: 100%;
                border: 0;
                outline: none;
                background: transparent;
                color: var(--ink);
                font-weight: 700;
            }

            .quick-chips {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                margin-top: 10px;
                padding-bottom: 2px;
            }

            .quick-chip {
                flex: 0 0 auto;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: linear-gradient(135deg, #ffffff, #f7fbff);
                color: var(--blue-dark);
                padding: 9px 12px;
                font-size: 12px;
                font-weight: 900;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.05);
            }

            .admin-link-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin: 12px 0 18px;
            }

            .admin-link {
                --admin-accent: #0b74e5;
                --admin-soft: #eaf3ff;
                min-width: 0;
                min-height: 70px;
                display: grid;
                grid-template-columns: 38px minmax(0, 1fr) 16px;
                align-items: center;
                gap: 9px;
                border: 1px solid rgba(214, 224, 238, 0.95);
                border-radius: 18px;
                background:
                    radial-gradient(circle at 92% 8%, var(--admin-soft), transparent 38%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
                padding: 10px;
                color: var(--ink);
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.07);
                transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
            }

            .admin-link:hover,
            .admin-link.active {
                border-color: color-mix(in srgb, var(--admin-accent), white 56%);
                box-shadow: 0 16px 30px rgba(11, 116, 229, 0.13);
                transform: translateY(-2px);
            }

            .admin-link.active {
                background:
                    radial-gradient(circle at 92% 8%, rgba(255, 255, 255, 0.22), transparent 38%),
                    linear-gradient(135deg, var(--admin-accent), #084f9e);
                color: #ffffff;
            }

            .admin-link-icon {
                width: 38px;
                height: 38px;
                display: grid;
                place-items: center;
                border-radius: 14px;
                background: var(--admin-soft);
                color: var(--admin-accent);
                font-size: 11px;
                font-weight: 900;
                letter-spacing: 0;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72);
            }

            .admin-link.active .admin-link-icon {
                background: rgba(255, 255, 255, 0.2);
                color: #ffffff;
            }

            .admin-link-copy {
                min-width: 0;
                display: grid;
                gap: 3px;
            }

            .admin-link-copy strong,
            .admin-link-copy small {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .admin-link-copy strong {
                font-size: 12px;
                line-height: 1.1;
            }

            .admin-link-copy small {
                color: var(--muted);
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .admin-link.active .admin-link-copy small {
                color: rgba(255, 255, 255, 0.78);
            }

            .admin-link-arrow {
                color: var(--admin-accent);
                font-size: 17px;
                font-weight: 900;
                text-align: right;
            }

            .admin-link.active .admin-link-arrow {
                color: #ffffff;
            }

            .admin-link-blue { --admin-accent: #0b74e5; --admin-soft: #eaf3ff; }
            .admin-link-orange { --admin-accent: #f97316; --admin-soft: #fff1e4; }
            .admin-link-green { --admin-accent: #16a34a; --admin-soft: #ecfdf3; }
            .admin-link-cyan { --admin-accent: #0891b2; --admin-soft: #e6f9fd; }
            .admin-link-violet { --admin-accent: #7c3aed; --admin-soft: #f2edff; }
            .admin-link-slate { --admin-accent: #475467; --admin-soft: #f2f4f7; }

            .service-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 13px;
            }

            .service-card {
                --accent: #0b74e5;
                --accent-soft: #eaf3ff;
                position: relative;
                min-height: 160px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                border: 1px solid rgba(230, 235, 242, 0.9);
                border-radius: 24px;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.9)),
                    linear-gradient(135deg, var(--accent-soft), #ffffff);
                padding: 16px;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.08);
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            }

            .service-card::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 5px;
                background: linear-gradient(90deg, var(--accent), rgba(255, 138, 29, 0.75));
            }

            .service-card::after {
                content: "";
                position: absolute;
                width: 92px;
                height: 92px;
                right: -38px;
                top: -38px;
                border-radius: 999px;
                background: var(--accent-soft);
                opacity: 0.9;
            }

            .service-card:hover {
                transform: translateY(-2px);
                border-color: color-mix(in srgb, var(--accent), white 68%);
                box-shadow: 0 20px 42px rgba(11, 116, 229, 0.13);
            }

            .service-icon {
                position: relative;
                z-index: 1;
                width: 50px;
                height: 50px;
                display: grid;
                place-items: center;
                border-radius: 19px;
                background: var(--accent-soft);
                color: var(--accent);
                font-size: 25px;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.64);
            }

            .service-copy {
                position: relative;
                z-index: 1;
            }

            .service-card > span:not(.service-icon) {
                position: relative;
                z-index: 1;
            }

            .service-tag {
                display: inline-flex;
                align-items: center;
                margin-bottom: 7px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.72);
                color: var(--accent);
                padding: 5px 8px;
                font-size: 10px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .service-card strong {
                display: block;
                margin: 0 0 6px;
                color: var(--ink);
                font-size: 16px;
                line-height: 1.2;
            }

            .service-card span {
                color: var(--muted);
                font-size: 12px;
                line-height: 1.35;
            }

            .service-ride { --accent: #0b74e5; --accent-soft: #eaf3ff; }
            .service-hotel { --accent: #f97316; --accent-soft: #fff1e4; }
            .service-train { --accent: #0891b2; --accent-soft: #e6f9fd; }
            .service-bus { --accent: #2563eb; --accent-soft: #eaf0ff; }
            .service-restaurant { --accent: #dc2626; --accent-soft: #fff1f2; }
            .service-ticket { --accent: #7c3aed; --accent-soft: #f2edff; }
            .service-rewards { --accent: #16a34a; --accent-soft: #ecfdf3; }
            .service-bookings { --accent: #475467; --accent-soft: #f2f4f7; }

            .logout-card {
                border-color: #fedf89;
                --accent: #f97316;
                --accent-soft: #fff7ed;
                background: linear-gradient(135deg, #ffffff, #fff7ed);
            }

            .mini-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 9px;
                margin-top: 14px;
            }

            .mini-metric {
                border: 1px solid rgba(255, 255, 255, 0.24);
                border-radius: 17px;
                background: rgba(255, 255, 255, 0.16);
                padding: 10px;
            }

            .mini-metric strong {
                display: block;
                color: #ffffff;
                font-size: 18px;
                line-height: 1;
            }

            .mini-metric span {
                color: rgba(255, 255, 255, 0.78);
                font-size: 11px;
            }

            .profile-strip {
                display: flex;
                align-items: center;
                gap: 13px;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 14px;
            }

            .avatar {
                width: 48px;
                height: 48px;
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                border-radius: 18px;
                background: linear-gradient(135deg, var(--blue), var(--orange));
                color: #ffffff;
                font-weight: 900;
            }

            .profile-strip strong {
                display: block;
                margin-bottom: 3px;
            }

            .profile-strip span {
                color: var(--muted);
                font-size: 12px;
            }

            .module-page-card {
                min-height: 260px;
                display: grid;
                align-content: center;
                gap: 14px;
                text-align: center;
            }

            .module-page-icon {
                width: 78px;
                height: 78px;
                display: grid;
                place-items: center;
                margin: 0 auto;
                border-radius: 26px;
                background: linear-gradient(135deg, #eaf3ff, #fff1e4);
                font-size: 34px;
            }

            .ride-type-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .location-picker {
                display: grid;
                gap: 12px;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: linear-gradient(135deg, #ffffff, #f2f7ff);
                padding: 14px;
            }

            .location-row {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 11px;
                align-items: center;
            }

            .location-pin {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 13px;
                background: #eaf3ff;
                color: var(--blue);
                font-size: 16px;
            }

            .location-pin.drop {
                background: var(--orange-soft);
                color: var(--orange);
            }

            .location-connector {
                width: 2px;
                height: 20px;
                margin-left: 16px;
                background: linear-gradient(var(--blue), var(--orange));
                opacity: 0.45;
            }

            .ride-radio {
                position: relative;
            }

            .ride-radio input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .ride-radio span {
                min-height: 98px;
                display: grid;
                gap: 6px;
                align-content: center;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: #ffffff;
                padding: 13px;
                cursor: pointer;
            }

            .ride-radio input:checked + span {
                border-color: rgba(11, 116, 229, 0.62);
                background: #eaf3ff;
                box-shadow: 0 0 0 4px rgba(11, 116, 229, 0.08);
            }

            .ride-radio b {
                display: block;
                font-size: 14px;
            }

            .ride-radio small {
                color: var(--muted);
                font-weight: 800;
            }

            .captain-card {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 13px;
                align-items: start;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 15px;
            }

            .captain-card + .captain-card {
                margin-top: 12px;
            }

            .captain-avatar {
                width: 50px;
                height: 50px;
                display: grid;
                place-items: center;
                border-radius: 18px;
                background: linear-gradient(135deg, var(--blue), var(--orange));
                color: #ffffff;
                font-weight: 900;
            }

            .captain-card h3 {
                margin: 0 0 5px;
                font-size: 15px;
            }

            .captain-card p {
                margin: 0 0 10px;
                color: var(--muted);
                font-size: 12px;
                line-height: 1.45;
            }

            .ride-summary {
                display: grid;
                gap: 10px;
            }

            .summary-row {
                display: flex;
                justify-content: space-between;
                gap: 14px;
                border-bottom: 1px solid var(--line);
                padding: 11px 0;
            }

            .summary-row:last-child {
                border-bottom: 0;
            }

            .summary-row span {
                color: var(--muted);
                font-size: 13px;
            }

            .summary-row strong {
                text-align: right;
                font-size: 13px;
            }

            .map-box {
                height: 360px;
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: #eaf3ff;
                box-shadow: 0 16px 36px rgba(20, 33, 61, 0.08);
            }

            .status-rail {
                display: grid;
                gap: 10px;
            }

            .status-step {
                display: flex;
                align-items: center;
                gap: 10px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                padding: 12px;
                color: var(--muted);
                font-size: 13px;
                font-weight: 800;
            }

            .status-step.active {
                border-color: rgba(11, 116, 229, 0.3);
                background: #eaf3ff;
                color: var(--blue);
            }

            .status-dot {
                width: 12px;
                height: 12px;
                flex: 0 0 auto;
                border-radius: 999px;
                background: currentColor;
            }

            .payment-brand {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: linear-gradient(135deg, #ffffff, #f2f7ff);
                padding: 14px;
            }

            .payment-brand strong {
                display: block;
                margin-bottom: 3px;
            }

            .payment-brand span {
                color: var(--muted);
                font-size: 12px;
            }

            .cashfree-mark {
                width: 54px;
                height: 54px;
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                border-radius: 20px;
                background: linear-gradient(135deg, #16a34a, #0b74e5);
                color: #ffffff;
                font-weight: 900;
            }

            .hotel-card {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: #ffffff;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.08);
            }

            .hotel-card + .hotel-card {
                margin-top: 14px;
            }

            .hotel-photo {
                min-height: 150px;
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 12px;
                padding: 14px;
                background-size: cover;
                background-position: center;
                color: #ffffff;
                position: relative;
            }

            .hotel-photo::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(20, 33, 61, 0.08), rgba(20, 33, 61, 0.78));
            }

            .hotel-photo > * {
                position: relative;
                z-index: 1;
            }

            .hotel-photo strong {
                display: block;
                font-size: 18px;
                line-height: 1.15;
            }

            .hotel-photo span {
                color: rgba(255, 255, 255, 0.82);
                font-size: 12px;
            }

            .hotel-rating {
                flex: 0 0 auto;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.18);
                border: 1px solid rgba(255, 255, 255, 0.26);
                padding: 8px 10px;
                font-size: 12px;
                font-weight: 900;
                backdrop-filter: blur(8px);
            }

            .hotel-body {
                padding: 15px;
            }

            .hotel-info-row {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 9px;
                margin-bottom: 13px;
            }

            .hotel-info {
                border-radius: 16px;
                background: #f6f9fd;
                padding: 10px;
            }

            .hotel-info span {
                display: block;
                color: var(--muted);
                font-size: 10px;
                font-weight: 900;
                text-transform: uppercase;
                margin-bottom: 4px;
            }

            .hotel-info strong {
                display: block;
                font-size: 12px;
                line-height: 1.25;
            }

            .trip-card {
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 15px;
                box-shadow: 0 14px 32px rgba(20, 33, 61, 0.06);
            }

            .trip-card + .trip-card {
                margin-top: 12px;
            }

            .trip-card h3 {
                margin: 0 0 8px;
                font-size: 16px;
            }

            .trip-meta {
                display: grid;
                gap: 7px;
                margin: 0 0 13px;
                color: var(--muted);
                font-size: 12px;
                line-height: 1.45;
            }

            .trip-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 9px;
            }

            .trip-actions.single {
                grid-template-columns: 1fr;
            }

            .admin-table-wrap {
                overflow-x: auto;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                box-shadow: 0 14px 32px rgba(20, 33, 61, 0.06);
                margin-bottom: 14px;
            }

            .admin-table {
                width: 100%;
                min-width: 720px;
                border-collapse: collapse;
                font-size: 12px;
            }

            .admin-table th,
            .admin-table td {
                padding: 12px 10px;
                border-bottom: 1px solid var(--line);
                text-align: left;
                vertical-align: top;
            }

            .admin-table th {
                color: #344054;
                background: #f6f9fd;
                font-size: 10px;
                font-weight: 900;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .admin-table td {
                color: var(--ink);
                line-height: 1.35;
            }

            .admin-table tr:last-child td {
                border-bottom: 0;
            }

            .admin-inline-form {
                display: flex;
                gap: 8px;
                align-items: center;
            }

            .admin-inline-form select {
                min-height: 38px;
                border: 1px solid var(--line);
                border-radius: 12px;
                background: #ffffff;
                padding: 0 9px;
                font-size: 12px;
            }

            .admin-inline-form .btn {
                min-height: 38px;
                padding: 0 12px;
                font-size: 12px;
            }

            .transport-card {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: #ffffff;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.08);
            }

            .transport-card + .transport-card {
                margin-top: 14px;
            }

            .transport-head {
                min-height: 118px;
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 12px;
                padding: 16px;
                background:
                    linear-gradient(135deg, rgba(11, 116, 229, 0.92), rgba(255, 138, 31, 0.86)),
                    radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.5), transparent 34%);
                color: #ffffff;
            }

            .transport-head.green {
                background: linear-gradient(135deg, #16a34a, #0b74e5);
            }

            .transport-head.orange {
                background: linear-gradient(135deg, #ff8a1f, #0b74e5);
            }

            .transport-head.dark {
                background: linear-gradient(135deg, #14213d, #0b74e5);
            }

            .transport-head strong {
                display: block;
                font-size: 18px;
                line-height: 1.15;
            }

            .transport-head span {
                color: rgba(255, 255, 255, 0.84);
                font-size: 12px;
            }

            .transport-badge {
                flex: 0 0 auto;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.18);
                border: 1px solid rgba(255, 255, 255, 0.28);
                padding: 8px 10px;
                color: #ffffff;
                font-size: 12px;
                font-weight: 900;
                backdrop-filter: blur(8px);
            }

            .transport-body {
                padding: 15px;
            }

            .route-line {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                gap: 10px;
                margin-bottom: 13px;
            }

            .route-line span {
                min-width: 0;
            }

            .route-line small {
                display: block;
                color: var(--muted);
                font-size: 10px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .route-line strong {
                display: block;
                font-size: 13px;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }

            .route-arrow {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 14px;
                background: #eef6ff;
                color: var(--blue);
                font-weight: 900;
            }

            /* Final UI polish layer: shared mobile-app styling for all TripNovaa screens. */
            body {
                background:
                    radial-gradient(circle at 14% 12%, rgba(11, 116, 229, 0.18), transparent 26%),
                    radial-gradient(circle at 86% 18%, rgba(255, 138, 29, 0.16), transparent 27%),
                    linear-gradient(145deg, #edf6ff 0%, #fbfdff 42%, #fff7ef 100%);
            }

            .app-shell {
                padding: 22px;
            }

            .phone {
                width: min(100%, 410px);
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(246, 250, 255, 0.98)),
                    #f9fcff;
                border: 1px solid rgba(255, 255, 255, 0.86);
                border-radius: 38px;
                box-shadow:
                    0 34px 90px rgba(8, 79, 158, 0.19),
                    0 12px 28px rgba(255, 138, 29, 0.08),
                    inset 0 0 0 1px rgba(255, 255, 255, 0.72);
            }

            .phone::before {
                content: "";
                position: absolute;
                top: 10px;
                left: 50%;
                z-index: 5;
                width: 68px;
                height: 5px;
                border-radius: 999px;
                background: rgba(20, 33, 61, 0.12);
                transform: translateX(-50%);
                pointer-events: none;
            }

            .topbar {
                position: sticky;
                top: 0;
                z-index: 4;
                padding: 25px 20px 12px;
                background: linear-gradient(180deg, rgba(249, 252, 255, 0.96), rgba(249, 252, 255, 0.78));
                backdrop-filter: blur(16px);
            }

            .logo-mark,
            .mini-brand span,
            .avatar,
            .captain-avatar {
                background:
                    radial-gradient(circle at 72% 24%, rgba(255, 255, 255, 0.38), transparent 28%),
                    linear-gradient(140deg, #0b74e5 0%, #1978d8 42%, #ff8a1d 100%);
                box-shadow: 0 14px 28px rgba(11, 116, 229, 0.22);
            }

            .topbar h1 {
                font-size: 17px;
                letter-spacing: 0;
            }

            .status-pill {
                background: rgba(255, 255, 255, 0.82);
                box-shadow: 0 8px 20px rgba(20, 33, 61, 0.06);
            }

            .content {
                padding: 14px 19px 24px;
                scrollbar-width: thin;
                scrollbar-color: rgba(11, 116, 229, 0.35) transparent;
            }

            .content::-webkit-scrollbar {
                width: 5px;
            }

            .content::-webkit-scrollbar-thumb {
                border-radius: 999px;
                background: rgba(11, 116, 229, 0.32);
            }

            .card,
            .auth-card,
            .stat-card,
            .tile,
            .placeholder-row,
            .profile-strip,
            .trip-card,
            .captain-card,
            .hotel-card,
            .transport-card,
            .admin-table-wrap {
                border-color: rgba(217, 226, 238, 0.92);
                background: rgba(255, 255, 255, 0.94);
                box-shadow:
                    0 18px 42px rgba(20, 33, 61, 0.08),
                    inset 0 1px 0 rgba(255, 255, 255, 0.75);
            }

            .auth-hero,
            .dashboard-hero {
                border: 1px solid rgba(255, 255, 255, 0.34);
                background:
                    linear-gradient(135deg, rgba(8, 79, 158, 0.95) 0%, rgba(11, 116, 229, 0.84) 45%, rgba(255, 138, 29, 0.92) 100%),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                box-shadow:
                    0 20px 46px rgba(11, 116, 229, 0.2),
                    inset 0 1px 0 rgba(255, 255, 255, 0.24);
            }

            .auth-hero::before,
            .dashboard-hero::before {
                content: "";
                position: absolute;
                inset: auto 18px 16px 18px;
                height: 2px;
                border-radius: 999px;
                background: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.54), rgba(255, 255, 255, 0));
            }

            .auth-badge,
            .dashboard-kicker,
            .slide-chip,
            .splash-tag {
                background: rgba(255, 255, 255, 0.18);
                border-color: rgba(255, 255, 255, 0.28);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
                backdrop-filter: blur(12px);
            }

            .field input,
            .field select,
            .field textarea,
            .search-field {
                border-color: rgba(214, 224, 238, 0.95);
                background: linear-gradient(180deg, #ffffff, #f7fbff);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.86);
            }

            .field input::placeholder,
            .field textarea::placeholder {
                color: #98a2b3;
            }

            .btn,
            .mini-action.primary {
                border: 1px solid rgba(255, 255, 255, 0.18);
                background:
                    radial-gradient(circle at 76% 18%, rgba(255, 255, 255, 0.26), transparent 26%),
                    linear-gradient(135deg, #0b74e5 0%, #0861c4 58%, #084f9e 100%);
                box-shadow:
                    0 16px 30px rgba(11, 116, 229, 0.24),
                    inset 0 1px 0 rgba(255, 255, 255, 0.24);
                transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
            }

            .btn:hover,
            .mini-action.primary:hover,
            .service-card:hover,
            .role-card:hover,
            .trip-card:hover,
            .hotel-card:hover,
            .transport-card:hover {
                transform: translateY(-2px);
            }

            .btn:active,
            .mini-action:active,
            .service-card:active,
            .role-card:active {
                transform: translateY(0) scale(0.99);
            }

            .btn-orange {
                background:
                    radial-gradient(circle at 74% 18%, rgba(255, 255, 255, 0.28), transparent 26%),
                    linear-gradient(135deg, #ff9a2d 0%, #ff7a00 54%, #e85d04 100%);
                box-shadow:
                    0 16px 30px rgba(255, 138, 29, 0.24),
                    inset 0 1px 0 rgba(255, 255, 255, 0.22);
            }

            .btn-light,
            .mini-action {
                background: linear-gradient(180deg, #ffffff, #f8fbff);
                border-color: rgba(214, 224, 238, 0.96);
                box-shadow: 0 10px 20px rgba(20, 33, 61, 0.06);
            }

            .role-card,
            .service-card,
            .stat-card,
            .tile,
            .trip-card {
                position: relative;
                overflow: hidden;
            }

            .role-card::after,
            .stat-card::after,
            .tile::after,
            .trip-card::after {
                content: "";
                position: absolute;
                width: 82px;
                height: 82px;
                right: -42px;
                top: -42px;
                border-radius: 999px;
                background: rgba(255, 138, 29, 0.08);
                pointer-events: none;
            }

            .role-card > *,
            .stat-card > *,
            .tile > *,
            .trip-card > * {
                position: relative;
                z-index: 1;
            }

            .circle-icon,
            .service-icon,
            .module-page-icon,
            .otp-icon {
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.75),
                    0 12px 24px rgba(20, 33, 61, 0.08);
            }

            .service-grid {
                gap: 12px;
            }

            .service-card {
                min-height: 148px;
                border-radius: 24px;
                background:
                    radial-gradient(circle at 86% 12%, var(--accent-soft), transparent 35%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 255, 0.96));
            }

            .service-card strong {
                font-size: 15px;
            }

            .stat-card {
                min-height: 104px;
                background:
                    radial-gradient(circle at 82% 8%, rgba(255, 138, 29, 0.12), transparent 34%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
            }

            .stat-card strong {
                color: #0b74e5;
                text-shadow: 0 8px 22px rgba(11, 116, 229, 0.16);
            }

            .mini-metric {
                background: rgba(255, 255, 255, 0.18);
                backdrop-filter: blur(10px);
            }

            .bottom-nav {
                left: 20px;
                right: 20px;
                bottom: 14px;
                gap: 5px;
                border: 1px solid rgba(255, 255, 255, 0.72);
                border-radius: 27px;
                background: rgba(255, 255, 255, 0.9);
                box-shadow:
                    0 22px 46px rgba(20, 33, 61, 0.16),
                    inset 0 1px 0 rgba(255, 255, 255, 0.72);
            }

            .bottom-nav a {
                min-height: 55px;
                border-radius: 20px;
                transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
            }

            .bottom-nav a.active {
                background:
                    radial-gradient(circle at 74% 18%, rgba(255, 138, 29, 0.18), transparent 30%),
                    linear-gradient(180deg, #edf6ff, #e7f1ff);
                color: #0b74e5;
                box-shadow: inset 0 0 0 1px rgba(11, 116, 229, 0.08);
            }

            .nav-symbol {
                width: 24px;
                height: 24px;
                border-radius: 11px;
                background: transparent;
                color: inherit;
                font-size: 17px;
            }

            .map-box {
                height: 340px;
                border-radius: 27px;
                border: 6px solid #ffffff;
                background: linear-gradient(135deg, #eaf3ff, #fff1e4);
                box-shadow:
                    0 20px 44px rgba(20, 33, 61, 0.12),
                    inset 0 0 0 1px rgba(214, 224, 238, 0.9);
            }

            .map-box .leaflet-container,
            .leaflet-container {
                font: inherit;
            }

            .status-step {
                background: linear-gradient(180deg, #ffffff, #f8fbff);
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.06);
            }

            .status-step.active {
                background:
                    linear-gradient(180deg, #eff8ff, #eaf3ff);
                box-shadow: inset 0 0 0 1px rgba(11, 116, 229, 0.08);
            }

            .payment-brand,
            .location-picker {
                border-color: rgba(214, 224, 238, 0.96);
                background:
                    radial-gradient(circle at 92% 10%, rgba(255, 138, 29, 0.14), transparent 34%),
                    linear-gradient(145deg, #ffffff, #f3f8ff);
                box-shadow:
                    0 16px 34px rgba(20, 33, 61, 0.08),
                    inset 0 1px 0 rgba(255, 255, 255, 0.78);
            }

            .cashfree-mark {
                background:
                    radial-gradient(circle at 74% 20%, rgba(255, 255, 255, 0.3), transparent 28%),
                    linear-gradient(135deg, #16a34a 0%, #0b74e5 70%);
                box-shadow: 0 14px 28px rgba(22, 163, 74, 0.18);
            }

            .module-page-card {
                min-height: 276px;
                border-radius: 28px;
                background:
                    radial-gradient(circle at 86% 10%, rgba(255, 138, 29, 0.14), transparent 35%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
                box-shadow:
                    0 22px 48px rgba(20, 33, 61, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.78);
            }

            .module-page-card .hero-title {
                font-size: 27px;
            }

            .hotel-photo,
            .transport-head {
                border-radius: 20px;
                margin: 10px 10px 0;
            }

            .hotel-card,
            .transport-card {
                border-radius: 28px;
            }

            .hotel-info,
            .transport-badge,
            .badge {
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
            }

            .ride-radio span {
                background:
                    radial-gradient(circle at 86% 12%, rgba(255, 138, 29, 0.08), transparent 34%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.05);
            }

            .ride-radio input:checked + span {
                background:
                    radial-gradient(circle at 84% 12%, rgba(255, 138, 29, 0.14), transparent 35%),
                    linear-gradient(180deg, #eff8ff, #eaf3ff);
            }

            .admin-table-wrap {
                border-radius: 24px;
            }

            .admin-table th {
                background: linear-gradient(180deg, #f6f9fd, #eef6ff);
                color: #0b4f93;
            }

            .admin-inline-form select {
                background: linear-gradient(180deg, #ffffff, #f8fbff);
            }

            .alert {
                box-shadow: 0 12px 26px rgba(20, 33, 61, 0.06);
            }

            .quick-chip {
                border-color: rgba(214, 224, 238, 0.96);
            }

            .splash-screen .content {
                padding-top: 44px;
            }

            .slide-window {
                border: 1px solid rgba(255, 255, 255, 0.76);
                box-shadow:
                    0 24px 56px rgba(20, 33, 61, 0.16),
                    inset 0 1px 0 rgba(255, 255, 255, 0.32);
            }

            .role-intro {
                margin: 2px 0 16px;
            }

            .role-intro .hero-title {
                font-size: 25px;
                line-height: 1.08;
            }

            .role-intro .lead {
                margin-bottom: 0;
                font-size: 13px;
            }

            .role-card.role-card-stack {
                min-height: 112px;
                border-radius: 18px;
                padding: 14px 12px;
                background:
                    radial-gradient(circle at 94% 0%, rgba(255, 138, 29, 0.16), transparent 28%),
                    linear-gradient(180deg, #ffffff, #f7fbff);
            }

            .role-main {
                display: grid;
                grid-template-columns: 1fr auto;
                align-items: center;
            }

            .role-main > span:first-child {
                min-width: 0;
            }

            .role-main .circle-icon {
                display: none;
            }

            .role-card strong {
                color: #14213d;
                font-size: 15px;
            }

            .role-card span span {
                font-size: 11px;
                line-height: 1.35;
            }

            .role-actions {
                margin-top: 2px;
            }

            .role-actions .mini-action {
                min-height: 38px;
                border-radius: 13px;
                font-size: 12px;
            }

            .tn-visual {
                position: relative;
                display: inline-block;
                overflow: hidden;
                flex: 0 0 auto;
                width: 66px;
                height: 66px;
                border-radius: 22px;
                background:
                    radial-gradient(circle at 72% 18%, rgba(255, 138, 29, 0.22), transparent 30%),
                    linear-gradient(145deg, #eef7ff, #ffffff);
                box-shadow:
                    0 14px 28px rgba(20, 33, 61, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
            }

            .tn-visual .tn-sky {
                position: absolute;
                inset: 0;
                background:
                    radial-gradient(circle at 72% 20%, rgba(255, 138, 29, 0.16), transparent 25%),
                    radial-gradient(circle at 22% 28%, rgba(11, 116, 229, 0.14), transparent 23%);
            }

            .tn-face {
                position: absolute;
                left: 24px;
                top: 14px;
                width: 20px;
                height: 22px;
                border-radius: 50% 50% 48% 48%;
                background: #f2b27b;
                box-shadow: inset -3px -2px 0 rgba(168, 87, 46, 0.16);
            }

            .tn-hair {
                position: absolute;
                left: 21px;
                top: 10px;
                width: 27px;
                height: 17px;
                border-radius: 16px 16px 9px 8px;
                background: #17233c;
                transform: rotate(-7deg);
            }

            .tn-hair::after {
                content: "";
                position: absolute;
                right: 2px;
                bottom: -4px;
                width: 9px;
                height: 12px;
                border-radius: 8px;
                background: #17233c;
            }

            .tn-body {
                position: absolute;
                left: 17px;
                bottom: 7px;
                width: 34px;
                height: 28px;
                border-radius: 16px 16px 10px 10px;
                background:
                    linear-gradient(90deg, #102a5e 0 30%, #ff8a1d 30% 72%, #102a5e 72% 100%);
            }

            .tn-body::before {
                content: "";
                position: absolute;
                left: 13px;
                top: 2px;
                width: 8px;
                height: 24px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.78);
            }

            .tn-pin {
                position: absolute;
                right: 8px;
                top: 16px;
                width: 15px;
                height: 15px;
                border-radius: 50% 50% 50% 4px;
                background: #0b74e5;
                transform: rotate(-45deg);
                box-shadow: 0 8px 14px rgba(11, 116, 229, 0.2);
            }

            .tn-pin::after {
                content: "";
                position: absolute;
                inset: 5px;
                border-radius: 999px;
                background: #ffffff;
            }

            .tn-car {
                position: absolute;
                left: 14px;
                bottom: 9px;
                width: 40px;
                height: 18px;
                border-radius: 13px 13px 9px 9px;
                background: linear-gradient(135deg, #ff7a00, #f04438);
                box-shadow: 0 8px 16px rgba(240, 68, 56, 0.16);
            }

            .tn-car span {
                position: absolute;
                left: 11px;
                top: -8px;
                width: 19px;
                height: 11px;
                border-radius: 10px 10px 2px 2px;
                background: #0b74e5;
            }

            .tn-car::before,
            .tn-car::after {
                content: "";
                position: absolute;
                bottom: -4px;
                width: 9px;
                height: 9px;
                border-radius: 999px;
                background: #14213d;
                box-shadow: inset 0 0 0 3px #ffffff;
            }

            .tn-car::before {
                left: 7px;
            }

            .tn-car::after {
                right: 7px;
            }

            .tn-visual-captain .tn-body {
                left: 19px;
                bottom: 21px;
                width: 30px;
                height: 22px;
                background: linear-gradient(90deg, #102a5e 0 34%, #ff8a1d 34% 100%);
            }

            .tn-shield {
                position: absolute;
                left: 19px;
                top: 12px;
                width: 30px;
                height: 34px;
                border-radius: 15px 15px 18px 18px;
                background: linear-gradient(145deg, #0b74e5, #084f9e);
                clip-path: polygon(50% 0, 92% 16%, 82% 78%, 50% 100%, 18% 78%, 8% 16%);
            }

            .tn-lock {
                position: absolute;
                left: 27px;
                top: 27px;
                width: 14px;
                height: 12px;
                border-radius: 4px;
                background: #ffffff;
            }

            .tn-lock::before {
                content: "";
                position: absolute;
                left: 3px;
                top: -8px;
                width: 8px;
                height: 9px;
                border: 2px solid #ffffff;
                border-bottom: 0;
                border-radius: 8px 8px 0 0;
            }

            .tn-brief {
                position: absolute;
                right: 9px;
                bottom: 9px;
                width: 22px;
                height: 17px;
                border-radius: 6px;
                background: #ff8a1d;
                box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.55);
            }

            .role-visual {
                width: 54px;
                height: 54px;
                border-radius: 18px;
            }

            .role-visual .tn-face {
                left: 20px;
                top: 12px;
                width: 16px;
                height: 18px;
            }

            .role-visual .tn-hair {
                left: 18px;
                top: 9px;
                width: 22px;
                height: 14px;
            }

            .role-visual .tn-body {
                left: 15px;
                width: 27px;
                height: 22px;
            }

            .role-visual .tn-car {
                left: 11px;
                width: 33px;
                height: 15px;
            }

            .role-visual .tn-shield {
                left: 15px;
                top: 10px;
                width: 25px;
                height: 29px;
            }

            .role-visual .tn-lock {
                left: 22px;
                top: 23px;
            }

            .auth-hero.auth-hero-split {
                display: grid;
                justify-items: center;
                gap: 10px;
                text-align: center;
                color: var(--ink);
                background:
                    radial-gradient(circle at 88% 8%, rgba(255, 138, 29, 0.16), transparent 30%),
                    radial-gradient(circle at 16% 18%, rgba(11, 116, 229, 0.12), transparent 28%),
                    linear-gradient(180deg, #ffffff, #f7fbff);
                padding: 16px 16px 18px;
                border-color: rgba(214, 224, 238, 0.86);
            }

            .auth-hero.auth-hero-split::before,
            .auth-hero.auth-hero-split::after {
                display: none;
            }

            .auth-hero-split .auth-visual {
                width: 122px;
                height: 122px;
                border-radius: 38px;
            }

            .auth-hero-split .auth-visual .tn-face {
                left: 49px;
                top: 24px;
                width: 30px;
                height: 34px;
            }

            .auth-hero-split .auth-visual .tn-hair {
                left: 44px;
                top: 18px;
                width: 40px;
                height: 25px;
            }

            .auth-hero-split .auth-visual .tn-body {
                left: 35px;
                bottom: 15px;
                width: 58px;
                height: 48px;
                border-radius: 25px 25px 14px 14px;
            }

            .auth-hero-split .auth-visual .tn-pin {
                right: 18px;
                top: 32px;
                width: 22px;
                height: 22px;
            }

            .auth-hero-split .auth-visual .tn-car {
                left: 29px;
                bottom: 16px;
                width: 64px;
                height: 29px;
            }

            .auth-hero-split .auth-visual .tn-car span {
                left: 18px;
                top: -13px;
                width: 31px;
                height: 17px;
            }

            .auth-hero-split .auth-visual.tn-visual-captain .tn-body {
                left: 40px;
                bottom: 48px;
                width: 45px;
                height: 33px;
            }

            .auth-hero-split .auth-badge {
                color: #0b74e5;
                background: #eaf3ff;
                border-color: #d8e9ff;
            }

            .auth-hero-split h2 {
                color: var(--ink);
                font-size: 24px;
            }

            .auth-hero-split p {
                margin-left: auto;
                margin-right: auto;
                color: var(--muted);
            }

            .input-shell {
                min-height: 48px;
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid rgba(214, 224, 238, 0.95);
                border-radius: 15px;
                background: linear-gradient(180deg, #ffffff, #f8fbff);
                padding: 0 10px;
                box-shadow:
                    0 10px 22px rgba(20, 33, 61, 0.05),
                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
            }

            .input-shell:focus-within {
                border-color: rgba(11, 116, 229, 0.55);
                box-shadow:
                    0 0 0 4px rgba(11, 116, 229, 0.1),
                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
            }

            .input-shell input,
            .input-shell select {
                width: 100%;
                min-height: 44px;
                border: 0;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
                padding: 0;
                outline: none;
            }

            .input-shell select {
                width: auto;
                color: #344054;
                font-weight: 900;
                cursor: pointer;
            }

            .phone-input select {
                max-width: 104px;
                font-size: 12px;
            }

            .input-icon {
                position: relative;
                width: 22px;
                height: 22px;
                display: inline-block;
                border-radius: 8px;
                background: #eef6ff;
                box-shadow: inset 0 0 0 1px #d8e9ff;
            }

            .input-icon-phone::before {
                content: "";
                position: absolute;
                left: 7px;
                top: 5px;
                width: 8px;
                height: 12px;
                border: 2px solid #667085;
                border-radius: 7px 7px 6px 6px;
                transform: rotate(-28deg);
            }

            .input-icon-card::before {
                content: "";
                position: absolute;
                left: 4px;
                top: 5px;
                width: 14px;
                height: 11px;
                border: 2px solid #667085;
                border-radius: 3px;
            }

            .input-icon-card::after {
                content: "";
                position: absolute;
                left: 8px;
                top: 9px;
                width: 7px;
                height: 1px;
                background: #667085;
                box-shadow: 0 3px 0 #667085;
            }

            .id-suffix,
            .id-type-select {
                color: #667085;
                font-size: 11px;
                font-weight: 900;
                white-space: nowrap;
            }

            .id-type-select {
                max-width: 94px;
            }

            .welcome-role-screen {
                background:
                    linear-gradient(180deg, #f8fbff 0%, #ffffff 56%, #f7fbff 100%);
            }

            .welcome-role-screen .content {
                position: relative;
                display: flex;
                min-height: 100%;
                padding: 20px 18px 18px;
                overflow: hidden;
            }

            .welcome-login {
                position: relative;
                width: 100%;
                min-height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 12px;
                text-align: center;
                border-radius: 32px;
                padding: 22px 14px 18px;
                background:
                    linear-gradient(180deg, rgba(226, 242, 255, 0.86) 0%, rgba(255, 255, 255, 0.88) 43%, rgba(255, 255, 255, 0.98) 100%);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.78),
                    0 18px 40px rgba(20, 33, 61, 0.08);
            }

            .admin-corner {
                position: absolute;
                top: 12px;
                right: 12px;
                z-index: 4;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                min-height: 34px;
                border: 1px solid rgba(214, 224, 238, 0.94);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                color: #0b4f93;
                padding: 5px 9px 5px 5px;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.09);
                backdrop-filter: blur(12px);
            }

            .admin-mini-visual {
                width: 24px;
                height: 24px;
                border-radius: 10px;
                box-shadow: none;
            }

            .admin-mini-visual .tn-shield {
                left: 7px;
                top: 4px;
                width: 11px;
                height: 14px;
            }

            .admin-mini-visual .tn-lock {
                left: 10px;
                top: 10px;
                width: 5px;
                height: 5px;
                border-radius: 2px;
            }

            .admin-mini-visual .tn-lock::before,
            .admin-mini-visual .tn-brief {
                display: none;
            }

            .welcome-scenery {
                position: absolute;
                inset: 0;
                overflow: hidden;
                border-radius: inherit;
                pointer-events: none;
            }

            .scene-cloud {
                position: absolute;
                width: 46px;
                height: 18px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.82);
                box-shadow:
                    14px -8px 0 rgba(255, 255, 255, 0.72),
                    28px 0 0 rgba(255, 255, 255, 0.56);
            }

            .scene-cloud-one {
                left: 20px;
                top: 76px;
            }

            .scene-cloud-two {
                right: 34px;
                top: 104px;
                transform: scale(0.72);
                opacity: 0.75;
            }

            .scene-building {
                position: absolute;
                bottom: 236px;
                width: 58px;
                height: 62px;
                opacity: 0.2;
                background:
                    linear-gradient(90deg, transparent 0 10px, #0b74e5 10px 18px, transparent 18px 27px, #0b74e5 27px 38px, transparent 38px),
                    linear-gradient(180deg, transparent 0 12px, #0b74e5 12px);
                clip-path: polygon(0 100%, 0 40%, 14% 40%, 14% 22%, 28% 22%, 28% 52%, 44% 52%, 44% 8%, 58% 8%, 58% 34%, 74% 34%, 74% 18%, 100% 18%, 100% 100%);
            }

            .scene-building-one {
                left: 8px;
            }

            .scene-building-two {
                right: 4px;
                transform: scaleX(-1);
            }

            .scene-car {
                position: absolute;
                bottom: 250px;
                width: 42px;
                height: 16px;
                border-radius: 12px 12px 7px 7px;
                background: rgba(11, 116, 229, 0.18);
            }

            .scene-car::before,
            .scene-car::after {
                content: "";
                position: absolute;
                bottom: -3px;
                width: 7px;
                height: 7px;
                border-radius: 999px;
                background: rgba(20, 33, 61, 0.22);
            }

            .scene-car::before {
                left: 7px;
            }

            .scene-car::after {
                right: 7px;
            }

            .scene-car-left {
                left: 18px;
            }

            .scene-car-right {
                right: 22px;
                background: rgba(255, 138, 29, 0.18);
            }

            .welcome-logo {
                position: relative;
                z-index: 2;
                display: grid;
                justify-items: center;
                gap: 2px;
                margin-top: 8px;
                margin-bottom: 2px;
            }

            .welcome-pin {
                position: relative;
                width: 62px;
                height: 82px;
                display: block;
                margin-bottom: -8px;
            }

            .welcome-pin::before {
                content: "";
                position: absolute;
                left: 12px;
                top: 8px;
                width: 46px;
                height: 46px;
                border-radius: 50% 50% 50% 8px;
                background: linear-gradient(140deg, #2538c7, #0b74e5 72%);
                transform: rotate(-45deg);
                box-shadow: 0 16px 28px rgba(11, 116, 229, 0.24);
            }

            .welcome-pin span {
                position: absolute;
                left: 27px;
                top: 23px;
                width: 14px;
                height: 14px;
                border-radius: 999px;
                background: #ffffff;
                box-shadow: inset 0 0 0 3px rgba(11, 116, 229, 0.08);
            }

            .welcome-plane {
                position: absolute;
                left: calc(50% + 20px);
                top: 4px;
                width: 35px;
                height: 24px;
                transform: rotate(-22deg);
            }

            .welcome-plane::before {
                content: "";
                position: absolute;
                left: 4px;
                top: 8px;
                width: 34px;
                height: 5px;
                border-radius: 999px;
                background: #ff7a00;
            }

            .welcome-plane::after {
                content: "";
                position: absolute;
                right: 1px;
                top: 1px;
                width: 0;
                height: 0;
                border-left: 12px solid #ff7a00;
                border-top: 8px solid transparent;
                border-bottom: 8px solid transparent;
            }

            .welcome-trail {
                position: absolute;
                left: calc(50% + 18px);
                top: 30px;
                width: 54px;
                height: 34px;
                border-top: 2px solid rgba(255, 122, 0, 0.74);
                border-radius: 50%;
                transform: rotate(-36deg);
            }

            .welcome-logo strong {
                color: #ff7a00;
                font-size: 28px;
                line-height: 1;
                letter-spacing: 0;
            }

            .welcome-logo strong span {
                color: #1436c8;
            }

            .welcome-logo em {
                color: #52637a;
                font-size: 10px;
                font-style: normal;
                font-weight: 800;
            }

            .welcome-copy {
                position: relative;
                z-index: 2;
            }

            .welcome-copy h2 {
                margin: 0;
                color: #14213d;
                font-size: 18px;
                line-height: 1.1;
            }

            .welcome-copy p {
                margin: 5px 0 0;
                color: #667085;
                font-size: 11px;
                font-weight: 700;
            }

            .welcome-choice-grid {
                position: relative;
                z-index: 2;
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-top: 2px;
            }

            .welcome-choice-card {
                position: relative;
                min-height: 218px;
                display: flex;
                flex-direction: column;
                align-items: center;
                overflow: hidden;
                border: 1px solid rgba(214, 224, 238, 0.92);
                border-radius: 18px;
                background:
                    radial-gradient(circle at 85% 12%, rgba(255, 138, 29, 0.12), transparent 26%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 255, 0.98));
                padding: 11px 8px 10px;
                box-shadow:
                    0 16px 28px rgba(20, 33, 61, 0.09),
                    inset 0 1px 0 rgba(255, 255, 255, 0.78);
            }

            .welcome-choice-card::before {
                content: "";
                position: absolute;
                inset: 0;
                background:
                    linear-gradient(180deg, rgba(226, 242, 255, 0.48), transparent 48%),
                    repeating-linear-gradient(90deg, transparent 0 18px, rgba(11, 116, 229, 0.04) 18px 19px);
                pointer-events: none;
            }

            .choice-pin,
            .choice-steer {
                position: absolute;
                top: 12px;
                left: 12px;
                width: 26px;
                height: 26px;
                border-radius: 10px;
                background: #eef6ff;
                box-shadow: inset 0 1px 0 #ffffff;
            }

            .choice-pin::before {
                content: "";
                position: absolute;
                left: 8px;
                top: 6px;
                width: 10px;
                height: 10px;
                border-radius: 50% 50% 50% 3px;
                background: #1436c8;
                transform: rotate(-45deg);
            }

            .choice-steer::before {
                content: "";
                position: absolute;
                left: 7px;
                top: 7px;
                width: 12px;
                height: 12px;
                border: 2px solid #1436c8;
                border-radius: 999px;
            }

            .choice-visual {
                width: 96px;
                height: 96px;
                border-radius: 24px;
                margin-top: 6px;
                margin-bottom: 6px;
                background:
                    radial-gradient(circle at 74% 12%, rgba(255, 138, 29, 0.2), transparent 30%),
                    linear-gradient(145deg, #edf7ff, #ffffff);
            }

            .choice-visual .tn-face {
                left: 39px;
                top: 19px;
                width: 25px;
                height: 28px;
            }

            .choice-visual .tn-hair {
                left: 35px;
                top: 14px;
                width: 34px;
                height: 22px;
            }

            .choice-visual .tn-body {
                left: 27px;
                bottom: 10px;
                width: 46px;
                height: 41px;
            }

            .choice-visual .tn-pin {
                right: 13px;
                top: 27px;
                width: 18px;
                height: 18px;
            }

            .choice-visual .tn-car {
                left: 22px;
                bottom: 10px;
                width: 54px;
                height: 25px;
            }

            .choice-visual .tn-car span {
                left: 15px;
                top: -11px;
                width: 27px;
                height: 15px;
            }

            .choice-visual.tn-visual-captain .tn-body {
                left: 32px;
                bottom: 39px;
                width: 38px;
                height: 29px;
            }

            .welcome-choice-card h3 {
                position: relative;
                z-index: 1;
                margin: 0;
                color: #14213d;
                font-size: 12px;
                line-height: 1.1;
            }

            .welcome-choice-card p {
                position: relative;
                z-index: 1;
                min-height: 34px;
                margin: 6px 0 9px;
                color: #667085;
                font-size: 9.5px;
                line-height: 1.35;
            }

            .choice-button {
                position: relative;
                z-index: 1;
                width: 100%;
                min-height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 10px;
                color: #ffffff;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 12px 20px rgba(20, 33, 61, 0.15);
            }

            .choice-button-user {
                background: linear-gradient(135deg, #1436c8, #0b74e5);
            }

            .choice-button-captain {
                background: linear-gradient(135deg, #ff8a1d, #f05a16);
            }

            .choice-register {
                position: relative;
                z-index: 1;
                margin-top: 7px;
                color: #0b74e5;
                font-size: 10px;
                font-weight: 900;
            }

            .captain-choice .choice-register {
                color: #f05a16;
            }

            .social-divider {
                position: relative;
                z-index: 2;
                width: 78%;
                display: flex;
                align-items: center;
                gap: 9px;
                color: #98a2b3;
                font-size: 10px;
                font-weight: 800;
            }

            .social-divider::before,
            .social-divider::after {
                content: "";
                height: 1px;
                flex: 1;
                background: #e6ebf2;
            }

            .social-row {
                position: relative;
                z-index: 2;
                display: flex;
                justify-content: center;
                gap: 12px;
            }

            .social-form {
                margin: 0;
            }

            .social-btn {
                width: 37px;
                height: 37px;
                display: grid;
                place-items: center;
                border: 1px solid #e6ebf2;
                border-radius: 12px;
                background: #ffffff;
                color: #14213d;
                cursor: pointer;
                font-size: 16px;
                font-weight: 900;
                box-shadow: 0 10px 20px rgba(20, 33, 61, 0.06);
                transition: transform 0.18s ease, box-shadow 0.18s ease;
            }

            .social-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 24px rgba(20, 33, 61, 0.1);
            }

            .social-google {
                color: #ea4335;
            }

            .social-facebook {
                color: #1877f2;
            }

            .welcome-terms {
                position: relative;
                z-index: 2;
                max-width: 260px;
                margin: 0;
                color: #667085;
                font-size: 8.5px;
                line-height: 1.45;
            }

            .welcome-terms strong {
                color: #1436c8;
            }

            .user-travel-dashboard {
                background: #f3f8ff;
            }

            .user-travel-dashboard .content {
                padding: 16px 12px 132px;
                scroll-padding-bottom: 132px;
            }

            .travel-home {
                display: grid;
                gap: 12px;
            }

            .travel-home-head {
                display: grid;
                grid-template-columns: auto 1fr auto auto;
                align-items: center;
                gap: 10px;
            }

            .hamburger-btn,
            .bell-btn,
            .filter-btn {
                width: 32px;
                height: 32px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                background: #ffffff;
                border: 1px solid #e2ecf7;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.06);
            }

            .hamburger-btn span,
            .hamburger-btn::before,
            .hamburger-btn::after {
                content: "";
                width: 14px;
                height: 2px;
                display: block;
                border-radius: 999px;
                background: #14213d;
            }

            .hamburger-btn {
                gap: 3px;
            }

            .travel-user {
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .travel-avatar {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                color: #ffffff;
                font-weight: 900;
                background: linear-gradient(135deg, #0b74e5, #ff8a1d);
            }

            .travel-user strong,
            .travel-user small {
                display: block;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .travel-user strong {
                font-size: 13px;
                color: #14213d;
            }

            .travel-user small {
                color: #667085;
                font-size: 9px;
            }

            .travel-location {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                color: #0b74e5;
                font-size: 10px;
                font-weight: 900;
            }

            .mini-pin {
                width: 8px;
                height: 8px;
                border-radius: 50% 50% 50% 2px;
                background: #0b74e5;
                transform: rotate(-45deg);
            }

            .bell-btn {
                position: relative;
            }

            .bell-btn::before {
                content: "";
                width: 13px;
                height: 14px;
                border: 2px solid #14213d;
                border-radius: 9px 9px 5px 5px;
            }

            .bell-btn span {
                position: absolute;
                right: -2px;
                top: -3px;
                min-width: 15px;
                height: 15px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: #ff6a00;
                color: #ffffff;
                font-size: 8px;
                font-weight: 900;
            }

            .travel-search {
                min-height: 42px;
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 17px;
                background: #ffffff;
                padding: 0 8px 0 12px;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.06);
            }

            .travel-search input {
                min-width: 0;
                border: 0;
                outline: 0;
                background: transparent;
                color: #14213d;
                font-size: 12px;
            }

            .search-mark {
                width: 14px;
                height: 14px;
                border: 2px solid #98a2b3;
                border-radius: 999px;
                position: relative;
            }

            .search-mark::after {
                content: "";
                position: absolute;
                right: -5px;
                bottom: -4px;
                width: 7px;
                height: 2px;
                border-radius: 999px;
                background: #98a2b3;
                transform: rotate(45deg);
            }

            .filter-btn {
                width: 28px;
                height: 28px;
                box-shadow: none;
                background: #f7fbff;
            }

            .filter-btn span,
            .filter-btn::before,
            .filter-btn::after {
                content: "";
                width: 14px;
                height: 2px;
                display: block;
                border-radius: 999px;
                background: #667085;
            }

            .quick-ride-panel {
                position: relative;
                overflow: hidden;
                border: 1px solid #e2ecf7;
                border-radius: 20px;
                background: #ffffff;
                padding: 10px;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.08);
            }

            .quick-ride-title {
                display: flex;
                align-items: center;
                gap: 7px;
                margin-bottom: 8px;
                color: #14213d;
                font-size: 12px;
            }

            .ride-card-body {
                display: grid;
                grid-template-columns: 1.16fr 0.84fr;
                gap: 9px;
                align-items: stretch;
            }

            .ride-fields-mini {
                display: grid;
                gap: 7px;
            }

            .ride-field-mini {
                min-height: 36px;
                border: 1px solid #e6ebf2;
                border-radius: 12px;
                background: #f9fcff;
                padding: 7px 9px 7px 25px;
                position: relative;
            }

            .ride-field-mini::before {
                content: "";
                position: absolute;
                left: 9px;
                top: 13px;
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #22c55e;
            }

            .ride-field-mini.drop::before {
                background: #ef4444;
            }

            .ride-field-mini small,
            .ride-field-mini strong {
                display: block;
            }

            .ride-field-mini small {
                color: #98a2b3;
                font-size: 8px;
                font-weight: 900;
            }

            .ride-field-mini strong {
                color: #14213d;
                font-size: 10px;
            }

            .ride-field-mini em {
                position: absolute;
                right: 8px;
                top: 11px;
                width: 15px;
                height: 15px;
                border-radius: 999px;
                background: #eef6ff;
            }

            .ride-field-mini em::before,
            .ride-field-mini em::after {
                content: "";
                position: absolute;
                background: #667085;
            }

            .ride-field-mini em::before {
                left: 4px;
                right: 4px;
                top: 7px;
                height: 1px;
            }

            .ride-field-mini em::after {
                top: 4px;
                bottom: 4px;
                left: 7px;
                width: 1px;
            }

            .ride-field-mini.pickup em::after {
                display: none;
            }

            .ride-type-mini {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 5px;
            }

            .ride-type-mini a {
                min-height: 30px;
                display: grid;
                place-items: center;
                gap: 2px;
                border-radius: 10px;
                border: 1px solid #e6ebf2;
                background: #ffffff;
                color: #0b4f93;
                font-size: 7px;
                font-weight: 900;
                transition: transform 0.16s ease, box-shadow 0.16s ease;
            }

            .ride-type-mini a.active {
                border-color: #ffb36b;
                background: #fff7ed;
                color: #ff6a00;
                box-shadow: inset 0 0 0 1px rgba(255, 106, 0, 0.1);
            }

            .ride-field-mini:hover,
            .ride-type-mini a:hover,
            .taxi-art:hover,
            .quick-ride-footer a:hover {
                transform: translateY(-1px);
            }

            .ride-type-mini b {
                width: 25px;
                height: 17px;
                display: block;
                position: relative;
                background:
                    radial-gradient(circle at 5px 14px, #14213d 0 3px, transparent 3.4px),
                    radial-gradient(circle at 20px 14px, #14213d 0 3px, transparent 3.4px);
            }

            .ride-type-mini b::before,
            .ride-type-mini b::after {
                content: "";
                position: absolute;
                box-sizing: border-box;
            }

            .ride-type-bike b::before {
                left: 6px;
                bottom: 6px;
                width: 13px;
                height: 6px;
                border-radius: 7px 8px 4px 4px;
                background: #0b4f93;
                transform: skewX(-18deg);
            }

            .ride-type-bike b::after {
                right: 2px;
                top: 3px;
                width: 9px;
                height: 8px;
                border-top: 2px solid #14213d;
                border-right: 2px solid #14213d;
                border-radius: 0 7px 0 0;
                transform: rotate(-18deg);
            }

            .ride-type-auto b {
                background:
                    radial-gradient(circle at 5px 14px, #14213d 0 3px, transparent 3.4px),
                    radial-gradient(circle at 19px 14px, #14213d 0 3px, transparent 3.4px);
            }

            .ride-type-auto b::before {
                left: 3px;
                bottom: 5px;
                width: 19px;
                height: 10px;
                border-radius: 5px 8px 4px 4px;
                background: #16a34a;
                box-shadow: inset 0 5px 0 #ffc857;
            }

            .ride-type-auto b::after {
                left: 8px;
                top: 1px;
                width: 11px;
                height: 8px;
                border-radius: 6px 6px 1px 1px;
                background: #ffd166;
            }

            .ride-type-cab b::before {
                left: 2px;
                bottom: 5px;
                width: 21px;
                height: 10px;
                border-radius: 9px 9px 5px 5px;
                background: #ff6a00;
            }

            .ride-type-cab b::after {
                left: 8px;
                top: 1px;
                width: 10px;
                height: 8px;
                border-radius: 8px 8px 2px 2px;
                background: #ffb36b;
            }

            .ride-type-premium b::before {
                left: 2px;
                bottom: 5px;
                width: 21px;
                height: 9px;
                border-radius: 10px 10px 4px 4px;
                background: #334155;
            }

            .ride-type-premium b::after {
                left: 7px;
                top: 2px;
                width: 12px;
                height: 7px;
                border-radius: 8px 8px 2px 2px;
                background: #94a3b8;
            }

            .taxi-art {
                position: relative;
                overflow: hidden;
                display: block;
                min-height: 132px;
                border-radius: 17px;
                background:
                    linear-gradient(180deg, rgba(255, 245, 235, 0.9), rgba(255, 255, 255, 0.5)),
                    linear-gradient(135deg, #fbe1c2, #d9ecff);
                transition: transform 0.16s ease;
            }

            .taxi-art::before {
                content: "";
                position: absolute;
                inset: auto -10px 0;
                height: 48px;
                background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,0.94));
            }

            .taxi-sun {
                position: absolute;
                right: 14px;
                top: 17px;
                width: 38px;
                height: 38px;
                border-radius: 999px;
                background: rgba(255, 138, 29, 0.22);
            }

            .taxi-pin {
                position: absolute;
                left: 30px;
                top: 22px;
                width: 18px;
                height: 18px;
                border-radius: 50% 50% 50% 4px;
                background: #ff6a00;
                transform: rotate(-45deg);
            }

            .taxi-car {
                position: absolute;
                left: 8px;
                right: 7px;
                bottom: 30px;
                height: 34px;
                border-radius: 20px 20px 10px 10px;
                background: linear-gradient(135deg, #ff8a1d, #ff6a00);
                box-shadow: 0 14px 22px rgba(255, 106, 0, 0.22);
            }

            .taxi-car::before {
                content: "";
                position: absolute;
                left: 21px;
                top: -15px;
                width: 54px;
                height: 22px;
                border-radius: 18px 18px 3px 3px;
                background: #0b74e5;
            }

            .taxi-car::after {
                content: "";
                position: absolute;
                left: 15px;
                right: 15px;
                bottom: -8px;
                height: 14px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 10px 7px, #14213d 0 7px, transparent 8px),
                    radial-gradient(circle at calc(100% - 10px) 7px, #14213d 0 7px, transparent 8px);
            }

            .book-ride-now {
                min-height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: linear-gradient(135deg, #ff8a1d, #ff6a00);
                color: #ffffff;
                padding: 0 13px;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 10px 20px rgba(255, 106, 0, 0.22);
            }

            .quick-ride-footer {
                display: grid;
                grid-template-columns: 1fr 0.8fr auto;
                align-items: center;
                gap: 8px;
                margin-top: 9px;
            }

            .quick-ride-footer > a {
                min-height: 34px;
                display: grid;
                align-content: center;
                border-radius: 12px;
                background: #f7fbff;
                padding: 6px 8px;
                transition: transform 0.16s ease, box-shadow 0.16s ease;
            }

            .quick-ride-footer strong,
            .quick-ride-footer small {
                display: block;
                line-height: 1.1;
            }

            .quick-ride-footer strong {
                color: #14213d;
                font-size: 10px;
            }

            .quick-ride-footer small {
                color: #98a2b3;
                font-size: 7px;
                font-weight: 800;
            }

            .ride-fare strong {
                color: #0b74e5;
                font-size: 12px;
            }

            .explore-banner {
                min-height: 82px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                overflow: hidden;
                border-radius: 18px;
                color: #ffffff;
                padding: 13px;
                background:
                    linear-gradient(90deg, rgba(8, 42, 87, 0.92), rgba(8, 42, 87, 0.5)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                box-shadow: 0 16px 28px rgba(20, 33, 61, 0.11);
            }

            .explore-banner small,
            .explore-banner strong,
            .explore-banner em {
                display: block;
            }

            .explore-banner small {
                font-size: 10px;
                font-weight: 900;
            }

            .explore-banner strong {
                color: #ffb86b;
                font-size: 13px;
            }

            .explore-banner em {
                color: rgba(255,255,255,0.78);
                font-size: 9px;
                font-style: normal;
            }

            .explore-banner a {
                flex: 0 0 auto;
                border-radius: 999px;
                background: rgba(0, 0, 0, 0.34);
                color: #ffffff;
                padding: 8px 11px;
                font-size: 9px;
                font-weight: 900;
            }

            .dashboard-shortcuts {
                display: grid;
                grid-template-columns: repeat(7, minmax(48px, 1fr));
                gap: 7px;
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .dashboard-shortcuts a {
                min-width: 48px;
                display: grid;
                justify-items: center;
                gap: 5px;
                color: #14213d;
                font-size: 9px;
                font-weight: 900;
            }

            .dash-icon {
                width: 33px;
                height: 33px;
                display: grid;
                place-items: center;
                border-radius: 13px;
                background: #eef6ff;
                position: relative;
                box-shadow: inset 0 1px 0 #ffffff;
            }

            .dash-icon::before,
            .dash-icon::after {
                content: "";
                position: absolute;
            }

            .dash-ico-car::before {
                width: 18px;
                height: 9px;
                border-radius: 8px 8px 4px 4px;
                background: #ff6a00;
                box-shadow: 0 5px 0 -2px #14213d;
            }

            .dash-ico-plane::before {
                width: 18px;
                height: 4px;
                border-radius: 999px;
                background: #0b74e5;
                transform: rotate(-28deg);
            }

            .dash-ico-plane::after {
                width: 10px;
                height: 10px;
                border-left: 4px solid #0b74e5;
                border-top: 4px solid transparent;
                border-bottom: 4px solid transparent;
                transform: rotate(-28deg);
            }

            .dash-ico-hotel::before {
                width: 17px;
                height: 18px;
                border-radius: 4px;
                background: #7c3aed;
                box-shadow: inset 5px 0 0 rgba(255,255,255,0.45);
            }

            .dash-ico-bus::before,
            .dash-ico-train::before {
                width: 18px;
                height: 19px;
                border-radius: 5px;
                background: #16a34a;
            }

            .dash-ico-train::before {
                background: #0b74e5;
            }

            .dash-ico-food::before {
                width: 18px;
                height: 3px;
                border-radius: 999px;
                background: #ff6a00;
                box-shadow: 0 5px 0 #ff6a00, 0 10px 0 #ff6a00;
                transform: rotate(-35deg);
            }

            .dash-ico-more::before {
                width: 5px;
                height: 5px;
                border-radius: 999px;
                background: #667085;
                box-shadow: 9px 0 0 #667085, -9px 0 0 #667085;
            }

            .travel-section-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-top: 2px;
            }

            .travel-section-head h2 {
                margin: 0;
                color: #14213d;
                font-size: 13px;
            }

            .travel-section-head a {
                color: #0b74e5;
                font-size: 9px;
                font-weight: 900;
            }

            .destination-strip {
                display: grid;
                grid-template-columns: repeat(4, minmax(118px, 1fr));
                gap: 9px;
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .destination-card {
                position: relative;
                min-height: 132px;
                overflow: hidden;
                display: flex;
                align-items: flex-end;
                border-radius: 16px;
                padding: 9px;
                color: #ffffff;
                background-size: cover;
                background-position: center;
                box-shadow: 0 14px 24px rgba(20, 33, 61, 0.12);
            }

            .destination-card::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(20,33,61,0.05), rgba(20,33,61,0.78));
            }

            .dash-dest-bali {
                background-image: url('https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=500&q=80');
            }

            .dash-dest-manali {
                background-image: url('https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=500&q=80');
            }

            .dash-dest-paris {
                background-image: url('https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=500&q=80');
            }

            .dash-dest-dubai {
                background-image: url('https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=500&q=80');
            }

            .rating-chip,
            .destination-body {
                position: relative;
                z-index: 1;
            }

            .rating-chip {
                position: absolute;
                right: 8px;
                top: 8px;
                border-radius: 999px;
                background: rgba(0,0,0,0.36);
                padding: 4px 6px;
                font-size: 9px;
                font-weight: 900;
            }

            .destination-body strong,
            .destination-body small {
                display: block;
            }

            .destination-body strong {
                font-size: 11px;
            }

            .destination-body small {
                color: rgba(255,255,255,0.8);
                font-size: 8px;
            }

            .offer-row {
                display: grid;
                grid-template-columns: 1.2fr 0.8fr;
                gap: 9px;
            }

            .offer-card {
                min-height: 74px;
                display: grid;
                align-content: center;
                gap: 3px;
                border-radius: 16px;
                padding: 10px;
                color: #14213d;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.08);
            }

            .orange-offer {
                background: linear-gradient(135deg, #fff1e4, #ffffff);
            }

            .green-offer {
                background: linear-gradient(135deg, #ecfdf3, #ffffff);
            }

            .offer-badge {
                width: fit-content;
                border-radius: 10px;
                color: #ffffff;
                background: #ff6a00;
                padding: 4px 7px;
                font-size: 10px;
                font-weight: 900;
            }

            .green-offer .offer-badge {
                background: #16a34a;
            }

            .offer-card strong {
                font-size: 12px;
            }

            .offer-card small {
                color: #667085;
                font-size: 9px;
                font-weight: 800;
            }

            .customer-plan-section {
                display: grid;
                gap: 12px;
            }

            .customer-plan-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 9px;
            }

            .customer-plan-card {
                min-width: 0;
                min-height: 86px;
                display: grid;
                place-items: center;
                align-content: center;
                gap: 8px;
                border: 1px solid #e8eef7;
                border-radius: 18px;
                background: #ffffff;
                color: #14213d;
                padding: 10px 7px;
                text-align: center;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.08);
            }

            .customer-plan-card strong {
                max-width: 100%;
                color: #14213d;
                font-size: 10px;
                font-weight: 900;
                line-height: 1.18;
                overflow-wrap: anywhere;
            }

            .customer-plan-icon {
                position: relative;
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 13px;
                background: #eef6ff;
                color: #0b74e5;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.88);
            }

            .customer-plan-green .customer-plan-icon { background: #ecfdf3; color: #16a34a; }
            .customer-plan-orange .customer-plan-icon { background: #fff1e4; color: #ff6a00; }
            .customer-plan-violet .customer-plan-icon { background: #f2edff; color: #7c3aed; }
            .customer-plan-red .customer-plan-icon { background: #fff1f3; color: #e11d48; }

            .customer-plan-icon::before,
            .customer-plan-icon::after {
                content: "";
                position: absolute;
                box-sizing: border-box;
            }

            .customer-icon-trip::before {
                width: 20px;
                height: 15px;
                top: 13px;
                border-radius: 5px;
                background: currentColor;
            }

            .customer-icon-trip::after {
                width: 10px;
                height: 7px;
                top: 8px;
                border: 2px solid currentColor;
                border-bottom: 0;
                border-radius: 7px 7px 0 0;
            }

            .customer-icon-driver::before {
                width: 22px;
                height: 22px;
                border: 3px solid currentColor;
                border-radius: 999px;
            }

            .customer-icon-driver::after {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: currentColor;
                box-shadow: 0 -8px 0 -3px currentColor, 0 8px 0 -3px currentColor;
            }

            .customer-icon-offer::before {
                width: 19px;
                height: 19px;
                border-radius: 5px 5px 5px 1px;
                background: currentColor;
                transform: rotate(-28deg);
            }

            .customer-icon-offer::after {
                width: 5px;
                height: 5px;
                left: 11px;
                top: 10px;
                border-radius: 999px;
                background: #ffffff;
            }

            .customer-icon-booking::before {
                width: 18px;
                height: 22px;
                border: 2px solid currentColor;
                border-radius: 5px;
                background: rgba(255, 255, 255, 0.35);
            }

            .customer-icon-booking::after {
                width: 10px;
                height: 2px;
                background: currentColor;
                box-shadow: 0 5px 0 currentColor, 0 10px 0 currentColor;
            }

            .customer-icon-heart::before,
            .customer-icon-heart::after {
                width: 14px;
                height: 20px;
                top: 9px;
                border-radius: 999px 999px 0 0;
                background: currentColor;
            }

            .customer-icon-heart::before {
                left: 9px;
                transform: rotate(-45deg);
                transform-origin: 50% 70%;
            }

            .customer-icon-heart::after {
                right: 9px;
                transform: rotate(45deg);
                transform-origin: 50% 70%;
            }

            .customer-icon-profile::before {
                width: 11px;
                height: 11px;
                top: 8px;
                border-radius: 999px;
                background: currentColor;
            }

            .customer-icon-profile::after {
                width: 22px;
                height: 12px;
                top: 20px;
                border-radius: 14px 14px 5px 5px;
                background: currentColor;
            }

            .customer-sightseeing-offer {
                position: relative;
                min-height: 104px;
                overflow: hidden;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto 74px;
                align-items: center;
                gap: 10px;
                border: 1px solid #dcecff;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 88% 18%, rgba(255, 138, 29, 0.18), transparent 24%),
                    linear-gradient(135deg, #eaf6ff, #ffffff 54%, #fff4e8);
                padding: 13px;
                color: #14213d;
                box-shadow: 0 14px 28px rgba(20, 33, 61, 0.09);
            }

            .customer-sightseeing-offer span {
                min-width: 0;
                display: grid;
                gap: 3px;
            }

            .customer-sightseeing-offer small,
            .customer-sightseeing-offer em {
                color: #667085;
                font-size: 9px;
                font-style: normal;
                font-weight: 800;
            }

            .customer-sightseeing-offer strong {
                max-width: 190px;
                color: #14213d;
                font-size: 15px;
                line-height: 1.12;
            }

            .customer-sightseeing-offer b {
                position: relative;
                z-index: 1;
                border-radius: 12px;
                background: #0b74e5;
                color: #ffffff;
                padding: 9px 10px;
                font-size: 10px;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
                box-shadow: 0 10px 18px rgba(11, 116, 229, 0.2);
            }

            .customer-sightseeing-offer i {
                position: relative;
                width: 68px;
                height: 58px;
                border-radius: 17px;
                background:
                    radial-gradient(circle at 75% 28%, #ff6a00 0 7px, transparent 8px),
                    linear-gradient(135deg, #8fd2ff, #edf7ff 62%, #ffd7a8);
                box-shadow: inset 0 0 0 1px rgba(255,255,255,0.72), 0 10px 20px rgba(20, 33, 61, 0.1);
            }

            .customer-sightseeing-offer i::before {
                content: "";
                position: absolute;
                left: 10px;
                bottom: 13px;
                width: 44px;
                height: 20px;
                border-radius: 8px 8px 5px 5px;
                background: #0b74e5;
                box-shadow: inset 13px 0 0 rgba(255,255,255,0.38);
            }

            .customer-sightseeing-offer i::after {
                content: "";
                position: absolute;
                left: 16px;
                right: 16px;
                bottom: 8px;
                height: 6px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 3px 3px, #14213d 0 3px, transparent 3.5px),
                    radial-gradient(circle at calc(100% - 3px) 3px, #14213d 0 3px, transparent 3.5px);
            }

            .customer-plan-head {
                margin-top: 0;
            }

            .customer-local-destinations {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 9px;
            }

            .customer-local-card {
                min-width: 0;
                overflow: hidden;
                display: grid;
                gap: 5px;
                border: 1px solid #e8eef7;
                border-radius: 17px;
                background: #ffffff;
                padding: 7px;
                color: #14213d;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.08);
            }

            .customer-local-card span {
                min-height: 70px;
                border-radius: 13px;
                background-size: cover;
                background-position: center;
            }

            .customer-dest-manali span {
                background:
                    linear-gradient(180deg, rgba(255,255,255,0), rgba(20,33,61,0.25)),
                    url('https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=400&q=80') center/cover;
            }

            .customer-dest-shimla span {
                background:
                    linear-gradient(180deg, rgba(255,255,255,0), rgba(20,33,61,0.22)),
                    url('https://images.unsplash.com/photo-1605649487212-47bdab064df7?auto=format&fit=crop&w=400&q=80') center/cover;
            }

            .customer-dest-leh span {
                background:
                    linear-gradient(180deg, rgba(255,255,255,0), rgba(20,33,61,0.2)),
                    url('https://images.unsplash.com/photo-1589793907316-f94025b46850?auto=format&fit=crop&w=400&q=80') center/cover;
            }

            .customer-local-card strong,
            .customer-local-card small,
            .customer-local-card b {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .customer-local-card strong {
                font-size: 11px;
                line-height: 1.1;
            }

            .customer-local-card small {
                color: #667085;
                font-size: 8px;
                font-weight: 800;
            }

            .customer-local-card b {
                color: #14213d;
                font-size: 10px;
                font-weight: 900;
            }

            .recent-searches {
                display: flex;
                gap: 8px;
                overflow-x: auto;
            }

            .recent-searches a {
                flex: 0 0 auto;
                border-radius: 999px;
                background: #ffffff;
                color: #14213d;
                padding: 8px 11px;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 8px 16px rgba(20,33,61,0.06);
            }

            .dashboard-mini-summary {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                color: #667085;
                font-size: 10px;
            }

            .dashboard-mini-summary strong,
            .dashboard-mini-summary a {
                color: #0b74e5;
                font-weight: 900;
            }

            .bottom-nav {
                grid-template-columns: repeat(var(--nav-count, 4), minmax(0, 1fr));
                align-items: end;
                overflow: visible;
            }

            .user-travel-dashboard .bottom-nav {
                z-index: 60;
                background: #ffffff;
                border: 1px solid #e8eef7;
                box-shadow: 0 -8px 28px rgba(15,31,61,.10);
            }

            .bottom-nav a {
                display: grid;
                grid-template-rows: 26px 20px;
                place-items: center;
                align-content: center;
                justify-items: center;
                gap: 4px;
                min-width: 0;
                padding: 6px 2px;
                text-align: center;
                line-height: 1.05;
                white-space: normal;
            }

            .bottom-nav .nav-symbol {
                position: relative;
                background: transparent;
                color: inherit;
                box-shadow: none;
            }

            .bottom-nav a:not(.nav-center) .nav-symbol {
                grid-row: 1;
                margin: 0 auto;
            }

            .bottom-nav a.nav-bookings {
                font-size: 9px;
                max-width: 54px;
                justify-self: center;
            }

            .bottom-nav .nav-symbol::before,
            .bottom-nav .nav-symbol::after {
                content: "";
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                box-sizing: border-box;
            }

            .bottom-nav a.nav-center {
                transform: translateY(-18px);
                color: #1436c8;
                gap: 5px;
            }

            .bottom-nav a.nav-center .nav-symbol {
                width: 52px;
                height: 52px;
                border-radius: 999px;
                background: linear-gradient(145deg, #1747ff, #0b2cc9);
                color: #ffffff;
                box-shadow:
                    0 18px 34px rgba(20, 54, 200, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.28);
            }

            .bottom-nav a.nav-center.active,
            .bottom-nav a.nav-center {
                background: transparent;
                box-shadow: none;
            }

            .bottom-nav a.nav-center:hover {
                transform: translateY(-20px);
            }

            .nav-home::before {
                width: 17px;
                height: 14px;
                top: 56%;
                border-radius: 4px;
                background: currentColor;
            }

            .nav-home::after {
                width: 14px;
                height: 14px;
                top: 39%;
                border-radius: 3px;
                border-left: 4px solid currentColor;
                border-top: 4px solid currentColor;
                transform: translate(-50%, -50%) rotate(45deg);
            }

            .nav-car::before {
                width: 19px;
                height: 10px;
                border-radius: 9px 9px 4px 4px;
                background: currentColor;
            }

            .nav-car::after {
                width: 22px;
                height: 7px;
                top: 62%;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 5px 3px, currentColor 0 3px, transparent 3.5px),
                    radial-gradient(circle at 17px 3px, currentColor 0 3px, transparent 3.5px);
            }

            .nav-bag::before {
                width: 20px;
                height: 18px;
                top: 57%;
                border: 2px solid currentColor;
                border-radius: 5px;
            }

            .nav-bag::after {
                width: 10px;
                height: 8px;
                top: 34%;
                border: 2px solid currentColor;
                border-bottom: 0;
                border-radius: 7px 7px 0 0;
            }

            .nav-calendar::before {
                width: 19px;
                height: 18px;
                border: 2px solid currentColor;
                border-radius: 5px;
            }

            .nav-calendar::after {
                width: 15px;
                height: 2px;
                top: 43%;
                border-radius: 999px;
                background: currentColor;
                box-shadow: -4px -6px 0 -0.5px currentColor, 4px -6px 0 -0.5px currentColor;
            }

            .nav-profile::before {
                width: 9px;
                height: 9px;
                top: 39%;
                border: 2px solid currentColor;
                border-radius: 999px;
            }

            .nav-profile::after {
                width: 18px;
                height: 10px;
                top: 67%;
                border: 2px solid currentColor;
                border-radius: 12px 12px 4px 4px;
            }

            .nav-requests::before {
                width: 19px;
                height: 17px;
                border: 2px solid currentColor;
                border-radius: 5px;
            }

            .nav-requests::after {
                width: 8px;
                height: 8px;
                border-right: 3px solid currentColor;
                border-bottom: 3px solid currentColor;
                transform: translate(-50%, -58%) rotate(45deg);
            }

            .nav-trip::before {
                width: 19px;
                height: 19px;
                border: 2px solid currentColor;
                border-radius: 999px;
            }

            .nav-trip::after {
                width: 8px;
                height: 8px;
                border-top: 3px solid currentColor;
                border-right: 3px solid currentColor;
                transform: translate(-50%, -50%) rotate(45deg);
            }

            .nav-wallet::before {
                width: 20px;
                height: 15px;
                border: 2px solid currentColor;
                border-radius: 5px;
            }

            .nav-wallet::after {
                width: 7px;
                height: 7px;
                left: 62%;
                border: 2px solid currentColor;
                border-radius: 999px;
            }

            .nav-users::before {
                width: 9px;
                height: 9px;
                left: 43%;
                top: 39%;
                border: 2px solid currentColor;
                border-radius: 999px;
                box-shadow: 9px 3px 0 -1px transparent;
            }

            .nav-users::after {
                width: 20px;
                height: 10px;
                top: 67%;
                border: 2px solid currentColor;
                border-radius: 12px 12px 4px 4px;
            }

            .nav-logout::before {
                width: 18px;
                height: 14px;
                border: 2px solid currentColor;
                border-right: 0;
                border-radius: 5px 0 0 5px;
            }

            .nav-logout::after {
                width: 10px;
                height: 10px;
                border-top: 3px solid currentColor;
                border-right: 3px solid currentColor;
                transform: translate(-30%, -50%) rotate(45deg);
            }

            .nav-plus::before,
            .nav-plus::after {
                width: 22px;
                height: 4px;
                border-radius: 99px;
                background: currentColor;
            }

            .nav-plus::after {
                transform: translate(-50%, -50%) rotate(90deg);
            }

            .bottom-nav .nav-symbol.nav-plus::after {
                transform: translate(-50%, -50%) rotate(90deg);
            }

            .nav-chat::before {
                width: 20px;
                height: 16px;
                border: 2px solid currentColor;
                border-radius: 6px;
            }

            .nav-chat::after {
                width: 7px;
                height: 7px;
                left: 42%;
                top: 68%;
                border-left: 2px solid currentColor;
                border-bottom: 2px solid currentColor;
                transform: translate(-50%, -50%) rotate(-20deg);
            }

            .nav-gift::before {
                width: 20px;
                height: 16px;
                top: 58%;
                border: 2px solid currentColor;
                border-radius: 5px;
            }

            .nav-gift::after {
                width: 18px;
                height: 18px;
                top: 43%;
                border-left: 3px solid currentColor;
                border-top: 3px solid currentColor;
                box-shadow: 8px 0 0 -6px currentColor, -8px 0 0 -6px currentColor;
            }

            .ride-flow-steps {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 6px;
                margin: 0 0 14px;
                padding: 10px;
                border: 1px solid rgba(20, 33, 61, 0.08);
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.92);
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .ride-flow-step {
                position: relative;
                display: grid;
                justify-items: center;
                align-content: center;
                gap: 4px;
                min-height: 54px;
                color: #98a2b3;
                text-align: center;
                font-size: 9px;
                font-weight: 900;
            }

            .ride-flow-step:not(:last-child)::after {
                content: "";
                position: absolute;
                top: 18px;
                left: calc(50% + 16px);
                width: calc(100% - 24px);
                height: 2px;
                border-radius: 99px;
                background: #d8e5f7;
            }

            .ride-flow-step b {
                position: relative;
                z-index: 1;
                display: grid;
                place-items: center;
                width: 28px;
                height: 28px;
                border-radius: 999px;
                background: #eef4ff;
                color: #667085;
                box-shadow: inset 0 0 0 1px rgba(20, 33, 61, 0.08);
            }

            .ride-flow-step small {
                max-width: 64px;
                line-height: 1.15;
            }

            .ride-flow-step.done b,
            .ride-flow-step.active b {
                background: linear-gradient(145deg, #0b74e5, #1436c8);
                color: #ffffff;
                box-shadow: 0 10px 20px rgba(11, 116, 229, 0.24);
            }

            .ride-flow-step.done,
            .ride-flow-step.active {
                color: #1436c8;
            }

            .ride-flow-step.active b {
                background: linear-gradient(145deg, #ff7a18, #ff4d00);
            }

            .rider-option-card {
                align-items: flex-start;
                position: relative;
                overflow: hidden;
            }

            .rider-option-card::after {
                content: "";
                position: absolute;
                right: -30px;
                top: -30px;
                width: 90px;
                height: 90px;
                border-radius: 999px;
                background: rgba(255, 122, 24, 0.12);
            }

            .rider-fare-pill {
                display: inline-grid;
                gap: 1px;
                margin: 0 0 10px;
                padding: 7px 10px;
                border-radius: 14px;
                background: #fff4eb;
                color: #ff5c00;
                font-size: 12px;
                font-weight: 900;
            }

            .rider-fare-pill small {
                color: #7a879c;
                font-size: 9px;
            }

            .on-ride-card {
                margin-top: 12px;
                align-items: center;
            }

            .ride-call-actions {
                margin-left: auto;
                display: flex;
                gap: 6px;
            }

            .ride-call-actions b {
                display: grid;
                place-items: center;
                width: 42px;
                height: 34px;
                border-radius: 999px;
                background: #eef4ff;
                color: #1436c8;
                font-size: 10px;
            }

            .payment-method-list {
                display: grid;
                gap: 8px;
                margin: 10px 0 2px;
            }

            .payment-method-option {
                display: grid;
                grid-template-columns: 38px 1fr auto;
                align-items: center;
                column-gap: 10px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #ffffff;
                padding: 10px;
            }

            .payment-method-option strong {
                font-size: 13px;
                color: #14213d;
            }

            .payment-method-option small {
                grid-column: 2;
                color: #7a879c;
                font-size: 10px;
            }

            .payment-method-option.active {
                border-color: rgba(255, 106, 0, 0.38);
                background: #fff8f2;
            }

            .payment-method-icon {
                grid-row: span 2;
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                background: #eef4ff;
                color: #1436c8;
                font-size: 10px;
                font-weight: 900;
            }

            .payment-method-icon.cash { background: #e8f8ee; color: #16a34a; }
            .payment-method-icon.upi { background: #f0fdf4; color: #15803d; }
            .payment-method-icon.phonepe { background: #f3e8ff; color: #7e22ce; }
            .payment-method-icon.card { background: #eff6ff; color: #1d4ed8; }
            .payment-method-icon.wallet { background: #fff7ed; color: #ea580c; }

            .method-radio {
                grid-row: span 2;
                width: 15px;
                height: 15px;
                border: 2px solid #d0d8e8;
                border-radius: 999px;
            }

            .method-radio.active {
                border-color: #ff6a00;
                background: radial-gradient(circle at center, #ff6a00 0 45%, transparent 47%);
            }

            .ride-rating-preview {
                display: grid;
                gap: 7px;
                border: 1px solid rgba(255, 106, 0, 0.16);
                border-radius: 20px;
                background: linear-gradient(135deg, #fff8f2, #ffffff);
                padding: 14px;
                text-align: center;
            }

            .ride-rating-preview strong {
                color: #14213d;
                font-size: 14px;
            }

            .ride-rating-preview span {
                color: #ff9f1a;
                font-size: 15px;
                font-weight: 900;
                word-spacing: 4px;
            }

            .ride-rating-preview small {
                color: #7a879c;
                font-size: 11px;
            }

            .plan-trip-screen .content {
                background: linear-gradient(180deg, #f6faff 0%, #ffffff 100%);
            }

            .plan-flow-steps {
                display: flex;
                gap: 7px;
                overflow-x: auto;
                margin: 0 0 14px;
                padding: 4px 2px 12px;
                scrollbar-width: none;
            }

            .plan-flow-steps::-webkit-scrollbar {
                display: none;
            }

            .plan-flow-step {
                flex: 0 0 74px;
                display: grid;
                justify-items: center;
                gap: 5px;
                color: #98a2b3;
                font-size: 9px;
                font-weight: 900;
                text-align: center;
                line-height: 1.15;
            }

            .plan-flow-step b {
                display: grid;
                place-items: center;
                width: 25px;
                height: 25px;
                border-radius: 999px;
                background: #eaf3ff;
                color: #667085;
            }

            .plan-flow-step.done b,
            .plan-flow-step.active b {
                background: #1436c8;
                color: #ffffff;
                box-shadow: 0 8px 18px rgba(20, 54, 200, 0.22);
            }

            .plan-flow-step.active b {
                background: #ff6a00;
            }

            .plan-flow-step.done,
            .plan-flow-step.active {
                color: #1436c8;
            }

            .plan-trip-hero,
            .plan-scenic-card {
                position: relative;
                overflow: hidden;
                min-height: 178px;
                border-radius: 26px;
                padding: 20px;
                color: #ffffff;
                background:
                    radial-gradient(circle at 82% 24%, rgba(255,255,255,0.58) 0 28px, transparent 29px),
                    linear-gradient(145deg, rgba(20, 54, 200, 0.2), rgba(255, 106, 0, 0.08)),
                    linear-gradient(135deg, #78b7ff, #2f78df 54%, #1b4f93);
                box-shadow: 0 18px 38px rgba(20, 33, 61, 0.14);
            }

            .plan-trip-hero::before,
            .plan-scenic-card::before {
                content: "";
                position: absolute;
                inset: auto 0 0;
                height: 92px;
                background:
                    linear-gradient(135deg, transparent 0 25%, rgba(255,255,255,0.96) 26% 42%, transparent 43%) 12px 23px / 116px 80px repeat-x,
                    linear-gradient(135deg, transparent 0 24%, rgba(14, 102, 68, 0.55) 25% 46%, transparent 47%) 0 42px / 130px 76px repeat-x;
                opacity: 0.92;
            }

            .plan-trip-hero::after {
                content: "";
                position: absolute;
                right: 34px;
                bottom: 24px;
                width: 72px;
                height: 100px;
                border-radius: 34px 34px 16px 16px;
                background:
                    radial-gradient(circle at 36px 18px, #ffd0a8 0 17px, transparent 18px),
                    linear-gradient(#ff7a18 0 52%, #173b8f 53%);
                box-shadow: -18px 42px 0 -30px #1436c8;
            }

            .plan-trip-hero span,
            .plan-scenic-card span {
                position: relative;
                z-index: 1;
                display: inline-flex;
                margin-bottom: 7px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.2);
                padding: 6px 10px;
                font-size: 10px;
                font-weight: 900;
            }

            .plan-trip-hero h2,
            .plan-scenic-card h2,
            .plan-trip-hero p,
            .plan-scenic-card p {
                position: relative;
                z-index: 1;
                max-width: 230px;
            }

            .plan-trip-hero h2,
            .plan-scenic-card h2 {
                margin: 0 0 5px;
                font-size: 24px;
                line-height: 1.05;
            }

            .plan-trip-hero p,
            .plan-scenic-card p {
                margin: 0;
                font-size: 12px;
                line-height: 1.45;
                color: rgba(255, 255, 255, 0.88);
            }

            .plan-search-card {
                margin-top: -24px;
                position: relative;
                z-index: 2;
            }

            .plan-chip-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .plan-check-chip input,
            .plan-transport-card input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .plan-check-chip span {
                display: inline-flex;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: #ffffff;
                padding: 8px 10px;
                color: #475467;
                font-size: 11px;
                font-weight: 900;
            }

            .plan-check-chip input:checked + span {
                border-color: rgba(255, 106, 0, 0.45);
                background: #fff4eb;
                color: #ff5c00;
            }

            .plan-info-card,
            .plan-offer-banner {
                display: grid;
                gap: 4px;
                border: 1px solid rgba(20, 54, 200, 0.12);
                border-radius: 20px;
                background: #eef6ff;
                padding: 14px;
                color: #14213d;
                font-size: 12px;
                box-shadow: 0 10px 24px rgba(20, 33, 61, 0.06);
            }

            .plan-info-card strong,
            .plan-offer-banner strong {
                color: #1436c8;
                font-size: 13px;
            }

            .plan-transport-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .plan-transport-card {
                position: relative;
                display: grid;
                justify-items: center;
                gap: 8px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                padding: 14px 8px;
                color: #14213d;
                font-size: 12px;
                font-weight: 900;
            }

            .plan-transport-icon {
                display: grid;
                place-items: center;
                width: 38px;
                height: 38px;
                border-radius: 14px;
                background: #eaf3ff;
                color: #1436c8;
                font-weight: 900;
            }

            .plan-transport-card input:checked ~ .plan-transport-icon {
                background: #1436c8;
                color: #ffffff;
                box-shadow: 0 10px 20px rgba(20, 54, 200, 0.22);
            }

            .plan-bus-visual {
                position: relative;
                min-height: 132px;
                overflow: hidden;
                border-radius: 24px;
                background: linear-gradient(180deg, #d9ecff, #fff7ef);
            }

            .plan-bus-visual::before {
                content: "";
                position: absolute;
                inset: 0;
                background:
                    linear-gradient(135deg, transparent 0 30%, rgba(32, 129, 91, 0.4) 31% 48%, transparent 49%) 0 68px / 125px 70px repeat-x;
            }

            .plan-road {
                position: absolute;
                left: 18px;
                right: 18px;
                bottom: 22px;
                height: 10px;
                border-radius: 99px;
                background: rgba(20, 33, 61, 0.22);
            }

            .plan-bus-shape {
                position: absolute;
                right: 40px;
                bottom: 36px;
                width: 116px;
                height: 58px;
                border-radius: 18px 26px 10px 10px;
                background:
                    radial-gradient(circle at 28px 56px, #14213d 0 8px, transparent 9px),
                    radial-gradient(circle at 88px 56px, #14213d 0 8px, transparent 9px),
                    linear-gradient(90deg, #0b74e5 0 62%, #ff7a18 63%);
                box-shadow: inset 18px 13px 0 rgba(255,255,255,0.55);
            }

            .plan-mini-route,
            .plan-summary-card {
                display: grid;
                gap: 10px;
            }

            .plan-mini-route div,
            .plan-summary-card div {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                border-bottom: 1px solid var(--line);
                padding-bottom: 9px;
            }

            .plan-mini-route div:last-child,
            .plan-summary-card div:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .plan-mini-route span,
            .plan-summary-card span {
                color: #667085;
                font-size: 12px;
                font-weight: 800;
            }

            .plan-mini-route strong,
            .plan-summary-card strong {
                text-align: right;
                font-size: 12px;
            }

            .plan-tabs {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                margin-bottom: 10px;
            }

            .plan-tabs span {
                border: 1px solid var(--line);
                border-radius: 14px;
                background: #ffffff;
                padding: 9px;
                color: #667085;
                text-align: center;
                font-size: 11px;
                font-weight: 900;
            }

            .plan-tabs span.active {
                background: #1436c8;
                color: #ffffff;
                border-color: #1436c8;
            }

            .plan-option-card {
                display: grid;
                grid-template-columns: 54px 1fr auto;
                gap: 12px;
                align-items: center;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: #ffffff;
                padding: 12px;
                margin-bottom: 10px;
                box-shadow: 0 12px 26px rgba(20, 33, 61, 0.07);
            }

            .plan-option-thumb {
                display: grid;
                place-items: center;
                width: 54px;
                height: 54px;
                border-radius: 17px;
                background: linear-gradient(145deg, #eaf3ff, #fff4eb);
                color: #1436c8;
                font-weight: 900;
            }

            .plan-option-card h3 {
                margin: 0 0 3px;
                color: #14213d;
                font-size: 13px;
            }

            .plan-option-card p,
            .plan-option-card small {
                margin: 0;
                color: #667085;
                font-size: 10px;
                line-height: 1.35;
                font-weight: 800;
            }

            .plan-option-price {
                display: grid;
                justify-items: end;
                gap: 3px;
                min-width: 70px;
            }

            .plan-option-price strong {
                color: #14213d;
                font-size: 12px;
            }

            .plan-option-price .btn {
                width: auto;
                min-height: 32px;
                padding: 8px 11px;
                font-size: 10px;
            }

            .plan-offer-banner {
                background: linear-gradient(135deg, #dcfce7, #ffffff);
                border-color: rgba(22, 163, 74, 0.18);
            }

            .plan-offer-banner strong {
                color: #16a34a;
            }

            .plan-scenic-card {
                margin-bottom: 14px;
                background:
                    radial-gradient(circle at 78% 28%, rgba(255,255,255,0.5) 0 30px, transparent 31px),
                    linear-gradient(145deg, rgba(255, 106, 0, 0.12), rgba(20, 54, 200, 0.12)),
                    linear-gradient(135deg, #8dccff, #206fd5 55%, #144b9a);
            }

            .captain-pickup,
            .arrival-card,
            .guide-card,
            .complete-card {
                background:
                    radial-gradient(circle at 78% 28%, rgba(255,255,255,0.5) 0 30px, transparent 31px),
                    linear-gradient(145deg, rgba(255, 106, 0, 0.2), rgba(20, 54, 200, 0.1)),
                    linear-gradient(135deg, #9ccfff, #2370cd 56%, #0e5f55);
            }

            .plan-highlight-grid,
            .plan-service-grid,
            .plan-restaurant-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 14px;
            }

            .plan-highlight-grid span,
            .plan-service-grid span,
            .plan-restaurant-row span {
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                padding: 12px;
                color: #14213d;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 10px 22px rgba(20, 33, 61, 0.06);
            }

            .plan-restaurant-row span small {
                color: #667085;
                font-size: 9px;
            }

            .plan-accepted-card,
            .plan-payment-card {
                background: linear-gradient(180deg, #ffffff, #f4f9ff);
            }

            .plan-reminder-card {
                min-height: 520px;
                display: grid;
                align-content: start;
                gap: 14px;
                border-radius: 28px;
                background: linear-gradient(180deg, #08356f, #0b1f4d);
                color: #ffffff;
                padding: 22px;
                box-shadow: 0 22px 46px rgba(8, 31, 77, 0.22);
            }

            .plan-reminder-card span {
                width: fit-content;
                border-radius: 999px;
                background: rgba(255, 184, 0, 0.18);
                color: #ffd166;
                padding: 7px 10px;
                font-size: 11px;
                font-weight: 900;
            }

            .plan-reminder-card h2 {
                margin: 0;
                font-size: 22px;
                line-height: 1.12;
            }

            .plan-reminder-card p {
                margin: 0;
                color: rgba(255, 255, 255, 0.82);
                font-size: 13px;
                line-height: 1.55;
            }

            .plan-reminder-visual {
                position: relative;
                min-height: 190px;
                border-radius: 24px;
                background:
                    radial-gradient(circle at 72% 30%, #ff9f1a 0 18px, transparent 19px),
                    linear-gradient(135deg, transparent 0 34%, rgba(255,255,255,0.9) 35% 47%, transparent 48%) 0 98px / 120px 86px repeat-x,
                    linear-gradient(180deg, #75b8ff, #eef7ff);
                overflow: hidden;
            }

            .plan-reminder-visual::after {
                content: "";
                position: absolute;
                right: 34px;
                bottom: 24px;
                width: 130px;
                height: 52px;
                border-radius: 28px 34px 12px 12px;
                background:
                    radial-gradient(circle at 29px 49px, #14213d 0 9px, transparent 10px),
                    radial-gradient(circle at 96px 49px, #14213d 0 9px, transparent 10px),
                    linear-gradient(90deg, #ffffff 0 55%, #ff7a18 56%);
            }

            .driver-home-hero {
                position: relative;
                min-height: 190px;
                overflow: hidden;
                border-radius: 28px;
                padding: 18px;
                color: #14213d;
                background:
                    radial-gradient(circle at 78% 22%, rgba(255,255,255,0.9) 0 34px, transparent 35px),
                    linear-gradient(135deg, transparent 0 30%, rgba(255,255,255,0.96) 31% 45%, transparent 46%) 0 92px / 130px 92px repeat-x,
                    linear-gradient(180deg, #dff0ff, #f8fbff);
                box-shadow: 0 18px 38px rgba(20, 33, 61, 0.12);
            }

            .driver-home-hero span {
                position: relative;
                z-index: 2;
                display: grid;
                gap: 3px;
                max-width: 190px;
            }

            .driver-home-hero small,
            .driver-home-hero em {
                color: #667085;
                font-size: 11px;
                font-style: normal;
                font-weight: 800;
            }

            .driver-home-hero strong {
                font-size: 16px;
                color: #14213d;
            }

            .driver-hero-pin {
                position: absolute;
                right: 58px;
                top: 38px;
                width: 42px;
                height: 42px;
                border-radius: 50% 50% 50% 6px;
                background: linear-gradient(145deg, #1b65ff, #1436c8);
                transform: rotate(-45deg);
                box-shadow: 0 12px 24px rgba(20, 54, 200, 0.24);
            }

            .driver-hero-pin::after {
                content: "";
                position: absolute;
                inset: 12px;
                border-radius: 999px;
                background: #ffffff;
            }

            .driver-hero-bus,
            .driver-hero-car {
                position: absolute;
                bottom: 24px;
                border-radius: 16px 24px 9px 9px;
                background:
                    radial-gradient(circle at 22px 43px, #14213d 0 7px, transparent 8px),
                    radial-gradient(circle at 70px 43px, #14213d 0 7px, transparent 8px),
                    linear-gradient(90deg, #ffffff 0 58%, #0b74e5 59%);
            }

            .driver-hero-bus {
                left: 32px;
                width: 92px;
                height: 46px;
            }

            .driver-hero-car {
                right: 32px;
                width: 118px;
                height: 50px;
                background:
                    radial-gradient(circle at 28px 47px, #14213d 0 8px, transparent 9px),
                    radial-gradient(circle at 92px 47px, #14213d 0 8px, transparent 9px),
                    linear-gradient(90deg, #ffffff 0 55%, #ff7a18 56%);
            }

            .driver-home-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
                margin: 14px 0;
            }

            .driver-home-grid a {
                min-height: 92px;
                display: grid;
                align-content: center;
                justify-items: center;
                gap: 9px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                color: #14213d;
                text-align: center;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.07);
            }

            .driver-discount-banner {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 14px;
                min-height: 94px;
                border-radius: 22px;
                background:
                    radial-gradient(circle at 82% 25%, rgba(255,255,255,0.62) 0 26px, transparent 27px),
                    linear-gradient(135deg, #e4f5ff, #ffffff 54%, #e9fff4);
                padding: 16px;
                box-shadow: 0 12px 28px rgba(20, 33, 61, 0.08);
            }

            .driver-discount-banner span {
                display: grid;
                gap: 3px;
            }

            .driver-discount-banner strong {
                color: #1436c8;
                font-size: 17px;
            }

            .driver-discount-banner small {
                color: #667085;
                font-weight: 800;
            }

            .driver-discount-banner a {
                border-radius: 12px;
                background: #1436c8;
                color: #ffffff;
                padding: 9px 12px;
                font-size: 11px;
                font-weight: 900;
            }

            .dash-ico-bag::before { content: "B"; }
            .dash-ico-offer::before { content: "%"; }
            .dash-ico-calendar::before { content: "D"; }
            .dash-ico-heart::before { content: "H"; }
            .dash-ico-profile::before { content: "P"; }

            .driver-screen-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 12px;
            }

            .driver-screen-head h2 {
                margin: 0;
                color: #14213d;
                font-size: 18px;
            }

            .driver-screen-head a {
                color: #1436c8;
                font-size: 12px;
                font-weight: 900;
            }

            .driver-tabs {
                display: flex;
                gap: 14px;
                overflow-x: auto;
                margin: 0 0 14px;
                padding-bottom: 6px;
                color: #667085;
                scrollbar-width: none;
            }

            .driver-tabs::-webkit-scrollbar {
                display: none;
            }

            .driver-tabs span,
            .driver-tabs a {
                flex: 0 0 auto;
                position: relative;
                font-size: 11px;
                font-weight: 900;
                color: #667085;
            }

            .driver-tabs span.active,
            .driver-tabs a.active {
                color: #1436c8;
            }

            .driver-tabs span.active::after,
            .driver-tabs a.active::after {
                content: "";
                position: absolute;
                left: 0;
                right: 0;
                bottom: -7px;
                height: 3px;
                border-radius: 999px;
                background: #1436c8;
            }

            .posted-trip-card {
                position: relative;
                display: grid;
                grid-template-columns: 82px 1fr auto;
                gap: 12px;
                align-items: center;
                border: 1px solid var(--line);
                border-radius: 20px;
                background: #ffffff;
                padding: 12px;
                margin-bottom: 12px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .posted-trip-thumb {
                width: 82px;
                height: 82px;
                border-radius: 16px;
                background:
                    radial-gradient(circle at 74% 20%, #ffffff 0 16px, transparent 17px),
                    linear-gradient(135deg, transparent 0 30%, rgba(255,255,255,0.88) 31% 45%, transparent 46%) 0 46px / 80px 56px repeat-x,
                    linear-gradient(180deg, #74b9ff, #1f6cc9);
            }

            .trip-img-delhi { background-color: #dbeafe; }
            .trip-img-shimla { background-color: #e0f2fe; }
            .trip-img-goa { background-color: #fef3c7; }

            .posted-trip-body small {
                color: #98a2b3;
                font-size: 9px;
                font-weight: 900;
            }

            .posted-trip-body h3 {
                margin: 4px 0;
                color: #14213d;
                font-size: 13px;
            }

            .posted-trip-body p {
                margin: 0 0 8px;
                color: #667085;
                font-size: 10px;
                line-height: 1.35;
                font-weight: 800;
            }

            .posted-trip-body a {
                display: inline-flex;
                border: 1px solid rgba(20, 54, 200, 0.2);
                border-radius: 10px;
                color: #1436c8;
                padding: 7px 18px;
                font-size: 10px;
                font-weight: 900;
            }

            .posted-trip-status {
                align-self: start;
                border-radius: 999px;
                background: #eef4ff;
                color: #1436c8;
                padding: 5px 8px;
                font-size: 9px;
                font-weight: 900;
                white-space: nowrap;
            }

            .posted-trip-status.offers-received {
                background: #fff4eb;
                color: #ff5c00;
            }

            .posted-trip-status.booked {
                background: #e8f8ee;
                color: #16a34a;
            }

            .posted-trip-status.completed {
                background: #f2f4f7;
                color: #667085;
            }

            .posted-trip-new {
                margin-top: 6px;
            }

            .post-trip-hero {
                position: relative;
                min-height: 138px;
                overflow: hidden;
                border-radius: 24px;
                background:
                    radial-gradient(circle at 82% 26%, rgba(255,255,255,0.72) 0 26px, transparent 27px),
                    linear-gradient(135deg, transparent 0 31%, rgba(255,255,255,0.9) 32% 45%, transparent 46%) 0 70px / 112px 74px repeat-x,
                    linear-gradient(135deg, #dff0ff, #f8fbff 54%, #fff4eb);
                padding: 18px;
                margin-bottom: 14px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .post-trip-hero::after {
                content: "";
                position: absolute;
                right: 26px;
                bottom: 22px;
                width: 116px;
                height: 48px;
                border-radius: 28px 36px 12px 12px;
                background:
                    radial-gradient(circle at 27px 44px, #14213d 0 8px, transparent 9px),
                    radial-gradient(circle at 90px 44px, #14213d 0 8px, transparent 9px),
                    linear-gradient(90deg, #ffffff 0 54%, #0b74e5 55%);
            }

            .post-trip-hero span {
                position: relative;
                z-index: 1;
                display: grid;
                gap: 5px;
                max-width: 220px;
            }

            .post-trip-hero strong {
                color: #14213d;
                font-size: 18px;
                line-height: 1.15;
            }

            .post-trip-hero small {
                color: #667085;
                font-size: 12px;
                font-weight: 800;
                line-height: 1.4;
            }

            .post-trip-form {
                margin-top: 0;
            }

            .driver-offer-card {
                display: grid;
                gap: 12px;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 14px;
                margin-bottom: 14px;
                box-shadow: 0 16px 34px rgba(20, 33, 61, 0.08);
            }

            .driver-offer-top,
            .driver-car-row {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .driver-offer-avatar {
                display: grid;
                place-items: center;
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: linear-gradient(145deg, #ffd0a8, #ff7a18);
                color: #ffffff;
                font-weight: 900;
            }

            .driver-offer-top strong,
            .driver-car-row strong {
                display: block;
                color: #14213d;
                font-size: 13px;
            }

            .driver-offer-top small,
            .driver-car-row small {
                color: #667085;
                font-size: 10px;
                font-weight: 800;
            }

            .driver-offer-price {
                margin-left: auto;
                display: grid;
                justify-items: end;
                color: #14213d;
                font-size: 14px;
                font-weight: 900;
            }

            .driver-offer-price small {
                color: #667085;
                font-size: 9px;
            }

            .driver-car-art {
                position: relative;
                width: 96px;
                height: 54px;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 24px 47px, #14213d 0 7px, transparent 8px),
                    radial-gradient(circle at 72px 47px, #14213d 0 7px, transparent 8px),
                    linear-gradient(90deg, #ffffff 0 58%, #e5e7eb 59%);
                box-shadow: inset 16px 12px 0 rgba(20, 116, 229, 0.12);
            }

            .offer-car-blue {
                background:
                    radial-gradient(circle at 24px 47px, #14213d 0 7px, transparent 8px),
                    radial-gradient(circle at 72px 47px, #14213d 0 7px, transparent 8px),
                    linear-gradient(90deg, #ffffff 0 50%, #0b74e5 51%);
            }

            .driver-offer-card h4 {
                margin: 0;
                color: #14213d;
                font-size: 12px;
            }

            .driver-service-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
            }

            .driver-service-grid span {
                display: grid;
                place-items: center;
                min-height: 48px;
                border-radius: 12px;
                background: #f7faff;
                color: #475467;
                padding: 7px 5px;
                text-align: center;
                font-size: 8px;
                font-weight: 900;
            }

            .promo-card-stack {
                display: grid;
                gap: 12px;
                margin-bottom: 14px;
            }

            .promo-card {
                position: relative;
                min-height: 112px;
                overflow: hidden;
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border-radius: 22px;
                padding: 18px;
                color: #ffffff;
                box-shadow: 0 16px 32px rgba(20, 33, 61, 0.12);
            }

            .promo-card::after {
                content: "";
                position: absolute;
                right: -22px;
                bottom: -24px;
                width: 138px;
                height: 74px;
                border-radius: 38px 48px 18px 18px;
                background:
                    radial-gradient(circle at 30px 68px, #14213d 0 9px, transparent 10px),
                    radial-gradient(circle at 96px 68px, #14213d 0 9px, transparent 10px),
                    linear-gradient(90deg, rgba(255,255,255,0.95) 0 55%, rgba(255,255,255,0.35) 56%);
                opacity: 0.92;
            }

            .promo-blue { background: linear-gradient(135deg, #1457ff, #0b2cc9); }
            .promo-green { background: linear-gradient(135deg, #07998a, #0f766e); }
            .promo-orange { background: linear-gradient(135deg, #ff7a18, #f97316); }
            .promo-purple { background: linear-gradient(135deg, #5636d8, #7c3aed); }

            .promo-card span {
                position: relative;
                z-index: 1;
                display: grid;
                gap: 4px;
            }

            .promo-card strong {
                font-size: 18px;
                line-height: 1.1;
            }

            .promo-card small,
            .promo-card em {
                color: rgba(255,255,255,0.86);
                font-size: 11px;
                font-style: normal;
                font-weight: 800;
            }

            .promo-card b {
                position: relative;
                z-index: 1;
                display: grid;
                place-items: center;
                width: 48px;
                height: 48px;
                border-radius: 16px;
                background: rgba(255,255,255,0.2);
                font-size: 14px;
            }

            .reward-points-pill {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                border: 1px solid rgba(20, 54, 200, 0.12);
                border-radius: 18px;
                background: #eef4ff;
                padding: 12px;
                margin-bottom: 14px;
                color: #1436c8;
                font-size: 11px;
                font-weight: 900;
            }

            .reward-points-pill span {
                color: #667085;
                font-weight: 800;
                text-align: right;
            }

            .wallet-offer-card {
                display: grid;
                grid-template-columns: 72px 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: #ffffff;
                padding: 12px;
                margin-bottom: 10px;
                box-shadow: 0 10px 24px rgba(20, 33, 61, 0.06);
            }

            .wallet-offer-card div {
                display: grid;
                place-items: center;
                min-height: 52px;
                border-radius: 14px;
                background: #f7faff;
                color: #1436c8;
                font-size: 11px;
                font-weight: 900;
            }

            .wallet-offer-card div span {
                color: #667085;
                font-size: 9px;
            }

            .wallet-offer-card p {
                margin: 0;
                color: #14213d;
                font-size: 12px;
                font-weight: 900;
                line-height: 1.35;
            }

            .wallet-offer-card small {
                color: #667085;
                font-size: 10px;
            }

            .wallet-offer-card a {
                color: #1436c8;
                font-size: 11px;
                font-weight: 900;
            }

            .bank-wallet-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 9px;
                margin-bottom: 14px;
            }

            .bank-wallet-grid a {
                min-height: 76px;
                display: grid;
                justify-items: center;
                align-content: center;
                gap: 5px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #ffffff;
                color: #14213d;
                text-align: center;
                box-shadow: 0 10px 22px rgba(20, 33, 61, 0.06);
            }

            .bank-wallet-grid strong {
                display: grid;
                place-items: center;
                width: 28px;
                height: 28px;
                border-radius: 10px;
                background: #eef4ff;
                color: #1436c8;
                font-size: 12px;
            }

            .bank-wallet-grid span {
                font-size: 9px;
                font-weight: 900;
            }

            .bank-wallet-grid small {
                color: #667085;
                font-size: 8px;
                font-weight: 800;
            }

            .group-tour-screen .content {
                background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
            }

            .group-flow-steps {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                margin: 0 0 14px;
                padding: 4px 2px 12px;
                scrollbar-width: none;
            }

            .group-flow-steps::-webkit-scrollbar {
                display: none;
            }

            .group-flow-steps span {
                flex: 0 0 74px;
                display: grid;
                justify-items: center;
                gap: 5px;
                color: #98a2b3;
                font-size: 9px;
                font-weight: 900;
                text-align: center;
            }

            .group-flow-steps b {
                display: grid;
                place-items: center;
                width: 25px;
                height: 25px;
                border-radius: 999px;
                background: #eaf3ff;
                color: #667085;
            }

            .group-flow-steps .done b,
            .group-flow-steps .active b {
                background: #1436c8;
                color: #ffffff;
            }

            .group-flow-steps .active b {
                background: #ff6a00;
            }

            .group-tour-hero,
            .group-tour-photo {
                position: relative;
                overflow: hidden;
                min-height: 176px;
                border-radius: 28px;
                padding: 20px;
                color: #ffffff;
                background:
                    radial-gradient(circle at 80% 24%, rgba(255,255,255,0.55) 0 30px, transparent 31px),
                    linear-gradient(135deg, transparent 0 28%, rgba(255,255,255,0.92) 29% 43%, transparent 44%) 0 94px / 126px 84px repeat-x,
                    linear-gradient(135deg, #8ed0ff, #1c64d1 56%, #12368f);
                box-shadow: 0 18px 38px rgba(20, 33, 61, 0.12);
            }

            .group-tour-hero::after,
            .group-tour-photo::after {
                content: "";
                position: absolute;
                right: 28px;
                bottom: 24px;
                width: 124px;
                height: 56px;
                border-radius: 24px 34px 12px 12px;
                background:
                    radial-gradient(circle at 30px 52px, #14213d 0 9px, transparent 10px),
                    radial-gradient(circle at 94px 52px, #14213d 0 9px, transparent 10px),
                    linear-gradient(90deg, #ffffff 0 54%, #0b74e5 55%);
            }

            .group-tour-hero span,
            .group-tour-photo span {
                position: relative;
                z-index: 1;
                display: block;
                margin-bottom: 5px;
                font-size: 13px;
                font-weight: 900;
            }

            .group-tour-hero h2 {
                position: relative;
                z-index: 1;
                margin: 0 0 5px;
                font-size: 28px;
                line-height: 1.05;
            }

            .group-tour-hero p,
            .group-tour-photo small {
                position: relative;
                z-index: 1;
                max-width: 210px;
                color: rgba(255,255,255,0.88);
                font-size: 12px;
                font-weight: 800;
            }

            .group-category-row {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                margin: 12px 0;
            }

            .group-category-row a {
                flex: 0 0 auto;
                border-radius: 999px;
                background: #ffffff;
                color: #667085;
                padding: 8px 11px;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 8px 18px rgba(20,33,61,0.06);
            }

            .group-category-row a.active {
                background: #1436c8;
                color: #ffffff;
            }

            .group-tour-card {
                display: grid;
                grid-template-columns: 82px 1fr auto;
                gap: 12px;
                align-items: center;
                border: 1px solid var(--line);
                border-radius: 22px;
                background: #ffffff;
                padding: 12px;
                margin-bottom: 12px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .group-tour-card.compact {
                grid-template-columns: 72px 1fr;
            }

            .group-tour-thumb {
                width: 82px;
                height: 82px;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 74% 20%, rgba(255,255,255,0.9) 0 15px, transparent 16px),
                    linear-gradient(135deg, transparent 0 30%, rgba(255,255,255,0.92) 31% 45%, transparent 46%) 0 46px / 80px 56px repeat-x,
                    linear-gradient(180deg, #73b9ff, #1b65d8);
            }

            .group-tour-card h3 {
                margin: 3px 0;
                color: #14213d;
                font-size: 13px;
            }

            .group-tour-card p {
                margin: 0 0 8px;
                color: #667085;
                font-size: 10px;
                line-height: 1.35;
                font-weight: 800;
            }

            .group-tour-card a {
                color: #1436c8;
                font-size: 10px;
                font-weight: 900;
            }

            .group-tour-card > strong {
                text-align: right;
                color: #14213d;
                font-size: 12px;
            }

            .group-tour-card > strong small {
                display: block;
                color: #667085;
                font-size: 8px;
            }

            .group-tour-badge {
                border-radius: 999px;
                background: #e8f8ee;
                color: #16a34a;
                padding: 4px 7px;
                font-size: 8px;
                font-weight: 900;
            }

            .group-info-grid,
            .group-live-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
                margin: 12px 0;
            }

            .group-info-grid span,
            .group-live-grid a {
                display: grid;
                place-items: center;
                min-height: 62px;
                border-radius: 16px;
                background: #f7faff;
                color: #14213d;
                text-align: center;
                padding: 8px;
                font-size: 9px;
                font-weight: 900;
            }

            .group-info-grid small {
                color: #667085;
                font-size: 8px;
            }

            .group-highlight-list,
            .group-itinerary-list {
                display: grid;
                gap: 8px;
                margin-bottom: 14px;
            }

            .group-highlight-list span,
            .group-itinerary-list span {
                border: 1px solid var(--line);
                border-radius: 15px;
                background: #ffffff;
                padding: 10px 12px;
                color: #14213d;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 8px 18px rgba(20,33,61,0.05);
            }

            .group-bus-card {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .group-bus-art {
                flex: 0 0 112px;
                height: 64px;
                border-radius: 20px;
                background:
                    radial-gradient(circle at 28px 58px, #14213d 0 9px, transparent 10px),
                    radial-gradient(circle at 88px 58px, #14213d 0 9px, transparent 10px),
                    linear-gradient(90deg, #ffffff 0 56%, #0b74e5 57%);
                box-shadow: inset 18px 12px 0 rgba(20, 116, 229, 0.12);
            }

            .seat-legend {
                display: flex;
                gap: 10px;
                color: #667085;
                font-size: 10px;
                font-weight: 900;
                margin-bottom: 10px;
            }

            .group-seat-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                border: 1px solid var(--line);
                border-radius: 24px;
                background: #ffffff;
                padding: 16px;
                margin-bottom: 14px;
            }

            .group-seat input {
                position: absolute;
                opacity: 0;
            }

            .group-seat span {
                display: grid;
                place-items: center;
                min-height: 38px;
                border: 1px solid #bbf7d0;
                border-radius: 10px;
                background: #dcfce7;
                color: #166534;
                font-size: 12px;
                font-weight: 900;
            }

            .group-seat input:checked + span,
            .group-seat.selected span {
                border-color: #1436c8;
                background: #1436c8;
                color: #ffffff;
            }

            .group-seat.booked span {
                border-color: #e5e7eb;
                background: #f2f4f7;
                color: #98a2b3;
            }

            .group-summary-card {
                display: grid;
                gap: 12px;
            }

            .group-success-card {
                display: grid;
                gap: 14px;
                border: 1px solid var(--line);
                border-radius: 28px;
                background: #ffffff;
                padding: 22px;
                text-align: center;
                box-shadow: 0 18px 38px rgba(20,33,61,0.1);
            }

            .group-success-check {
                display: grid;
                place-items: center;
                width: 74px;
                height: 74px;
                border-radius: 999px;
                margin: 0 auto;
                background: #16a34a;
                color: #ffffff;
                font-weight: 900;
            }

            .trip-home-screen .content,
            .booking-mobile-screen .content,
            .bookings-mobile-screen .content,
            .profile-mobile-screen .content,
            .chat-mobile-screen .content {
                padding: 18px 14px 118px;
                background: #f5f8fc;
            }

            .trip-home,
            .booking-mobile,
            .bookings-screen,
            .user-profile-screen,
            .driver-chat-screen {
                display: grid;
                gap: 14px;
                min-height: calc(100vh - 122px);
                grid-template-rows: auto auto minmax(0, 1fr) auto;
            }

            .trip-home-top,
            .module-mobile-head,
            .chat-mobile-head {
                display: grid;
                align-items: center;
                gap: 10px;
            }

            .trip-home-top {
                grid-template-columns: 1fr auto;
            }

            .trip-brand strong {
                display: block;
                color: #1436c8;
                font-size: 18px;
                line-height: 1.1;
            }

            .trip-brand strong span {
                color: #ff6a00;
            }

            .trip-brand small,
            .trip-dashboard-summary,
            .bookings-count-strip,
            .profile-identity small,
            .profile-identity em {
                color: #667085;
                font-size: 11px;
                font-weight: 800;
            }

            .trip-home-alert {
                position: relative;
                width: 38px;
                height: 38px;
                border: 1px solid #e2ecf7;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 10px 22px rgba(20, 33, 61, 0.07);
            }

            .trip-home-alert::before {
                content: "";
                position: absolute;
                left: 12px;
                top: 10px;
                width: 12px;
                height: 14px;
                border: 2px solid #14213d;
                border-radius: 10px 10px 6px 6px;
            }

            .trip-home-alert span {
                position: absolute;
                right: -4px;
                top: -4px;
                min-width: 17px;
                height: 17px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: #ff6a00;
                color: #ffffff;
                font-size: 8px;
                font-weight: 900;
            }

            .trip-hero-card {
                position: relative;
                min-height: 188px;
                overflow: hidden;
                display: flex;
                align-items: flex-end;
                border-radius: 24px;
                padding: 18px;
                color: #ffffff;
                background:
                    linear-gradient(180deg, rgba(11, 35, 70, 0.06), rgba(11, 35, 70, 0.72)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                box-shadow: 0 18px 38px rgba(20, 33, 61, 0.14);
            }

            .trip-hero-card strong,
            .trip-hero-card small,
            .trip-offer-banner strong,
            .trip-offer-banner small,
            .trip-offer-banner em {
                display: block;
            }

            .trip-hero-card strong {
                font-size: 25px;
                line-height: 1.05;
            }

            .trip-hero-card small {
                margin-top: 4px;
                color: rgba(255, 255, 255, 0.88);
                font-size: 12px;
                font-weight: 800;
            }

            .trip-search-bar,
            .restaurant-search-bar {
                min-height: 44px;
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 16px;
                background: #ffffff;
                padding: 0 8px 0 13px;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.06);
            }

            .trip-search-bar input,
            .restaurant-search-bar input {
                min-width: 0;
                border: 0;
                outline: 0;
                background: transparent;
                color: #14213d;
                font-size: 12px;
            }

            button.filter-btn {
                border: 0;
                cursor: pointer;
            }

            .trip-service-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
            }

            .trip-service-grid a {
                min-height: 82px;
                display: grid;
                align-content: center;
                justify-items: center;
                gap: 8px;
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                color: #14213d;
                text-align: center;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.07);
            }

            .trip-service-grid strong {
                max-width: 86px;
                font-size: 10px;
                line-height: 1.2;
            }

            .trip-offer-banner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                min-height: 104px;
                border-radius: 20px;
                padding: 16px;
                color: #ffffff;
                background:
                    linear-gradient(90deg, rgba(20, 54, 200, 0.92), rgba(11, 116, 229, 0.78)),
                    url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80') center/cover;
                box-shadow: 0 18px 34px rgba(20, 54, 200, 0.18);
            }

            .trip-offer-banner small,
            .trip-offer-banner em {
                color: rgba(255, 255, 255, 0.82);
                font-size: 10px;
                font-style: normal;
                font-weight: 800;
            }

            .trip-offer-banner strong {
                margin: 4px 0;
                font-size: 19px;
                line-height: 1.05;
            }

            .trip-offer-banner b {
                flex: 0 0 auto;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.18);
                border: 1px solid rgba(255, 255, 255, 0.32);
                padding: 8px 10px;
                font-size: 10px;
            }

            .trip-dashboard-summary,
            .bookings-count-strip,
            .profile-stats {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .trip-dashboard-summary strong,
            .trip-dashboard-summary a,
            .bookings-count-strip strong,
            .bookings-count-strip a {
                color: #1436c8;
                font-weight: 900;
            }

            .module-mobile-head {
                grid-template-columns: 34px 1fr 34px;
                min-height: 36px;
            }

            .module-mobile-head h2 {
                margin: 0;
                color: #14213d;
                font-size: 16px;
                text-align: center;
            }

            .back-link {
                position: relative;
                width: 34px;
                height: 34px;
                border-radius: 12px;
                background: #ffffff;
                border: 1px solid #e2ecf7;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.06);
            }

            .back-link::before {
                content: "";
                position: absolute;
                left: 13px;
                top: 11px;
                width: 9px;
                height: 9px;
                border-left: 2px solid #14213d;
                border-bottom: 2px solid #14213d;
                transform: rotate(45deg);
            }

            .booking-tabs,
            .booking-filter-tabs {
                display: flex;
                gap: 10px;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .booking-tabs::-webkit-scrollbar,
            .booking-filter-tabs::-webkit-scrollbar {
                display: none;
            }

            .booking-tabs span,
            .booking-filter-tabs a {
                flex: 0 0 auto;
                border-radius: 999px;
                color: #667085;
                font-size: 10px;
                font-weight: 900;
            }

            .booking-tabs span {
                padding: 7px 10px;
                background: #ffffff;
            }

            .booking-tabs span.active,
            .booking-filter-tabs a.active {
                background: #1436c8;
                color: #ffffff;
            }

            .booking-search-card {
                display: grid;
                gap: 11px;
                border: 1px solid #e2ecf7;
                border-radius: 20px;
                background: #ffffff;
                padding: 14px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .booking-search-card .field {
                margin: 0;
            }

            .booking-search-card .compact-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .booking-search-card label {
                color: #667085;
                font-size: 9px;
                font-weight: 900;
            }

            .booking-search-card input {
                min-height: 42px;
                border-radius: 12px;
                font-size: 12px;
            }

            .booking-search-card .btn {
                min-height: 42px;
                border-radius: 12px;
                font-size: 12px;
            }

            .mobile-section-head h3 {
                margin: 0;
                color: #14213d;
                font-size: 13px;
            }

            .mobile-result-list {
                display: grid;
                gap: 10px;
            }

            .mobile-result-card {
                display: grid;
                grid-template-columns: 66px 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 16px;
                background: #ffffff;
                padding: 9px;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.07);
            }

            .result-thumb,
            .result-vehicle,
            .restaurant-thumb {
                width: 66px;
                height: 56px;
                border-radius: 13px;
                background-size: cover;
                background-position: center;
            }

            .result-copy {
                min-width: 0;
                display: grid;
                gap: 3px;
            }

            .result-copy strong,
            .result-copy small,
            .result-copy em {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .result-copy strong {
                color: #14213d;
                font-size: 12px;
            }

            .result-copy small,
            .result-copy em {
                color: #667085;
                font-size: 9px;
                font-style: normal;
                font-weight: 800;
            }

            .mobile-result-card b {
                color: #14213d;
                font-size: 10px;
                text-align: right;
                white-space: nowrap;
            }

            .result-vehicle {
                position: relative;
                background: #eef6ff;
            }

            .result-vehicle::before {
                content: "";
                position: absolute;
                left: 11px;
                right: 11px;
                top: 18px;
                height: 20px;
                border-radius: 12px 12px 6px 6px;
                background: #0b74e5;
                box-shadow: inset 10px 0 0 rgba(255, 255, 255, 0.35);
            }

            .result-vehicle::after {
                content: "";
                position: absolute;
                left: 17px;
                right: 17px;
                bottom: 12px;
                height: 6px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 4px 3px, #14213d 0 3px, transparent 3.5px),
                    radial-gradient(circle at calc(100% - 4px) 3px, #14213d 0 3px, transparent 3.5px);
            }

            .bus-art::before {
                background: #16a34a;
            }

            .train-art::before {
                border-radius: 7px;
                background: #0b74e5;
            }

            .restaurant-thumb {
                background:
                    radial-gradient(circle at 44px 16px, rgba(255,255,255,0.9) 0 9px, transparent 10px),
                    linear-gradient(135deg, #ff8a1d, #f97316);
            }

            .restaurant-thumb-green {
                background:
                    radial-gradient(circle at 44px 16px, rgba(255,255,255,0.9) 0 9px, transparent 10px),
                    linear-gradient(135deg, #16a34a, #0b74e5);
            }

            .restaurant-thumb-blue {
                background:
                    radial-gradient(circle at 44px 16px, rgba(255,255,255,0.9) 0 9px, transparent 10px),
                    linear-gradient(135deg, #0b74e5, #1436c8);
            }

            .restaurant-thumb-dark {
                background:
                    radial-gradient(circle at 44px 16px, rgba(255,255,255,0.9) 0 9px, transparent 10px),
                    linear-gradient(135deg, #14213d, #475467);
            }

            .booking-filter-tabs a {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 8px 10px;
                background: #ffffff;
                border: 1px solid #e2ecf7;
            }

            .booking-filter-tabs a span {
                display: grid;
                place-items: center;
                min-width: 18px;
                height: 18px;
                border-radius: 999px;
                background: rgba(20, 33, 61, 0.08);
                font-size: 8px;
            }

            .bookings-count-strip {
                border: 1px solid #e2ecf7;
                border-radius: 16px;
                background: #ffffff;
                padding: 10px 12px;
            }

            .bookings-mobile-screen .section-title {
                margin-top: 4px;
            }

            .bookings-mobile-screen .section-title h2 {
                font-size: 13px;
            }

            .bookings-mobile-screen .trip-card {
                position: relative;
                display: grid;
                gap: 7px;
                border-radius: 16px;
                padding: 12px;
            }

            .bookings-mobile-screen .trip-card h3 {
                margin: 0;
                color: #14213d;
                font-size: 13px;
                line-height: 1.25;
            }

            .bookings-mobile-screen .trip-meta {
                margin: 0;
                gap: 4px;
                font-size: 10px;
            }

            .bookings-mobile-screen .trip-meta span {
                overflow-wrap: anywhere;
                line-height: 1.35;
            }

            .bookings-mobile-screen .trip-actions {
                margin-top: 3px;
            }

            .bookings-mobile-screen .trip-actions .btn {
                min-height: 34px;
                border-radius: 12px;
                font-size: 10px;
            }

            .profile-identity {
                display: grid;
                justify-items: center;
                gap: 5px;
                border-radius: 22px;
                background: #ffffff;
                padding: 18px 14px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .profile-photo,
            .chat-avatar {
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: linear-gradient(135deg, #0b74e5, #ff8a1d);
                color: #ffffff;
                font-weight: 900;
            }

            .profile-photo {
                width: 72px;
                height: 72px;
                font-size: 24px;
            }

            .profile-identity strong {
                color: #14213d;
                font-size: 16px;
            }

            .profile-identity em {
                font-style: normal;
            }

            .profile-stats span {
                flex: 1;
                display: grid;
                place-items: center;
                gap: 3px;
                border: 1px solid #e2ecf7;
                border-radius: 16px;
                background: #ffffff;
                padding: 11px;
            }

            .profile-stats strong {
                color: #1436c8;
                font-size: 16px;
            }

            .profile-stats small {
                color: #667085;
                font-size: 10px;
                font-weight: 900;
            }

            .profile-menu-list {
                display: grid;
                gap: 8px;
            }

            .profile-menu-list a {
                display: grid;
                grid-template-columns: 34px 1fr auto;
                align-items: center;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 15px;
                background: #ffffff;
                padding: 10px;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.05);
            }

            .profile-menu-list a::after {
                content: "";
                width: 7px;
                height: 7px;
                border-right: 2px solid #98a2b3;
                border-top: 2px solid #98a2b3;
                transform: rotate(45deg);
            }

            .profile-menu-list strong,
            .profile-menu-list small {
                display: block;
            }

            .profile-menu-list strong {
                color: #14213d;
                font-size: 12px;
            }

            .profile-menu-list small {
                color: #667085;
                font-size: 9px;
                font-weight: 800;
            }

            .profile-menu-icon {
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                border-radius: 12px;
                background: #eef6ff;
                color: #1436c8;
                font-size: 12px;
                font-weight: 900;
            }

            .profile-menu-icon::before { content: "i"; }
            .profile-payment::before { content: "$"; }
            .profile-address::before { content: "A"; }
            .profile-review::before { content: "*"; }
            .profile-bell::before { content: "!"; }
            .profile-help::before { content: "?"; }
            .profile-settings::before { content: "S"; }
            .profile-logout::before { content: "L"; }

            .profile-menu-list .logout-row strong,
            .profile-menu-list .logout-row .profile-menu-icon {
                color: #ef4444;
            }

            .profile-menu-list .logout-row .profile-menu-icon {
                background: #fff1f2;
            }

            .settings-dot {
                justify-self: end;
                width: 34px;
                height: 34px;
                border-radius: 12px;
                background:
                    radial-gradient(circle at center, #667085 0 2px, transparent 2.5px),
                    #ffffff;
                border: 1px solid #e2ecf7;
                box-shadow:
                    0 8px 18px rgba(20, 33, 61, 0.06),
                    inset 8px 0 0 transparent;
            }

            .chat-mobile-head {
                grid-template-columns: 34px 42px 1fr 34px 34px;
                min-height: 46px;
            }

            .chat-avatar {
                width: 42px;
                height: 42px;
            }

            .chat-driver-meta {
                min-width: 0;
                display: grid;
                gap: 2px;
            }

            .chat-driver-meta strong,
            .chat-driver-meta small {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .chat-driver-meta strong {
                color: #14213d;
                font-size: 13px;
            }

            .chat-driver-meta small {
                color: #667085;
                font-size: 9px;
                font-weight: 800;
            }

            .chat-action {
                position: relative;
                width: 34px;
                height: 34px;
                border-radius: 12px;
                background: #ffffff;
                border: 1px solid #e2ecf7;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.06);
            }

            .call-action::before {
                content: "";
                position: absolute;
                left: 12px;
                top: 9px;
                width: 8px;
                height: 14px;
                border: 2px solid #16a34a;
                border-left-width: 4px;
                border-radius: 8px;
                transform: rotate(-24deg);
            }

            .menu-action::before {
                content: "";
                position: absolute;
                left: 15px;
                top: 9px;
                width: 4px;
                height: 4px;
                border-radius: 999px;
                background: #667085;
                box-shadow: 0 7px 0 #667085, 0 14px 0 #667085;
            }

            .chat-trip-card {
                display: grid;
                gap: 4px;
                border: 1px solid #dbeafe;
                border-radius: 16px;
                background: #eef6ff;
                padding: 12px;
            }

            .chat-trip-card strong {
                color: #1436c8;
                font-size: 12px;
            }

            .chat-trip-card span,
            .chat-trip-card small {
                color: #475467;
                font-size: 10px;
                font-weight: 800;
            }

            .chat-select-captain-btn {
                min-height: 38px;
                display: inline-grid;
                place-items: center;
                justify-self: start;
                margin-top: 6px;
                border-radius: 13px;
                background: linear-gradient(135deg, #1457ff, #0b4bd8);
                color: #ffffff;
                padding: 0 14px;
                font-size: 11px;
                font-weight: 900;
                box-shadow: 0 12px 24px rgba(20, 87, 255, 0.18);
            }

            .chat-thread {
                display: grid;
                gap: 10px;
                align-content: end;
                min-height: 0;
                max-height: none;
                overflow-y: auto;
                padding: 8px 2px 20px;
                scroll-behavior: smooth;
            }

            .chat-thread-setup {
                min-height: 360px;
                align-content: center;
                overflow: visible;
            }

            .chat-bubble {
                max-width: 82%;
                display: grid;
                gap: 4px;
                border-radius: 16px;
                padding: 10px 12px;
                box-shadow: 0 8px 18px rgba(20, 33, 61, 0.06);
                word-break: break-word;
            }

            .chat-bubble p {
                margin: 0;
                font-size: 11px;
                line-height: 1.45;
            }

            .chat-bubble span {
                font-size: 8px;
                font-weight: 800;
            }

            .chat-bubble.driver {
                justify-self: start;
                background: #ffffff;
                color: #14213d;
                border: 1px solid #e7eef8;
                border-bottom-left-radius: 6px;
            }

            .chat-bubble.driver span {
                color: #98a2b3;
            }

            .chat-bubble.user {
                justify-self: end;
                background: linear-gradient(135deg, #1457ff, #0b4bd8);
                color: #ffffff;
                border-bottom-right-radius: 6px;
                box-shadow: 0 12px 24px rgba(20, 87, 255, 0.22);
            }

            .chat-bubble.user span {
                color: rgba(255, 255, 255, 0.76);
            }

            .chat-compose {
                position: relative;
                z-index: 3;
                display: grid;
                grid-template-columns: 1fr 38px;
                gap: 8px;
                align-items: center;
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                padding: 8px;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.09);
            }

            .chat-compose input {
                min-width: 0;
                min-height: 38px;
                border: 0;
                outline: 0;
                padding: 0 8px;
                color: #14213d;
                font-size: 12px;
                background: transparent;
            }

            .chat-compose button {
                position: relative;
                width: 38px;
                height: 38px;
                border: 0;
                border-radius: 999px;
                background: #1457ff;
                cursor: pointer;
            }

            .chat-compose button::before {
                content: "";
                position: absolute;
                left: 13px;
                top: 11px;
                width: 12px;
                height: 12px;
                border-top: 3px solid #ffffff;
                border-right: 3px solid #ffffff;
                transform: rotate(45deg);
            }

            .chat-compose.disabled {
                background: #f8fbff;
                opacity: 0.82;
            }

            .chat-compose input:disabled,
            .chat-compose button:disabled {
                cursor: not-allowed;
            }

            .chat-compose button:disabled {
                background: #98a2b3;
            }

            .chat-empty-state {
                align-self: center;
                justify-self: center;
                width: min(100%, 260px);
                display: grid;
                gap: 6px;
                border: 1px dashed #c7d7eb;
                border-radius: 20px;
                background:
                    radial-gradient(circle at 86% 8%, rgba(20, 87, 255, 0.08), transparent 35%),
                    #ffffff;
                padding: 18px;
                color: #667085;
                text-align: center;
                box-shadow: 0 10px 24px rgba(20, 33, 61, 0.05);
            }

            .chat-setup-state {
                border-style: solid;
                background:
                    radial-gradient(circle at 84% 8%, rgba(255, 138, 29, 0.12), transparent 35%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
            }

            .chat-setup-state .chat-select-captain-btn {
                justify-self: center;
                margin-top: 6px;
            }

            .chat-empty-state strong {
                color: #14213d;
                font-size: 13px;
            }

            .chat-empty-state span {
                font-size: 11px;
                line-height: 1.45;
            }

            .message-thread-list {
                display: grid;
                gap: 10px;
            }

            .message-thread-card {
                min-width: 0;
                display: grid;
                grid-template-columns: 44px minmax(0, 1fr) auto;
                align-items: center;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                padding: 11px;
                box-shadow: 0 12px 24px rgba(20, 33, 61, 0.07);
            }

            .message-thread-avatar {
                width: 44px;
                height: 44px;
                display: grid;
                place-items: center;
                border-radius: 16px;
                background: linear-gradient(135deg, #1457ff, #ff8a1d);
                color: #ffffff;
                font-size: 14px;
                font-weight: 900;
                box-shadow: 0 10px 18px rgba(20, 87, 255, 0.18);
            }

            .message-thread-copy {
                min-width: 0;
                display: grid;
                gap: 3px;
            }

            .message-thread-copy strong,
            .message-thread-copy small,
            .message-thread-copy em {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .message-thread-copy strong {
                color: #14213d;
                font-size: 12px;
            }

            .message-thread-copy small {
                color: #475467;
                font-size: 11px;
                font-weight: 800;
            }

            .message-thread-copy em {
                color: #98a2b3;
                font-size: 9px;
                font-style: normal;
                font-weight: 900;
            }

            .message-thread-meta {
                display: grid;
                justify-items: end;
                gap: 7px;
            }

            .message-thread-meta small {
                color: #98a2b3;
                font-size: 9px;
                font-weight: 900;
            }

            .message-thread-meta b {
                min-width: 22px;
                height: 22px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: #1457ff;
                color: #ffffff;
                font-size: 10px;
            }

            .captain-mobile-screen .content {
                padding: 18px 14px 104px;
                background: #f5f8fc;
            }

            .captain-mobile {
                display: grid;
                gap: 14px;
            }

            .captain-mobile-head,
            .captain-dashboard-top {
                display: grid;
                align-items: center;
                gap: 10px;
            }

            .captain-mobile-head {
                grid-template-columns: 34px 1fr 34px;
                min-height: 36px;
            }

            .captain-mobile-head h2 {
                margin: 0;
                color: #14213d;
                font-size: 16px;
                text-align: center;
            }

            .captain-dashboard-top {
                grid-template-columns: minmax(0, 1fr) auto;
            }

            .captain-brand {
                display: grid;
                grid-template-columns: 30px minmax(0, 1fr);
                align-items: center;
                column-gap: 8px;
                text-decoration: none;
                min-width: 0;
            }

            .captain-brand::before {
                content: "";
                grid-row: 1 / span 2;
                width: 30px;
                height: 30px;
                border-radius: 10px;
                background:
                    radial-gradient(circle at 50% 34%, #ffffff 0 4px, transparent 5px),
                    linear-gradient(180deg, #1457ff 0 48%, #0b2cc9 49% 100%);
                box-shadow: 0 10px 18px rgba(20, 87, 255, 0.18);
            }

            .captain-brand strong {
                display: block;
                grid-column: 2;
                color: #1436c8;
                font-size: 17px;
                line-height: 1.1;
                letter-spacing: 0;
            }

            .captain-brand strong span {
                color: #ff6a00;
            }

            .captain-brand small {
                grid-column: 2;
                color: #667085;
                font-size: 8.5px;
                font-weight: 900;
                line-height: 1.2;
                white-space: nowrap;
            }

            .captain-home-card,
            .captain-request-card,
            .captain-detail-hero,
            .captain-pickup-card,
            .captain-nav-card,
            .captain-passenger-lead,
            .captain-list-card,
            .captain-breakdown-card {
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.08);
            }

            .captain-home-card {
                display: grid;
                grid-template-columns: 54px minmax(0, 1fr) 40px;
                align-items: center;
                gap: 10px;
                padding: 11px;
            }

            .captain-home-card .profile-photo {
                width: 54px;
                height: 54px;
                font-size: 20px;
            }

            .captain-home-card > div {
                min-width: 0;
            }

            .captain-home-card strong,
            .captain-request-main strong,
            .captain-detail-hero strong,
            .captain-chat-title strong,
            .captain-passenger-lead strong {
                color: #14213d;
                font-size: 13px;
            }

            .captain-home-card strong {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .captain-home-card small,
            .captain-home-card em,
            .captain-request-main small,
            .captain-request-main span,
            .captain-detail-hero small,
            .captain-detail-hero em,
            .captain-chat-title small {
                display: block;
                color: #667085;
                font-size: 10px;
                font-style: normal;
                font-weight: 800;
                line-height: 1.35;
            }

            .captain-toggle {
                position: relative;
                justify-self: end;
                width: 40px;
                height: 22px;
                border-radius: 999px;
                background: #d0d8e8;
            }

            .captain-toggle::after {
                content: "";
                position: absolute;
                top: 3px;
                left: 3px;
                width: 16px;
                height: 16px;
                border-radius: 999px;
                background: #ffffff;
                box-shadow: 0 2px 6px rgba(20,33,61,0.16);
            }

            .captain-toggle.active {
                background: #16a34a;
            }

            .captain-toggle.active::after {
                left: 21px;
            }

            .captain-earning-card,
            .captain-wallet-hero {
                display: grid;
                gap: 4px;
                border-radius: 20px;
                padding: 16px;
                color: #ffffff;
                background:
                    linear-gradient(135deg, #1457ff, #0b2cc9),
                    radial-gradient(circle at 85% 22%, rgba(255,255,255,0.45), transparent 28%);
                box-shadow: 0 18px 34px rgba(20, 54, 200, 0.22);
            }

            .captain-earning-card span,
            .captain-earning-card small,
            .captain-wallet-hero small,
            .captain-wallet-hero span {
                color: rgba(255,255,255,0.82);
                font-size: 10px;
                font-weight: 800;
            }

            .captain-earning-card strong,
            .captain-wallet-hero strong {
                font-size: 25px;
                line-height: 1;
            }

            .captain-action-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 9px;
            }

            .captain-action-grid a,
            .captain-passenger-row a,
            .captain-contact-actions a,
            .captain-nav-actions a {
                display: grid;
                place-items: center;
                border: 1px solid #e2ecf7;
                border-radius: 15px;
                background: #ffffff;
                color: #14213d;
                text-align: center;
                font-size: 9px;
                font-weight: 900;
                box-shadow: 0 10px 22px rgba(20,33,61,0.06);
            }

            .captain-action-grid a {
                min-height: 74px;
                gap: 7px;
            }

            .dash-ico-wallet::before {
                width: 18px;
                height: 13px;
                border: 2px solid #16a34a;
                border-radius: 5px;
            }

            .dash-ico-wallet::after {
                width: 6px;
                height: 6px;
                left: 60%;
                border-radius: 999px;
                background: #16a34a;
            }

            .dash-ico-chat::before {
                width: 18px;
                height: 14px;
                border: 2px solid #0b74e5;
                border-radius: 6px;
            }

            .dash-ico-chat::after {
                width: 7px;
                height: 7px;
                left: 41%;
                top: 66%;
                border-left: 2px solid #0b74e5;
                border-bottom: 2px solid #0b74e5;
                transform: rotate(-20deg);
            }

            .captain-action-grid .captain-quick-icon {
                position: relative;
                width: 34px;
                height: 34px;
                overflow: visible;
                border-radius: 12px;
                background: #eef6ff;
                box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
            }

            .captain-action-grid .captain-quick-icon::before,
            .captain-action-grid .captain-quick-icon::after {
                content: "";
                position: absolute;
                box-sizing: border-box;
            }

            .captain-quick-request {
                background: #fff1e4;
                color: #ff6a00;
            }

            .captain-quick-request::before {
                left: 7px;
                top: 10px;
                width: 20px;
                height: 14px;
                border-radius: 8px 8px 5px 5px;
                background: currentColor;
                box-shadow:
                    3px 8px 0 -2px #14213d,
                    16px 8px 0 -2px #14213d;
            }

            .captain-quick-request::after {
                right: 6px;
                top: 4px;
                width: 12px;
                height: 12px;
                border-radius: 999px;
                background: #ffffff;
                box-shadow: inset -6px 0 0 currentColor;
            }

            .captain-quick-trips {
                background: #eef6ff;
                color: #0b74e5;
            }

            .captain-quick-trips::before {
                left: 8px;
                top: 12px;
                width: 19px;
                height: 15px;
                border-radius: 5px;
                background: currentColor;
            }

            .captain-quick-trips::after {
                left: 13px;
                top: 7px;
                width: 9px;
                height: 7px;
                border: 2px solid currentColor;
                border-bottom: 0;
                border-radius: 8px 8px 0 0;
            }

            .captain-quick-message {
                background: #eef6ff;
                color: #1457ff;
            }

            .captain-quick-message::before {
                left: 8px;
                top: 8px;
                width: 19px;
                height: 16px;
                border: 2px solid currentColor;
                border-radius: 7px;
                background: #ffffff;
            }

            .captain-quick-message::after {
                left: 12px;
                top: 22px;
                width: 8px;
                height: 8px;
                border-left: 2px solid currentColor;
                border-bottom: 2px solid currentColor;
                transform: rotate(-22deg);
            }

            .captain-quick-earning {
                background: #eafaf1;
                color: #16a34a;
            }

            .captain-quick-earning::before {
                left: 8px;
                top: 8px;
                width: 19px;
                height: 19px;
                border-radius: 999px;
                background: currentColor;
            }

            .captain-quick-earning::after {
                content: "$";
                left: 0;
                top: 0;
                width: 34px;
                height: 34px;
                display: grid;
                place-items: center;
                color: #ffffff;
                font-size: 15px;
                font-weight: 900;
            }

            .captain-quick-wallet {
                background: #fff1e4;
                color: #ff8a1d;
            }

            .captain-quick-wallet::before {
                left: 7px;
                top: 10px;
                width: 21px;
                height: 17px;
                border-radius: 6px;
                background: currentColor;
                box-shadow: inset 0 5px 0 rgba(255,255,255,.34);
            }

            .captain-quick-wallet::after {
                right: 6px;
                top: 15px;
                width: 9px;
                height: 8px;
                border-radius: 999px 0 0 999px;
                background: #ffffff;
                box-shadow: inset 3px 0 0 rgba(20,33,61,.16);
            }

            .captain-quick-reward {
                background: #ecfdf3;
                color: #16a34a;
            }

            .captain-quick-reward::before {
                left: 9px;
                top: 7px;
                width: 17px;
                height: 21px;
                border-radius: 9px 9px 7px 7px;
                background: currentColor;
                clip-path: polygon(50% 0, 88% 13%, 88% 50%, 50% 100%, 12% 50%, 12% 13%);
            }

            .captain-quick-reward::after {
                left: 13px;
                top: 13px;
                width: 9px;
                height: 6px;
                border-left: 2px solid #ffffff;
                border-bottom: 2px solid #ffffff;
                transform: rotate(-45deg);
            }

            .captain-quick-profile {
                background: #eef6ff;
                color: #1457ff;
            }

            .captain-quick-profile::before {
                left: 12px;
                top: 7px;
                width: 10px;
                height: 10px;
                border-radius: 999px;
                background: currentColor;
            }

            .captain-quick-profile::after {
                left: 8px;
                top: 19px;
                width: 18px;
                height: 10px;
                border-radius: 12px 12px 4px 4px;
                background: currentColor;
            }

            .captain-request-card {
                display: grid;
                grid-template-columns: 72px 1fr auto;
                gap: 10px;
                align-items: center;
                padding: 10px;
            }

            .captain-empty-state {
                display: grid;
                justify-items: center;
                gap: 9px;
                border: 1px dashed #c7d7eb;
                border-radius: 18px;
                background:
                    radial-gradient(circle at 78% 12%, rgba(255, 138, 29, 0.12), transparent 32%),
                    linear-gradient(180deg, #ffffff, #f8fbff);
                padding: 24px 18px;
                color: #667085;
                text-align: center;
                box-shadow: 0 14px 30px rgba(20, 33, 61, 0.07);
            }

            .captain-empty-icon {
                position: relative;
                width: 52px;
                height: 52px;
                border-radius: 18px;
                background: #eef6ff;
            }

            .captain-empty-icon::before,
            .captain-empty-icon::after {
                content: "";
                position: absolute;
                left: 14px;
                right: 14px;
                border-radius: 999px;
                background: #0b74e5;
            }

            .captain-empty-icon::before {
                top: 17px;
                height: 4px;
                box-shadow: 0 8px 0 rgba(11, 116, 229, 0.35);
            }

            .captain-empty-icon::after {
                bottom: 14px;
                height: 4px;
                width: 16px;
                right: auto;
            }

            .captain-empty-state strong {
                color: #14213d;
                font-size: 17px;
                line-height: 1.15;
            }

            .captain-empty-state small {
                max-width: 230px;
                color: #667085;
                font-size: 11px;
                font-weight: 800;
                line-height: 1.45;
            }

            .captain-empty-state .btn {
                width: min(100%, 220px);
                min-height: 38px;
                border-radius: 12px;
                font-size: 10px;
            }

            .captain-card-actions {
                grid-column: 1 / -1;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .captain-card-actions form {
                display: grid;
            }

            .captain-card-actions .btn {
                min-height: 38px;
                border-radius: 12px;
                font-size: 10px;
            }

            .captain-trip-thumb {
                width: 72px;
                height: 66px;
                border-radius: 15px;
                background:
                    radial-gradient(circle at 74% 20%, rgba(255,255,255,0.9) 0 12px, transparent 13px),
                    linear-gradient(135deg, transparent 0 31%, rgba(255,255,255,0.86) 32% 45%, transparent 46%) 0 34px / 84px 58px repeat-x,
                    linear-gradient(180deg, #75c5ff, #1b65d8);
            }

            .captain-trip-thumb.large {
                width: 92px;
                height: 86px;
            }

            .captain-request-main {
                min-width: 0;
                display: grid;
                gap: 3px;
            }

            .captain-request-main strong,
            .captain-request-main small,
            .captain-request-main span {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .captain-request-price {
                display: grid;
                justify-items: end;
                gap: 3px;
                color: #14213d;
                font-size: 11px;
                font-weight: 900;
                text-align: right;
            }

            .captain-request-price small {
                color: #667085;
                font-size: 8px;
            }

            .captain-detail-hero {
                display: grid;
                grid-template-columns: 92px 1fr;
                gap: 12px;
                padding: 12px;
                align-items: center;
            }

            .captain-info-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px;
            }

            .captain-info-grid span {
                display: grid;
                gap: 4px;
                border: 1px solid #e2ecf7;
                border-radius: 15px;
                background: #ffffff;
                padding: 10px;
                box-shadow: 0 8px 18px rgba(20,33,61,0.05);
            }

            .captain-info-grid small {
                color: #667085;
                font-size: 9px;
                font-weight: 900;
            }

            .captain-info-grid strong {
                color: #14213d;
                font-size: 12px;
                line-height: 1.25;
            }

            .captain-breakdown-card {
                display: grid;
                gap: 9px;
                padding: 14px;
            }

            .captain-breakdown-card h3,
            .captain-list-card h3 {
                margin: 0;
                color: #14213d;
                font-size: 13px;
            }

            .captain-breakdown-card div {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                color: #667085;
                font-size: 11px;
                font-weight: 800;
            }

            .captain-breakdown-card strong {
                color: #14213d;
                text-align: right;
            }

            .captain-breakdown-card .total {
                border-top: 1px solid #e2ecf7;
                padding-top: 9px;
                color: #1436c8;
            }

            .captain-breakdown-card .total strong {
                color: #1436c8;
            }

            .captain-inclusion-row,
            .advance-timeline,
            .captain-passenger-row,
            .captain-contact-actions,
            .captain-nav-actions,
            .captain-action-row {
                display: grid;
                gap: 8px;
            }

            .captain-inclusion-row {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .captain-inclusion-row span {
                display: grid;
                place-items: center;
                min-height: 46px;
                border-radius: 13px;
                background: #eef6ff;
                color: #1436c8;
                padding: 7px;
                text-align: center;
                font-size: 8px;
                font-weight: 900;
            }

            .captain-confetti-card,
            .captain-scenic-card {
                position: relative;
                overflow: hidden;
                display: grid;
                justify-items: center;
                gap: 5px;
                min-height: 178px;
                align-content: center;
                border-radius: 24px;
                color: #14213d;
                background:
                    radial-gradient(circle at 18% 18%, #ff8a1d 0 2px, transparent 3px),
                    radial-gradient(circle at 82% 24%, #16a34a 0 2px, transparent 3px),
                    radial-gradient(circle at 66% 72%, #1457ff 0 2px, transparent 3px),
                    #ffffff;
                box-shadow: 0 16px 34px rgba(20,33,61,0.09);
            }

            .captain-group-avatar {
                width: 68px;
                height: 68px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 28px 24px, #ffd0a8 0 15px, transparent 16px),
                    radial-gradient(circle at 44px 24px, #f5b386 0 15px, transparent 16px),
                    linear-gradient(135deg, #0b74e5, #ff8a1d);
            }

            .captain-pickup-card {
                display: grid;
                gap: 9px;
                padding: 12px;
            }

            .captain-pickup-card div {
                display: grid;
                gap: 3px;
            }

            .captain-pickup-card small,
            .captain-note-card {
                color: #667085;
                font-size: 10px;
                font-weight: 800;
            }

            .captain-pickup-card strong {
                color: #14213d;
                font-size: 12px;
            }

            .captain-note-card {
                border: 1px solid #dbeafe;
                border-radius: 15px;
                background: #eef6ff;
                padding: 11px;
                line-height: 1.45;
            }

            .captain-note-card.success {
                border-color: #bbf7d0;
                background: #f0fdf4;
                color: #15803d;
            }

            .advance-timeline {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .advance-timeline span {
                display: grid;
                place-items: center;
                min-height: 46px;
                border-radius: 13px;
                background: #ffffff;
                color: #98a2b3;
                font-size: 8px;
                font-weight: 900;
            }

            .advance-timeline span.done {
                background: #e8f8ee;
                color: #16a34a;
            }

            .advance-timeline span.active {
                background: #eef6ff;
                color: #1436c8;
            }

            .captain-map-card {
                position: relative;
                height: 324px;
                min-height: 324px;
                overflow: hidden;
                border: 1px solid #d9e8f8;
                border-radius: 18px;
                background: #eaf4ff;
                box-shadow:
                    inset 0 0 0 1px rgba(255, 255, 255, 0.76),
                    0 14px 28px rgba(20, 33, 61, 0.06);
            }

            .captain-leaflet-map {
                position: absolute;
                inset: 0;
                z-index: 0;
                width: 100%;
                height: 100%;
            }

            .captain-map-card .leaflet-container {
                width: 100%;
                height: 100%;
                background: #eaf4ff;
                font-family: inherit;
            }

            .captain-map-card .leaflet-control-container {
                display: none;
            }

            .captain-map-pin {
                position: relative;
                width: 24px;
                height: 24px;
                background: transparent;
            }

            .captain-map-pin::before {
                content: "";
                position: absolute;
                inset: 0;
                border-radius: 50% 50% 50% 5px;
                transform: rotate(-45deg);
                background: #16a34a;
                box-shadow: 0 10px 18px rgba(20, 33, 61, 0.22);
            }

            .captain-map-pin::after {
                content: "";
                position: absolute;
                inset: 7px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.58);
            }

            .captain-map-pin-pickup::before {
                background: #16a34a;
            }

            .captain-map-pin-drop::before {
                background: #ef4444;
            }

            .captain-map-card b {
                position: absolute;
                left: 26px;
                top: 158px;
                z-index: 3;
                border-radius: 999px;
                background: #ffffff;
                color: #14213d;
                padding: 8px 10px;
                font-size: 9px;
                box-shadow: 0 10px 22px rgba(20,33,61,0.1);
            }

            .captain-nav-card {
                display: grid;
                grid-template-columns: 42px 1fr;
                gap: 10px;
                align-items: center;
                padding: 11px;
            }

            .captain-avatar-mini {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: linear-gradient(135deg, #0b74e5, #ff8a1d);
                color: #ffffff;
                font-weight: 900;
            }

            .captain-nav-card strong,
            .captain-nav-card small {
                display: block;
            }

            .captain-nav-card strong {
                color: #14213d;
                font-size: 12px;
            }

            .captain-nav-card small {
                color: #667085;
                font-size: 10px;
                font-weight: 800;
            }

            .captain-nav-actions,
            .captain-passenger-row,
            .captain-contact-actions,
            .captain-action-row {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .captain-nav-actions a,
            .captain-passenger-row a,
            .captain-contact-actions a {
                min-height: 46px;
            }

            .captain-action-row {
                grid-template-columns: 1fr 1fr;
            }

            .captain-scenic-card {
                justify-items: start;
                align-content: end;
                min-height: 170px;
                padding: 18px;
                color: #ffffff;
                background:
                    linear-gradient(180deg, rgba(20,33,61,0.02), rgba(20,33,61,0.62)),
                    url('https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=900&q=80') center/cover;
            }

            .captain-scenic-card strong {
                font-size: 18px;
            }

            .captain-scenic-card small {
                color: rgba(255,255,255,0.86);
                font-weight: 800;
            }

            .captain-progress-list {
                display: grid;
                gap: 8px;
            }

            .captain-progress-list span {
                border: 1px solid #e2ecf7;
                border-radius: 14px;
                background: #ffffff;
                color: #667085;
                padding: 11px 12px;
                font-size: 11px;
                font-weight: 900;
            }

            .captain-progress-list span.done {
                color: #16a34a;
                background: #f0fdf4;
                border-color: #bbf7d0;
            }

            .captain-progress-list span.active {
                color: #1436c8;
                background: #eef6ff;
                border-color: #bfdbfe;
            }

            .captain-passenger-lead {
                display: grid;
                gap: 5px;
                padding: 14px;
                text-align: center;
            }

            .captain-passenger-lead small,
            .captain-passenger-lead span {
                color: #667085;
                font-size: 10px;
                font-weight: 800;
            }

            .captain-list-card {
                display: grid;
                gap: 8px;
                padding: 12px;
            }

            .captain-list-card a {
                display: grid;
                grid-template-columns: 28px 1fr auto;
                align-items: center;
                gap: 8px;
                border-radius: 12px;
                background: #f7fbff;
                padding: 9px;
            }

            .captain-list-card a span {
                display: grid;
                place-items: center;
                width: 28px;
                height: 28px;
                border-radius: 999px;
                background: #eef6ff;
                color: #1436c8;
                font-size: 10px;
                font-weight: 900;
            }

            .captain-list-card a strong {
                color: #14213d;
                font-size: 11px;
            }

            .captain-list-card a small {
                color: #667085;
                font-size: 9px;
                font-weight: 800;
            }

            .captain-chat-title {
                display: grid;
                gap: 3px;
                border-radius: 16px;
                background: #ffffff;
                padding: 12px;
                box-shadow: 0 10px 22px rgba(20,33,61,0.06);
            }

            .captain-chat-thread {
                display: grid;
                gap: 10px;
                align-content: end;
                min-height: 360px;
                max-height: none;
                overflow-y: auto;
                padding: 8px 2px 20px;
                scroll-behavior: smooth;
            }

            .captain-compose {
                position: relative;
            }

            .captain-wallet-hero {
                align-items: start;
            }

            .analytics-hero {
                background: linear-gradient(135deg, #0f766e, #1457ff);
            }

            .captain-chart-card {
                min-height: 190px;
                display: flex;
                align-items: end;
                gap: 10px;
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                padding: 18px;
                box-shadow: 0 14px 30px rgba(20,33,61,0.08);
            }

            .captain-chart-card span {
                flex: 1;
                min-height: 26px;
                border-radius: 999px 999px 4px 4px;
                background: linear-gradient(180deg, #ff8a1d, #1457ff);
            }

            .captain-rewards-screen .content {
                background: #f5f8fc;
            }

            .captain-rewards-top {
                display: grid;
                grid-template-columns: 34px 1fr 38px;
                align-items: center;
                gap: 10px;
            }

            .captain-rewards-top > strong {
                color: #ffffff;
                text-align: center;
                font-size: 16px;
            }

            .captain-rewards-page {
                margin: -18px -14px -104px;
                padding: 18px 14px 104px;
                background: linear-gradient(180deg, #071333 0 252px, #f5f8fc 253px);
            }

            .captain-reward-hero {
                position: relative;
                min-height: 148px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                color: #ffffff;
                padding: 8px 0 12px;
            }

            .captain-reward-hero small,
            .captain-reward-hero strong {
                display: block;
            }

            .captain-reward-hero small {
                color: rgba(255,255,255,0.78);
                font-size: 12px;
                font-weight: 900;
            }

            .captain-reward-hero strong {
                max-width: 210px;
                margin-top: 5px;
                font-size: 22px;
                line-height: 1.15;
            }

            .captain-reward-hero b,
            .reward-how-card b {
                position: relative;
                flex: 0 0 auto;
                width: 88px;
                height: 88px;
                border-radius: 24px;
                background:
                    radial-gradient(circle at 24px 24px, #ffd166 0 12px, transparent 13px),
                    radial-gradient(circle at 62px 24px, #ff8a1d 0 10px, transparent 11px),
                    linear-gradient(135deg, #7c3aed, #ff4d6d);
                box-shadow: 0 16px 32px rgba(0,0,0,0.22);
            }

            .captain-reward-hero b::before,
            .reward-how-card b::before {
                content: "";
                position: absolute;
                left: 29px;
                top: 16px;
                width: 30px;
                height: 58px;
                border-radius: 8px;
                background:
                    linear-gradient(90deg, transparent 0 40%, #ffd166 41% 58%, transparent 59%),
                    linear-gradient(#ff4d6d 0 42%, #7c3aed 43%);
            }

            .captain-reward-hero b::after,
            .reward-how-card b::after {
                content: "";
                position: absolute;
                left: 18px;
                top: 26px;
                width: 52px;
                height: 14px;
                border-radius: 5px;
                background: #ffd166;
            }

            .reward-journey-card,
            .reward-milestone-card,
            .reward-how-card {
                border: 1px solid #e2ecf7;
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 12px 26px rgba(20,33,61,0.08);
            }

            .reward-journey-card {
                display: grid;
                gap: 12px;
                padding: 14px;
                margin-top: -24px;
            }

            .reward-journey-card h3,
            .reward-how-card h3 {
                margin: 0;
                color: #14213d;
                font-size: 13px;
            }

            .reward-progress-head {
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 12px;
            }

            .reward-progress-head span {
                display: grid;
                gap: 2px;
            }

            .reward-progress-head strong {
                color: #1436c8;
                font-size: 20px;
                line-height: 1;
            }

            .reward-progress-head small,
            .reward-progress-head b,
            .reward-journey-card a {
                color: #667085;
                font-size: 9px;
                font-weight: 900;
            }

            .reward-journey-card a {
                justify-self: end;
                color: #1457ff;
            }

            .reward-progress-bar {
                height: 8px;
                overflow: hidden;
                border-radius: 999px;
                background: #e8eef8;
            }

            .reward-progress-bar span {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #1457ff, #22c55e);
            }

            .reward-milestone-list {
                display: grid;
                gap: 10px;
            }

            .reward-milestone-card {
                display: grid;
                grid-template-columns: 58px 1fr auto auto;
                align-items: center;
                gap: 9px;
                padding: 9px;
            }

            .reward-level {
                min-height: 58px;
                display: grid;
                place-items: center;
                align-content: center;
                border-radius: 14px;
                color: #ffffff;
                text-align: center;
            }

            .reward-level strong {
                font-size: 18px;
                line-height: 1;
            }

            .reward-level small {
                margin-top: 3px;
                font-size: 7px;
                font-weight: 900;
                text-transform: uppercase;
            }

            .reward-purple { background: #8b5cf6; }
            .reward-indigo { background: #7c3aed; }
            .reward-blue { background: #3b82f6; }
            .reward-cyan { background: #06b6d4; }
            .reward-green { background: #22c55e; }
            .reward-mint { background: #34d399; }
            .reward-yellow { background: #facc15; }
            .reward-amber { background: #fb923c; }
            .reward-orange { background: #f97316; }
            .reward-red { background: #ef4444; }
            .reward-rose { background: #e11d48; }

            .reward-items {
                min-width: 0;
                display: flex;
                gap: 7px;
                overflow: hidden;
            }

            .reward-item {
                position: relative;
                flex: 0 0 26px;
                width: 26px;
                height: 34px;
                border-radius: 8px;
                background: #eef6ff;
            }

            .reward-item::before,
            .reward-item::after {
                content: "";
                position: absolute;
                box-sizing: border-box;
            }

            .reward-item-1::before {
                left: 9px;
                top: 5px;
                width: 8px;
                height: 24px;
                border-radius: 5px;
                background: #1457ff;
                box-shadow: inset 0 4px 0 rgba(255,255,255,0.45);
            }

            .reward-item-2::before {
                left: 5px;
                top: 8px;
                width: 16px;
                height: 18px;
                border-radius: 5px 5px 8px 8px;
                background: #14213d;
            }

            .reward-item-3::before {
                left: 7px;
                top: 8px;
                width: 12px;
                height: 12px;
                border: 2px solid #ff8a1d;
                border-radius: 999px;
            }

            .reward-item-3::after {
                left: 12px;
                top: 20px;
                width: 3px;
                height: 10px;
                background: #ff8a1d;
            }

            .reward-item-4::before {
                left: 5px;
                top: 9px;
                width: 16px;
                height: 16px;
                border-radius: 999px;
                background: #111827;
                box-shadow: 0 0 0 3px #dbeafe inset;
            }

            .reward-item-5::before {
                left: 5px;
                top: 11px;
                width: 16px;
                height: 12px;
                border-radius: 4px;
                background: #22c55e;
            }

            .reward-count {
                color: #667085;
                font-size: 8px;
                font-weight: 900;
                white-space: nowrap;
            }

            .reward-milestone-card > a {
                border: 1px solid #dbeafe;
                border-radius: 999px;
                background: #eef6ff;
                color: #1457ff;
                padding: 6px 8px;
                font-size: 8px;
                font-weight: 900;
            }

            .reward-how-card {
                display: grid;
                grid-template-columns: 1fr 72px;
                gap: 10px;
                align-items: center;
                padding: 13px;
            }

            .reward-how-card span {
                display: block;
                position: relative;
                margin-top: 7px;
                padding-left: 13px;
                color: #667085;
                font-size: 9px;
                font-weight: 800;
            }

            .reward-how-card span::before {
                content: "";
                position: absolute;
                left: 0;
                top: 6px;
                width: 5px;
                height: 5px;
                border-radius: 999px;
                background: #1457ff;
            }

            .reward-how-card b {
                width: 72px;
                height: 72px;
                box-shadow: none;
            }

            @media (max-width: 520px) {
                .app-shell {
                    padding: 0;
                    align-items: stretch;
                }

                .phone {
                    width: 100%;
                    min-height: 100vh;
                    max-height: none;
                    border: 0;
                    border-radius: 0;
                }

                .content {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .row {
                    grid-template-columns: 1fr;
                }
            }


            /* ==========================================================
               FINAL ASSIGNMENT UI MATCH LAYER
               Keeps all PHP/MySQL backend logic unchanged and only makes
               the screens closer to the provided TripNovaa mobile mockups.
               ========================================================== */
            :root {
                --tn-blue: #075bff;
                --tn-blue-dark: #053fc7;
                --tn-orange: #ff6b18;
                --tn-orange-2: #ff8a1d;
                --tn-ink: #0f1f3d;
                --tn-muted: #667085;
                --tn-soft: #f3f8ff;
                --tn-border: #e7edf6;
            }

            body {
                background:
                    radial-gradient(circle at 50% 0%, rgba(11, 116, 229, 0.12), transparent 35%),
                    linear-gradient(180deg, #f7fbff 0%, #eef6ff 100%);
            }

            .app-shell {
                padding: 18px;
            }

            .phone {
                width: min(100%, 402px);
                min-height: min(870px, calc(100vh - 36px));
                max-height: calc(100vh - 36px);
                border: 7px solid #0b0d12;
                border-radius: 50px;
                background: #ffffff;
                box-shadow:
                    0 44px 110px rgba(15, 31, 61, 0.20),
                    0 18px 42px rgba(7, 91, 255, 0.10),
                    inset 0 0 0 1px rgba(255, 255, 255, 0.7);
            }

            .phone::before {
                content: "";
                position: absolute;
                top: 9px;
                left: 50%;
                z-index: 30;
                width: 88px;
                height: 26px;
                border-radius: 999px;
                background: #05070a;
                box-shadow: inset 18px 0 0 rgba(255,255,255,0.05), 0 2px 2px rgba(255,255,255,0.1);
                transform: translateX(-50%);
                pointer-events: none;
            }

            .screen {
                position: relative;
                background: #ffffff;
                border-radius: 42px;
                overflow: hidden;
            }

            .screen::before {
                content: "9:41";
                position: absolute;
                top: 12px;
                left: 22px;
                right: 22px;
                z-index: 24;
                color: #0d182e;
                font-size: 12px;
                font-weight: 900;
                letter-spacing: -0.02em;
                text-align: left;
                pointer-events: none;
            }

            .screen::after {
                content: "▮▮  Wi‑Fi  ▰";
                position: absolute;
                top: 12px;
                right: 22px;
                z-index: 24;
                color: #0d182e;
                font-size: 10px;
                font-weight: 900;
                letter-spacing: 0.02em;
                pointer-events: none;
            }

            .phone::before,
            .screen::before,
            .screen::after {
                content: none !important;
                display: none !important;
            }

            .content {
                padding-top: 18px;
            }

            .with-topbar .topbar {
                padding-top: 18px;
            }

            .with-topbar .content {
                padding-top: 12px;
            }

            .splash-screen .content,
            .onboarding-screen .content,
            .welcome-role-screen .content,
            .auth-mobile-screen .content,
            .trip-home-screen .content,
            .captain-mobile-screen .content {
                padding-top: 18px;
            }

            /* Splash mockup: clean white launch screen with mountains, car, bus, pin and plane logo. */
            .splash-screen .screen,
            .splash-screen .content {
                background: linear-gradient(180deg, #ffffff 0%, #ffffff 60%, #eef6ff 100%) !important;
            }

            .splash-launch {
                min-height: 760px;
                align-content: center;
                padding-bottom: 214px;
            }

            .welcome-logo strong {
                letter-spacing: -0.05em;
            }

            .welcome-logo strong span { color: var(--tn-blue); }
            .welcome-logo strong { color: var(--tn-orange); }

            .splash-launch .welcome-logo {
                transform: scale(1.33);
            }

            .splash-launch p {
                color: #8a97aa;
                font-size: 12px;
                font-weight: 800;
            }

            .splash-mountain {
                background: linear-gradient(180deg, #edf5ff, #cfe2fb) !important;
            }

            .splash-bus,
            .splash-car,
            .splash-location-pin {
                background: rgba(7, 91, 255, 0.22) !important;
            }

            /* Get Started screen: three sliding travel cards like the supplied onboarding screen. */
            .onboarding-story {
                justify-content: space-between;
                min-height: 100%;
            }

            .onboarding-title h2 {
                font-size: 30px;
                letter-spacing: -0.04em;
            }

            .onboarding-title p {
                max-width: 300px;
                font-size: 12px;
                color: #667085;
            }

            .journey-carousel {
                height: 380px;
                margin-top: 4px;
            }

            .journey-slide {
                height: 314px;
                border-radius: 20px;
                box-shadow: 0 22px 42px rgba(15, 31, 61, 0.22);
            }

            .journey-slide.active {
                transform: translateX(-50%) scale(1.06);
                z-index: 3;
            }

            .journey-slide.previous {
                transform: translateX(-122%) rotate(-7deg) scale(.86);
                opacity: .88;
                filter: saturate(.9) brightness(.98);
            }

            .journey-slide.next {
                transform: translateX(22%) rotate(7deg) scale(.86);
                opacity: .88;
                filter: saturate(.9) brightness(.98);
            }

            .onboarding-primary {
                height: 54px;
                border-radius: 16px;
                background: #0757d8 !important;
            }

            /* Role / login page: two cards, character illustrations, social row. */
            .welcome-role-screen .content {
                background:
                    linear-gradient(180deg, rgba(230, 243, 255, .95) 0%, #ffffff 36%, #ffffff 100%);
            }

            .welcome-login {
                min-height: 760px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 14px;
            }

            .welcome-logo {
                margin-top: 0;
            }

            .welcome-choice-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .welcome-choice-card {
                border-radius: 22px;
                padding: 13px 10px 12px;
                border: 1px solid var(--tn-border);
                box-shadow: 0 18px 36px rgba(15, 31, 61, .10);
            }

            .choice-visual {
                width: 106px;
                height: 122px;
                margin: 0 auto -4px;
                border-radius: 25px;
                background: linear-gradient(180deg, #e8f5ff, #ffffff) !important;
            }

            .choice-button {
                height: 36px;
                border-radius: 10px;
                font-size: 12px;
            }

            .choice-button-user { background: linear-gradient(135deg, #0a55f7, #073fc7); }
            .choice-button-captain { background: linear-gradient(135deg, #ff7a18, #f05b11); }

            .social-btn {
                width: 46px;
                height: 46px;
                border-radius: 15px;
                background: #fff;
                border: 1px solid var(--tn-border);
                box-shadow: 0 12px 24px rgba(15, 31, 61, .08);
            }

            .social-google { color: #0b74e5; }
            .social-apple { color: #05070a; }
            .social-facebook { color: #1877f2; }

            /* Login screens. */
            .auth-mobile-screen .content {
                background: #ffffff;
            }

            .auth-mobile-page {
                min-height: 760px;
                padding: 0 2px 10px;
            }

            .auth-person-visual {
                width: 128px;
                height: 128px;
                margin: 0 auto;
                border-radius: 30px;
            }

            .auth-mobile-copy h2 {
                font-size: 25px;
                letter-spacing: -0.04em;
            }

            .auth-mobile-copy p {
                font-weight: 700;
                color: #78859a;
                font-size: 12px;
            }

            .auth-mobile-form {
                border-radius: 26px;
                background: #ffffff;
                padding: 14px;
                box-shadow: 0 -12px 40px rgba(15, 31, 61, .08);
            }

            .auth-mobile-form .field label {
                display: none;
            }

            .input-shell {
                min-height: 57px;
                border-radius: 18px;
            }

            .forgot-link {
                display: block;
                margin-top: 7px;
                color: #0a55f7;
                font-weight: 900;
                font-size: 11px;
                text-align: right;
            }

            .auth-login-btn {
                height: 56px;
                border-radius: 17px;
                margin-top: 6px;
            }

            .captain-login-screen .auth-mobile-form {
                background: linear-gradient(180deg, #ffffff, #fff8f1);
            }

            .captain-auth-btn {
                background: linear-gradient(135deg, #ff7a18 0%, #f05b11 100%) !important;
                box-shadow: 0 18px 32px rgba(255, 107, 24, .25) !important;
            }

            /* OTP screens: user light / captain dark variants. */
            .otp-mobile-page {
                min-height: 760px;
                justify-content: flex-end;
            }

            .otp-copy h2 {
                font-size: 25px;
                letter-spacing: -0.04em;
            }

            .otp-role-captain {
                background: linear-gradient(180deg, #07162d 0%, #0c1c33 58%, #0f1b2d 100%);
                color: #ffffff;
                border-radius: 30px;
                padding: 12px;
            }

            .otp-role-captain .welcome-logo strong span,
            .otp-role-captain .welcome-logo strong,
            .otp-role-captain .welcome-logo em,
            .otp-role-captain .auth-mobile-copy h2,
            .otp-role-captain .auth-mobile-copy p {
                color: #ffffff;
            }

            .otp-role-captain .auth-mobile-form,
            .otp-role-captain .otp-demo-strip {
                background: rgba(255, 255, 255, .08);
                border-color: rgba(255,255,255,.14);
                color: #ffffff;
            }

            .otp-role-captain .otp-input {
                color: #ffffff;
                background: rgba(255,255,255,.08);
                border-color: rgba(255,255,255,.16);
            }

            .otp-input {
                height: 58px;
                border-radius: 14px !important;
                letter-spacing: .36em;
            }

            /* Customer dashboard: final home screen connected to all modules. */
            .trip-home-screen .content {
                background: #f4f8ff;
                padding-left: 14px;
                padding-right: 14px;
                padding-bottom: 110px;
            }

            .travel-home {
                gap: 13px;
            }

            .travel-home-head {
                align-items: center;
                gap: 9px;
            }

            .travel-avatar {
                width: 43px;
                height: 43px;
                border: 3px solid #ffffff;
                box-shadow: 0 9px 18px rgba(15,31,61,.14);
                background:
                    linear-gradient(180deg, #ffd7b5 0 40%, transparent 40%),
                    linear-gradient(135deg, #0a55f7, #ff7a18);
            }

            .travel-user strong {
                font-size: 14px;
            }

            .travel-user small,
            .travel-location {
                font-size: 10px;
            }

            .travel-search {
                min-height: 48px;
                border-radius: 16px;
                box-shadow: 0 12px 24px rgba(15,31,61,.08);
            }

            .quick-ride-panel {
                border-radius: 22px;
                background:
                    radial-gradient(circle at 84% 42%, rgba(255,107,24,.20), transparent 32%),
                    linear-gradient(135deg, #ffffff 0%, #fff6ef 100%);
                border: 1px solid #e8eef7;
                box-shadow: 0 18px 36px rgba(15,31,61,.10);
            }

            .quick-ride-title {
                color: #13213d;
            }

            .ride-field-mini {
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 8px 18px rgba(15,31,61,.06);
            }

            .ride-field-mini.pickup::before { background: #16a34a; }
            .ride-field-mini.drop::before { background: #ef4444; }

            .taxi-art {
                min-height: 146px;
                border-radius: 20px;
                background: linear-gradient(160deg, #fff4eb, #eef6ff);
            }

            .taxi-car {
                filter: drop-shadow(0 14px 18px rgba(255,107,24,.28));
            }

            .quick-ride-footer {
                grid-template-columns: 1fr .78fr 1.15fr;
                gap: 8px;
            }

            .book-ride-now {
                height: 48px;
                border-radius: 15px;
                background: linear-gradient(135deg, #ff7a18, #f05b11);
                color: #ffffff;
                font-size: 12px;
                box-shadow: 0 14px 25px rgba(255,107,24,.25);
            }

            .explore-banner {
                min-height: 94px;
                border-radius: 20px;
                background:
                    linear-gradient(90deg, rgba(5,24,53,.88), rgba(5,24,53,.32)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
            }

            .dashboard-shortcuts-compact {
                grid-template-columns: repeat(5, 1fr);
                gap: 8px;
            }

            .dashboard-shortcuts-compact a,
            .trip-service-grid a {
                min-height: 74px;
                border-radius: 16px;
                background: #ffffff;
                box-shadow: 0 10px 22px rgba(15,31,61,.07);
            }

            .dashboard-shortcuts-compact a {
                position: relative;
            }

            .dash-icon {
                width: 32px;
                height: 32px;
                border-radius: 12px;
            }

            .dashboard-shortcuts-compact .dash-icon {
                width: 36px;
                height: 36px;
                overflow: visible;
                border-radius: 12px;
                background: #ffffff;
                box-shadow: none;
            }

            .dashboard-shortcuts-compact a:first-child::before {
                content: "NEW";
                position: absolute;
                top: 4px;
                right: 8px;
                z-index: 2;
                border-radius: 999px;
                background: #ff6a00;
                color: #ffffff;
                padding: 1px 4px;
                font-size: 6px;
                line-height: 1.25;
                font-weight: 900;
            }

            .dashboard-shortcuts-compact .dash-icon::before,
            .dashboard-shortcuts-compact .dash-icon::after {
                content: "";
                position: absolute;
                box-sizing: border-box;
            }

            .dashboard-shortcuts-compact .dash-ico-car::before {
                left: 6px;
                top: 14px;
                width: 24px;
                height: 13px;
                border-radius: 8px 8px 5px 5px;
                background: #ff6a00;
                box-shadow:
                    4px 7px 0 -3px #14213d,
                    17px 7px 0 -3px #14213d;
            }

            .dashboard-shortcuts-compact .dash-ico-car::after {
                left: 11px;
                top: 8px;
                width: 14px;
                height: 9px;
                border-radius: 9px 9px 2px 2px;
                background: #ffb36b;
            }

            .dashboard-shortcuts-compact .dash-ico-plane::before {
                left: 6px;
                top: 16px;
                width: 26px;
                height: 5px;
                border-radius: 999px;
                background: #0b74e5;
                transform: rotate(-32deg);
            }

            .dashboard-shortcuts-compact .dash-ico-plane::after {
                left: 16px;
                top: 6px;
                width: 10px;
                height: 23px;
                border-radius: 9px 9px 2px 2px;
                background: #0b74e5;
                clip-path: polygon(45% 0, 72% 0, 62% 44%, 100% 55%, 100% 73%, 58% 66%, 46% 100%, 29% 100%, 34% 65%, 0 70%, 0 53%, 38% 43%);
                transform: rotate(18deg);
            }

            .dashboard-shortcuts-compact .dash-ico-hotel::before {
                left: 9px;
                top: 8px;
                width: 19px;
                height: 23px;
                border-radius: 5px 5px 3px 3px;
                background:
                    linear-gradient(#ffffff 0 0) 5px 6px / 3px 3px no-repeat,
                    linear-gradient(#ffffff 0 0) 12px 6px / 3px 3px no-repeat,
                    linear-gradient(#ffffff 0 0) 5px 13px / 3px 3px no-repeat,
                    linear-gradient(#ffffff 0 0) 12px 13px / 3px 3px no-repeat,
                    #7c3aed;
            }

            .dashboard-shortcuts-compact .dash-ico-hotel::after {
                left: 6px;
                top: 15px;
                width: 24px;
                height: 16px;
                border-radius: 4px;
                border: 3px solid #7c3aed;
                border-top: 0;
            }

            .dashboard-shortcuts-compact .dash-ico-bus::before {
                left: 8px;
                top: 8px;
                width: 21px;
                height: 23px;
                border-radius: 6px 6px 4px 4px;
                background:
                    linear-gradient(#ffffff 0 0) 4px 5px / 13px 6px no-repeat,
                    linear-gradient(#ffffff 0 0) 4px 14px / 5px 4px no-repeat,
                    linear-gradient(#ffffff 0 0) 12px 14px / 5px 4px no-repeat,
                    #16a34a;
            }

            .dashboard-shortcuts-compact .dash-ico-bus::after {
                left: 11px;
                top: 28px;
                width: 15px;
                height: 4px;
                border-radius: 999px;
                background:
                    radial-gradient(circle at 2px 2px, #14213d 0 2px, transparent 2.4px),
                    radial-gradient(circle at 13px 2px, #14213d 0 2px, transparent 2.4px);
            }

            .dashboard-shortcuts-compact .dash-ico-train::before {
                left: 9px;
                top: 6px;
                width: 18px;
                height: 25px;
                border-radius: 6px 6px 4px 4px;
                background:
                    linear-gradient(#ffffff 0 0) 4px 5px / 10px 6px no-repeat,
                    linear-gradient(#ffffff 0 0) 4px 15px / 10px 2px no-repeat,
                    #0b74e5;
            }

            .dashboard-shortcuts-compact .dash-ico-train::after {
                left: 7px;
                top: 29px;
                width: 22px;
                height: 5px;
                border-radius: 999px;
                border-top: 2px solid #0b74e5;
                box-shadow:
                    4px -3px 0 -1px #14213d,
                    16px -3px 0 -1px #14213d;
            }

            .dashboard-shortcuts-compact .dash-ico-food::before {
                left: 8px;
                top: 8px;
                width: 22px;
                height: 22px;
                border-radius: 7px;
                background:
                    linear-gradient(135deg, transparent 34%, #ff6a00 35% 45%, transparent 46% 54%, #ff6a00 55% 65%, transparent 66%),
                    #fff1e4;
                border: 2px solid #ff6a00;
                transform: rotate(-18deg);
            }

            .dashboard-shortcuts-compact .dash-ico-food::after {
                left: 15px;
                top: 9px;
                width: 4px;
                height: 21px;
                border-radius: 999px;
                background: #ff6a00;
                transform: rotate(47deg);
            }

            .dashboard-shortcuts-compact .dash-ico-bag::before {
                left: 8px;
                top: 11px;
                width: 21px;
                height: 17px;
                border-radius: 5px;
                background: #14213d;
            }

            .dashboard-shortcuts-compact .dash-ico-bag::after {
                left: 14px;
                top: 7px;
                width: 9px;
                height: 7px;
                border: 2px solid #14213d;
                border-bottom: 0;
                border-radius: 8px 8px 0 0;
            }

            .dashboard-shortcuts-compact .dash-ico-offer::before {
                left: 8px;
                top: 8px;
                width: 20px;
                height: 20px;
                border-radius: 6px 6px 6px 2px;
                background: #0b74e5;
                transform: rotate(-28deg);
            }

            .dashboard-shortcuts-compact .dash-ico-offer::after {
                left: 13px;
                top: 13px;
                width: 10px;
                height: 10px;
                border-radius: 999px;
                border: 2px solid #ffffff;
                transform: rotate(-28deg);
            }

            .dashboard-shortcuts-compact .dash-ico-more::before {
                left: 8px;
                top: 9px;
                width: 6px;
                height: 6px;
                border-radius: 999px;
                background: #94a3b8;
                box-shadow:
                    11px 0 0 #94a3b8,
                    0 11px 0 #94a3b8,
                    11px 11px 0 #94a3b8;
            }

            .dashboard-shortcuts-compact .dash-ico-more::after {
                display: none;
            }

            .destination-strip {
                display: grid;
                grid-auto-flow: column;
                grid-auto-columns: 132px;
                overflow-x: auto;
                gap: 10px;
                padding-bottom: 4px;
            }

            .destination-card {
                height: 132px;
                border-radius: 17px;
                box-shadow: 0 14px 28px rgba(15,31,61,.12);
            }

            .destination-card::before {
                background: linear-gradient(180deg, rgba(0,0,0,0) 28%, rgba(5,24,53,.72) 100%);
            }

            .offer-row {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .offer-card {
                min-height: 92px;
                border-radius: 18px;
                padding: 14px;
                overflow: hidden;
            }

            .orange-offer {
                background:
                    radial-gradient(circle at 86% 48%, rgba(255,255,255,.85) 0 28px, transparent 29px),
                    linear-gradient(135deg, #fff1e6, #ffd7ba);
            }

            .green-offer {
                background:
                    radial-gradient(circle at 86% 48%, rgba(255,255,255,.85) 0 28px, transparent 29px),
                    linear-gradient(135deg, #e9fff4, #ccf6df);
            }

            .recent-searches a {
                background: #ffffff;
                border: 1px solid #e8eef7;
                border-radius: 999px;
                box-shadow: 0 8px 18px rgba(15,31,61,.06);
            }

            /* Quick ride flow, payment and feedback mockups. */
            .ride-flow-steps,
            .plan-flow-steps,
            .group-flow-steps {
                position: sticky;
                top: 0;
                z-index: 8;
                margin: -5px -6px 12px;
                padding: 8px 6px;
                border-radius: 18px;
                background: rgba(244,248,255,.92);
                backdrop-filter: blur(12px);
                overflow-x: auto;
            }

            .ride-flow-step,
            .plan-flow-steps span,
            .group-flow-steps span {
                min-width: 78px;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 8px 18px rgba(15,31,61,.06);
            }

            .ride-flow-step.active,
            .plan-flow-steps span.active,
            .group-flow-steps span.active {
                background: linear-gradient(135deg, #0a55f7, #073fc7);
                color: #ffffff;
            }

            .location-picker,
            .payment-card,
            .driver-card,
            .captain-card,
            .group-tour-card,
            .my-booking-card,
            .booking-card,
            .profile-menu-card,
            .reward-milestone-card {
                border-radius: 22px;
                box-shadow: 0 16px 34px rgba(15,31,61,.09);
            }

            .map-box,
            #rideMap,
            #captainMap {
                border-radius: 24px;
                overflow: hidden;
            }

            .payment-method-row,
            .payment-option,
            .feedback-option {
                border-radius: 16px;
                background: #ffffff;
            }

            /* Trip planning / group tour / driver offers screens. */
            .plan-trip-screen .content,
            .group-tour-screen .content {
                background: #f4f8ff;
                padding-bottom: 112px;
            }

            .plan-hero,
            .group-tour-hero,
            .captain-dashboard-hero,
            .driver-offer-hero {
                border-radius: 24px;
                background:
                    linear-gradient(180deg, rgba(255,255,255,.42), rgba(255,255,255,.94)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
                box-shadow: 0 18px 36px rgba(15,31,61,.11);
            }

            .plan-scenic-card,
            .group-tour-photo,
            .captain-trip-photo {
                border-radius: 24px;
                box-shadow: 0 18px 38px rgba(15,31,61,.13);
            }

            .transport-tabs a,
            .group-category-row a {
                border-radius: 16px;
            }

            .transport-tabs a.active,
            .group-category-row a.active {
                background: #0a55f7;
                color: #ffffff;
            }

            /* Captain dashboard and rewards. */
            .captain-mobile-screen .content {
                background: #f4f8ff;
                padding-left: 14px;
                padding-right: 14px;
                padding-bottom: 112px;
            }

            .with-bottom-nav .content {
                padding-bottom: 150px;
                scroll-padding-bottom: 150px;
            }

            .trip-home-screen .content,
            .booking-mobile-screen .content,
            .bookings-mobile-screen .content,
            .profile-mobile-screen .content,
            .chat-mobile-screen .content,
            .captain-mobile-screen .content {
                padding-bottom: 156px;
            }

            .travel-home::after,
            .booking-mobile::after,
            .bookings-screen::after,
            .user-profile-screen::after,
            .driver-chat-screen::after,
            .captain-mobile::after {
                content: "";
                display: block;
                height: 18px;
            }

            .bookings-mobile-screen .trip-card:last-child {
                margin-bottom: 18px;
            }

            .phone[data-page^="captain-"] .content {
                padding-bottom: 214px;
                scroll-padding-bottom: 214px;
            }

            .phone[data-page^="captain-"] .bottom-nav {
                z-index: 70;
                background: #ffffff;
                border: 1px solid #e2ecf7;
                box-shadow: 0 -8px 28px rgba(15,31,61,.12);
                backdrop-filter: none;
            }

            .phone[data-page^="captain-"] .content > .trip-card:last-of-type,
            .phone[data-page^="captain-"] .content > .module-page-card:last-child {
                margin-bottom: 44px;
            }

            .phone[data-page^="admin-"] .content {
                padding-bottom: 220px;
                scroll-padding-bottom: 220px;
            }

            .phone[data-page^="admin-"] .bottom-nav {
                left: 18px;
                right: 18px;
                bottom: 15px;
                z-index: 80;
                min-height: 72px;
                border: 1px solid #dbe7f5;
                border-radius: 24px;
                background: #ffffff;
                padding: 9px 10px;
                box-shadow:
                    0 18px 38px rgba(20, 33, 61, 0.18),
                    inset 0 1px 0 rgba(255, 255, 255, 0.85);
                backdrop-filter: none;
            }

            .phone[data-page^="admin-"] .bottom-nav a {
                min-height: 58px;
                display: grid;
                grid-template-rows: 26px 18px;
                place-items: center;
                align-content: center;
                justify-items: center;
                gap: 4px;
                border-radius: 16px;
                color: #5d6b82;
                font-size: 9px;
                font-weight: 900;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
            }

            .phone[data-page^="admin-"] .bottom-nav a.active {
                background: linear-gradient(180deg, #eef6ff, #e6f0ff);
                color: #0b74e5;
                box-shadow: inset 0 0 0 1px rgba(11, 116, 229, 0.08);
            }

            .phone[data-page^="admin-"] .bottom-nav .nav-symbol {
                width: 22px;
                height: 22px;
                grid-row: 1;
            }

            .phone[data-page="admin-dashboard"] .stat-grid {
                gap: 10px;
                margin-bottom: 42px;
            }

            .phone[data-page="admin-dashboard"] .stat-card {
                min-height: 88px;
                display: grid;
                align-content: center;
                gap: 5px;
                border-radius: 18px;
                padding: 13px 12px;
            }

            .phone[data-page="admin-dashboard"] .stat-card strong {
                margin: 0;
                font-size: 25px;
            }

            .phone[data-page="admin-dashboard"] .stat-card span {
                font-size: 11px;
                font-weight: 800;
                line-height: 1.25;
            }

            .phone[data-page="admin-dashboard"] .admin-link-grid {
                margin-bottom: 28px;
            }

            .captain-mobile .captain-home-hero,
            .captain-dashboard-hero {
                border-radius: 25px;
                background:
                    linear-gradient(180deg, rgba(255,255,255,.26), rgba(255,255,255,.92)),
                    url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80') center/cover;
            }

            .captain-action-grid,
            .captain-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .captain-action-grid a,
            .captain-dashboard-grid a {
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 12px 24px rgba(15,31,61,.08);
            }

            .captain-reward-hero {
                border-radius: 24px;
                background: linear-gradient(135deg, #07162d, #0f2f62);
            }

            .reward-level {
                border-radius: 18px;
            }

            .reward-items .reward-item {
                border-radius: 12px;
                box-shadow: 0 6px 12px rgba(15,31,61,.08);
            }

            @media (max-width: 520px) {
                .app-shell { padding: 0; }
                .phone {
                    min-height: 100vh;
                    max-height: 100vh;
                    border: 0;
                    border-radius: 0;
                }
                .phone::before { display: none; }
                .screen { border-radius: 0; }
                .screen::before { top: 10px; }
                .screen::after { top: 10px; }
            }

        </style>
    </head>
    <body>
        <div class="app-shell">
            <main class="phone <?php echo $showTopbar ? 'with-topbar' : ''; ?> <?php echo h($screenClass); ?>" data-page="<?php echo h($page); ?>">
                <section class="screen <?php echo h($screenClass); ?>">
                    <?php if ($showTopbar): ?>
                        <div class="topbar">
                            <a class="brand-lockup" href="index.php?page=role-selection">
                                <span class="logo-mark">TN</span>
                                <span>
                                    <h1><?php echo h($title); ?></h1>
                                    <p>TripNovaa mobile travel</p>
                                </span>
                            </a>
                            <span class="status-pill"><?php echo $auth ? h($auth['role']) : 'Guest'; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="content">
                        <?php render_alerts(); ?>
    <?php
}

function bottom_navigation(string $role, string $active = 'home'): void
{
    if ($role === 'captain') {
        $items = [
            ['home', 'captain-dashboard', 'Home', '🏠'],
            ['requests', 'captain-ride-requests', 'Requests', '📥'],
            ['trips', 'captain-current-trips', 'Trips', '🧭'],
            ['earnings', 'captain-earnings', 'Earnings', '💵'],
        ];
    } elseif ($role === 'admin') {
        $items = [
            ['home', 'admin-dashboard', 'Home', '🏠'],
            ['users', 'admin-dashboard', 'Users', '👥'],
            ['rides', 'admin-dashboard', 'Rides', '🚕'],
            ['logout', 'logout', 'Logout', '↗'],
        ];
    } else {
        $items = [
            ['home', 'user-dashboard', 'Home', '🏠'],
            ['bookings', 'my-bookings', 'Bookings', '🧾'],
            ['rewards', 'rewards-offers', 'Rewards', '🎁'],
            ['profile', 'user-profile', 'Profile', '👤'],
        ];
    }
    ?>
    <nav class="bottom-nav" aria-label="<?php echo h($role); ?> navigation">
        <?php foreach ($items as $item): ?>
            <a class="<?php echo $active === $item[0] ? 'active' : ''; ?>" href="index.php?page=<?php echo h($item[1]); ?>">
                <span class="nav-symbol"><?php echo h($item[3]); ?></span>
                <?php echo h($item[2]); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function bottom_navigation_v2(string $role, string $active = 'home'): void
{
    if ($role === 'captain') {
        $items = [
            ['home', 'captain-dashboard', 'Home', 'nav-home', ''],
            ['trips', 'captain-trip-history', 'Trips', 'nav-calendar', ''],
            ['earnings', 'captain-earnings', 'Earning', 'nav-wallet', ''],
            ['rewards', 'captain-rewards', 'Rewards', 'nav-gift', ''],
            ['profile', 'captain-profile', 'Profile', 'nav-profile', ''],
        ];
    } elseif ($role === 'admin') {
        $items = [
            ['home', 'admin-dashboard', 'Home', 'nav-home', ''],
            ['users', 'admin-dashboard', 'Users', 'nav-users', ''],
            ['rides', 'admin-dashboard', 'Rides', 'nav-car', ''],
            ['logout', 'logout', 'Logout', 'nav-logout', ''],
        ];
    } else {
        $items = [
            ['home', 'user-dashboard', 'Home', 'nav-home', ''],
                ['bookings', 'my-bookings', 'My Booking', 'nav-calendar', 'nav-bookings'],
            ['plan', 'plan-trip', '', 'nav-plus', 'nav-center'],
            ['messages', 'driver-chat', 'Messages', 'nav-chat', ''],
            ['profile', 'user-profile', 'Profile', 'nav-profile', ''],
        ];
    }
    ?>
    <nav class="bottom-nav" style="--nav-count: <?php echo h(count($items)); ?>;" aria-label="<?php echo h($role); ?> navigation">
        <?php foreach ($items as $item): ?>
            <a class="<?php echo h(trim(($active === $item[0] ? 'active ' : '') . ($item[4] ?? ''))); ?>" href="index.php?page=<?php echo h($item[1]); ?>">
                <span class="nav-symbol <?php echo h($item[3]); ?>" aria-hidden="true"></span>
                <?php echo h($item[2]); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function app_footer(?string $navRole = null, string $active = 'home'): void
{
    global $page;
    ?>
                    </div>
                    <?php if ($navRole): ?>
                        <?php bottom_navigation_v2($navRole, $active); ?>
                    <?php endif; ?>
                </section>
            </main>
        </div>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const currentPage = <?php echo json_encode($page); ?>;

            document.addEventListener('DOMContentLoaded', () => {
                if (currentPage === 'splash') {
                    setTimeout(() => {
                        window.location.href = 'index.php?page=get-started';
                    }, 2000);
                }

                const slides = Array.from(document.querySelectorAll('.slide'));
                const dots = Array.from(document.querySelectorAll('.dot'));
                const nextBtn = document.querySelector('[data-next-slide]');
                const prevBtn = document.querySelector('[data-prev-slide]');
                let activeSlide = 0;

                function showSlide(index) {
                    if (!slides.length) return;
                    activeSlide = (index + slides.length) % slides.length;
                    slides.forEach((slide, idx) => slide.classList.toggle('active', idx === activeSlide));
                    dots.forEach((dot, idx) => dot.classList.toggle('active', idx === activeSlide));
                }

                if (slides.length) {
                    showSlide(0);
                    nextBtn?.addEventListener('click', () => showSlide(activeSlide + 1));
                    prevBtn?.addEventListener('click', () => showSlide(activeSlide - 1));
                    dots.forEach((dot, idx) => dot.addEventListener('click', () => showSlide(idx)));
                    if (document.querySelector('[data-auto-slide="true"]')) {
                        setInterval(() => showSlide(activeSlide + 1), 3200);
                    }
                }

                const journeySlides = Array.from(document.querySelectorAll('.journey-slide'));
                const journeyDots = Array.from(document.querySelectorAll('.journey-dot'));
                let activeJourney = 0;

                function showJourney(index) {
                    if (!journeySlides.length) return;
                    activeJourney = (index + journeySlides.length) % journeySlides.length;
                    const previous = (activeJourney - 1 + journeySlides.length) % journeySlides.length;
                    const next = (activeJourney + 1) % journeySlides.length;
                    journeySlides.forEach((slide, idx) => {
                        slide.classList.toggle('active', idx === activeJourney);
                        slide.classList.toggle('previous', idx === previous);
                        slide.classList.toggle('next', idx === next);
                    });
                    journeyDots.forEach((dot, idx) => dot.classList.toggle('active', idx === activeJourney));
                }

                if (journeySlides.length) {
                    showJourney(0);
                    journeyDots.forEach((dot, idx) => dot.addEventListener('click', () => showJourney(idx)));
                    if (document.querySelector('[data-auto-journey="true"]')) {
                        setInterval(() => showJourney(activeJourney + 1), 3200);
                    }
                }

                const otpInput = document.querySelector('input[name="otp"]');
                if (otpInput) {
                    otpInput.addEventListener('input', () => {
                        otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
                    });
                }

                document.querySelectorAll('[data-chat-thread]').forEach((thread) => {
                    thread.scrollTop = thread.scrollHeight;
                });

                const rideMap = document.getElementById('rideMap');
                if (rideMap && window.L) {
                    const pickup = [
                        Number(rideMap.dataset.pickupLat || 34.0151),
                        Number(rideMap.dataset.pickupLng || 71.5249)
                    ];
                    const drop = [
                        Number(rideMap.dataset.dropLat || 33.6844),
                        Number(rideMap.dataset.dropLng || 73.0479)
                    ];
                    const map = L.map('rideMap', { zoomControl: false }).setView(pickup, 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                    L.marker(pickup).addTo(map).bindPopup('<strong>Pickup marker</strong><br>Demo pickup point').openPopup();
                    L.marker(drop).addTo(map).bindPopup('<strong>Drop marker</strong><br>Demo drop point');
                    const route = L.polyline([pickup, drop], {
                        color: '#0b74e5',
                        weight: 5,
                        opacity: 0.86
                    }).addTo(map);
                    map.fitBounds(route.getBounds(), { padding: [28, 28] });
                }

                const captainMap = document.getElementById('captainMap');
                if (captainMap && window.L) {
                    const pickup = [
                        Number(captainMap.dataset.pickupLat || 34.0151),
                        Number(captainMap.dataset.pickupLng || 71.5249)
                    ];
                    const drop = [
                        Number(captainMap.dataset.dropLat || 33.6844),
                        Number(captainMap.dataset.dropLng || 73.0479)
                    ];
                    const map = L.map('captainMap', {
                        zoomControl: false,
                        attributionControl: false
                    }).setView(pickup, 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

                    const pickupIcon = L.divIcon({
                        className: 'captain-map-pin captain-map-pin-pickup',
                        iconSize: [24, 24],
                        iconAnchor: [12, 22]
                    });
                    const dropIcon = L.divIcon({
                        className: 'captain-map-pin captain-map-pin-drop',
                        iconSize: [24, 24],
                        iconAnchor: [12, 22]
                    });

                    L.marker(pickup, { icon: pickupIcon }).addTo(map);
                    L.marker(drop, { icon: dropIcon }).addTo(map);
                    const route = L.polyline([pickup, drop], {
                        color: '#1457ff',
                        weight: 5,
                        opacity: 0.92
                    }).addTo(map);
                    map.fitBounds(route.getBounds(), { padding: [38, 38] });
                    setTimeout(() => map.invalidateSize(), 80);
                }
            });
        </script>
    </body>
    </html>
    <?php
}

function tripnovaa_icon(string $type, string $extraClass = ''): string
{
    $type = in_array($type, ['user', 'captain', 'admin'], true) ? $type : 'user';
    $class = trim('tn-visual tn-visual-' . $type . ' ' . $extraClass);

    if ($type === 'captain') {
        return '<span class="' . h($class) . '" aria-hidden="true">
            <span class="tn-sky"></span>
            <span class="tn-face"></span>
            <span class="tn-hair"></span>
            <span class="tn-body"></span>
            <span class="tn-car"><span></span></span>
        </span>';
    }

    if ($type === 'admin') {
        return '<span class="' . h($class) . '" aria-hidden="true">
            <span class="tn-sky"></span>
            <span class="tn-shield"></span>
            <span class="tn-lock"></span>
            <span class="tn-brief"></span>
        </span>';
    }

    return '<span class="' . h($class) . '" aria-hidden="true">
        <span class="tn-sky"></span>
        <span class="tn-face"></span>
        <span class="tn-hair"></span>
        <span class="tn-body"></span>
        <span class="tn-pin"></span>
    </span>';
}

function tripnovaa_welcome_logo(): string
{
    return '<div class="welcome-logo" aria-label="TripNovaa">
        <span class="welcome-pin"><span></span></span>
        <span class="welcome-plane"></span>
        <span class="welcome-trail"></span>
        <strong><span>Trip</span>Novaa</strong>
        <em>Plan. Book. Explore.</em>
    </div>';
}

function page_splash(): void
{
    app_header('TripNovaa', false, 'splash-screen');
    ?>
    <div class="splash-card splash-launch">
        <div class="splash-sky" aria-hidden="true">
            <span class="splash-cloud one"></span>
            <span class="splash-cloud two"></span>
            <span class="splash-route"></span>
        </div>
        <?php echo tripnovaa_welcome_logo(); ?>
        <p>A World Of Bright Journey</p>
        <div class="splash-landscape" aria-hidden="true">
            <span class="splash-mountain back"></span>
            <span class="splash-mountain front"></span>
            <span class="splash-city"></span>
            <span class="splash-bus"></span>
            <span class="splash-car"></span>
            <span class="splash-location-pin"></span>
        </div>
        <div class="loader"><span></span></div>
    </div>
    <?php
    app_footer();
}

function page_get_started(): void
{
    app_header('Get Started', false, 'onboarding-screen');
    ?>
    <div class="onboarding onboarding-story">
        <div class="onboarding-head">
            <span></span>
            <a class="skip-link" href="index.php?page=role-selection">Skip</a>
        </div>

        <div class="onboarding-title">
            <h2>Your Journey<br>Starts <span>Here</span></h2>
            <p>Plan your trips, book rides, explore places and create unforgettable memories with TripNovaa.</p>
        </div>

        <div class="journey-carousel" data-auto-journey="true">
            <article class="journey-slide active" style="background-image: url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80');">
                <span>Plan Smart</span>
                <p>Plan your trip with ease and travel like a pro.</p>
            </article>
            <article class="journey-slide" style="background-image: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80');">
                <span>Book Easy</span>
                <p>Book rides, hotels, trains and tours in one tap.</p>
            </article>
            <article class="journey-slide" style="background-image: url('https://images.unsplash.com/photo-1518684079-3c830dcef090?auto=format&fit=crop&w=900&q=80');">
                <span>Explore More</span>
                <p>Discover new places and experiences.</p>
            </article>
        </div>
        <div class="dots journey-dots">
            <button class="journey-dot active" type="button" aria-label="Slide 1"></button>
            <button class="journey-dot" type="button" aria-label="Slide 2"></button>
            <button class="journey-dot" type="button" aria-label="Slide 3"></button>
        </div>
        <div class="onboarding-actions">
            <a class="btn onboarding-primary" href="index.php?page=role-selection">
                <span>Get Started</span>
                <span class="start-arrow" aria-hidden="true">&rarr;</span>
            </a>
            <div class="login-strip">
                Already have an account?
                <a class="tiny-link" href="index.php?page=user-login">Log In</a>
            </div>
        </div>
    </div>
    <?php
    app_footer();
}

function page_role_selection(): void
{
    app_header('Choose Role', true);
    ?>
    <section class="role-intro">
        <h2 class="hero-title">Choose your TripNovaa role</h2>
        <p class="lead">Continue as a traveler, captain, or admin. Each role opens its own mobile dashboard.</p>
    </section>

    <div class="role-grid">
        <article class="role-card role-card-stack">
            <div class="role-main">
                <span>
                    <strong>Customer/User</strong>
                    <span>Book rides, hotels, trains, buses, restaurants, tickets, and rewards.</span>
                </span>
                <?php echo tripnovaa_icon('user', 'role-visual'); ?>
                <span class="circle-icon">👤</span>
            </div>
            <div class="role-actions">
                <a class="mini-action primary" href="index.php?page=user-login">Login</a>
                <a class="mini-action" href="index.php?page=user-register">Register</a>
            </div>
        </article>
        <article class="role-card role-card-stack">
            <div class="role-main">
                <span>
                    <strong>Captain/Rider</strong>
                    <span>Manage ride requests, trip status, earnings, and availability.</span>
                </span>
                <?php echo tripnovaa_icon('captain', 'role-visual'); ?>
                <span class="circle-icon">🚗</span>
            </div>
            <div class="role-actions">
                <a class="mini-action primary" href="index.php?page=captain-login">Login</a>
                <a class="mini-action" href="index.php?page=captain-register">Register</a>
            </div>
        </article>
        <article class="role-card role-card-stack">
            <div class="role-main">
                <span>
                    <strong>Admin</strong>
                    <span>View users, captains, rides, bookings, payments, rewards, and offers.</span>
                </span>
                <?php echo tripnovaa_icon('admin', 'role-visual'); ?>
                <span class="circle-icon">🔐</span>
            </div>
            <div class="role-actions single">
                <a class="mini-action primary" href="index.php?page=admin-login">Admin Login</a>
            </div>
        </article>
    </div>
    <?php
    app_footer();
}

function page_role_selection_welcome(): void
{
    app_header('TripNovaa', false, 'welcome-role-screen');
    ?>
    <section class="welcome-login">
        <a class="admin-corner" href="index.php?page=admin-login">
            <?php echo tripnovaa_icon('admin', 'admin-mini-visual'); ?>
            <span>Admin</span>
        </a>

        <div class="welcome-scenery" aria-hidden="true">
            <span class="scene-cloud scene-cloud-one"></span>
            <span class="scene-cloud scene-cloud-two"></span>
            <span class="scene-building scene-building-one"></span>
            <span class="scene-building scene-building-two"></span>
            <span class="scene-car scene-car-left"></span>
            <span class="scene-car scene-car-right"></span>
        </div>

        <?php echo tripnovaa_welcome_logo(); ?>

        <div class="welcome-copy">
            <h2>Welcome Back!</h2>
            <p>Login to continue your journey</p>
        </div>

        <div class="welcome-choice-grid">
            <article class="welcome-choice-card user-choice">
                <span class="choice-pin" aria-hidden="true"></span>
                <?php echo tripnovaa_icon('user', 'choice-visual'); ?>
                <h3>User Login</h3>
                <p>Login as a user to book rides, explore places and more.</p>
                <a class="choice-button choice-button-user" href="index.php?page=user-login">
                    User Login <span aria-hidden="true">-&gt;</span>
                </a>
                <a class="choice-register" href="index.php?page=user-register">Create account</a>
            </article>

            <article class="welcome-choice-card captain-choice">
                <span class="choice-steer" aria-hidden="true"></span>
                <?php echo tripnovaa_icon('captain', 'choice-visual'); ?>
                <h3>Captain Login</h3>
                <p>Login as a captain to manage rides and earn more.</p>
                <a class="choice-button choice-button-captain" href="index.php?page=captain-login">
                    Captain Login <span aria-hidden="true">-&gt;</span>
                </a>
                <a class="choice-register" href="index.php?page=captain-register">Join as captain</a>
            </article>
        </div>

        <div class="social-divider"><span>or continue with</span></div>
        <div class="social-row" aria-label="Demo social login buttons">
            <form class="social-form" method="post" action="index.php?page=role-selection">
                <input type="hidden" name="action" value="social_demo_login">
                <input type="hidden" name="provider" value="google">
                <button class="social-btn social-google" type="submit" title="Continue with Google">G</button>
            </form>
            <form class="social-form" method="post" action="index.php?page=role-selection">
                <input type="hidden" name="action" value="social_demo_login">
                <input type="hidden" name="provider" value="apple">
                <button class="social-btn social-apple" type="submit" title="Continue with Apple">A</button>
            </form>
            <form class="social-form" method="post" action="index.php?page=role-selection">
                <input type="hidden" name="action" value="social_demo_login">
                <input type="hidden" name="provider" value="facebook">
                <button class="social-btn social-facebook" type="submit" title="Continue with Facebook">f</button>
            </form>
        </div>
        <p class="welcome-terms">By continuing, you agree to our <strong>Terms &amp; Conditions</strong> and <strong>Privacy Policy</strong>.</p>
    </section>
    <?php
    app_footer();
}

function page_user_register(): void
{
    app_header('User Register', true);
    ?>
    <div class="auth-hero auth-hero-split">
        <?php echo tripnovaa_icon('user', 'auth-visual'); ?>
        <div>
            <span class="auth-badge">Customer signup</span>
            <h2>Create your travel account</h2>
            <p>Register once, verify demo OTP, then start booking rides and travel services.</p>
        </div>
    </div>

    <form class="form card auth-card" method="post" action="index.php?page=user-register">
        <input type="hidden" name="action" value="user_register">
        <div class="field">
            <label for="user_full_name">Full name</label>
            <input id="user_full_name" name="full_name" type="text" required>
        </div>
        <div class="field">
            <label for="user_email">Email</label>
            <input id="user_email" name="email" type="email" required>
        </div>
        <div class="field">
            <label for="user_phone">Phone</label>
            <input id="user_phone" name="phone" type="tel" required>
        </div>
        <div class="field">
            <label for="user_password">Password</label>
            <input id="user_password" name="password" type="password" minlength="6" required>
        </div>
        <div class="field">
            <label for="user_confirm_password">Confirm password</label>
            <input id="user_confirm_password" name="confirm_password" type="password" minlength="6" required>
        </div>
        <button class="btn" type="submit">Register User</button>
        <p class="form-note">Demo OTP after signup: <strong>123456</strong></p>
        <div class="auth-switch">
            <a class="mini-action" href="index.php?page=user-login">User Login</a>
            <a class="mini-action" href="index.php?page=role-selection">Change Role</a>
        </div>
    </form>
    <?php
    app_footer();
}

function page_user_login(): void
{
    app_header('User Login', false, 'auth-mobile-screen user-login-screen');
    ?>
    <section class="auth-mobile-page user-auth-page">
        <div class="auth-scenery" aria-hidden="true">
            <span class="scene-cloud scene-cloud-one"></span>
            <span class="scene-cloud scene-cloud-two"></span>
            <span class="scene-building scene-building-one"></span>
            <span class="scene-building scene-building-two"></span>
            <span class="scene-car scene-car-left"></span>
            <span class="scene-car scene-car-right"></span>
        </div>
        <a class="auth-back-link back-link" href="index.php?page=role-selection" aria-label="Back"></a>
        <?php echo tripnovaa_welcome_logo(); ?>
        <div class="auth-mobile-copy">
            <?php echo tripnovaa_icon('user', 'auth-person-visual'); ?>
            <h2>User Login</h2>
            <p>Welcome back! Please login to continue your journey.</p>
        </div>

        <form class="form auth-mobile-form" method="post" action="index.php?page=user-login">
            <input type="hidden" name="action" value="user_login">
            <div class="field">
                <label for="user_full_name">Full Name</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-user" aria-hidden="true"></span>
                    <input id="user_full_name" name="full_name" type="text" placeholder="Full Name" autocomplete="name">
                </div>
            </div>
            <div class="field">
                <label for="user_login">Email Address</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-mail" aria-hidden="true"></span>
                    <input id="user_login" name="login" type="text" placeholder="Email Address" autocomplete="email" required>
                </div>
            </div>
            <div class="field">
                <label for="user_phone">Phone Number</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-phone" aria-hidden="true"></span>
                    <input id="user_phone" name="phone" type="tel" placeholder="Phone Number" autocomplete="tel">
                </div>
            </div>
            <div class="field">
                <label for="user_login_password">Password</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-lock" aria-hidden="true"></span>
                    <input id="user_login_password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
                    <span class="input-eye" aria-hidden="true"></span>
                </div>
                <a class="forgot-link" href="index.php?page=user-login">Forgot Password?</a>
            </div>
            <button class="btn auth-login-btn user-auth-btn" type="submit">Login <span aria-hidden="true">-&gt;</span></button>
            <p class="form-note auth-demo-note">Demo: <strong>sara@tripnovaa.com</strong> / <strong>user123</strong></p>
            <div class="login-strip">Don't have an account? <a class="tiny-link" href="index.php?page=user-register">Sign Up</a></div>
        </form>
    </section>
    <?php
    app_footer();
}

function page_captain_register(): void
{
    app_header('Captain Register', true);
    ?>
    <div class="auth-hero auth-hero-split">
        <?php echo tripnovaa_icon('captain', 'auth-visual'); ?>
        <div>
            <span class="auth-badge">Captain signup</span>
            <h2>Start driving with TripNovaa</h2>
            <p>Create your rider profile, verify demo OTP, and open the captain dashboard.</p>
        </div>
    </div>

    <form class="form card auth-card" method="post" action="index.php?page=captain-register">
        <input type="hidden" name="action" value="captain_register">
        <div class="field">
            <label for="captain_full_name">Full name</label>
            <input id="captain_full_name" name="full_name" type="text" required>
        </div>
        <div class="field">
            <label for="captain_email">Email</label>
            <input id="captain_email" name="email" type="email" required>
        </div>
        <div class="field">
            <label for="captain_phone">Mobile Number</label>
            <div class="input-shell phone-input">
                <span class="input-icon input-icon-phone" aria-hidden="true"></span>
                <input id="captain_phone" name="phone" type="tel" placeholder="3001234567" required>
                <select name="phone_country_code" aria-label="Country code">
                    <?php foreach (phone_country_code_options() as $option): ?>
                        <option value="<?php echo h($option['code']); ?>" <?php echo $option['code'] === '+92' ? 'selected' : ''; ?>><?php echo h($option['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="license_number">Driving Licence Number</label>
            <div class="input-shell">
                <span class="input-icon input-icon-card" aria-hidden="true"></span>
                <input id="license_number" name="license_number" type="text" placeholder="Enter driving licence number" required>
                <span class="id-suffix">DL Number</span>
            </div>
        </div>
        <div class="field">
            <label for="id_card_number">Aadhar Card Number</label>
            <div class="input-shell">
                <span class="input-icon input-icon-card" aria-hidden="true"></span>
                <input id="id_card_number" name="id_card_number" type="text" placeholder="Enter Aadhar or PAN card number" required>
                <select class="id-type-select" name="id_card_type" aria-label="ID card type">
                    <option value="aadhar">Aadhar Card</option>
                    <option value="pan">PAN Card</option>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="captain_password">Password</label>
            <input id="captain_password" name="password" type="password" minlength="6" required>
        </div>
        <div class="field">
            <label for="captain_confirm_password">Confirm password</label>
            <input id="captain_confirm_password" name="confirm_password" type="password" minlength="6" required>
        </div>
        <button class="btn" type="submit">Register Captain</button>
        <p class="form-note">Demo status: <strong>active</strong>. Demo OTP: <strong>123456</strong></p>
        <div class="auth-switch">
            <a class="mini-action" href="index.php?page=captain-login">Captain Login</a>
            <a class="mini-action" href="index.php?page=role-selection">Change Role</a>
        </div>
    </form>
    <?php
    app_footer();
}

function page_captain_login(): void
{
    app_header('Captain Login', false, 'auth-mobile-screen captain-login-screen');
    ?>
    <section class="auth-mobile-page captain-auth-page">
        <div class="auth-scenery" aria-hidden="true">
            <span class="scene-cloud scene-cloud-one"></span>
            <span class="scene-cloud scene-cloud-two"></span>
            <span class="scene-building scene-building-one"></span>
            <span class="scene-building scene-building-two"></span>
            <span class="scene-car scene-car-left"></span>
            <span class="scene-car scene-car-right"></span>
        </div>
        <a class="auth-back-link back-link" href="index.php?page=role-selection" aria-label="Back"></a>
        <?php echo tripnovaa_welcome_logo(); ?>
        <div class="auth-mobile-copy">
            <?php echo tripnovaa_icon('captain', 'auth-person-visual'); ?>
            <h2>Captain Login</h2>
            <p>Login to manage rides and earn more.</p>
        </div>

        <form class="form auth-mobile-form" method="post" action="index.php?page=captain-login">
            <input type="hidden" name="action" value="captain_login">
            <div class="field">
                <label for="captain_full_name_login">Full Name</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-user" aria-hidden="true"></span>
                    <input id="captain_full_name_login" name="full_name" type="text" placeholder="Full Name" autocomplete="name">
                </div>
            </div>
            <div class="field">
                <label for="captain_login">Email Address</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-mail" aria-hidden="true"></span>
                    <input id="captain_login" name="login" type="text" placeholder="Email Address" autocomplete="email" required>
                </div>
            </div>
            <div class="field">
                <label for="captain_login_phone">Mobile Number</label>
                <div class="input-shell phone-input">
                    <span class="input-icon input-icon-phone" aria-hidden="true"></span>
                    <input id="captain_login_phone" name="phone" type="tel" placeholder="Mobile Number" autocomplete="tel">
                    <select name="phone_country_code" aria-label="Country code">
                        <?php foreach (phone_country_code_options() as $option): ?>
                            <option value="<?php echo h($option['code']); ?>" <?php echo $option['code'] === '+92' ? 'selected' : ''; ?>><?php echo h($option['code']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="captain_login_license">Driving Licence Number</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-card" aria-hidden="true"></span>
                    <input id="captain_login_license" name="license_number" type="text" placeholder="Driving Licence Number">
                    <span class="id-suffix">DL Number</span>
                </div>
            </div>
            <div class="field">
                <label for="captain_login_id_card">Aadhar Card Number</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-card" aria-hidden="true"></span>
                    <input id="captain_login_id_card" name="id_card_number" type="text" placeholder="Aadhar Card Number">
                    <select class="id-type-select" name="id_card_type" aria-label="ID card type">
                        <option value="aadhar">Aadhar Card</option>
                        <option value="pan">PAN Card</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="captain_login_password">Password</label>
                <div class="input-shell">
                    <span class="input-icon input-icon-lock" aria-hidden="true"></span>
                    <input id="captain_login_password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
                    <span class="input-eye" aria-hidden="true"></span>
                </div>
            </div>
            <div class="password-rules">
                <span>At least 8 characters</span>
                <span>Contains letters and numbers</span>
                <span>At least one special character</span>
            </div>
            <button class="btn auth-login-btn captain-auth-btn" type="submit"><span class="shield-mark" aria-hidden="true"></span> Login as Captain <span aria-hidden="true">-&gt;</span></button>
            <p class="form-note auth-demo-note">Demo: <strong>ahmed.captain@tripnovaa.com</strong> / <strong>captain123</strong></p>
            <div class="social-divider"><span>or continue with</span></div>
            <div class="auth-social-options">
                <span>Google</span><span>Apple</span><span>Facebook</span>
            </div>
            <div class="login-strip">Don't have an account? <a class="tiny-link" href="index.php?page=captain-register">Sign Up</a></div>
        </form>
    </section>
    <?php
    app_footer();
}

function page_admin_login(): void
{
    app_header('Admin Login', true);
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Admin secure access</span>
        <h2>Control TripNovaa</h2>
        <p>Login with the seeded admin account to view platform users, captains, bookings, payments, and offers.</p>
    </div>

    <form class="form card auth-card" method="post" action="index.php?page=admin-login">
        <input type="hidden" name="action" value="admin_login">
        <div class="field">
            <label for="admin_email">Email</label>
            <input id="admin_email" name="email" type="email" required>
        </div>
        <div class="field">
            <label for="admin_password">Password</label>
            <input id="admin_password" name="password" type="password" required>
        </div>
        <button class="btn btn-orange" type="submit">Login as Admin</button>
        <p class="form-note">Sample admin: <strong>admin@tripnovaa.com</strong> / <strong>admin123</strong></p>
        <div class="auth-switch">
            <a class="mini-action" href="index.php?page=role-selection">Change Role</a>
            <a class="mini-action" href="index.php?page=user-login">User Login</a>
        </div>
    </form>
    <?php
    app_footer();
}

function page_otp(): void
{
    $pending = $_SESSION['pending_otp'] ?? null;
    app_header('OTP Verification', false, 'auth-mobile-screen otp-mobile-screen');

    if (!$pending) {
        ?>
        <div class="auth-mobile-page otp-mobile-page">
            <?php echo tripnovaa_welcome_logo(); ?>
            <div class="card auth-card otp-card">
                <div class="otp-icon" aria-hidden="true"></div>
                <h2 class="hero-title">No OTP pending</h2>
                <p class="lead">Please login or register first so TripNovaa can create a demo OTP request.</p>
            </div>
            <a class="btn" href="index.php?page=role-selection">Choose Role</a>
        </div>
        <?php
        app_footer();
        return;
    }
    ?>
    <section class="auth-mobile-page otp-mobile-page otp-role-<?php echo h((string) $pending['role']); ?>">
        <div class="auth-scenery" aria-hidden="true">
            <span class="scene-cloud scene-cloud-one"></span>
            <span class="scene-cloud scene-cloud-two"></span>
            <span class="scene-building scene-building-one"></span>
            <span class="scene-building scene-building-two"></span>
            <span class="scene-car scene-car-left"></span>
            <span class="scene-car scene-car-right"></span>
        </div>
        <a class="auth-back-link back-link" href="index.php?page=role-selection" aria-label="Back"></a>
        <?php echo tripnovaa_welcome_logo(); ?>
        <div class="auth-mobile-copy otp-copy">
            <span class="otp-lock-visual" aria-hidden="true"></span>
            <h2>Verify Your Number</h2>
            <p>Enter the 6-digit OTP sent to <?php echo h($pending['phone']); ?>.</p>
        </div>

        <div class="otp-demo-strip">
            <span><?php echo h(ucfirst((string) $pending['role'])); ?> login</span>
            <strong>Demo OTP: 123456</strong>
        </div>

        <form class="form auth-mobile-form otp-mobile-form" method="post" action="index.php?page=otp">
            <input type="hidden" name="action" value="verify_otp">
            <div class="field">
                <label for="otp">Enter OTP</label>
                <input class="otp-input" id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" placeholder="123456" required>
            </div>
            <button class="btn auth-login-btn user-auth-btn" type="submit">Verify &amp; Continue <span aria-hidden="true">-&gt;</span></button>
            <p class="form-note auth-demo-note">Use <strong>123456</strong> for this assignment demo.</p>
        </form>
    </section>
    <?php
    app_footer();
}

function page_user_dashboard(): void
{
    require_role('user', 'user-login');
    $userId = current_user_id() ?? 0;
    app_header('User Dashboard', true, 'with-bottom-nav');
    ?>
    <?php
    $bookingTotal =
        user_table_count('rides', $userId) +
        user_table_count('hotel_bookings', $userId) +
        user_table_count('train_bookings', $userId) +
        user_table_count('bus_bookings', $userId) +
        user_table_count('restaurant_bookings', $userId) +
        user_table_count('ticket_bookings', $userId);
    $modules = [
        ['Book Ride', 'Fast pickup and captain selection', '🚕', 'book-ride', 'service-ride', 'Ride'],
        ['Hotel Booking', 'Find stays for every trip', '🏨', 'hotel-search', 'service-hotel', 'Stay'],
        ['Train Booking', 'Search routes and seats', '🚆', 'train-search', 'service-train', 'Rail'],
        ['Bus Booking', 'Intercity buses and fares', '🚌', 'bus-search', 'service-bus', 'Bus'],
        ['Restaurant Booking', 'Reserve tables nearby', '🍽', 'restaurant-search', 'service-restaurant', 'Food'],
        ['Tours/Tickets', 'Events, tours, and passes', '🎟', 'tour-ticket-search', 'service-ticket', 'Explore'],
        ['Rewards/Offers', 'Coupons and travel points', '🎁', 'rewards-offers', 'service-rewards', 'Deals'],
        ['My Bookings', 'All your trips in one place', '🧾', 'my-bookings', 'service-bookings', 'History'],
        ['Logout', 'Securely end this session', '↗', 'logout', 'logout-card', 'Account'],
    ];
    ?>

    <section class="dashboard-hero">
        <span class="dashboard-kicker">📍 TripNovaa customer</span>
        <h2>Welcome, <?php echo h(current_user_name()); ?></h2>
        <p>Where do you want to go today? Book rides, stays, food, tickets, and rewards from one app.</p>
        <div class="mini-metrics">
            <div class="mini-metric"><strong><?php echo $bookingTotal; ?></strong><span>Bookings</span></div>
            <div class="mini-metric"><strong><?php echo user_table_count('rewards', $userId); ?></strong><span>Rewards</span></div>
            <div class="mini-metric"><strong><?php echo table_count('offers'); ?></strong><span>Offers</span></div>
        </div>
    </section>

    <section class="search-panel" aria-label="Quick search">
        <div class="search-field">
            <span>🔎</span>
            <input type="search" placeholder="Search rides, hotels, tickets..." aria-label="Search rides, hotels, tickets">
        </div>
        <div class="quick-chips">
            <a class="quick-chip" href="index.php?page=book-ride">Airport ride</a>
            <a class="quick-chip" href="index.php?page=hotel-search">Weekend stay</a>
            <a class="quick-chip" href="index.php?page=tour-ticket-search">City tour</a>
        </div>
    </section>

    <div class="section-title">
        <h2>Explore services</h2>
        <a class="tiny-link" href="index.php?page=my-bookings">My Bookings</a>
    </div>

    <section class="service-grid" aria-label="TripNovaa customer modules">
        <?php foreach ($modules as $module): ?>
            <a class="service-card <?php echo h($module[4] ?? ''); ?>" href="index.php?page=<?php echo h($module[3]); ?>">
                <span class="service-icon"><?php echo h($module[2]); ?></span>
                <span class="service-copy">
                    <span class="service-tag"><?php echo h($module[5] ?? 'Trip'); ?></span>
                    <strong><?php echo h($module[0]); ?></strong>
                    <span><?php echo h($module[1]); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="section-title">
        <h2>Profile</h2>
        <a class="tiny-link" href="index.php?page=user-profile">Open</a>
    </div>
    <a class="profile-strip" href="index.php?page=user-profile">
        <span class="avatar"><?php echo h(strtoupper(substr(current_user_name(), 0, 1))); ?></span>
        <span>
            <strong><?php echo h(current_user_name()); ?></strong>
            <span>Customer account, rewards, bookings, and logout options</span>
        </span>
    </a>
    <?php
    app_footer('user', 'home');
}

function page_user_dashboard_home(): void
{
    require_role('user', 'user-login');
    $userId = current_user_id() ?? 0;
    $bookingTotal =
        user_table_count('rides', $userId) +
        user_table_count('hotel_bookings', $userId) +
        user_table_count('train_bookings', $userId) +
        user_table_count('bus_bookings', $userId) +
        user_table_count('restaurant_bookings', $userId) +
        user_table_count('ticket_bookings', $userId);
    $userFirstName = trim(explode(' ', current_user_name())[0] ?? current_user_name());

    // This is the final Customer Home Dashboard.
    // The login flow stays the same: Splash -> Get Started -> Role Selection -> User Login/Register -> OTP -> User Dashboard.
    // From here, every required user module is connected through direct links.
    $shortcuts = [
        ['Quick Ride', 'book-ride', 'dash-ico-car'],
        ['Plan Trip', 'plan-trip', 'dash-ico-plane'],
        ['Driver Offers', 'driver-offers', 'dash-ico-car'],
        ['Hotels', 'hotel-search', 'dash-ico-hotel'],
        ['Buses', 'bus-search', 'dash-ico-bus'],
        ['Trains', 'train-search', 'dash-ico-train'],
        ['Restaurants', 'restaurant-search', 'dash-ico-food'],
        ['Group Tours', 'group-tours', 'dash-ico-bag'],
        ['Offers', 'rewards-offers', 'dash-ico-offer'],
        ['Tickets', 'tour-ticket-search', 'dash-ico-more'],
    ];

    $destinations = [
        ['Bali, Indonesia', '4.8', '5 nights', 'Rs. 45,999', 'dash-dest-bali', 'hotel-search'],
        ['Manali, India', '4.6', '3 nights', 'Rs. 11,999', 'dash-dest-manali', 'plan-trip'],
        ['Paris, France', '4.9', '5 nights', 'Rs. 68,999', 'dash-dest-paris', 'tour-ticket-search'],
        ['Dubai, UAE', '4.7', '4 nights', 'Rs. 32,999', 'dash-dest-dubai', 'tour-ticket-search'],
    ];
    $planTripCards = [
        ['My Trips Posted', 'plan-trip', 'customer-icon-trip', 'blue'],
        ['Driver Offers', 'driver-offers', 'customer-icon-driver', 'green'],
        ['Offers', 'rewards-offers', 'customer-icon-offer', 'orange'],
        ['Booked Trips', 'my-bookings', 'customer-icon-booking', 'violet'],
        ['Saved Trips', 'saved-trips', 'customer-icon-heart', 'red'],
        ['Profile', 'user-profile', 'customer-icon-profile', 'blue'],
    ];
    $localDestinations = [
        ['Manali', '5 Days / 4 Nights', '9,799', 'customer-dest-manali'],
        ['Shimla', '3 Days / 2 Nights', '6,499', 'customer-dest-shimla'],
        ['Leh Ladakh', '6 Days / 5 Nights', '15,999', 'customer-dest-leh'],
    ];

    app_header('TripNovaa', false, 'with-bottom-nav user-travel-dashboard trip-home-screen');
    ?>
    <section class="travel-home">
        <div class="travel-home-head">
            <a class="hamburger-btn" href="index.php?page=user-profile" aria-label="Open profile"><span></span></a>
            <a class="travel-user" href="index.php?page=user-profile">
                <span class="travel-avatar"><?php echo h(strtoupper(substr(current_user_name(), 0, 1))); ?></span>
                <span>
                    <strong>Hey, <?php echo h($userFirstName ?: 'Traveler'); ?> 👋</strong>
                    <small>Where do you want to go today?</small>
                </span>
            </a>
            <a class="travel-location" href="index.php?page=user-profile"><span class="mini-pin"></span>Peshawar</a>
            <a class="bell-btn" href="index.php?page=rewards-offers" aria-label="Offers"><span><?php echo h(table_count('offers')); ?></span></a>
        </div>

        <form class="travel-search" method="get" action="index.php">
            <input type="hidden" name="page" value="tour-ticket-search">
            <span class="search-mark"></span>
            <input name="keyword" type="search" placeholder="Search destinations, hotels, flights, etc." aria-label="Search TripNovaa">
            <button class="filter-btn" type="submit" aria-label="Search"><span></span></button>
        </form>

        <article class="quick-ride-panel">
            <div class="quick-ride-title"><span class="dash-icon dash-ico-car"></span><strong>Quick Ride</strong></div>
            <div class="ride-card-body">
                <div class="ride-fields-mini">
                    <a class="ride-field-mini pickup" href="index.php?page=book-ride">
                        <small>Pickup Location</small>
                        <strong>Current Location</strong>
                        <em></em>
                    </a>
                    <a class="ride-field-mini drop" href="index.php?page=book-ride">
                        <small>Drop Location</small>
                        <strong>Where to?</strong>
                        <em></em>
                    </a>
                    <div class="ride-type-mini">
                        <a class="ride-type-bike" href="index.php?page=book-ride"><b></b>Bike</a>
                        <a class="ride-type-auto" href="index.php?page=book-ride"><b></b>Auto</a>
                        <a class="ride-type-cab active" href="index.php?page=book-ride"><b></b>Cab</a>
                        <a class="ride-type-premium" href="index.php?page=book-ride"><b></b>Premium</a>
                    </div>
                </div>
                <a class="taxi-art" href="index.php?page=book-ride" aria-label="Book a ride">
                    <span class="taxi-sun"></span>
                    <span class="taxi-pin"></span>
                    <span class="taxi-car"></span>
                </a>
            </div>
            <div class="quick-ride-footer">
                <a class="ride-time" href="index.php?page=book-ride"><strong>8 mins away</strong><small>1.8 km from you</small></a>
                <a class="ride-fare" href="index.php?page=book-ride"><small>Est. Fare</small><strong>&#8377;120</strong></a>
                <a class="book-ride-now" href="index.php?page=book-ride">Book Ride Now</a>
            </div>
        </article>

        <article class="explore-banner">
            <span>
                <small>Explore the World</small>
                <strong>Smartly with TripNovaa</strong>
                <em>Plan trips, group tours, transport, hotels, guides, and offers.</em>
            </span>
            <a href="index.php?page=plan-trip">Plan Now</a>
        </article>

        <section class="customer-plan-section" aria-label="Customer plan trip shortcuts">
            <nav class="customer-plan-grid">
                <?php foreach ($planTripCards as $card): ?>
                    <a class="customer-plan-card customer-plan-<?php echo h($card[3]); ?>" href="index.php?page=<?php echo h($card[1]); ?>">
                        <span class="customer-plan-icon <?php echo h($card[2]); ?>" aria-hidden="true"></span>
                        <strong><?php echo h($card[0]); ?></strong>
                    </a>
                <?php endforeach; ?>
            </nav>

            <a class="customer-sightseeing-offer" href="index.php?page=rewards-offers">
                <span>
                    <small>Local Sightseeing</small>
                    <strong>Up to 20% OFF on Local Sightseeing</strong>
                    <em>Explore guided day trips and city tours.</em>
                </span>
                <b>Explore Now</b>
                <i aria-hidden="true"></i>
            </a>

            <div class="travel-section-head customer-plan-head">
                <h2>Popular Destinations</h2>
                <a href="index.php?page=plan-trip">View All</a>
            </div>
            <div class="customer-local-destinations">
                <?php foreach ($localDestinations as $destination): ?>
                    <a class="customer-local-card <?php echo h($destination[3]); ?>" href="index.php?page=plan-trip">
                        <span></span>
                        <strong><?php echo h($destination[0]); ?></strong>
                        <small><?php echo h($destination[1]); ?></small>
                        <b>&#8377;<?php echo h($destination[2]); ?></b>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <nav class="dashboard-shortcuts dashboard-shortcuts-compact" aria-label="Customer services">
            <?php foreach ($shortcuts as $item): ?>
                <a href="index.php?page=<?php echo h($item[1]); ?>">
                    <span class="dash-icon <?php echo h($item[2]); ?>"></span>
                    <strong><?php echo h($item[0]); ?></strong>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="travel-section-head">
            <h2>Popular Destinations</h2>
            <a href="index.php?page=tour-ticket-search">View All</a>
        </div>
        <section class="destination-strip">
            <?php foreach ($destinations as $destination): ?>
                <a class="destination-card <?php echo h($destination[4]); ?>" href="index.php?page=<?php echo h($destination[5]); ?>">
                    <span class="rating-chip"><?php echo h($destination[1]); ?></span>
                    <span class="destination-body">
                        <strong><?php echo h($destination[0]); ?></strong>
                        <small><?php echo h($destination[3]); ?> · <?php echo h($destination[2]); ?></small>
                    </span>
                </a>
            <?php endforeach; ?>
        </section>

        <div class="travel-section-head">
            <h2>TripNovaa Services</h2>
            <a href="index.php?page=my-bookings">My Bookings</a>
        </div>
        <nav class="trip-service-grid" aria-label="Required booking modules">
            <a href="index.php?page=bus-search"><span class="dash-icon dash-ico-bus"></span><strong>Bus Booking</strong></a>
            <a href="index.php?page=train-search"><span class="dash-icon dash-ico-train"></span><strong>Train Booking</strong></a>
            <a href="index.php?page=hotel-search"><span class="dash-icon dash-ico-hotel"></span><strong>Hotel Booking</strong></a>
            <a href="index.php?page=group-tours"><span class="dash-icon dash-ico-bag"></span><strong>Group Tours</strong></a>
            <a href="index.php?page=driver-offers"><span class="dash-icon dash-ico-car"></span><strong>Driver Offers</strong></a>
            <a href="index.php?page=restaurant-search"><span class="dash-icon dash-ico-food"></span><strong>Restaurants</strong></a>
            <a href="index.php?page=rewards-offers"><span class="dash-icon dash-ico-offer"></span><strong>Offers</strong></a>
        </nav>

        <div class="travel-section-head">
            <h2>Exclusive Offers</h2>
            <a href="index.php?page=rewards-offers">View All</a>
        </div>
        <section class="offer-row">
            <a class="offer-card orange-offer" href="index.php?page=rewards-offers">
                <span class="offer-badge">25%</span>
                <strong>Flat 20% OFF</strong>
                <small>On flights and ticket booking</small>
            </a>
            <a class="offer-card green-offer" href="index.php?page=rewards-offers">
                <span class="offer-badge">Up to</span>
                <strong>30% OFF</strong>
                <small>On hotels</small>
            </a>
        </section>

        <div class="travel-section-head">
            <h2>Recently Searched</h2>
            <a href="index.php?page=my-bookings">Clear all</a>
        </div>
        <div class="recent-searches">
            <a href="index.php?page=hotel-search">Goa, India</a>
            <a href="index.php?page=plan-trip">Manali, India</a>
            <a href="index.php?page=tour-ticket-search">Dubai, UAE</a>
        </div>

        <div class="dashboard-mini-summary">
            <span><strong><?php echo h($bookingTotal); ?></strong> bookings</span>
            <span><strong><?php echo h(user_table_count('rewards', $userId)); ?></strong> rewards</span>
            <a href="index.php?page=logout">Logout</a>
        </div>
    </section>
    <?php
    app_footer('user', 'home');
}

function render_ride_flow_steps(int $activeStep): void
{
    $steps = [
        1 => 'Search',
        2 => 'Captain',
        3 => 'Status',
        4 => 'Payment',
        5 => 'Completed',
    ];
    ?>
    <div class="ride-flow-steps" aria-label="Quick ride booking progress">
        <?php foreach ($steps as $stepNumber => $label): ?>
            <span class="ride-flow-step <?php echo h($stepNumber === $activeStep ? 'active' : ($stepNumber < $activeStep ? 'done' : '')); ?>">
                <b><?php echo h($stepNumber); ?></b>
                <small><?php echo h($label); ?></small>
            </span>
        <?php endforeach; ?>
    </div>
    <?php
}

function page_book_ride(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');
    app_header('Book Ride', true, 'with-bottom-nav');
    ?>
    <?php render_ride_flow_steps(1); ?>
    <div class="auth-hero">
        <span class="auth-badge">Ride booking</span>
        <h2>Quick Ride</h2>
        <p>Search your pickup, drop, ride type, and find nearby riders.</p>
    </div>

    <form class="form card auth-card" method="post" action="index.php?page=book-ride">
        <input type="hidden" name="action" value="book_ride">
        <div class="location-picker">
            <div class="location-row">
                <span class="location-pin">📍</span>
                <div class="field">
                    <label for="pickup_location">Pickup location</label>
                    <input id="pickup_location" name="pickup_location" type="text" placeholder="University Road, Peshawar" required>
                </div>
            </div>
            <span class="location-connector"></span>
            <div class="location-row">
                <span class="location-pin drop">🏁</span>
                <div class="field">
                    <label for="drop_location">Drop location</label>
                    <input id="drop_location" name="drop_location" type="text" placeholder="Faisal Mosque, Islamabad" required>
                </div>
            </div>
            <p class="form-note">Demo map uses pickup 34.0151, 71.5249 and drop 33.6844, 73.0479.</p>
        </div>

        <div class="field">
            <label>Ride type</label>
            <div class="ride-type-grid">
                <?php foreach (ride_type_options() as $value => $option): ?>
                    <label class="ride-radio">
                        <input type="radio" name="ride_type" value="<?php echo h($value); ?>" <?php echo $value === 'bike' ? 'checked' : ''; ?> required>
                        <span>
                            <b><?php echo h($option['icon'] . ' ' . $option['label']); ?></b>
                            <small>Estimated fare: Rs <?php echo h($option['fare']); ?></small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label for="travel_date">Travel date</label>
                <input id="travel_date" name="travel_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="travel_time">Travel time</label>
                <input id="travel_time" name="travel_time" type="time" required>
            </div>
        </div>

        <button class="btn btn-orange" type="submit">Find Captains</button>
        <p class="form-note">Fare is fixed for this demo and saved with the ride.</p>
    </form>
    <?php
    app_footer('user', 'quick');
}

function page_available_captains(): void
{
    require_role('user', 'user-login');
    $rideId = (int) ($_GET['ride_id'] ?? 0);
    $ride = get_user_ride($rideId);

    if (!$ride && isset($_SESSION['last_ride_id'])) {
        $rideId = (int) $_SESSION['last_ride_id'];
        $ride = get_user_ride($rideId);
    }

    if (!$ride) {
        $rideId = get_latest_user_ride_id();
        $ride = get_user_ride($rideId);
    }

    if (!$ride) {
        set_flash('danger', 'Ride not found. Please book a ride again.');
        redirect_to('book-ride');
    }

    $captains = [];
    $pdo = db();
    if ($pdo) {
        try {
            ensure_captain_table_ready($pdo);
            $stmt = $pdo->prepare(
                'SELECT id, full_name, vehicle_type, vehicle_number, city, rating
                 FROM captains
                 WHERE availability_status = "available"
                   AND account_status IN ("active", "approved")
                 ORDER BY rating DESC, full_name ASC'
            );
            $stmt->execute();
            $captains = $stmt->fetchAll();
        } catch (Throwable $e) {
            set_flash('danger', 'Could not load captains: ' . $e->getMessage());
        }
    }

    app_header('Available Captains', true, 'with-bottom-nav');
    ?>
    <?php render_ride_flow_steps(2); ?>
    <div class="card ride-summary">
        <div class="summary-row"><span>Pickup</span><strong><?php echo h($ride['pickup_location']); ?></strong></div>
        <div class="summary-row"><span>Drop</span><strong><?php echo h($ride['drop_location']); ?></strong></div>
        <div class="summary-row"><span>Ride</span><strong><?php echo h(ride_type_label($ride['ride_type'])); ?> · Rs <?php echo h($ride['fare']); ?></strong></div>
    </div>

    <div class="section-title">
        <h2>Available Captains</h2>
        <span class="badge"><?php echo count($captains); ?> found</span>
    </div>

    <?php if (!$captains): ?>
        <div class="card">
            <h2 class="hero-title">No captains available</h2>
            <p class="lead">Please try again later or change your ride details.</p>
            <a class="btn" href="index.php?page=book-ride">Back to Ride Booking</a>
        </div>
    <?php else: ?>
        <?php foreach ($captains as $captain): ?>
            <form class="captain-card rider-option-card" method="post" action="index.php?page=available-captains&ride_id=<?php echo h($rideId); ?>">
                <input type="hidden" name="action" value="select_captain">
                <input type="hidden" name="ride_id" value="<?php echo h($rideId); ?>">
                <input type="hidden" name="captain_id" value="<?php echo h($captain['id']); ?>">
                <span class="captain-avatar"><?php echo h(strtoupper(substr($captain['full_name'], 0, 1))); ?></span>
                <span>
                    <h3><?php echo h($captain['full_name']); ?></h3>
                    <p>
                        <?php echo h(ride_type_label($captain['vehicle_type'])); ?> ·
                        <?php echo h($captain['vehicle_number']); ?> ·
                        <?php echo h($captain['city'] ?? 'City not set'); ?><br>
                        Rating <?php echo h($captain['rating'] ?? '5.00'); ?> · Available now
                    </p>
                    <span class="rider-fare-pill">
                        <strong>Rs <?php echo h($ride['fare']); ?></strong>
                        <small>Send request</small>
                    </span>
                    <button class="btn btn-orange" type="submit">Send Request</button>
                </span>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    app_footer('user', 'quick');
}

function page_ride_confirm(): void
{
    require_role('user', 'user-login');
    $rideId = (int) ($_GET['ride_id'] ?? 0);
    $ride = get_user_ride($rideId);

    if (!$ride) {
        set_flash('danger', 'Ride not found.');
        redirect_to('book-ride');
    }

    app_header('Ride Confirm', true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Ride confirmation</span>
        <h2>Your ride is ready</h2>
        <p>Review the details, proceed to payment, or open tracking for the demo route.</p>
    </div>

    <div class="card ride-summary">
        <div class="summary-row"><span>Pickup</span><strong><?php echo h($ride['pickup_location']); ?></strong></div>
        <div class="summary-row"><span>Drop</span><strong><?php echo h($ride['drop_location']); ?></strong></div>
        <div class="summary-row"><span>Ride type</span><strong><?php echo h(ride_type_label($ride['ride_type'])); ?></strong></div>
        <div class="summary-row"><span>Date/time</span><strong><?php echo h(($ride['travel_date'] ?? 'Today') . ' ' . substr((string) ($ride['travel_time'] ?? ''), 0, 5)); ?></strong></div>
        <div class="summary-row"><span>Fare</span><strong>Rs <?php echo h($ride['fare']); ?></strong></div>
        <div class="summary-row"><span>Status</span><strong><?php echo h(ride_status_label((string) ($ride['status'] ?? 'pending'))); ?></strong></div>
    </div>

    <div class="section-title"><h2>Captain</h2><span class="badge">Selected</span></div>
    <div class="captain-card">
        <span class="captain-avatar"><?php echo h(strtoupper(substr((string) ($ride['captain_name'] ?? 'C'), 0, 1))); ?></span>
        <span>
            <h3><?php echo h($ride['captain_name'] ?? 'No captain selected'); ?></h3>
            <p>
                <?php echo h(ride_type_label($ride['captain_vehicle_type'] ?? 'car')); ?> ·
                <?php echo h($ride['captain_vehicle_number'] ?? 'Vehicle pending'); ?> ·
                <?php echo h($ride['captain_city'] ?? 'City pending'); ?>
            </p>
        </span>
    </div>

    <div class="btn-row" style="margin-top: 14px;">
        <a class="btn btn-orange" href="index.php?page=payment&booking_type=ride&booking_id=<?php echo h($rideId); ?>&amount=<?php echo h($ride['fare']); ?>">Proceed to Payment</a>
        <a class="btn" href="index.php?page=ride-tracking&ride_id=<?php echo h($rideId); ?>">Track Ride</a>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_ride_tracking(): void
{
    require_role('user', 'user-login');
    $rideId = (int) ($_GET['ride_id'] ?? 0);
    $ride = get_user_ride($rideId);

    if (!$ride) {
        set_flash('danger', 'Ride not found.');
        redirect_to('book-ride');
    }

    $isPaid = strtolower((string) ($ride['payment_status'] ?? '')) === 'paid';
    $rideStatus = strtolower((string) ($ride['status'] ?? 'pending'));
    $isCompleted = $rideStatus === 'completed';
    $displayStatus = ride_status_label($rideStatus);
    $isRequestWaiting = in_array($rideStatus, ['pending', 'captain_selected'], true);

    app_header('Ride Tracking', true, 'with-bottom-nav');
    ?>
    <?php render_ride_flow_steps(3); ?>
    <div
        id="rideMap"
        class="map-box"
        data-pickup-lat="<?php echo h($ride['pickup_lat'] ?: 34.0151); ?>"
        data-pickup-lng="<?php echo h($ride['pickup_lng'] ?: 71.5249); ?>"
        data-drop-lat="<?php echo h($ride['drop_lat'] ?: 33.6844); ?>"
        data-drop-lng="<?php echo h($ride['drop_lng'] ?: 73.0479); ?>"
    ></div>

    <div class="captain-card on-ride-card">
        <span class="captain-avatar"><?php echo h(strtoupper(substr((string) ($ride['captain_name'] ?? 'R'), 0, 1))); ?></span>
        <span>
            <h3><?php echo h($ride['captain_name'] ?? 'Rider assigned'); ?></h3>
            <p>
                <?php echo h(ride_type_label($ride['captain_vehicle_type'] ?? $ride['ride_type'])); ?> -
                <?php echo h($ride['captain_vehicle_number'] ?? 'Vehicle pending'); ?><br>
                <?php if ($isRequestWaiting): ?>
                    Waiting for captain approval
                <?php else: ?>
                    3 min away - OTP: <strong>1234</strong>
                <?php endif; ?>
            </p>
        </span>
        <span class="ride-call-actions"><b>Call</b><b>Chat</b></span>
    </div>

    <div class="card ride-summary" style="margin-top: 14px;">
        <div class="summary-row"><span>Pickup location</span><strong><?php echo h($ride['pickup_location']); ?></strong></div>
        <div class="summary-row"><span>Drop location</span><strong><?php echo h($ride['drop_location']); ?></strong></div>
        <div class="summary-row"><span>Fare</span><strong>Rs <?php echo h($ride['fare']); ?></strong></div>
        <div class="summary-row"><span>Status</span><strong><?php echo h($displayStatus); ?></strong></div>
    </div>

    <div class="section-title"><h2>Ride status</h2><span class="badge"><?php echo h($displayStatus); ?></span></div>
    <div class="status-rail">
        <div class="status-step active"><span class="status-dot"></span> Pickup marker placed</div>
        <div class="status-step active"><span class="status-dot"></span> Route drawn with Leaflet</div>
        <div class="status-step active"><span class="status-dot"></span> Drop marker placed</div>
        <div class="status-step <?php echo $isRequestWaiting ? 'active' : ''; ?>"><span class="status-dot"></span> Captain: <?php echo h($ride['captain_name'] ?? 'Pending'); ?> - <?php echo h($displayStatus); ?></div>
    </div>

    <div class="btn-row" style="margin-top: 14px;">
        <a class="btn btn-light" href="index.php?page=my-bookings">Cancel Ride</a>
        <?php if ($isPaid || $isCompleted): ?>
            <a class="btn btn-orange" href="index.php?page=ride-success&ride_id=<?php echo h($rideId); ?>">View Completed Ride</a>
        <?php else: ?>
            <a class="btn btn-orange" href="index.php?page=payment&booking_type=ride&booking_id=<?php echo h($rideId); ?>&amount=<?php echo h($ride['fare']); ?>">Proceed to Payment</a>
        <?php endif; ?>
        <a class="btn btn-light" href="index.php?page=ride-confirm&ride_id=<?php echo h($rideId); ?>">Ride Details</a>
    </div>
    <?php
    app_footer('user', 'quick');
}

function page_ride_success(): void
{
    require_role('user', 'user-login');
    $rideId = (int) ($_GET['ride_id'] ?? 0);
    $ride = get_user_ride($rideId);

    if (!$ride) {
        set_flash('danger', 'Ride not found.');
        redirect_to('book-ride');
    }

    $isCompleted = strtolower((string) ($ride['status'] ?? '')) === 'completed';
    $hasFeedback = user_feedback_exists('ride', $rideId, current_user_id() ?? 0);

    app_header('Ride Success', true, 'with-bottom-nav');
    ?>
    <?php render_ride_flow_steps(5); ?>
    <div class="card module-page-card">
        <span class="module-page-icon">✅</span>
        <div>
            <h2 class="hero-title"><?php echo $isCompleted ? 'Ride completed' : 'Ride booked successfully'; ?></h2>
            <p class="lead"><?php echo $isCompleted ? 'Thank you for riding with TripNovaa. You can now submit feedback.' : 'Your ride has been saved. Complete payment to open feedback.'; ?></p>
        </div>
        <div class="ride-summary">
            <div class="summary-row"><span>Pickup</span><strong><?php echo h($ride['pickup_location']); ?></strong></div>
            <div class="summary-row"><span>Drop</span><strong><?php echo h($ride['drop_location']); ?></strong></div>
            <div class="summary-row"><span>Captain</span><strong><?php echo h($ride['captain_name'] ?? 'Pending'); ?></strong></div>
            <div class="summary-row"><span>Total fare</span><strong>Rs <?php echo h($ride['fare']); ?></strong></div>
            <div class="summary-row"><span>Payment</span><strong><?php echo h(ucwords((string) ($ride['payment_status'] ?? 'unpaid'))); ?></strong></div>
        </div>
        <?php if ($isCompleted): ?>
            <div class="ride-rating-preview">
                <strong>How was your ride?</strong>
                <span>&#9733; &#9733; &#9733; &#9733; &#9733;</span>
                <small><?php echo $hasFeedback ? 'Feedback already submitted' : 'Tap Give Feedback to rate your rider'; ?></small>
            </div>
        <?php endif; ?>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <?php if ($isCompleted && !$hasFeedback): ?>
                <a class="btn btn-orange" href="index.php?page=feedback&booking_type=ride&booking_id=<?php echo h($rideId); ?>">Give Feedback</a>
            <?php elseif (!$isCompleted): ?>
                <a class="btn btn-orange" href="index.php?page=payment&booking_type=ride&booking_id=<?php echo h($rideId); ?>&amount=<?php echo h($ride['fare']); ?>">Pay Now</a>
            <?php endif; ?>
            <a class="btn" href="index.php?page=user-dashboard">Home</a>
        </div>
    </div>
    <?php
    app_footer('user', 'quick');
}

function page_feedback(): void
{
    require_role('user', 'user-login');
    $bookingType = trim($_GET['booking_type'] ?? 'ride');
    $bookingId = (int) ($_GET['booking_id'] ?? 0);
    $userId = current_user_id() ?? 0;
    $ride = $bookingType === 'ride' ? get_completed_ride_for_feedback($bookingId, $userId) : null;
    $alreadySubmitted = $bookingType === 'ride' && user_feedback_exists('ride', $bookingId, $userId);

    app_header('Feedback', true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Ride feedback</span>
        <h2>Rate your completed ride</h2>
        <p>Your feedback helps TripNovaa improve rides and updates the captain's average rating.</p>
    </div>

    <?php if (!$ride): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">⭐</span>
            <div>
                <h2 class="hero-title">Feedback not available</h2>
                <p class="lead">Feedback opens after the ride status becomes completed.</p>
            </div>
            <a class="btn" href="index.php?page=my-bookings">My Bookings</a>
        </div>
    <?php elseif ($alreadySubmitted): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">✅</span>
            <div>
                <h2 class="hero-title">Feedback already sent</h2>
                <p class="lead">You have already reviewed this completed ride.</p>
            </div>
            <a class="btn" href="index.php?page=my-bookings">My Bookings</a>
        </div>
    <?php else: ?>
        <div class="card ride-summary">
            <div class="summary-row"><span>Ride</span><strong>#<?php echo h($bookingId); ?></strong></div>
            <div class="summary-row"><span>Pickup</span><strong><?php echo h($ride['pickup_location'] ?? ''); ?></strong></div>
            <div class="summary-row"><span>Drop</span><strong><?php echo h($ride['drop_location'] ?? ''); ?></strong></div>
            <div class="summary-row"><span>Captain</span><strong><?php echo h($ride['captain_name'] ?? 'Captain'); ?></strong></div>
        </div>

        <form class="form card auth-card" method="post" action="index.php?page=feedback">
            <input type="hidden" name="action" value="feedback_submit">
            <input type="hidden" name="booking_type" value="ride">
            <input type="hidden" name="booking_id" value="<?php echo h($bookingId); ?>">
            <div class="field">
                <label for="feedback_rating">Rating</label>
                <select id="feedback_rating" name="rating" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3">3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Bad</option>
                </select>
            </div>
            <div class="field">
                <label for="feedback_comment">Comment</label>
                <textarea id="feedback_comment" name="comment" placeholder="Share your ride experience..." required></textarea>
            </div>
            <button class="btn btn-orange" type="submit">Submit Feedback</button>
            <a class="btn btn-light" href="index.php?page=my-bookings">Cancel</a>
        </form>
    <?php endif; ?>
    <?php
    app_footer('user', 'bookings');
}

function page_feedback_success(): void
{
    require_role('user', 'user-login');
    app_header('Feedback Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">⭐</span>
        <div>
            <h2 class="hero-title">Feedback submitted</h2>
            <p class="lead">Thanks for reviewing your completed ride. The captain rating has been refreshed.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=user-dashboard">Home</a>
        </div>
    </div>
    <?php
    app_footer('user', 'bookings');
}

function page_payment(): void
{
    require_role('user', 'user-login');

    if (($_GET['action'] ?? '') === 'cashfree_return') {
        handle_cashfree_return();
        return;
    }

    $bookingType = trim($_GET['booking_type'] ?? '');
    $bookingId = (int) ($_GET['booking_id'] ?? 0);
    $amount = (float) ($_GET['amount'] ?? 0);
    $couponCode = strtoupper(trim($_GET['coupon_code'] ?? ''));

    if (!in_array($bookingType, supported_booking_types(), true) || $bookingId <= 0 || $amount <= 0) {
        set_flash('danger', 'Invalid payment link.');
        redirect_to('payment-failed');
    }

    $booking = get_booking_payment_details($bookingType, $bookingId, $amount);
    if (!$booking) {
        set_flash('danger', 'Booking not found for this customer.');
        redirect_to('payment-failed', [
            'booking_type' => $bookingType,
            'booking_id' => $bookingId,
            'amount' => $amount,
        ]);
    }

    $amount = (float) ($booking['amount'] ?? $amount);
    $offerResult = calculate_offer_result($couponCode, $amount);
    $discountAmount = (float) $offerResult['discount_amount'];
    $finalAmount = (float) $offerResult['final_amount'];
    $customer = get_current_customer() ?? [];
    $config = cashfree_config();
    $checkoutOrderId = trim((string) ($_GET['cf_order_id'] ?? ''));
    $pendingCheckout = $checkoutOrderId !== '' ? ($_SESSION['cashfree_pending'][$checkoutOrderId] ?? null) : null;
    $checkoutSessionId = is_array($pendingCheckout) ? (string) ($pendingCheckout['order']['payment_session_id'] ?? '') : '';

    app_header('Payment', true, 'with-bottom-nav');
    ?>
    <?php if ($bookingType === 'ride') render_ride_flow_steps(4); ?>
    <div class="auth-hero">
        <span class="auth-badge">Cashfree <?php echo h($config['configured'] ? $config['mode'] : 'demo fallback'); ?></span>
        <h2>Secure payment</h2>
        <p>TripNovaa creates a Cashfree order and opens Web Checkout when sandbox or production keys are configured.</p>
    </div>

    <div class="payment-brand">
        <span>
            <strong>Cashfree Payments</strong>
            <span>Environment: <?php echo h($config['mode']); ?> · App ID placeholder configured in code</span>
        </span>
        <span class="cashfree-mark">CF</span>
    </div>

    <div class="section-title"><h2>Payment summary</h2><span class="badge"><?php echo h(strtoupper($bookingType)); ?></span></div>
    <div class="card ride-summary">
        <div class="summary-row"><span>Booking type</span><strong><?php echo h(ucwords($bookingType)); ?></strong></div>
        <div class="summary-row"><span>Booking ID</span><strong>#<?php echo h($bookingId); ?></strong></div>
        <div class="summary-row"><span>Booking</span><strong><?php echo h($booking['title'] ?? 'TripNovaa booking'); ?></strong></div>
        <div class="summary-row"><span>Original amount</span><strong>Rs <?php echo h(number_format($amount, 2)); ?></strong></div>
        <?php if ($couponCode !== ''): ?>
            <div class="summary-row"><span>Coupon</span><strong><?php echo h($couponCode); ?> · <?php echo h($offerResult['message']); ?></strong></div>
        <?php endif; ?>
        <div class="summary-row"><span>Discount</span><strong>Rs <?php echo h(number_format($discountAmount, 2)); ?></strong></div>
        <div class="summary-row"><span>Final amount</span><strong>Rs <?php echo h(number_format($finalAmount, 2)); ?></strong></div>
        <div class="summary-row"><span>Customer</span><strong><?php echo h($customer['full_name'] ?? current_user_name()); ?></strong></div>
        <div class="summary-row"><span>Email</span><strong><?php echo h($customer['email'] ?? ''); ?></strong></div>
        <div class="summary-row"><span>Phone</span><strong><?php echo h($customer['phone'] ?? ''); ?></strong></div>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="payment">
        <input type="hidden" name="booking_type" value="<?php echo h($bookingType); ?>">
        <input type="hidden" name="booking_id" value="<?php echo h($bookingId); ?>">
        <input type="hidden" name="amount" value="<?php echo h($amount); ?>">
        <div class="field">
            <label for="coupon_code">Coupon code</label>
            <input id="coupon_code" name="coupon_code" type="text" value="<?php echo h($couponCode); ?>" placeholder="TRIP10, HOTEL20, BUS50, TICKET15">
        </div>
        <button class="btn btn-light" type="submit">Apply Coupon</button>
    </form>

    <form class="form card auth-card" method="post" action="index.php?page=payment">
        <input type="hidden" name="action" value="cashfree_demo_payment">
        <input type="hidden" name="booking_type" value="<?php echo h($bookingType); ?>">
        <input type="hidden" name="booking_id" value="<?php echo h($bookingId); ?>">
        <input type="hidden" name="amount" value="<?php echo h($amount); ?>">
        <input type="hidden" name="coupon_code" value="<?php echo h($offerResult['valid'] ? $couponCode : ''); ?>">
        <div class="demo-box">
            <?php if ($config['configured']): ?>
                Real Cashfree API mode is enabled. This will create an order on Cashfree and open the Cashfree Web Checkout SDK.
            <?php else: ?>
                Add <strong>CASHFREE_APP_ID</strong> and <strong>CASHFREE_SECRET_KEY</strong> to enable real Cashfree sandbox checkout. Until then, TripNovaa uses a local demo fallback.
            <?php endif; ?>
        </div>
        <div class="payment-method-list">
            <div class="payment-method-option active"><span class="payment-method-icon cash">Rs</span><strong>Cash</strong><small>Secure demo payment</small><span class="method-radio active"></span></div>
            <div class="payment-method-option"><span class="payment-method-icon upi">UPI</span><strong>UPI</strong><small>Pay using any UPI app</small><span class="method-radio"></span></div>
            <div class="payment-method-option"><span class="payment-method-icon phonepe">Ph</span><strong>PhonePe</strong><small>Demo option</small><span class="method-radio"></span></div>
            <div class="payment-method-option"><span class="payment-method-icon card">CC</span><strong>Credit / Debit Card</strong><small>Demo option</small><span class="method-radio"></span></div>
            <div class="payment-method-option"><span class="payment-method-icon wallet">W</span><strong>Wallet</strong><small>Demo option</small><span class="method-radio"></span></div>
        </div>
        <button class="btn btn-orange" type="submit">Pay Rs <?php echo h(number_format($finalAmount, 2)); ?></button>
        <a class="btn btn-light" href="index.php?page=user-dashboard">Cancel</a>
    </form>

    <?php if ($checkoutSessionId !== ''): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">CF</span>
            <div>
                <h2 class="hero-title">Opening Cashfree</h2>
                <p class="lead">If checkout does not open automatically, tap the button below.</p>
            </div>
            <button class="btn btn-orange" id="cashfreeCheckoutButton" type="button">Open Cashfree Checkout</button>
        </div>
        <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const openCashfreeCheckout = () => {
                    if (typeof Cashfree !== 'function') {
                        return;
                    }
                    const cashfree = Cashfree({ mode: <?php echo json_encode($config['mode']); ?> });
                    cashfree.checkout({
                        paymentSessionId: <?php echo json_encode($checkoutSessionId); ?>,
                        redirectTarget: '_self'
                    });
                };
                document.getElementById('cashfreeCheckoutButton')?.addEventListener('click', openCashfreeCheckout);
                openCashfreeCheckout();
            });
        </script>
    <?php elseif ($checkoutOrderId !== ''): ?>
        <div class="alert alert-warning">Cashfree checkout session was not found. Please start the payment again.</div>
    <?php endif; ?>
    <?php
    app_footer('user', $bookingType === 'ride' ? 'quick' : 'home');
}

function page_payment_success(): void
{
    require_role('user', 'user-login');
    $paymentId = (int) ($_GET['payment_id'] ?? 0);
    $payment = get_user_payment($paymentId);

    app_header('Payment Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">✅</span>
        <div>
            <h2 class="hero-title">Payment successful</h2>
            <p class="lead">Your Cashfree payment has been saved and reward points were added.</p>
        </div>
        <?php if ($payment): ?>
            <div class="ride-summary">
                <div class="summary-row"><span>Payment ID</span><strong>#<?php echo h($payment['id']); ?></strong></div>
                <div class="summary-row"><span>Booking type</span><strong><?php echo h(ucwords($payment['booking_type'])); ?></strong></div>
                <div class="summary-row"><span>Amount</span><strong>Rs <?php echo h(number_format((float) $payment['amount'], 2)); ?></strong></div>
                <div class="summary-row"><span>Status</span><strong><?php echo h(ucwords($payment['payment_status'])); ?></strong></div>
                <div class="summary-row"><span>Order ID</span><strong><?php echo h($payment['cashfree_order_id']); ?></strong></div>
            </div>
        <?php endif; ?>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <?php if ($payment && ($payment['booking_type'] ?? '') === 'ride' && !empty($payment['ride_id'])): ?>
                <?php
                $paymentRideId = (int) $payment['ride_id'];
                $canReviewRide = get_completed_ride_for_feedback($paymentRideId, current_user_id() ?? 0)
                    && !user_feedback_exists('ride', $paymentRideId, current_user_id() ?? 0);
                ?>
                <?php if ($canReviewRide): ?>
                    <a class="btn btn-orange" href="index.php?page=feedback&booking_type=ride&booking_id=<?php echo h($paymentRideId); ?>">Give Feedback</a>
                <?php endif; ?>
            <?php endif; ?>
            <a class="btn" href="index.php?page=user-dashboard">Home</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_payment_failed(): void
{
    require_role('user', 'user-login');
    $bookingType = trim($_GET['booking_type'] ?? '');
    $bookingId = (int) ($_GET['booking_id'] ?? 0);
    $amount = (float) ($_GET['amount'] ?? 0);
    $retryUrl = 'index.php?page=user-dashboard';

    if (in_array($bookingType, supported_booking_types(), true) && $bookingId > 0 && $amount > 0) {
        $retryUrl = 'index.php?' . http_build_query([
            'page' => 'payment',
            'booking_type' => $bookingType,
            'booking_id' => $bookingId,
            'amount' => $amount,
        ]);
    }

    app_header('Payment Failed', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">⚠</span>
        <div>
            <h2 class="hero-title">Payment failed</h2>
            <p class="lead">The Cashfree test/demo payment could not be completed. Please review the booking details and try again.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=user-dashboard">Home</a>
            <a class="btn btn-orange" href="<?php echo h($retryUrl); ?>">Try Again</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_hotel_search(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $hotels = hotel_catalog();

    app_header('Hotel Booking', false, 'with-bottom-nav booking-mobile-screen hotel-mobile-screen');
    ?>
    <section class="booking-mobile">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>Hotel Booking</h2>
            <span></span>
        </div>

        <form class="booking-search-card" method="get" action="index.php">
            <input type="hidden" name="page" value="hotel-list">
            <div class="field">
                <label for="hotel_city_mobile">Destination / Hotel City</label>
                <input id="hotel_city_mobile" name="city" type="text" value="Varanasi" required>
            </div>
            <div class="row compact-row">
                <div class="field">
                    <label for="check_in_mobile">Check-in</label>
                    <input id="check_in_mobile" name="check_in" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
                </div>
                <div class="field">
                    <label for="check_out_mobile">Check-out</label>
                    <input id="check_out_mobile" name="check_out" type="date" min="<?php echo h($tomorrow); ?>" value="<?php echo h($tomorrow); ?>" required>
                </div>
            </div>
            <div class="row compact-row">
                <div class="field">
                    <label for="hotel_guests_mobile">Guests</label>
                    <input id="hotel_guests_mobile" name="guests" type="number" min="1" max="12" value="2" required>
                </div>
                <div class="field">
                    <label for="hotel_rooms_mobile">Rooms</label>
                    <input id="hotel_rooms_mobile" name="rooms" type="number" min="1" max="6" value="1" required>
                </div>
            </div>
            <button class="btn" type="submit">Search Hotels</button>
        </form>

        <div class="mobile-section-head">
            <h3>Recommended Hotels</h3>
        </div>
        <div class="mobile-result-list">
            <?php foreach (array_slice($hotels, 0, 3, true) as $key => $hotel): ?>
                <?php
                $bookingUrl = 'index.php?' . http_build_query([
                    'page' => 'hotel-book',
                    'hotel_key' => $key,
                    'city' => 'Varanasi',
                    'check_in' => $today,
                    'check_out' => $tomorrow,
                    'guests' => 2,
                    'rooms' => 1,
                ]);
                ?>
                <a class="mobile-result-card hotel-result-card" href="<?php echo h($bookingUrl); ?>">
                    <span class="result-thumb" style="background-image: url('<?php echo h($hotel['image']); ?>');"></span>
                    <span class="result-copy">
                        <strong><?php echo h($hotel['name']); ?></strong>
                        <small><?php echo h($hotel['room_type']); ?> - Varanasi</small>
                        <em>Rating <?php echo h($hotel['rating']); ?></em>
                    </span>
                    <b>Rs <?php echo h(number_format((float) $hotel['price'])); ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    app_footer('user', 'home');
    return;
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Hotel booking</span>
        <h2>Find your next stay</h2>
        <p>Search hotels by city, dates, and guests, then confirm with Cashfree test payment.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="hotel-list">
        <div class="field">
            <label for="hotel_city">City</label>
            <input id="hotel_city" name="city" type="text" value="Lahore" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="check_in">Check-in</label>
                <input id="check_in" name="check_in" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="check_out">Check-out</label>
                <input id="check_out" name="check_out" type="date" min="<?php echo h($tomorrow); ?>" value="<?php echo h($tomorrow); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="hotel_guests">Guests</label>
            <input id="hotel_guests" name="guests" type="number" min="1" max="12" value="2" required>
        </div>
        <button class="btn btn-orange" type="submit">Search Hotels</button>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_hotel_list(): void
{
    require_role('user', 'user-login');
    $city = trim($_GET['city'] ?? 'Lahore');
    $checkIn = trim($_GET['check_in'] ?? date('Y-m-d'));
    $checkOut = trim($_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day')));
    $guests = max(1, (int) ($_GET['guests'] ?? 1));
    $rooms = max(1, (int) ($_GET['rooms'] ?? 1));
    $hotels = hotel_catalog();

    app_header('Hotel List', true, 'with-bottom-nav');
    ?>
    <div class="section-title">
        <h2>Hotels in <?php echo h($city); ?></h2>
        <a class="tiny-link" href="index.php?page=hotel-search">Edit</a>
    </div>
    <div class="demo-box">
        <?php echo h($checkIn); ?> to <?php echo h($checkOut); ?> · <?php echo h($guests); ?> guest<?php echo $guests > 1 ? 's' : ''; ?>
    </div>

    <?php foreach ($hotels as $key => $hotel): ?>
        <?php
        $bookingUrl = 'index.php?' . http_build_query([
            'page' => 'hotel-book',
            'hotel_key' => $key,
            'city' => $city,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'rooms' => $rooms,
        ]);
        ?>
        <article class="hotel-card">
            <div class="hotel-photo" style="background-image: url('<?php echo h($hotel['image']); ?>');">
                <span>
                    <strong><?php echo h($hotel['name']); ?></strong>
                    <span><?php echo h($city); ?> · <?php echo h($hotel['room_type']); ?></span>
                </span>
                <span class="hotel-rating">★ <?php echo h($hotel['rating']); ?></span>
            </div>
            <div class="hotel-body">
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Price</span><strong>Rs <?php echo h(number_format($hotel['price'])); ?></strong></div>
                    <div class="hotel-info"><span>Room</span><strong><?php echo h($hotel['room_type']); ?></strong></div>
                    <div class="hotel-info"><span>Guests</span><strong><?php echo h($guests); ?></strong></div>
                </div>
                <a class="btn" href="<?php echo h($bookingUrl); ?>">Book Now</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'home');
}

function page_hotel_book(): void
{
    require_role('user', 'user-login');
    $hotelKey = trim($_GET['hotel_key'] ?? 'luxury');
    $hotel = get_hotel_from_catalog($hotelKey);
    if (!$hotel) {
        set_flash('danger', 'Selected hotel was not found.');
        redirect_to('hotel-search');
    }

    $city = trim($_GET['city'] ?? 'Lahore');
    $checkIn = trim($_GET['check_in'] ?? date('Y-m-d'));
    $checkOut = trim($_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day')));
    $guests = max(1, (int) ($_GET['guests'] ?? 1));
    $rooms = max(1, (int) ($_GET['rooms'] ?? 1));
    $backUrl = 'index.php?' . http_build_query([
        'page' => 'hotel-list',
        'city' => $city,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'guests' => $guests,
        'rooms' => $rooms,
    ]);

    app_header('Book Hotel', true, 'with-bottom-nav');
    ?>
    <article class="hotel-card">
        <div class="hotel-photo" style="background-image: url('<?php echo h($hotel['image']); ?>');">
            <span>
                <strong><?php echo h($hotel['name']); ?></strong>
                <span><?php echo h($city); ?> · ★ <?php echo h($hotel['rating']); ?></span>
            </span>
            <span class="hotel-rating">Rs <?php echo h(number_format($hotel['price'])); ?></span>
        </div>
    </article>

    <div class="section-title"><h2>Confirm stay</h2><span class="badge">Payment pending</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=hotel-book">
        <input type="hidden" name="action" value="hotel_booking">
        <div class="field">
            <label for="hotel_name">Hotel name</label>
            <input id="hotel_name" name="hotel_name" type="text" value="<?php echo h($hotel['name']); ?>" required>
        </div>
        <div class="field">
            <label for="hotel_book_city">City</label>
            <input id="hotel_book_city" name="city" type="text" value="<?php echo h($city); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="hotel_check_in">Check-in</label>
                <input id="hotel_check_in" name="check_in" type="date" value="<?php echo h($checkIn); ?>" required>
            </div>
            <div class="field">
                <label for="hotel_check_out">Check-out</label>
                <input id="hotel_check_out" name="check_out" type="date" value="<?php echo h($checkOut); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="room_type">Room type</label>
            <input id="room_type" name="room_type" type="text" value="<?php echo h($hotel['room_type']); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="hotel_book_guests">Guests</label>
                <input id="hotel_book_guests" name="guests" type="number" min="1" value="<?php echo h($guests); ?>" required>
            </div>
            <div class="field">
                <label for="hotel_book_rooms">Rooms</label>
                <input id="hotel_book_rooms" name="rooms" type="number" min="1" value="<?php echo h($rooms); ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label for="hotel_price">Price</label>
                <input id="hotel_price" name="price" type="number" min="1" value="<?php echo h($hotel['price']); ?>" required>
            </div>
        </div>
        <button class="btn btn-orange" type="submit">Save Booking and Pay</button>
        <a class="btn btn-light" href="<?php echo h($backUrl); ?>">Back to Hotels</a>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_hotel_success(): void
{
    require_role('user', 'user-login');
    app_header('Hotel Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">🏨</span>
        <div>
            <h2 class="hero-title">Hotel booking saved</h2>
            <p class="lead">Your hotel booking was created. Complete Cashfree test payment to confirm the stay.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=hotel-search">Book Another</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_train_search(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');
    $trains = train_catalog();

    app_header('Train Booking', false, 'with-bottom-nav booking-mobile-screen train-mobile-screen');
    ?>
    <section class="booking-mobile">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>Train Booking</h2>
            <span></span>
        </div>

        <form class="booking-search-card" method="get" action="index.php">
            <input type="hidden" name="page" value="train-list">
            <div class="field">
                <label for="train_from_mobile">From</label>
                <input id="train_from_mobile" name="from_city" type="text" value="Vijayawada (BZA)" required>
            </div>
            <div class="field">
                <label for="train_to_mobile">To</label>
                <input id="train_to_mobile" name="to_city" type="text" value="Varanasi (BSB)" required>
            </div>
            <div class="row compact-row">
                <div class="field">
                    <label for="train_date_mobile">Date of Journey</label>
                    <input id="train_date_mobile" name="travel_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
                </div>
                <div class="field">
                    <label for="train_passengers_mobile">Passengers</label>
                    <input id="train_passengers_mobile" name="passengers" type="number" min="1" max="10" value="1" required>
                </div>
            </div>
            <button class="btn" type="submit">Search Trains</button>
        </form>

        <div class="mobile-section-head">
            <h3>Popular Trains</h3>
        </div>
        <div class="mobile-result-list">
            <?php foreach (array_slice($trains, 0, 3, true) as $key => $train): ?>
                <?php
                $bookingUrl = 'index.php?' . http_build_query([
                    'page' => 'train-book',
                    'train_key' => $key,
                    'from_city' => 'Vijayawada (BZA)',
                    'to_city' => 'Varanasi (BSB)',
                    'travel_date' => $today,
                    'passengers' => 1,
                ]);
                ?>
                <a class="mobile-result-card transport-result-card" href="<?php echo h($bookingUrl); ?>">
                    <span class="result-vehicle train-art"></span>
                    <span class="result-copy">
                        <strong><?php echo h($train['train_number'] . ' - ' . $train['name']); ?></strong>
                        <small><?php echo h($train['departure']); ?> - <?php echo h($train['arrival']); ?></small>
                        <em><?php echo h($train['seat_type']); ?> - Available</em>
                    </span>
                    <b>Rs <?php echo h(number_format((float) $train['price'])); ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    app_footer('user', 'home');
    return;
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Train booking</span>
        <h2>Choose your rail route</h2>
        <p>Search demo train routes, compare seats, and confirm with Cashfree test payment.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="train-list">
        <div class="field">
            <label for="from_city">From city</label>
            <input id="from_city" name="from_city" type="text" value="Peshawar" required>
        </div>
        <div class="field">
            <label for="to_city">To city</label>
            <input id="to_city" name="to_city" type="text" value="Lahore" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="train_travel_date">Travel date</label>
                <input id="train_travel_date" name="travel_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="train_passengers">Passengers</label>
                <input id="train_passengers" name="passengers" type="number" min="1" max="10" value="1" required>
            </div>
        </div>
        <button class="btn btn-orange" type="submit">Search Trains</button>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_train_list(): void
{
    require_role('user', 'user-login');
    $fromCity = trim($_GET['from_city'] ?? 'Peshawar');
    $toCity = trim($_GET['to_city'] ?? 'Lahore');
    $travelDate = trim($_GET['travel_date'] ?? date('Y-m-d'));
    $passengers = max(1, (int) ($_GET['passengers'] ?? 1));
    $trains = train_catalog();

    app_header('Train List', true, 'with-bottom-nav');
    ?>
    <div class="section-title">
        <h2><?php echo h($fromCity); ?> to <?php echo h($toCity); ?></h2>
        <a class="tiny-link" href="index.php?page=train-search">Edit</a>
    </div>
    <div class="demo-box">
        <?php echo h($travelDate); ?> · <?php echo h($passengers); ?> passenger<?php echo $passengers > 1 ? 's' : ''; ?> · Demo train inventory
    </div>

    <?php foreach ($trains as $key => $train): ?>
        <?php
        $totalPrice = (int) $train['price'] * $passengers;
        $bookingUrl = 'index.php?' . http_build_query([
            'page' => 'train-book',
            'train_key' => $key,
            'from_city' => $fromCity,
            'to_city' => $toCity,
            'travel_date' => $travelDate,
            'passengers' => $passengers,
        ]);
        ?>
        <article class="transport-card">
            <div class="transport-head <?php echo h($train['accent']); ?>">
                <span>
                    <strong><?php echo h($train['name']); ?></strong>
                    <span><?php echo h($train['train_number']); ?> · <?php echo h($train['seat_type']); ?></span>
                </span>
                <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
            </div>
            <div class="transport-body">
                <div class="route-line">
                    <span><small>Depart</small><strong><?php echo h($train['departure']); ?></strong></span>
                    <span class="route-arrow">&rarr;</span>
                    <span><small>Arrive</small><strong><?php echo h($train['arrival']); ?></strong></span>
                </div>
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Seat</span><strong><?php echo h($train['seat_type']); ?></strong></div>
                    <div class="hotel-info"><span>Per seat</span><strong>Rs <?php echo h(number_format($train['price'])); ?></strong></div>
                    <div class="hotel-info"><span>People</span><strong><?php echo h($passengers); ?></strong></div>
                </div>
                <a class="btn" href="<?php echo h($bookingUrl); ?>">Book Train</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'home');
}

function page_train_book(): void
{
    require_role('user', 'user-login');
    $trainKey = trim($_GET['train_key'] ?? 'green-line');
    $train = get_train_from_catalog($trainKey);
    if (!$train) {
        set_flash('danger', 'Selected train was not found.');
        redirect_to('train-search');
    }

    $fromCity = trim($_GET['from_city'] ?? 'Peshawar');
    $toCity = trim($_GET['to_city'] ?? 'Lahore');
    $travelDate = trim($_GET['travel_date'] ?? date('Y-m-d'));
    $passengers = max(1, (int) ($_GET['passengers'] ?? 1));
    $totalPrice = (int) $train['price'] * $passengers;
    $backUrl = 'index.php?' . http_build_query([
        'page' => 'train-list',
        'from_city' => $fromCity,
        'to_city' => $toCity,
        'travel_date' => $travelDate,
        'passengers' => $passengers,
    ]);

    app_header('Book Train', true, 'with-bottom-nav');
    ?>
    <article class="transport-card">
        <div class="transport-head <?php echo h($train['accent']); ?>">
            <span>
                <strong><?php echo h($train['name']); ?></strong>
                <span><?php echo h($train['departure']); ?> to <?php echo h($train['arrival']); ?> · <?php echo h($train['seat_type']); ?></span>
            </span>
            <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
        </div>
        <div class="transport-body">
            <div class="route-line">
                <span><small>From</small><strong><?php echo h($fromCity); ?></strong></span>
                <span class="route-arrow">&rarr;</span>
                <span><small>To</small><strong><?php echo h($toCity); ?></strong></span>
            </div>
        </div>
    </article>

    <div class="section-title"><h2>Confirm train seats</h2><span class="badge">Payment pending</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=train-book">
        <input type="hidden" name="action" value="train_booking">
        <input type="hidden" name="train_number" value="<?php echo h($train['train_number']); ?>">
        <div class="row">
            <div class="field">
                <label for="train_from_city">From city</label>
                <input id="train_from_city" name="from_city" type="text" value="<?php echo h($fromCity); ?>" required>
            </div>
            <div class="field">
                <label for="train_to_city">To city</label>
                <input id="train_to_city" name="to_city" type="text" value="<?php echo h($toCity); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="train_date_confirm">Travel date</label>
            <input id="train_date_confirm" name="travel_date" type="date" value="<?php echo h($travelDate); ?>" required>
        </div>
        <div class="field">
            <label for="train_name">Train name</label>
            <input id="train_name" name="train_name" type="text" value="<?php echo h($train['name']); ?>" required>
        </div>
        <div class="field">
            <label for="seat_type">Seat type</label>
            <input id="seat_type" name="seat_type" type="text" value="<?php echo h($train['seat_type']); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="train_book_passengers">Passengers</label>
                <input id="train_book_passengers" name="passengers" type="number" min="1" value="<?php echo h($passengers); ?>" required>
            </div>
            <div class="field">
                <label for="train_price">Price</label>
                <input id="train_price" name="price" type="number" min="1" value="<?php echo h($totalPrice); ?>" required>
            </div>
        </div>
        <button class="btn btn-orange" type="submit">Save Booking and Pay</button>
        <a class="btn btn-light" href="<?php echo h($backUrl); ?>">Back to Trains</a>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_train_success(): void
{
    require_role('user', 'user-login');
    app_header('Train Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">🚆</span>
        <div>
            <h2 class="hero-title">Train booking saved</h2>
            <p class="lead">Your train seats were created. Complete Cashfree test payment to confirm the trip.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=train-search">Book Another</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_bus_search(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');
    $buses = bus_catalog();

    app_header('Bus Booking', false, 'with-bottom-nav booking-mobile-screen bus-mobile-screen');
    ?>
    <section class="booking-mobile">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>Bus Booking</h2>
            <span></span>
        </div>

        <div class="booking-tabs subtle-tabs">
            <span class="active">One Way</span>
            <span>Round Trip</span>
        </div>

        <form class="booking-search-card" method="get" action="index.php">
            <input type="hidden" name="page" value="bus-list">
            <div class="field">
                <label for="bus_from_mobile">From</label>
                <input id="bus_from_mobile" name="from_city" type="text" value="Vijayawada" required>
            </div>
            <div class="field">
                <label for="bus_to_mobile">To</label>
                <input id="bus_to_mobile" name="to_city" type="text" value="Hyderabad" required>
            </div>
            <div class="row compact-row">
                <div class="field">
                    <label for="bus_date_mobile">Date of Journey</label>
                    <input id="bus_date_mobile" name="travel_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
                </div>
                <div class="field">
                    <label for="bus_passengers_mobile">Passengers</label>
                    <input id="bus_passengers_mobile" name="passengers" type="number" min="1" max="8" value="1" required>
                </div>
            </div>
            <button class="btn" type="submit">Search Buses</button>
        </form>

        <div class="mobile-section-head">
            <h3>Recommended Buses</h3>
        </div>
        <div class="mobile-result-list">
            <?php foreach (array_slice($buses, 0, 3, true) as $key => $bus): ?>
                <?php
                $bookingUrl = 'index.php?' . http_build_query([
                    'page' => 'bus-book',
                    'bus_key' => $key,
                    'from_city' => 'Vijayawada',
                    'to_city' => 'Hyderabad',
                    'travel_date' => $today,
                    'passengers' => 1,
                ]);
                ?>
                <a class="mobile-result-card transport-result-card" href="<?php echo h($bookingUrl); ?>">
                    <span class="result-vehicle bus-art"></span>
                    <span class="result-copy">
                        <strong><?php echo h($bus['name']); ?></strong>
                        <small><?php echo h($bus['departure']); ?> - <?php echo h($bus['arrival']); ?></small>
                        <em><?php echo h($bus['bus_type']); ?> - <?php echo h($bus['available_seats']); ?> seats left</em>
                    </span>
                    <b>Rs <?php echo h(number_format((float) $bus['price'])); ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    app_footer('user', 'home');
    return;
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Bus booking</span>
        <h2>Find a road trip seat</h2>
        <p>Search intercity buses, compare seat availability, and confirm with Cashfree test payment.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="bus-list">
        <div class="field">
            <label for="bus_from_city">From city</label>
            <input id="bus_from_city" name="from_city" type="text" value="Lahore" required>
        </div>
        <div class="field">
            <label for="bus_to_city">To city</label>
            <input id="bus_to_city" name="to_city" type="text" value="Islamabad" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="bus_travel_date">Travel date</label>
                <input id="bus_travel_date" name="travel_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="bus_passengers">Passengers</label>
                <input id="bus_passengers" name="passengers" type="number" min="1" max="8" value="1" required>
            </div>
        </div>
        <button class="btn btn-orange" type="submit">Search Buses</button>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_bus_list(): void
{
    require_role('user', 'user-login');
    $fromCity = trim($_GET['from_city'] ?? 'Lahore');
    $toCity = trim($_GET['to_city'] ?? 'Islamabad');
    $travelDate = trim($_GET['travel_date'] ?? date('Y-m-d'));
    $passengers = max(1, (int) ($_GET['passengers'] ?? 1));
    $buses = bus_catalog();

    app_header('Bus List', true, 'with-bottom-nav');
    ?>
    <div class="section-title">
        <h2><?php echo h($fromCity); ?> to <?php echo h($toCity); ?></h2>
        <a class="tiny-link" href="index.php?page=bus-search">Edit</a>
    </div>
    <div class="demo-box">
        <?php echo h($travelDate); ?> · <?php echo h($passengers); ?> passenger<?php echo $passengers > 1 ? 's' : ''; ?> · Demo bus inventory
    </div>

    <?php foreach ($buses as $key => $bus): ?>
        <?php
        $totalPrice = (int) $bus['price'] * $passengers;
        $canBook = $bus['available_seats'] >= $passengers;
        $bookingUrl = 'index.php?' . http_build_query([
            'page' => 'bus-book',
            'bus_key' => $key,
            'from_city' => $fromCity,
            'to_city' => $toCity,
            'travel_date' => $travelDate,
            'passengers' => $passengers,
        ]);
        ?>
        <article class="transport-card">
            <div class="transport-head <?php echo h($bus['accent']); ?>">
                <span>
                    <strong><?php echo h($bus['name']); ?></strong>
                    <span><?php echo h($bus['bus_number']); ?> · <?php echo h($bus['bus_type']); ?></span>
                </span>
                <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
            </div>
            <div class="transport-body">
                <div class="route-line">
                    <span><small>Depart</small><strong><?php echo h($bus['departure']); ?></strong></span>
                    <span class="route-arrow">&rarr;</span>
                    <span><small>Arrive</small><strong><?php echo h($bus['arrival']); ?></strong></span>
                </div>
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Type</span><strong><?php echo h($bus['bus_type']); ?></strong></div>
                    <div class="hotel-info"><span>Seats</span><strong><?php echo h($bus['available_seats']); ?> left</strong></div>
                    <div class="hotel-info"><span>Per seat</span><strong>Rs <?php echo h(number_format($bus['price'])); ?></strong></div>
                </div>
                <?php if ($canBook): ?>
                    <a class="btn" href="<?php echo h($bookingUrl); ?>">Book Bus</a>
                <?php else: ?>
                    <button class="btn btn-light" type="button" disabled>Not Enough Seats</button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'home');
}

function page_bus_book(): void
{
    require_role('user', 'user-login');
    $busKey = trim($_GET['bus_key'] ?? 'daewoo');
    $bus = get_bus_from_catalog($busKey);
    if (!$bus) {
        set_flash('danger', 'Selected bus was not found.');
        redirect_to('bus-search');
    }

    $fromCity = trim($_GET['from_city'] ?? 'Lahore');
    $toCity = trim($_GET['to_city'] ?? 'Islamabad');
    $travelDate = trim($_GET['travel_date'] ?? date('Y-m-d'));
    $passengers = max(1, (int) ($_GET['passengers'] ?? 1));
    $totalPrice = (int) $bus['price'] * $passengers;
    $seatNo = 'S' . str_pad((string) max(1, 22 - (int) $bus['available_seats']), 2, '0', STR_PAD_LEFT);
    $backUrl = 'index.php?' . http_build_query([
        'page' => 'bus-list',
        'from_city' => $fromCity,
        'to_city' => $toCity,
        'travel_date' => $travelDate,
        'passengers' => $passengers,
    ]);

    app_header('Book Bus', true, 'with-bottom-nav');
    ?>
    <article class="transport-card">
        <div class="transport-head <?php echo h($bus['accent']); ?>">
            <span>
                <strong><?php echo h($bus['name']); ?></strong>
                <span><?php echo h($bus['departure']); ?> to <?php echo h($bus['arrival']); ?> · <?php echo h($bus['bus_type']); ?></span>
            </span>
            <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
        </div>
        <div class="transport-body">
            <div class="route-line">
                <span><small>From</small><strong><?php echo h($fromCity); ?></strong></span>
                <span class="route-arrow">&rarr;</span>
                <span><small>To</small><strong><?php echo h($toCity); ?></strong></span>
            </div>
        </div>
    </article>

    <div class="section-title"><h2>Confirm bus seats</h2><span class="badge">Payment pending</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=bus-book">
        <input type="hidden" name="action" value="bus_booking">
        <input type="hidden" name="bus_number" value="<?php echo h($bus['bus_number']); ?>">
        <input type="hidden" name="bus_type" value="<?php echo h($bus['bus_type']); ?>">
        <div class="row">
            <div class="field">
                <label for="bus_book_from_city">From city</label>
                <input id="bus_book_from_city" name="from_city" type="text" value="<?php echo h($fromCity); ?>" required>
            </div>
            <div class="field">
                <label for="bus_book_to_city">To city</label>
                <input id="bus_book_to_city" name="to_city" type="text" value="<?php echo h($toCity); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="bus_date_confirm">Travel date</label>
            <input id="bus_date_confirm" name="travel_date" type="date" value="<?php echo h($travelDate); ?>" required>
        </div>
        <div class="field">
            <label for="bus_name">Bus name</label>
            <input id="bus_name" name="bus_name" type="text" value="<?php echo h($bus['name']); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="seat_no">Seat no</label>
                <input id="seat_no" name="seat_no" type="text" value="<?php echo h($seatNo); ?>" required>
            </div>
            <div class="field">
                <label for="bus_book_passengers">Passengers</label>
                <input id="bus_book_passengers" name="passengers" type="number" min="1" value="<?php echo h($passengers); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="bus_price">Price</label>
            <input id="bus_price" name="price" type="number" min="1" value="<?php echo h($totalPrice); ?>" required>
        </div>
        <button class="btn btn-orange" type="submit">Save Booking and Pay</button>
        <a class="btn btn-light" href="<?php echo h($backUrl); ?>">Back to Buses</a>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_bus_success(): void
{
    require_role('user', 'user-login');
    app_header('Bus Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">🚌</span>
        <div>
            <h2 class="hero-title">Bus booking saved</h2>
            <p class="lead">Your bus seats were created. Complete Cashfree test payment to confirm the trip.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=bus-search">Book Another</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function page_restaurant_search(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');
    $defaultTime = date('H:i', strtotime('+2 hours'));
    $restaurants = restaurant_catalog();

    app_header('Restaurants', false, 'with-bottom-nav booking-mobile-screen restaurant-mobile-screen');
    ?>
    <section class="booking-mobile">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>Restaurants</h2>
            <span></span>
        </div>

        <form class="restaurant-search-bar" method="get" action="index.php">
            <input type="hidden" name="page" value="restaurant-list">
            <span class="search-mark"></span>
            <input name="city" type="search" value="Varanasi" aria-label="Search restaurant or cuisine">
            <input type="hidden" name="booking_date" value="<?php echo h($today); ?>">
            <input type="hidden" name="booking_time" value="<?php echo h($defaultTime); ?>">
            <input type="hidden" name="guests" value="2">
            <button class="filter-btn" type="submit" aria-label="Search"><span></span></button>
        </form>

        <div class="booking-tabs category-tabs">
            <span class="active">All</span>
            <span>Pure Veg</span>
            <span>Non Veg</span>
            <span>Top Rated</span>
        </div>

        <div class="mobile-result-list restaurant-result-list">
            <?php foreach ($restaurants as $key => $restaurant): ?>
                <?php
                $bookingUrl = 'index.php?' . http_build_query([
                    'page' => 'restaurant-book',
                    'restaurant_key' => $key,
                    'city' => 'Varanasi',
                    'booking_date' => $today,
                    'booking_time' => $defaultTime,
                    'guests' => 2,
                ]);
                ?>
                <a class="mobile-result-card restaurant-result-card" href="<?php echo h($bookingUrl); ?>">
                    <span class="restaurant-thumb restaurant-thumb-<?php echo h($restaurant['accent']); ?>"></span>
                    <span class="result-copy">
                        <strong><?php echo h($restaurant['name']); ?></strong>
                        <small><?php echo h($restaurant['cuisine']); ?></small>
                        <em><?php echo h($restaurant['city']); ?> - 20-30 mins</em>
                    </span>
                    <b>Rating <?php echo h($restaurant['rating']); ?></b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    app_footer('user', 'home');
    return;
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Restaurant booking</span>
        <h2>Reserve a table nearby</h2>
        <p>Pick a city, date, time, and guests, then choose a restaurant for Cashfree test payment.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="restaurant-list">
        <div class="field">
            <label for="restaurant_city">City</label>
            <input id="restaurant_city" name="city" type="text" value="Lahore" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="restaurant_booking_date">Booking date</label>
                <input id="restaurant_booking_date" name="booking_date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="restaurant_booking_time">Booking time</label>
                <input id="restaurant_booking_time" name="booking_time" type="time" value="<?php echo h($defaultTime); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="restaurant_guests">Guests</label>
            <input id="restaurant_guests" name="guests" type="number" min="1" max="20" value="2" required>
        </div>
        <button class="btn btn-orange" type="submit">Find Restaurants</button>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_restaurant_list(): void
{
    require_role('user', 'user-login');
    $city = trim($_GET['city'] ?? 'Lahore');
    $bookingDate = trim($_GET['booking_date'] ?? date('Y-m-d'));
    $bookingTime = trim($_GET['booking_time'] ?? date('H:i', strtotime('+2 hours')));
    $guests = max(1, (int) ($_GET['guests'] ?? 2));
    $restaurants = restaurant_catalog();

    app_header('Restaurant List', true, 'with-bottom-nav');
    ?>
    <div class="section-title">
        <h2>Tables in <?php echo h($city); ?></h2>
        <a class="tiny-link" href="index.php?page=restaurant-search">Edit</a>
    </div>
    <div class="demo-box">
        <?php echo h($bookingDate); ?> · <?php echo h($bookingTime); ?> · <?php echo h($guests); ?> guest<?php echo $guests > 1 ? 's' : ''; ?>
    </div>

    <?php foreach ($restaurants as $key => $restaurant): ?>
        <?php
        $reservationFee = (int) $restaurant['price'];
        $bookingUrl = 'index.php?' . http_build_query([
            'page' => 'restaurant-book',
            'restaurant_key' => $key,
            'city' => $city,
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'guests' => $guests,
        ]);
        ?>
        <article class="transport-card">
            <div class="transport-head <?php echo h($restaurant['accent']); ?>">
                <span>
                    <strong><?php echo h($restaurant['name']); ?></strong>
                    <span><?php echo h($restaurant['city']); ?> · <?php echo h($restaurant['cuisine']); ?></span>
                </span>
                <span class="transport-badge">★ <?php echo h($restaurant['rating']); ?></span>
            </div>
            <div class="transport-body">
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Cuisine</span><strong><?php echo h($restaurant['cuisine']); ?></strong></div>
                    <div class="hotel-info"><span>City</span><strong><?php echo h($city); ?></strong></div>
                    <div class="hotel-info"><span>Fee</span><strong>Rs <?php echo h(number_format($reservationFee)); ?></strong></div>
                </div>
                <a class="btn" href="<?php echo h($bookingUrl); ?>">Reserve Table</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'home');
}

function page_restaurant_book(): void
{
    require_role('user', 'user-login');
    $restaurantKey = trim($_GET['restaurant_key'] ?? 'tripnovaa-cafe');
    $restaurant = get_restaurant_from_catalog($restaurantKey);
    if (!$restaurant) {
        set_flash('danger', 'Selected restaurant was not found.');
        redirect_to('restaurant-search');
    }

    $city = trim($_GET['city'] ?? $restaurant['city']);
    $bookingDate = trim($_GET['booking_date'] ?? date('Y-m-d'));
    $bookingTime = trim($_GET['booking_time'] ?? date('H:i', strtotime('+2 hours')));
    $guests = max(1, (int) ($_GET['guests'] ?? 2));
    $reservationFee = (int) $restaurant['price'];
    $backUrl = 'index.php?' . http_build_query([
        'page' => 'restaurant-list',
        'city' => $city,
        'booking_date' => $bookingDate,
        'booking_time' => $bookingTime,
        'guests' => $guests,
    ]);

    app_header('Book Restaurant', true, 'with-bottom-nav');
    ?>
    <article class="transport-card">
        <div class="transport-head <?php echo h($restaurant['accent']); ?>">
            <span>
                <strong><?php echo h($restaurant['name']); ?></strong>
                <span><?php echo h($restaurant['cuisine']); ?> · ★ <?php echo h($restaurant['rating']); ?></span>
            </span>
            <span class="transport-badge">Rs <?php echo h(number_format($reservationFee)); ?></span>
        </div>
        <div class="transport-body">
            <div class="hotel-info-row">
                <div class="hotel-info"><span>Date</span><strong><?php echo h($bookingDate); ?></strong></div>
                <div class="hotel-info"><span>Time</span><strong><?php echo h($bookingTime); ?></strong></div>
                <div class="hotel-info"><span>Guests</span><strong><?php echo h($guests); ?></strong></div>
            </div>
        </div>
    </article>

    <div class="section-title"><h2>Confirm table</h2><span class="badge">Payment pending</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=restaurant-book">
        <input type="hidden" name="action" value="restaurant_booking">
        <div class="field">
            <label for="restaurant_name">Restaurant name</label>
            <input id="restaurant_name" name="restaurant_name" type="text" value="<?php echo h($restaurant['name']); ?>" required>
        </div>
        <div class="field">
            <label for="restaurant_book_city">City</label>
            <input id="restaurant_book_city" name="city" type="text" value="<?php echo h($city); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="restaurant_book_date">Booking date</label>
                <input id="restaurant_book_date" name="booking_date" type="date" value="<?php echo h($bookingDate); ?>" required>
            </div>
            <div class="field">
                <label for="restaurant_book_time">Booking time</label>
                <input id="restaurant_book_time" name="booking_time" type="time" value="<?php echo h($bookingTime); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="restaurant_book_guests">Guests</label>
            <input id="restaurant_book_guests" name="guests" type="number" min="1" value="<?php echo h($guests); ?>" required>
        </div>
        <div class="field">
            <label for="special_request">Special request</label>
            <textarea id="special_request" name="special_request" placeholder="Window table, birthday setup, allergy note..."></textarea>
        </div>
        <div class="field">
            <label for="restaurant_price">Price / reservation fee</label>
            <input id="restaurant_price" name="price" type="number" min="1" value="<?php echo h($reservationFee); ?>" required>
        </div>
        <button class="btn btn-orange" type="submit">Save Reservation and Pay</button>
        <a class="btn btn-light" href="<?php echo h($backUrl); ?>">Back to Restaurants</a>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_restaurant_success(): void
{
    require_role('user', 'user-login');
    app_header('Restaurant Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">🍽</span>
        <div>
            <h2 class="hero-title">Table reservation saved</h2>
            <p class="lead">Your restaurant booking was created. Complete Cashfree test payment to confirm the table.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=restaurant-search">Reserve Another</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function render_plan_trip_steps(int $activeStep): void
{
    $steps = [
        1 => 'Plan',
        2 => 'Transport',
        3 => 'Options',
        4 => 'Trip Plan',
        5 => 'Captain',
        6 => 'Arrival',
        7 => 'Accepted',
        8 => '5% Pay',
        9 => 'Guide',
        10 => 'Feedback',
    ];
    ?>
    <div class="plan-flow-steps" aria-label="Plan trip progress">
        <?php foreach ($steps as $stepNumber => $label): ?>
            <span class="plan-flow-step <?php echo h($stepNumber === $activeStep ? 'active' : ($stepNumber < $activeStep ? 'done' : '')); ?>">
                <b><?php echo h($stepNumber); ?></b>
                <small><?php echo h($label); ?></small>
            </span>
        <?php endforeach; ?>
    </div>
    <?php
}

function plan_trip_transport_label(string $transport): string
{
    return ['bus' => 'Bus', 'train' => 'Train', 'flight' => 'Flight'][$transport] ?? 'Bus';
}

function render_plan_trip_summary(array $state, array $option): void
{
    $dateText = date('d M Y', strtotime((string) $state['trip_date']));
    ?>
    <div class="card plan-summary-card">
        <div><span>From</span><strong><?php echo h($state['from_city']); ?></strong></div>
        <div><span>To</span><strong><?php echo h($state['to_city']); ?></strong></div>
        <div><span>Date</span><strong><?php echo h($dateText); ?></strong></div>
        <div><span>Travelers</span><strong><?php echo h($state['travelers']); ?> Adults</strong></div>
        <div><span>Transport</span><strong><?php echo h(plan_trip_transport_label((string) $state['transport']) . ' - ' . $option['name']); ?></strong></div>
    </div>
    <?php
}

function page_plan_trip(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    $interestOptions = ['Solang Valley', 'Rohtang Pass', 'Hidimba Temple', 'Local sightseeing', 'Adventure activities', 'Old Manali'];
    app_header('Plan Trip', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(1); ?>
    <div class="plan-trip-hero">
        <span>TripNovaa Plan</span>
        <h2>Plan Your Trip</h2>
        <p>Let TripNovaa find transport, itinerary, captain pickup, and guide support.</p>
    </div>

    <form class="form card auth-card plan-search-card" method="post" action="index.php?page=plan-trip">
        <input type="hidden" name="action" value="plan_trip_start">
        <div class="field">
            <label for="plan_from_city">From</label>
            <input id="plan_from_city" name="from_city" type="text" value="<?php echo h($state['from_city']); ?>" required>
        </div>
        <div class="field">
            <label for="plan_to_city">To</label>
            <input id="plan_to_city" name="to_city" type="text" value="<?php echo h($state['to_city']); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="plan_trip_date">Date</label>
                <input id="plan_trip_date" name="trip_date" type="date" value="<?php echo h($state['trip_date']); ?>" required>
            </div>
            <div class="field">
                <label for="plan_travelers">Travelers</label>
                <input id="plan_travelers" name="travelers" type="number" min="1" max="12" value="<?php echo h($state['travelers']); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="plan_budget">Budget</label>
            <select id="plan_budget" name="budget" required>
                <?php foreach (['10000-15000', '15000-25000', '25000-40000', '40000+'] as $budget): ?>
                    <option value="<?php echo h($budget); ?>" <?php echo $state['budget'] === $budget ? 'selected' : ''; ?>>Rs <?php echo h($budget); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Which places do you want to visit?</label>
            <div class="plan-chip-grid">
                <?php foreach ($interestOptions as $interest): ?>
                    <label class="plan-check-chip">
                        <input type="checkbox" name="interests[]" value="<?php echo h($interest); ?>" <?php echo in_array($interest, (array) $state['interests'], true) ? 'checked' : ''; ?>>
                        <span><?php echo h($interest); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="btn" type="submit">Find Best Options</button>
    </form>

    <div class="plan-info-card">
        <strong>Public transport first</strong>
        <span>TripNovaa books transport first, then unlocks captain pickup and guided trip support.</span>
    </div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_transport(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    $dateText = date('d M Y', strtotime((string) $state['trip_date']));
    app_header('Choose Transport', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(2); ?>
    <div class="section-title"><h2>Reach <?php echo h(str_replace('Himachal Pradesh', '', (string) $state['to_city'])); ?></h2><a href="index.php?page=plan-trip">Edit</a></div>
    <p class="lead">Choose how you want to reach your destination.</p>

    <form class="form card auth-card" method="post" action="index.php?page=plan-trip-transport">
        <input type="hidden" name="action" value="plan_trip_choose_transport">
        <div class="plan-transport-grid">
            <?php foreach (['bus' => 'Bus', 'train' => 'Train', 'flight' => 'Flight'] as $key => $label): ?>
                <label class="plan-transport-card">
                    <input type="radio" name="transport" value="<?php echo h($key); ?>" <?php echo $state['transport'] === $key ? 'checked' : ''; ?>>
                    <span class="plan-transport-icon"><?php echo h(substr($label, 0, 1)); ?></span>
                    <strong><?php echo h($label); ?></strong>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="plan-bus-visual">
            <span class="plan-road"></span>
            <span class="plan-bus-shape"></span>
        </div>
        <div class="card plan-mini-route">
            <div><span>From</span><strong><?php echo h($state['from_city']); ?></strong></div>
            <div><span>To</span><strong><?php echo h($state['to_city']); ?></strong></div>
            <div><span>Date</span><strong><?php echo h($dateText); ?> - <?php echo h($state['travelers']); ?> Adults</strong></div>
        </div>
        <button class="btn" type="submit">Search Options</button>
    </form>

    <div class="plan-info-card">
        <strong>After reaching Manali</strong>
        <span>Your captain will pick you up and guide your inner trip.</span>
    </div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_options(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    $transport = (string) $state['transport'];
    $options = plan_trip_transport_options($transport);
    app_header('Available ' . plan_trip_transport_label($transport), true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(3); ?>
    <div class="section-title"><h2>Available <?php echo h(plan_trip_transport_label($transport)); ?> Options</h2><a href="index.php?page=plan-trip-transport">Edit</a></div>
    <div class="plan-tabs">
        <?php foreach (['bus', 'train', 'flight'] as $tab): ?>
            <span class="<?php echo $transport === $tab ? 'active' : ''; ?>"><?php echo h(plan_trip_transport_label($tab)); ?></span>
        <?php endforeach; ?>
    </div>

    <?php foreach ($options as $option): ?>
        <form class="plan-option-card" method="post" action="index.php?page=plan-trip-options">
            <input type="hidden" name="action" value="plan_trip_select_option">
            <input type="hidden" name="option_id" value="<?php echo h($option['id']); ?>">
            <span class="plan-option-thumb"><?php echo h(strtoupper(substr(plan_trip_transport_label($transport), 0, 1))); ?></span>
            <div>
                <h3><?php echo h($option['name']); ?></h3>
                <p><?php echo h($option['route']); ?><br><?php echo h($option['time']); ?> - <?php echo h($option['seat']); ?></p>
                <small><?php echo h($option['rating']); ?> rating</small>
            </div>
            <div class="plan-option-price">
                <strong>Rs <?php echo h(number_format((float) $option['price'])); ?></strong>
                <small>per person</small>
                <button class="btn btn-orange" type="submit">Select</button>
            </div>
        </form>
    <?php endforeach; ?>

    <div class="plan-offer-banner"><strong>Up to 10% OFF</strong><span>Use code TRIPNOVAA on selected trip options.</span></div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_detail(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    $option = selected_plan_trip_option();
    app_header('Trip Plan', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(4); ?>
    <div class="plan-scenic-card">
        <span><?php echo h($state['to_city']); ?></span>
        <h2>5 Days / 4 Nights</h2>
        <p>Snow peaks, local food, guided activities, and captain pickup.</p>
    </div>
    <?php render_plan_trip_summary($state, $option); ?>

    <div class="section-title"><h2>Itinerary Highlights</h2><a href="index.php?page=plan-trip">Edit</a></div>
    <div class="plan-highlight-grid">
        <?php foreach ((array) $state['interests'] as $interest): ?>
            <span><?php echo h($interest); ?></span>
        <?php endforeach; ?>
    </div>

    <div class="section-title"><h2>Services in Manali</h2><span class="badge">Included</span></div>
    <div class="plan-service-grid">
        <span>Captain with Car</span>
        <span>Hotel Stay</span>
        <span>Near Restaurants</span>
        <span>Tour Guide</span>
    </div>

    <div class="card ride-summary">
        <div class="summary-row"><span>Total estimated cost</span><strong>Rs <?php echo h(number_format((float) $state['package_total'])); ?></strong></div>
        <div class="summary-row"><span>Selected transport</span><strong><?php echo h($option['name']); ?></strong></div>
    </div>
    <div class="btn-row">
        <a class="btn btn-light" href="index.php?page=plan-trip-options">Back</a>
        <a class="btn" href="index.php?page=plan-trip-captain">View Captain Pickup</a>
    </div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_captain(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    app_header('Captain Pickup', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(5); ?>
    <div class="plan-scenic-card captain-pickup">
        <span>Captain & car in Manali</span>
        <h2>After you reach Manali</h2>
        <p>Your captain will pick you up and guide your trip.</p>
    </div>
    <div class="captain-card">
        <span class="captain-avatar">R</span>
        <span>
            <h3>Rohit Thakur</h3>
            <p>4.8 rating - Himachal local expert<br>Innova Crysta - HP 01 AB 1234</p>
        </span>
    </div>
    <div class="card ride-summary">
        <div class="summary-row"><span>Pickup location</span><strong>Manali Bus Stand</strong></div>
        <div class="summary-row"><span>Your captain will be there</span><strong><?php echo h(date('d M Y', strtotime((string) $state['trip_date']))); ?></strong></div>
    </div>
    <div class="btn-row">
        <a class="btn" href="tel:+920000000000">Call Captain</a>
        <a class="btn btn-light" href="index.php?page=plan-trip-arrival">I reached Manali</a>
    </div>
    <div class="plan-info-card"><strong>Message</strong><span>Rides must be booked only 2 days or 24 hours in advance before destination.</span></div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_arrival(): void
{
    require_role('user', 'user-login');
    app_header('Arrival in Manali', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(6); ?>
    <div class="plan-scenic-card arrival-card">
        <span>Welcome to Manali</span>
        <h2>Your adventure begins now</h2>
        <p>Your captain is waiting for you.</p>
    </div>
    <div class="captain-card">
        <span class="captain-avatar">R</span>
        <span><h3>Rohit Thakur</h3><p>Waiting for you near Manali Bus Stand.</p></span>
        <span class="ride-call-actions"><b>Call</b><b>Chat</b></span>
    </div>
    <a class="btn" href="index.php?page=plan-trip-accepted">Captain Accepts Trip</a>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_accepted(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    app_header('Trip Accepted', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(7); ?>
    <div class="card module-page-card plan-accepted-card">
        <span class="module-page-icon">OK</span>
        <div>
            <h2 class="hero-title">Trip Accepted</h2>
            <p class="lead">Rohit Thakur has accepted your trip.</p>
        </div>
        <div class="ride-summary">
            <div class="summary-row"><span>Trip</span><strong>Manali 5 Days / 4 Nights</strong></div>
            <div class="summary-row"><span>Total</span><strong>Rs <?php echo h(number_format((float) $state['package_total'])); ?></strong></div>
            <div class="summary-row"><span>Deposit due</span><strong>Rs <?php echo h(number_format(plan_trip_deposit_amount())); ?></strong></div>
        </div>
        <a class="btn" href="index.php?page=plan-trip-deposit">Pay 5% Before Trip</a>
    </div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_deposit(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    $deposit = plan_trip_deposit_amount();
    app_header('Secure Your Trip', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(8); ?>
    <div class="card module-page-card plan-payment-card">
        <span class="module-page-icon">5%</span>
        <div>
            <h2 class="hero-title">Secure Your Trip</h2>
            <p class="lead">To confirm your trip, please pay 5% of the total amount before start.</p>
        </div>
        <div class="ride-summary">
            <div class="summary-row"><span>Total trip amount</span><strong>Rs <?php echo h(number_format((float) $state['package_total'])); ?></strong></div>
            <div class="summary-row"><span>5% payment</span><strong>Rs <?php echo h(number_format($deposit)); ?></strong></div>
            <div class="summary-row"><span>Status</span><strong><?php echo !empty($state['deposit_paid']) ? 'Paid' : 'Pending'; ?></strong></div>
        </div>
        <div class="plan-info-card"><strong>Why 5%?</strong><span>This helps confirm your captain and keep booking secure.</span></div>
        <?php if (!empty($state['deposit_paid'])): ?>
            <a class="btn" href="index.php?page=plan-trip-guide">Continue to Guided Trip</a>
        <?php else: ?>
            <form method="post" action="index.php?page=plan-trip-deposit">
                <input type="hidden" name="action" value="plan_trip_deposit">
                <button class="btn" type="submit">Pay 5% Now</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_guide(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    if (empty($state['deposit_paid'])) {
        set_flash('warning', 'Please pay the 5% demo deposit before starting the guided trip.');
        redirect_to('plan-trip-deposit');
    }

    app_header('Guided Trip', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(9); ?>
    <div class="section-title"><h2>Today's Plan</h2><span class="badge">Day 2</span></div>
    <div class="plan-scenic-card guide-card">
        <span>Solang Valley</span>
        <h2>Skiing, rafting, and mountain views</h2>
        <p>Captain and guide coordinate your local stops.</p>
    </div>
    <div class="plan-highlight-grid">
        <span>Skiing Valley</span>
        <span>Rafting Pass</span>
        <span>Atal Tunnel</span>
        <span>Local Food</span>
    </div>
    <div class="section-title"><h2>Nearby restaurants</h2><span class="badge">Open</span></div>
    <div class="plan-restaurant-row">
        <span>Day Done Cafe<br><small>0.5 km</small></span>
        <span>Johnson's Cafe<br><small>0.8 km</small></span>
        <span>The Johnson Cafe<br><small>1.2 km</small></span>
    </div>
    <a class="btn" href="index.php?page=plan-trip-complete">Complete Trip</a>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_complete(): void
{
    require_role('user', 'user-login');
    $state = plan_trip_state();
    app_header('Trip Completed', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(10); ?>
    <div class="plan-scenic-card complete-card">
        <span>Trip Completed</span>
        <h2>Thank you for traveling with TripNovaa</h2>
        <p>Your Manali plan, captain pickup, and guide support are complete.</p>
    </div>
    <?php if ((int) ($state['trip_feedback_rating'] ?? 0) > 0): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">OK</span>
            <div><h2 class="hero-title">Feedback submitted</h2><p class="lead">Rating: <?php echo h($state['trip_feedback_rating']); ?>/5</p></div>
            <a class="btn" href="index.php?page=plan-trip-reminder">Important Reminder</a>
        </div>
    <?php else: ?>
        <form class="form card auth-card" method="post" action="index.php?page=plan-trip-complete">
            <input type="hidden" name="action" value="plan_trip_feedback">
            <div class="field">
                <label for="plan_feedback_rating">How was your overall experience?</label>
                <select id="plan_feedback_rating" name="rating" required>
                    <option value="">Select rating</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo h($i); ?>"><?php echo h($i); ?> stars</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="field">
                <label for="plan_feedback_comment">Comment</label>
                <textarea id="plan_feedback_comment" name="comment" placeholder="Tell us about your trip"></textarea>
            </div>
            <button class="btn" type="submit">Submit Feedback</button>
        </form>
    <?php endif; ?>
    <?php
    app_footer('user', 'plan');
}

function page_plan_trip_reminder(): void
{
    require_role('user', 'user-login');
    app_header('Important Reminder', true, 'with-bottom-nav plan-trip-screen');
    ?>
    <?php render_plan_trip_steps(10); ?>
    <div class="plan-reminder-card">
        <span>Important Reminder</span>
        <h2>Book rides, captains, hotels, and guide support on time</h2>
        <p>Rides, captain pickup, hotel, and guide support must be booked only 2 days or 24 hours in advance before reaching the destination address.</p>
        <div class="plan-reminder-visual"></div>
    </div>
    <div class="btn-row">
        <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
        <a class="btn" href="index.php?page=user-dashboard">Home</a>
    </div>
    <?php
    app_footer('user', 'plan');
}

function render_group_tour_steps(int $activeStep): void
{
    $steps = [
        1 => 'Tours',
        2 => 'Details',
        3 => 'Captain',
        4 => 'Seats',
        5 => '5% Pay',
        6 => 'Confirm',
        7 => 'Booked',
        8 => 'Itinerary',
        9 => 'During',
        10 => 'Balance',
        11 => 'Complete',
        12 => 'More',
    ];
    ?>
    <div class="group-flow-steps" aria-label="Group tour progress">
        <?php foreach ($steps as $number => $label): ?>
            <span class="<?php echo h($number === $activeStep ? 'active' : ($number < $activeStep ? 'done' : '')); ?>">
                <b><?php echo h($number); ?></b>
                <small><?php echo h($label); ?></small>
            </span>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_group_tour_card(array $tour, string $buttonLabel = 'Book Now'): void
{
    ?>
    <article class="group-tour-card">
        <span class="group-tour-thumb"></span>
        <div>
            <span class="group-tour-badge"><?php echo h($tour['badge']); ?></span>
            <h3><?php echo h($tour['title']); ?></h3>
            <p><?php echo h($tour['dates']); ?><br><?php echo h($tour['duration']); ?> - <?php echo h($tour['seats_left']); ?> Seats Left</p>
            <a href="index.php?page=group-tour-details&tour_id=<?php echo h($tour['id']); ?>"><?php echo h($buttonLabel); ?></a>
        </div>
        <strong>Rs <?php echo h(number_format((float) $tour['price'])); ?><small>/Person</small></strong>
    </article>
    <?php
}

function page_group_tours(): void
{
    require_role('user', 'user-login');
    $catalog = group_tour_catalog();
    app_header('Group Tours', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(1); ?>
    <div class="group-tour-hero">
        <span>Explore</span>
        <h2>Group Tours</h2>
        <p>Travel together, create memories, and book captain-led tour offers.</p>
    </div>
    <div class="travel-search">
        <span class="search-mark"></span>
        <input type="search" placeholder="Search tour, place or captain" aria-label="Search group tours">
        <a class="filter-btn" href="index.php?page=group-tour-more" aria-label="More tours"><span></span></a>
    </div>
    <div class="group-category-row">
        <a class="active" href="index.php?page=group-tours">All Tour</a>
        <a href="index.php?page=group-tours">Pilgrimage</a>
        <a href="index.php?page=group-tours">Holiday</a>
        <a href="index.php?page=group-tours">Adventure</a>
        <a href="index.php?page=group-tours">Intl Tour</a>
    </div>
    <div class="section-title"><h2>Popular Group Tours</h2><a class="tiny-link" href="index.php?page=group-tour-more">View All</a></div>
    <?php foreach ($catalog as $tour): ?>
        <?php render_group_tour_card($tour); ?>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'plan');
}

function page_group_tour_details(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    app_header('Tour Details', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(2); ?>
    <div class="group-tour-photo">
        <span><?php echo h($tour['title']); ?></span>
        <small><?php echo h($tour['place']); ?></small>
    </div>
    <article class="card group-detail-card">
        <h2><?php echo h($tour['title']); ?></h2>
        <div class="group-info-grid">
            <span><strong><?php echo h($tour['duration']); ?></strong><small><?php echo h($tour['dates']); ?></small></span>
            <span><strong><?php echo h($tour['bus']); ?></strong><small><?php echo h($tour['seats_left']); ?> seats left</small></span>
            <span><strong><?php echo h($tour['food']); ?></strong><small>Included</small></span>
            <span><strong>Tour Guide</strong><small>Captain support</small></span>
        </div>
        <div class="section-title"><h2>Tour Highlights</h2><span class="badge">Included</span></div>
        <div class="group-highlight-list">
            <?php foreach ($tour['highlights'] as $highlight): ?>
                <span><?php echo h($highlight); ?></span>
            <?php endforeach; ?>
        </div>
        <div class="captain-card">
            <span class="captain-avatar">R</span>
            <span><h3><?php echo h($tour['captain']); ?></h3><p><?php echo h($tour['rating']); ?> rating - Trip captain</p></span>
            <a class="tiny-link" href="index.php?page=group-tour-captain&tour_id=<?php echo h($tour['id']); ?>">View Profile</a>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=group-tours">Back</a>
            <a class="btn" href="index.php?page=group-tour-captain&tour_id=<?php echo h($tour['id']); ?>">Book Now</a>
        </div>
    </article>
    <?php
    app_footer('user', 'plan');
}

function page_group_tour_captain(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    app_header('Captain & Vehicle', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(3); ?>
    <div class="captain-card">
        <span class="captain-avatar">R</span>
        <span><h3><?php echo h($tour['captain']); ?></h3><p><?php echo h($tour['rating']); ?> (320 Trips) - Verified Captain</p></span>
    </div>
    <article class="card group-bus-card">
        <span class="group-bus-art"></span>
        <div>
            <h2><?php echo h($tour['vehicle']); ?></h2>
            <p>AC, WiFi, charging, pushback, first aid, luggage assist.</p>
        </div>
    </article>
    <div class="section-title"><h2>Trip Inclusions</h2><span class="badge">Offer</span></div>
    <div class="group-highlight-list">
        <span>AC Bus Travel</span><span>Stay</span><span>Meals</span><span>Guide & Captain Support</span><span>Water Bottle</span><span>First Aid</span>
    </div>
    <div class="card ride-summary">
        <div class="summary-row"><span>Total trip amount</span><strong>Rs <?php echo h(number_format((float) $tour['price'])); ?> / person</strong></div>
        <div class="summary-row"><span>Advance to book</span><strong>5%</strong></div>
    </div>
    <a class="btn" href="index.php?page=group-tour-seats&tour_id=<?php echo h($tour['id']); ?>">Select Seats</a>
    <?php
    app_footer('user', 'plan');
}

function page_group_tour_seats(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    $booking = group_tour_booking_state();
    $selected = (array) $booking['selected_seats'];
    $booked = ['9', '12', '17', '18', '49'];
    app_header('Select Seats', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(4); ?>
    <div class="section-title"><h2>Select Your Seats</h2><span class="badge"><?php echo h($tour['title']); ?></span></div>
    <form method="post" action="index.php?page=group-tour-seats">
        <input type="hidden" name="action" value="group_tour_select_seats">
        <input type="hidden" name="tour_id" value="<?php echo h($tour['id']); ?>">
        <div class="seat-legend"><span>Available</span><span>Selected</span><span>Booked</span></div>
        <div class="group-seat-grid">
            <?php foreach (range(1, 50) as $seat): ?>
                <?php $seatValue = (string) $seat; $isBooked = in_array($seatValue, $booked, true); ?>
                <label class="group-seat <?php echo h($isBooked ? 'booked' : (in_array($seatValue, $selected, true) ? 'selected' : '')); ?>">
                    <input type="checkbox" name="selected_seats[]" value="<?php echo h($seatValue); ?>" <?php echo in_array($seatValue, $selected, true) ? 'checked' : ''; ?> <?php echo $isBooked ? 'disabled' : ''; ?>>
                    <span><?php echo h($seatValue); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="card ride-summary">
            <div class="summary-row"><span>Selected seats</span><strong>Choose 1-6 seats</strong></div>
            <div class="summary-row"><span>Amount</span><strong>Rs <?php echo h(number_format((float) $tour['price'])); ?> / person</strong></div>
        </div>
        <button class="btn" type="submit">Proceed to Pay</button>
    </form>
    <?php
    app_footer('user', 'plan');
}

function page_group_tour_advance(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    $booking = group_tour_booking_state();
    app_header('Pay Advance', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(5); ?>
    <article class="card group-summary-card">
        <span class="group-tour-thumb"></span>
        <div>
            <h2>Trip Summary</h2>
            <p><?php echo h($tour['title']); ?><br><?php echo h($tour['dates']); ?></p>
        </div>
        <div class="ride-summary">
            <div class="summary-row"><span>Seats</span><strong><?php echo h(implode(', ', (array) $booking['selected_seats'])); ?></strong></div>
            <div class="summary-row"><span>Members</span><strong><?php echo h($booking['members']); ?> Adults</strong></div>
            <div class="summary-row"><span>Total amount</span><strong>Rs <?php echo h(number_format(group_tour_total_amount())); ?></strong></div>
            <div class="summary-row"><span>Advance payment (5%)</span><strong>Rs <?php echo h(number_format(group_tour_advance_amount())); ?></strong></div>
            <div class="summary-row"><span>Remaining</span><strong>Rs <?php echo h(number_format(group_tour_total_amount() - group_tour_advance_amount())); ?></strong></div>
        </div>
    </article>
    <div class="payment-method-list">
        <div class="payment-method-option active"><span class="payment-method-icon upi">UPI</span><strong>UPI</strong><small>Demo secure payment</small><span class="method-radio active"></span></div>
        <div class="payment-method-option"><span class="payment-method-icon card">CC</span><strong>Debit / Credit Card</strong><small>Demo option</small><span class="method-radio"></span></div>
        <div class="payment-method-option"><span class="payment-method-icon wallet">W</span><strong>Wallets</strong><small>Demo option</small><span class="method-radio"></span></div>
    </div>
    <form method="post" action="index.php?page=group-tour-advance">
        <input type="hidden" name="action" value="group_tour_pay_advance">
        <button class="btn" type="submit">Pay Advance Rs <?php echo h(number_format(group_tour_advance_amount())); ?></button>
    </form>
    <?php
    app_footer('user', 'plan');
}

function page_group_tour_confirmed(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    $booking = group_tour_booking_state();
    app_header('Booking Confirmed', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(6); ?>
    <div class="group-success-card">
        <span class="group-success-check">OK</span>
        <h2>Booking Confirmed!</h2>
        <p>Your seats are reserved.</p>
        <article class="group-tour-card compact">
            <span class="group-tour-thumb"></span>
            <div><h3><?php echo h($tour['title']); ?></h3><p><?php echo h($tour['dates']); ?><br><?php echo h($tour['duration']); ?></p></div>
        </article>
        <div class="ride-summary">
            <div class="summary-row"><span>Seats</span><strong><?php echo h(implode(', ', (array) $booking['selected_seats'])); ?></strong></div>
            <div class="summary-row"><span>PNR No.</span><strong><?php echo h($booking['pnr']); ?></strong></div>
            <div class="summary-row"><span>Advance paid</span><strong>Rs <?php echo h(number_format(group_tour_advance_amount())); ?></strong></div>
        </div>
        <a class="btn" href="index.php?page=group-tour-booking">View My Booking</a>
        <a class="btn btn-light" href="index.php?page=group-tour-details">Share Trip</a>
    </div>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_booking(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    $booking = group_tour_booking_state();
    app_header('My Booked Tour', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(7); ?>
    <div class="driver-tabs">
        <a class="active" href="index.php?page=group-tour-booking">Upcoming</a>
        <a href="index.php?page=group-tour-during">Ongoing</a>
        <a href="index.php?page=group-tour-completed">Completed</a>
        <a href="index.php?page=group-tour-more">Cancelled</a>
    </div>
    <article class="group-tour-card">
        <span class="group-tour-thumb"></span>
        <div>
            <span class="group-tour-badge">Upcoming</span>
            <h3><?php echo h($tour['title']); ?></h3>
            <p>Seats: <?php echo h(implode(', ', (array) $booking['selected_seats'])); ?><br>Advance paid: Rs <?php echo h(number_format(group_tour_advance_amount())); ?></p>
            <a href="index.php?page=group-tour-itinerary">View Details</a>
        </div>
        <strong>Rs <?php echo h(number_format(group_tour_total_amount())); ?></strong>
    </article>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_itinerary(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    app_header('Tour Itinerary', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(8); ?>
    <h2 class="hero-title">Itinerary</h2>
    <p class="lead"><?php echo h($tour['title']); ?></p>
    <div class="group-itinerary-list">
        <span><b>Day 1</b> Vijayawada to Prayagraj - night journey</span>
        <span><b>Day 2</b> Prayagraj Sangam - Kashi</span>
        <span><b>Day 3</b> Kashi Vishwanath Darshan and Ganga Aarti</span>
        <span><b>Day 4</b> Sarnath visit</span>
        <span><b>Day 5</b> Ayodhya Darshan</span>
    </div>
    <a class="btn" href="index.php?page=group-tour-during">View Full Itinerary</a>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_during(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    app_header('During Tour', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(9); ?>
    <div class="group-tour-photo"><span>Your In Progress Tour</span><small><?php echo h($tour['title']); ?> - Day 3 of 10</small></div>
    <div class="group-live-grid">
        <a href="#">Live Location</a><a href="#">Captain Info</a><a href="#">Group Chat</a><a href="#">Gallery</a>
    </div>
    <div class="section-title"><h2>Important Contacts</h2><span class="badge">Live</span></div>
    <div class="captain-card"><span class="captain-avatar">R</span><span><h3><?php echo h($tour['captain']); ?></h3><p>Captain - 9876543210</p></span></div>
    <div class="captain-card"><span class="captain-avatar">T</span><span><h3>Tour Manager</h3><p>Support - 9549876901</p></span></div>
    <a class="btn" href="index.php?page=group-tour-remaining">Trip Started - Pay Remaining</a>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_remaining(): void
{
    require_role('user', 'user-login');
    app_header('Pay Remaining', true, 'with-bottom-nav group-tour-screen');
    $remaining = group_tour_total_amount() - group_tour_advance_amount();
    ?>
    <?php render_group_tour_steps(10); ?>
    <article class="card group-summary-card">
        <h2>Trip Started</h2>
        <p>Please pay the remaining amount to continue the tour services.</p>
        <div class="ride-summary">
            <div class="summary-row"><span>Total trip amount</span><strong>Rs <?php echo h(number_format(group_tour_total_amount())); ?></strong></div>
            <div class="summary-row"><span>Advance paid</span><strong>Rs <?php echo h(number_format(group_tour_advance_amount())); ?></strong></div>
            <div class="summary-row"><span>Remaining amount</span><strong>Rs <?php echo h(number_format($remaining)); ?></strong></div>
        </div>
    </article>
    <form method="post" action="index.php?page=group-tour-remaining">
        <input type="hidden" name="action" value="group_tour_pay_remaining">
        <button class="btn" type="submit">Pay Now Rs <?php echo h(number_format($remaining)); ?></button>
    </form>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_completed(): void
{
    require_role('user', 'user-login');
    $tour = current_group_tour();
    $booking = group_tour_booking_state();
    app_header('Tour Completed', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(11); ?>
    <div class="group-tour-photo"><span>Trip Completed</span><small>Thank you for traveling with TripNovaa</small></div>
    <?php if ((int) ($booking['rating'] ?? 0) > 0): ?>
        <div class="card module-page-card"><span class="module-page-icon">OK</span><div><h2 class="hero-title">Thanks for feedback</h2><p class="lead">Rating: <?php echo h($booking['rating']); ?>/5 for <?php echo h($tour['title']); ?></p></div><a class="btn" href="index.php?page=group-tour-more">More Tours</a></div>
    <?php else: ?>
        <form class="form card auth-card" method="post" action="index.php?page=group-tour-completed">
            <input type="hidden" name="action" value="group_tour_feedback">
            <div class="field"><label for="group_rating">How was your trip?</label><select id="group_rating" name="rating" required><option value="">Select rating</option><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?php echo h($i); ?>"><?php echo h($i); ?> stars</option><?php endfor; ?></select></div>
            <div class="field"><label for="group_feedback">Feedback</label><textarea id="group_feedback" name="feedback" placeholder="Share your group tour experience"></textarea></div>
            <button class="btn" type="submit">Submit Feedback</button>
        </form>
    <?php endif; ?>
    <?php
    app_footer('user', 'bookings');
}

function page_group_tour_more(): void
{
    require_role('user', 'user-login');
    $catalog = group_tour_catalog();
    app_header('More Group Tours', true, 'with-bottom-nav group-tour-screen');
    ?>
    <?php render_group_tour_steps(12); ?>
    <div class="section-title"><h2>Explore More Group Tours</h2><span class="badge"><?php echo h(count($catalog)); ?> tours</span></div>
    <div class="travel-search"><span class="search-mark"></span><input type="search" placeholder="Search destinations or tours" aria-label="Search more tours"></div>
    <?php foreach ($catalog as $tour): ?>
        <?php render_group_tour_card($tour, 'View Details'); ?>
    <?php endforeach; ?>
    <a class="btn" href="index.php?page=group-tours">View All Tours</a>
    <?php
    app_footer('user', 'plan');
}

function page_tour_ticket_search(): void
{
    require_role('user', 'user-login');
    $today = date('Y-m-d');

    app_header('Tours/Tickets', true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Ticket API</span>
        <h2>Find tours and events</h2>
        <p>Search a third-party/demo ticket API, choose an event, then confirm with Cashfree test payment.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="tour-ticket-results">
        <div class="field">
            <label for="ticket_keyword">Keyword / event name</label>
            <input id="ticket_keyword" name="keyword" type="text" value="heritage tour" required>
        </div>
        <div class="field">
            <label for="ticket_location">Location</label>
            <input id="ticket_location" name="location" type="text" value="Lahore" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="ticket_date">Date</label>
                <input id="ticket_date" name="date" type="date" min="<?php echo h($today); ?>" value="<?php echo h($today); ?>" required>
            </div>
            <div class="field">
                <label for="ticket_quantity">Quantity</label>
                <input id="ticket_quantity" name="quantity" type="number" min="1" max="10" value="2" required>
            </div>
        </div>
        <button class="btn btn-orange" type="submit">Search Tickets</button>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_tour_ticket_results(): void
{
    require_role('user', 'user-login');
    $keyword = trim($_GET['keyword'] ?? 'heritage tour');
    $location = trim($_GET['location'] ?? 'Lahore');
    $date = trim($_GET['date'] ?? date('Y-m-d'));
    $quantity = max(1, (int) ($_GET['quantity'] ?? 1));
    $results = fetchTicketApiResults($keyword, $location, $date);
    $ticketConfig = ticket_api_config();

    app_header('Ticket Results', true, 'with-bottom-nav');
    ?>
    <div class="section-title">
        <h2><?php echo h(ucwords($keyword)); ?></h2>
        <a class="tiny-link" href="index.php?page=tour-ticket-search">Edit</a>
    </div>
    <div class="demo-box">
        <?php echo h($location); ?> · <?php echo h($date); ?> · <?php echo h($quantity); ?> ticket<?php echo $quantity > 1 ? 's' : ''; ?> · Demo/API results
    </div>

    <div class="demo-box">
        <strong><?php echo h($ticketConfig['configured'] ? 'Live Ticketmaster API enabled' : 'Demo fallback - add TICKETMASTER_API_KEY for live Ticketmaster results'); ?></strong>
    </div>

    <?php foreach ($results as $event): ?>
        <?php
        $eventTime = strtotime($event['event_date'] ?? '') ?: strtotime($date . ' 18:00:00');
        $eventDate = date('Y-m-d H:i:s', strtotime($date . ' ' . date('H:i:s', $eventTime)));
        $price = (float) ($event['price'] ?? 0);
        $totalPrice = $price * $quantity;
        $bookingUrl = 'index.php?' . http_build_query([
            'page' => 'tour-ticket-book',
            'event_name' => $event['event_name'] ?? 'Demo Ticket Event',
            'location' => $event['location'] ?? $location,
            'event_date' => $eventDate,
            'ticket_type' => $event['ticket_type'] ?? 'General Admission',
            'price' => $price,
            'quantity' => $quantity,
            'api_reference' => $event['api_reference'] ?? 'DEMO-TICKET-API',
        ]);
        ?>
        <article class="transport-card">
            <div class="transport-head orange">
                <span>
                    <strong><?php echo h($event['event_name'] ?? 'Demo Ticket Event'); ?></strong>
                    <span><?php echo h($event['location'] ?? $location); ?> · <?php echo h($event['ticket_type'] ?? 'General Admission'); ?></span>
                </span>
                <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
            </div>
            <div class="transport-body">
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Date</span><strong><?php echo h(date('M d, Y', strtotime($eventDate))); ?></strong></div>
                    <div class="hotel-info"><span>Time</span><strong><?php echo h(date('h:i A', strtotime($eventDate))); ?></strong></div>
                    <div class="hotel-info"><span>Ref</span><strong><?php echo h($event['api_reference'] ?? 'DEMO'); ?></strong></div>
                </div>
                <div class="trip-meta"><span>Source: <?php echo h($event['api_source'] ?? ($ticketConfig['configured'] ? 'Ticketmaster Discovery API' : 'Demo fallback')); ?></span></div>
                <a class="btn" href="<?php echo h($bookingUrl); ?>">Book Ticket</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'home');
}

function page_tour_ticket_book(): void
{
    require_role('user', 'user-login');
    $eventName = trim($_GET['event_name'] ?? 'Demo Ticket Event');
    $location = trim($_GET['location'] ?? 'Lahore');
    $eventDate = trim($_GET['event_date'] ?? date('Y-m-d H:i:s', strtotime('+5 days 18:00')));
    $ticketType = trim($_GET['ticket_type'] ?? 'General Admission');
    $quantity = max(1, (int) ($_GET['quantity'] ?? 1));
    $price = (float) ($_GET['price'] ?? 2500);
    $apiReference = trim($_GET['api_reference'] ?? 'DEMO-TICKET-API');
    $totalPrice = $price * $quantity;
    $backUrl = 'index.php?' . http_build_query([
        'page' => 'tour-ticket-results',
        'keyword' => $eventName,
        'location' => $location,
        'date' => date('Y-m-d', strtotime($eventDate)),
        'quantity' => $quantity,
    ]);

    app_header('Book Ticket', true, 'with-bottom-nav');
    ?>
    <article class="transport-card">
        <div class="transport-head dark">
            <span>
                <strong><?php echo h($eventName); ?></strong>
                <span><?php echo h($location); ?> · <?php echo h($ticketType); ?></span>
            </span>
            <span class="transport-badge">Rs <?php echo h(number_format($totalPrice)); ?></span>
        </div>
        <div class="transport-body">
            <div class="hotel-info-row">
                <div class="hotel-info"><span>Date</span><strong><?php echo h(date('M d, Y', strtotime($eventDate))); ?></strong></div>
                <div class="hotel-info"><span>Tickets</span><strong><?php echo h($quantity); ?></strong></div>
                <div class="hotel-info"><span>API Ref</span><strong><?php echo h($apiReference); ?></strong></div>
            </div>
        </div>
    </article>

    <div class="section-title"><h2>Confirm tickets</h2><span class="badge">Payment pending</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=tour-ticket-book">
        <input type="hidden" name="action" value="ticket_booking">
        <div class="field">
            <label for="ticket_event_name">Event name</label>
            <input id="ticket_event_name" name="event_name" type="text" value="<?php echo h($eventName); ?>" required>
        </div>
        <div class="field">
            <label for="ticket_location_confirm">Location</label>
            <input id="ticket_location_confirm" name="location" type="text" value="<?php echo h($location); ?>" required>
        </div>
        <div class="field">
            <label for="ticket_event_date">Event date</label>
            <input id="ticket_event_date" name="event_date" type="datetime-local" value="<?php echo h(date('Y-m-d\TH:i', strtotime($eventDate))); ?>" required>
        </div>
        <div class="field">
            <label for="ticket_type">Ticket type</label>
            <input id="ticket_type" name="ticket_type" type="text" value="<?php echo h($ticketType); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="ticket_quantity_confirm">Quantity</label>
                <input id="ticket_quantity_confirm" name="quantity" type="number" min="1" value="<?php echo h($quantity); ?>" required>
            </div>
            <div class="field">
                <label for="ticket_price">Price</label>
                <input id="ticket_price" name="price" type="number" min="1" value="<?php echo h($price); ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="ticket_api_reference">API reference</label>
            <input id="ticket_api_reference" name="api_reference" type="text" value="<?php echo h($apiReference); ?>" required>
        </div>
        <button class="btn btn-orange" type="submit">Save Ticket and Pay</button>
        <a class="btn btn-light" href="<?php echo h($backUrl); ?>">Back to Results</a>
    </form>
    <?php
    app_footer('user', 'home');
}

function page_ticket_success(): void
{
    require_role('user', 'user-login');
    app_header('Ticket Success', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">🎟</span>
        <div>
            <h2 class="hero-title">Ticket booking saved</h2>
            <p class="lead">Your tour or event tickets were created. Complete Cashfree test payment to confirm them.</p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=my-bookings">My Bookings</a>
            <a class="btn" href="index.php?page=tour-ticket-search">Book More Tickets</a>
        </div>
    </div>
    <?php
    app_footer('user', 'home');
}

function demo_posted_trips(): array
{
    $state = plan_trip_state();

    $defaultTrips = [
        [
            'id' => 'TRP1023',
            'image' => 'trip-img-manali',
            'route' => (($state['from_city'] ?? 'Bangalore') . ' -> ' . (($state['to_city'] ?? 'Manali') ?: 'Manali')),
            'date' => date('d M Y', strtotime((string) ($state['trip_date'] ?? '+7 days'))),
            'travelers' => (int) ($state['travelers'] ?? 2),
            'status' => 'Open',
            'badge' => 'Open',
        ],
        ['id' => 'TRP1022', 'image' => 'trip-img-delhi', 'route' => 'Delhi -> Manali', 'date' => '18 May - 23 May 2024', 'travelers' => 4, 'status' => 'Offers Received', 'badge' => 'Offers Received'],
        ['id' => 'TRP1021', 'image' => 'trip-img-shimla', 'route' => 'Chandigarh -> Shimla', 'date' => '10 May - 12 May 2024', 'travelers' => 3, 'status' => 'Booked', 'badge' => 'Booked'],
        ['id' => 'TRP1019', 'image' => 'trip-img-goa', 'route' => 'Mumbai -> Goa', 'date' => '25 Apr - 29 Apr 2024', 'travelers' => 2, 'status' => 'Completed', 'badge' => 'Completed'],
    ];

    $postedTrips = $_SESSION['posted_trips'] ?? [];
    if (!is_array($postedTrips)) {
        $postedTrips = [];
    }

    return array_merge($postedTrips, $defaultTrips);
}

function demo_driver_offers(): array
{
    return [
        [
            'driver' => 'Amit Thakur',
            'rating' => '4.9',
            'trips' => 230,
            'car' => 'Innova Crysta',
            'type' => 'AC Car',
            'fuel' => 'Diesel',
            'price' => 24500,
            'avatar' => 'A',
            'car_class' => 'offer-car-white',
            'services' => ['AC Vehicle', 'Local Guide', 'Sightseeing', 'Pickup & Drop', 'Luggage Assist', 'Water Bottle', 'Music System', 'Emergency Support'],
        ],
        [
            'driver' => 'Vikram Negi',
            'rating' => '4.8',
            'trips' => 180,
            'car' => 'Toyota Innova',
            'type' => 'AC Car',
            'fuel' => 'Diesel',
            'price' => 24800,
            'avatar' => 'V',
            'car_class' => 'offer-car-silver',
            'services' => ['AC Vehicle', 'Local Guide', 'Sightseeing', 'Pickup & Drop', 'Luggage Assist', 'Water Bottle', 'Music System', 'Emergency Support'],
        ],
        [
            'driver' => 'Rohit Thakur',
            'rating' => '4.8',
            'trips' => 120,
            'car' => 'Honda BR-V',
            'type' => 'SUV',
            'fuel' => 'Petrol',
            'price' => 22800,
            'avatar' => 'R',
            'car_class' => 'offer-car-blue',
            'services' => ['AC Vehicle', 'Local Guide', 'Sightseeing', 'Pickup & Drop', 'Water Bottle', 'Emergency Support'],
        ],
    ];
}

function page_my_trips_posted(): void
{
    require_role('captain', 'captain-login');
    $trips = demo_posted_trips();
    $activeStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
    $tripTabs = [
        'all' => 'All',
        'open' => 'Open',
        'offers-received' => 'Offers Received',
        'booked' => 'Booked',
        'completed' => 'Completed',
    ];

    if (!isset($tripTabs[$activeStatus])) {
        $activeStatus = 'all';
    }

    if ($activeStatus !== 'all') {
        $trips = array_values(array_filter($trips, static function (array $trip) use ($activeStatus): bool {
            return strtolower(str_replace(' ', '-', (string) ($trip['status'] ?? 'open'))) === $activeStatus;
        }));
    }

    app_header('My Trips Posted', true, 'with-bottom-nav driver-posted-screen');
    ?>
    <div class="driver-screen-head">
        <h2>My Trips Posted</h2>
        <a href="index.php?page=post-new-trip">Post Trip</a>
    </div>
    <div class="driver-tabs">
        <?php foreach ($tripTabs as $statusKey => $label): ?>
            <a class="<?php echo h($activeStatus === $statusKey ? 'active' : ''); ?>" href="index.php?page=my-trips-posted&status=<?php echo h($statusKey); ?>"><?php echo h($label); ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$trips): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">TN</span>
            <div>
                <h2 class="hero-title">No <?php echo h($tripTabs[$activeStatus]); ?> trips</h2>
                <p class="lead">Post a new trip or switch tabs to view another status.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($trips as $trip): ?>
        <article class="posted-trip-card">
            <span class="posted-trip-thumb <?php echo h($trip['image']); ?>"></span>
            <div class="posted-trip-body">
                <small>Trip ID: <?php echo h($trip['id']); ?></small>
                <h3><?php echo h($trip['route']); ?></h3>
                <p><?php echo h($trip['date']); ?><br><?php echo h($trip['travelers']); ?> Travelers</p>
                <a href="index.php?page=driver-offers">View Details</a>
            </div>
            <span class="posted-trip-status <?php echo h(strtolower(str_replace(' ', '-', $trip['status']))); ?>"><?php echo h($trip['badge']); ?></span>
        </article>
    <?php endforeach; ?>

    <a class="btn posted-trip-new" href="index.php?page=post-new-trip">+ Post New Trip</a>
    <?php
    app_footer('captain', 'bookings');
}

function page_post_new_trip(): void
{
    require_role('captain', 'captain-login');
    $state = plan_trip_state();
    $startDate = date('Y-m-d', strtotime((string) ($state['trip_date'] ?? '+7 days')));
    $endDate = date('Y-m-d', strtotime($startDate . ' +4 days'));

    app_header('Post New Trip', true, 'with-bottom-nav driver-posted-screen');
    ?>
    <div class="driver-screen-head">
        <h2>Post New Trip</h2>
        <a href="index.php?page=my-trips-posted">My Trips</a>
    </div>
    <div class="post-trip-hero">
        <span>
            <strong>Tell drivers where you want to go</strong>
            <small>Drivers will send offers with car, captain, guide, and total price.</small>
        </span>
    </div>

    <form class="form card auth-card post-trip-form" method="post" action="index.php?page=post-new-trip">
        <input type="hidden" name="action" value="post_new_trip">
        <div class="field">
            <label for="post_from_city">From</label>
            <input id="post_from_city" name="from_city" type="text" value="<?php echo h($state['from_city'] ?? 'Bangalore'); ?>" required>
        </div>
        <div class="field">
            <label for="post_to_city">To</label>
            <input id="post_to_city" name="to_city" type="text" value="<?php echo h($state['to_city'] ?? 'Manali'); ?>" required>
        </div>
        <div class="row">
            <div class="field">
                <label for="post_start_date">Start date</label>
                <input id="post_start_date" name="start_date" type="date" value="<?php echo h($startDate); ?>" required>
            </div>
            <div class="field">
                <label for="post_end_date">End date</label>
                <input id="post_end_date" name="end_date" type="date" value="<?php echo h($endDate); ?>" required>
            </div>
        </div>
        <div class="row">
            <div class="field">
                <label for="post_travelers">Travelers</label>
                <input id="post_travelers" name="travelers" type="number" min="1" max="12" value="<?php echo h($state['travelers'] ?? 2); ?>" required>
            </div>
            <div class="field">
                <label for="post_budget">Budget</label>
                <select id="post_budget" name="budget" required>
                    <?php foreach (['10000-15000', '15000-25000', '25000-40000', '40000+'] as $budget): ?>
                        <option value="<?php echo h($budget); ?>" <?php echo ($state['budget'] ?? '') === $budget ? 'selected' : ''; ?>>Rs <?php echo h($budget); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="post_trip_type">Trip type</label>
            <select id="post_trip_type" name="trip_type" required>
                <option value="Family Trip">Family Trip</option>
                <option value="Friends Trip">Friends Trip</option>
                <option value="Couple Trip">Couple Trip</option>
                <option value="Adventure Trip">Adventure Trip</option>
                <option value="Business Trip">Business Trip</option>
            </select>
        </div>
        <div class="field">
            <label for="post_notes">Trip notes</label>
            <textarea id="post_notes" name="notes" placeholder="Need captain, local sightseeing, hotel pickup, and guide support."></textarea>
        </div>
        <button class="btn" type="submit">Post Trip</button>
        <a class="btn btn-light" href="index.php?page=my-trips-posted">Cancel</a>
    </form>
    <?php
    app_footer('captain', 'post');
}

function page_driver_offers(): void
{
    $role = (string) (auth()['role'] ?? '');
    if (!in_array($role, ['user', 'captain'], true)) {
        set_flash('warning', 'Please login to view driver offers.');
        redirect_to('user-login');
    }

    $isCaptain = $role === 'captain';
    $offers = demo_driver_offers();
    $activeFilter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
    $offerTabs = [
        'all' => 'All Offers',
        'best-price' => 'Best Price',
        'top-rated' => 'Top Rated',
        'recommended' => 'Recommended',
    ];

    if (!isset($offerTabs[$activeFilter])) {
        $activeFilter = 'all';
    }

    if ($activeFilter === 'best-price') {
        usort($offers, static fn(array $a, array $b): int => ((float) $a['price']) <=> ((float) $b['price']));
    } elseif ($activeFilter === 'top-rated') {
        usort($offers, static fn(array $a, array $b): int => ((float) $b['rating']) <=> ((float) $a['rating']));
    } elseif ($activeFilter === 'recommended') {
        usort($offers, static function (array $a, array $b): int {
            $ratingCompare = ((float) $b['rating']) <=> ((float) $a['rating']);
            return $ratingCompare !== 0 ? $ratingCompare : ((int) $b['trips']) <=> ((int) $a['trips']);
        });
    }

    app_header('Driver Offers', true, 'with-bottom-nav driver-offers-screen');
    ?>
    <div class="driver-screen-head">
        <h2>Driver Offers</h2>
        <a href="index.php?page=<?php echo $isCaptain ? 'my-trips-posted' : 'user-dashboard'; ?>"><?php echo $isCaptain ? 'Trips' : 'Home'; ?></a>
    </div>
    <div class="driver-tabs">
        <?php foreach ($offerTabs as $filterKey => $label): ?>
            <a class="<?php echo h($activeFilter === $filterKey ? 'active' : ''); ?>" href="index.php?page=driver-offers&filter=<?php echo h($filterKey); ?>"><?php echo h($label); ?></a>
        <?php endforeach; ?>
    </div>

    <?php foreach ($offers as $offer): ?>
        <article class="driver-offer-card">
            <div class="driver-offer-top">
                <span class="driver-offer-avatar"><?php echo h($offer['avatar']); ?></span>
                <span>
                    <strong><?php echo h($offer['driver']); ?></strong>
                    <small><?php echo h($offer['rating']); ?> (<?php echo h($offer['trips']); ?> Trips)</small>
                </span>
                <span class="driver-offer-price">Rs <?php echo h(number_format((float) $offer['price'])); ?><small>Total Price</small></span>
            </div>
            <div class="driver-car-row">
                <span class="driver-car-art <?php echo h($offer['car_class']); ?>"></span>
                <span>
                    <strong><?php echo h($offer['car']); ?></strong>
                    <small><?php echo h($offer['type']); ?> - 4 Seats - <?php echo h($offer['fuel']); ?></small>
                </span>
            </div>
            <h4>Services Included</h4>
            <div class="driver-service-grid">
                <?php foreach ($offer['services'] as $service): ?>
                    <span><?php echo h($service); ?></span>
                <?php endforeach; ?>
            </div>
            <div class="btn-row">
                <a class="btn btn-light" href="index.php?page=driver-offers&filter=<?php echo h($activeFilter); ?>">View Details</a>
                <a class="btn" href="index.php?page=<?php echo $isCaptain ? 'captain-dashboard' : 'plan-trip'; ?>"><?php echo $isCaptain ? 'Accept Offer' : 'Plan Trip'; ?></a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer($isCaptain ? 'captain' : 'user', $isCaptain ? 'trips' : 'home');
}

function page_saved_trips(): void
{
    require_role('captain', 'captain-login');
    app_header('Saved Trips', true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon">TN</span>
        <div>
            <h2 class="hero-title">Saved trips</h2>
            <p class="lead">Your saved Manali, Shimla, and Leh Ladakh trip ideas appear here.</p>
        </div>
        <a class="btn" href="index.php?page=post-new-trip">Post New Trip</a>
    </div>
    <?php
    app_footer('captain', 'bookings');
}

function page_trip_messages(): void
{
    require_role('captain', 'captain-login');
    $threads = fetch_captain_message_threads();

    app_header('Messages', false, 'with-bottom-nav captain-mobile-screen captain-chat-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Messages', 'captain-dashboard'); ?>
        <div class="captain-chat-title">
            <strong>Ride conversations</strong>
            <small>Messages are saved locally and shared with assigned passengers.</small>
        </div>

        <div class="message-thread-list">
            <?php if (!$threads): ?>
                <div class="chat-empty-state">
                    <strong>No conversations yet</strong>
                    <span>Assigned ride chats will appear here after users send messages.</span>
                </div>
            <?php endif; ?>

            <?php foreach ($threads as $thread): ?>
                <?php
                $lastMessage = trim((string) ($thread['last_message'] ?? ''));
                $preview = $lastMessage !== '' ? $lastMessage : 'No messages yet. Open this ride chat.';
                $messageCount = (int) ($thread['message_count'] ?? 0);
                ?>
                <a class="message-thread-card" href="index.php?page=captain-trip-chat&ride_id=<?php echo h($thread['id']); ?>">
                    <span class="message-thread-avatar"><?php echo h(strtoupper(substr((string) ($thread['user_name'] ?? 'P'), 0, 1))); ?></span>
                    <span class="message-thread-copy">
                        <strong><?php echo h($thread['user_name'] ?? 'Passenger'); ?></strong>
                        <small><?php echo h($preview); ?></small>
                        <em><?php echo h(($thread['pickup_location'] ?? 'Pickup') . ' to ' . ($thread['drop_location'] ?? 'Drop')); ?></em>
                    </span>
                    <span class="message-thread-meta">
                        <small><?php echo h(chat_time_label($thread['last_message_at'] ?? ($thread['created_at'] ?? null))); ?></small>
                        <?php if ($messageCount > 0): ?><b><?php echo h($messageCount); ?></b><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    app_footer('captain', 'messages');
}

function page_driver_chat(): void
{
    require_role('user', 'user-login');
    $chat = user_driver_chat_context();
    $messages = $chat['can_message']
        ? fetch_ride_messages((int) $chat['ride_id'], (int) $chat['user_id'], (int) $chat['captain_id'])
        : [];

    app_header('Chat with Driver', false, 'with-bottom-nav chat-mobile-screen');
    ?>
    <section class="driver-chat-screen">
        <div class="chat-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <span class="chat-avatar"><?php echo h(strtoupper(substr((string) $chat['captain_name'], 0, 1))); ?></span>
            <span class="chat-driver-meta">
                <strong><?php echo h($chat['captain_name']); ?></strong>
                <small><?php echo h($chat['captain_vehicle'] . ' - ' . $chat['captain_number']); ?></small>
            </span>
            <a class="chat-action call-action" href="tel:#" aria-label="Call driver"></a>
            <span class="chat-action menu-action" aria-hidden="true"></span>
        </div>

        <div class="chat-trip-card">
            <strong>Your captain is <?php echo h(strtolower((string) $chat['status'])); ?></strong>
            <span><?php echo h($chat['pickup']); ?> to <?php echo h($chat['drop']); ?></span>
            <small><?php echo h($chat['message_hint']); ?></small>
            <?php if (!$chat['can_message'] && !empty($chat['action_url'])): ?>
                <a class="chat-select-captain-btn" href="<?php echo h($chat['action_url']); ?>"><?php echo h($chat['action_label']); ?></a>
            <?php endif; ?>
        </div>

        <?php if ($chat['can_message']): ?>
            <div class="chat-thread" data-chat-thread>
                <?php render_ride_messages($messages, 'user'); ?>
            </div>

            <form class="chat-compose" method="post" action="index.php?page=driver-chat">
                <input type="hidden" name="action" value="send_ride_message">
                <input type="hidden" name="ride_id" value="<?php echo h($chat['ride_id']); ?>">
                <input name="message" type="text" maxlength="1000" placeholder="Type your message..." aria-label="Type your message" required>
                <button type="submit" aria-label="Send message"></button>
            </form>
        <?php else: ?>
            <div class="chat-thread chat-thread-setup">
                <div class="chat-empty-state chat-setup-state">
                    <strong>Choose a captain to start chat</strong>
                    <span>Open available captains, pick one, and this conversation will unlock automatically.</span>
                    <?php if (!empty($chat['action_url'])): ?>
                        <a class="chat-select-captain-btn" href="<?php echo h($chat['action_url']); ?>"><?php echo h($chat['action_label']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php
    app_footer('user', 'messages');
}

function page_user_profile(): void
{
    require_role('user', 'user-login');
    $profile = current_user_profile();
    $bookingTotal =
        user_table_count('rides', current_user_id() ?? 0) +
        user_table_count('hotel_bookings', current_user_id() ?? 0) +
        user_table_count('train_bookings', current_user_id() ?? 0) +
        user_table_count('bus_bookings', current_user_id() ?? 0) +
        user_table_count('restaurant_bookings', current_user_id() ?? 0) +
        user_table_count('ticket_bookings', current_user_id() ?? 0);
    $menuItems = [
        ['Personal Information', $profile['email'] ?? '', 'user-profile', 'profile-info'],
        ['Payment Method', 'Cashfree demo and saved preferences', 'rewards-offers', 'profile-payment'],
        ['Saved Addresses', $profile['city'] ?: 'Add home city', 'user-dashboard', 'profile-address'],
        ['My Reviews', 'Ride feedback and ratings', 'my-bookings', 'profile-review'],
        ['Notifications', 'Offers and booking alerts', 'rewards-offers', 'profile-bell'],
        ['Help & Support', 'TripNovaa customer support', 'driver-chat', 'profile-help'],
        ['Settings', 'Account and security options', 'user-profile', 'profile-settings'],
    ];

    app_header('User Profile', false, 'with-bottom-nav profile-mobile-screen');
    ?>
    <section class="user-profile-screen">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>User Profile</h2>
            <span class="settings-dot" aria-hidden="true"></span>
        </div>

        <div class="profile-identity">
            <span class="profile-photo"><?php echo h(strtoupper(substr((string) $profile['full_name'], 0, 1))); ?></span>
            <strong><?php echo h($profile['full_name']); ?></strong>
            <small><?php echo h($profile['phone']); ?></small>
            <em><?php echo h($profile['city'] ?: 'TripNovaa traveler'); ?></em>
        </div>

        <div class="profile-stats">
            <span><strong><?php echo h($bookingTotal); ?></strong><small>Bookings</small></span>
            <span><strong><?php echo h((int) ($profile['reward_points'] ?? 0)); ?></strong><small>Points</small></span>
        </div>

        <nav class="profile-menu-list" aria-label="Profile options">
            <?php foreach ($menuItems as $item): ?>
                <a href="index.php?page=<?php echo h($item[2]); ?>">
                    <span class="profile-menu-icon <?php echo h($item[3]); ?>"></span>
                    <span><strong><?php echo h($item[0]); ?></strong><small><?php echo h($item[1]); ?></small></span>
                </a>
            <?php endforeach; ?>
            <a class="logout-row" href="index.php?page=logout">
                <span class="profile-menu-icon profile-logout"></span>
                <span><strong>Logout</strong><small>Securely end this session</small></span>
            </a>
        </nav>
    </section>
    <?php
    app_footer('user', 'profile');
}

function page_rewards_offers(): void
{
    require_role('user', 'user-login');
    $userId = current_user_id() ?? 0;
    $rewardPoints = user_reward_points_total($userId);
    $offers = fetch_available_offers();

    app_header('Rewards/Offers', true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Rewards wallet</span>
        <h2><?php echo h(number_format($rewardPoints)); ?> points</h2>
        <p>Earn reward points after successful payments and completed rides. Use active offer codes on the payment page.</p>
    </div>

    <div class="section-title">
        <h2>Available offers</h2>
        <span class="badge"><?php echo h(count($offers)); ?> offers</span>
    </div>

    <?php if (!$offers): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">🎁</span>
            <div>
                <h2 class="hero-title">No offers yet</h2>
                <p class="lead">Add active rows in the offers table to show travel coupons here.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($offers as $offer): ?>
        <?php
        $applyUrl = 'index.php?' . http_build_query([
            'page' => 'apply-offer',
            'coupon_code' => $offer['code'],
            'amount' => max(1000, (float) ($offer['min_booking_amount'] ?? 0)),
        ]);
        ?>
        <article class="transport-card">
            <div class="transport-head <?php echo ($offer['status'] ?? '') === 'active' ? 'green' : 'dark'; ?>">
                <span>
                    <strong><?php echo h($offer['title']); ?></strong>
                    <span><?php echo h($offer['description']); ?></span>
                </span>
                <span class="transport-badge"><?php echo h($offer['code']); ?></span>
            </div>
            <div class="transport-body">
                <div class="hotel-info-row">
                    <div class="hotel-info"><span>Discount</span><strong><?php echo h(format_offer_discount($offer)); ?></strong></div>
                    <div class="hotel-info"><span>Valid until</span><strong><?php echo h($offer['valid_to']); ?></strong></div>
                    <div class="hotel-info"><span>Status</span><strong><?php echo h(ucwords($offer['status'])); ?></strong></div>
                </div>
                <a class="btn" href="<?php echo h($applyUrl); ?>">Apply Offer</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
    app_footer('user', 'rewards');
}

function page_apply_offer(): void
{
    require_role('user', 'user-login');
    $couponCode = strtoupper(trim($_GET['coupon_code'] ?? ($_GET['code'] ?? '')));
    $amount = (float) ($_GET['amount'] ?? 1000);
    $bookingType = trim($_GET['booking_type'] ?? '');
    $bookingId = (int) ($_GET['booking_id'] ?? 0);
    $result = calculate_offer_result($couponCode, $amount);
    $hasBookingContext = in_array($bookingType, supported_booking_types(), true) && $bookingId > 0 && $amount > 0;

    app_header('Apply Offer', true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Coupon check</span>
        <h2>Apply travel offer</h2>
        <p>Preview a discount here, or enter the same code on the payment page before paying.</p>
    </div>

    <form class="form card auth-card" method="get" action="index.php">
        <input type="hidden" name="page" value="apply-offer">
        <div class="field">
            <label for="apply_coupon_code">Coupon code</label>
            <input id="apply_coupon_code" name="coupon_code" type="text" value="<?php echo h($couponCode); ?>" placeholder="TRIP10">
        </div>
        <div class="field">
            <label for="apply_amount">Booking amount</label>
            <input id="apply_amount" name="amount" type="number" min="1" value="<?php echo h($amount); ?>">
        </div>
        <button class="btn btn-orange" type="submit">Check Offer</button>
    </form>

    <?php if ($couponCode !== ''): ?>
        <div class="card ride-summary">
            <div class="summary-row"><span>Coupon</span><strong><?php echo h($couponCode); ?></strong></div>
            <div class="summary-row"><span>Status</span><strong><?php echo h($result['valid'] ? 'Valid' : 'Not applied'); ?></strong></div>
            <div class="summary-row"><span>Message</span><strong><?php echo h($result['message']); ?></strong></div>
            <div class="summary-row"><span>Original amount</span><strong>Rs <?php echo h(number_format((float) $result['original_amount'], 2)); ?></strong></div>
            <div class="summary-row"><span>Discount</span><strong>Rs <?php echo h(number_format((float) $result['discount_amount'], 2)); ?></strong></div>
            <div class="summary-row"><span>Final amount</span><strong>Rs <?php echo h(number_format((float) $result['final_amount'], 2)); ?></strong></div>
        </div>
    <?php endif; ?>

    <div class="btn-row">
        <a class="btn btn-light" href="index.php?page=rewards-offers">Back to Offers</a>
        <?php if ($hasBookingContext && $result['valid']): ?>
            <?php
            $paymentUrl = 'index.php?' . http_build_query([
                'page' => 'payment',
                'booking_type' => $bookingType,
                'booking_id' => $bookingId,
                'amount' => $amount,
                'coupon_code' => $couponCode,
            ]);
            ?>
            <a class="btn" href="<?php echo h($paymentUrl); ?>">Continue to Payment</a>
        <?php endif; ?>
    </div>
    <?php
    app_footer('user', 'rewards');
}

function render_my_booking_section(string $title, string $icon, string $type, array $rows): void
{
    $heading = trim(($icon !== '' ? $icon . ' ' : '') . $title);
    ?>
    <div class="section-title">
        <h2><?php echo h($heading); ?></h2>
        <span class="badge"><?php echo h(count($rows)); ?></span>
    </div>

    <?php if (!$rows): ?>
        <div class="trip-card">
            <h3>No <?php echo h(strtolower($title)); ?> yet</h3>
            <div class="trip-meta">
                <span>This section will show your <?php echo h(strtolower($title)); ?> after booking.</span>
            </div>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php foreach ($rows as $row): ?>
        <?php render_my_booking_card($type, $row); ?>
    <?php endforeach; ?>
    <?php
}

function render_my_booking_card(string $type, array $row): void
{
    $bookingId = (int) ($row['id'] ?? 0);
    $amount = (float) ($row['amount'] ?? 0);
    $status = ride_status_label((string) ($row['status'] ?? 'pending'));
    $paymentLabel = booking_payment_label($row, $type);
    $date = 'Not set';
    $title = 'TripNovaa booking';
    $details = [];

    if ($type === 'ride') {
        $title = ride_type_label($row['ride_type'] ?? 'car') . ' ride';
        $date = display_booking_date(trim((string) (($row['travel_date'] ?? '') . ' ' . ($row['travel_time'] ?? ''))));
        $details[] = 'Pickup: ' . ($row['pickup_location'] ?? '');
        $details[] = 'Drop: ' . ($row['drop_location'] ?? '');
        if (!empty($row['captain_name'])) {
            $details[] = 'Captain: ' . $row['captain_name'];
        }
    } elseif ($type === 'hotel') {
        $title = (string) ($row['title'] ?? 'Hotel booking');
        $date = display_booking_date($row['check_in_date'] ?? '');
        $details[] = ($row['city'] ?? '') . ' · ' . ($row['room_type'] ?? 'Room');
        $details[] = 'Check-out: ' . display_booking_date($row['check_out_date'] ?? '');
        $details[] = 'Guests: ' . ($row['guests'] ?? 1) . ' - Rooms: ' . ($row['rooms'] ?? 1);
    } elseif ($type === 'train') {
        $title = (string) ($row['title'] ?? 'Train booking');
        $date = display_booking_date($row['travel_date'] ?? '');
        $details[] = ($row['origin'] ?? '') . ' to ' . ($row['destination'] ?? '');
        $details[] = ($row['seat_class'] ?? 'Seat') . ' · ' . ($row['passengers'] ?? 1) . ' passenger(s)';
        $details[] = 'Train: ' . ($row['train_number'] ?? 'N/A');
    } elseif ($type === 'bus') {
        $title = (string) ($row['title'] ?? 'Bus booking');
        $date = display_booking_date($row['travel_date'] ?? '');
        $details[] = ($row['origin'] ?? '') . ' to ' . ($row['destination'] ?? '');
        $details[] = ($row['bus_type'] ?? 'Bus') . ' · Seat ' . ($row['seat_no'] ?? 'N/A');
        $details[] = ($row['seats'] ?? 1) . ' passenger(s)';
    } elseif ($type === 'restaurant') {
        $title = (string) ($row['title'] ?? 'Restaurant booking');
        $date = display_booking_date(trim((string) (($row['booking_date'] ?? '') . ' ' . ($row['booking_time'] ?? ''))));
        $details[] = ($row['city'] ?? '') . ' · ' . ($row['guests'] ?? 1) . ' guest(s)';
    } elseif ($type === 'ticket') {
        $title = (string) ($row['title'] ?? 'Ticket booking');
        $date = display_booking_date($row['event_date'] ?? '');
        $details[] = ($row['location'] ?? '') . ' · ' . ($row['ticket_type'] ?? 'Ticket');
        $details[] = 'Quantity: ' . ($row['quantity'] ?? ($row['tickets'] ?? 1));
        $details[] = 'API ref: ' . ($row['api_reference'] ?? 'N/A');
    }

    $canPay = !booking_is_paid($row, $type)
        && $bookingId > 0
        && $amount > 0
        && !in_array(strtolower((string) ($row['status'] ?? '')), ['cancelled', 'rejected'], true);
    $actions = [];
    if ($type === 'ride') {
        $actions[] = ['label' => 'Track Ride', 'url' => 'index.php?page=ride-tracking&ride_id=' . $bookingId, 'class' => 'btn'];
        if (strtolower((string) ($row['status'] ?? '')) === 'completed' && (int) ($row['feedback_count'] ?? 0) === 0) {
            $actions[] = ['label' => 'Give Feedback', 'url' => 'index.php?page=feedback&booking_type=ride&booking_id=' . $bookingId, 'class' => 'btn btn-light'];
        }
    }
    if ($canPay) {
        $actions[] = ['label' => 'Pay Now', 'url' => booking_payment_url($type, $bookingId, $amount), 'class' => 'btn btn-orange'];
    }
    ?>
    <article class="trip-card">
        <h3><?php echo h($title); ?> · Rs <?php echo h(number_format($amount, 2)); ?></h3>
        <div class="trip-meta">
            <?php foreach ($details as $detail): ?>
                <span><?php echo h($detail); ?></span>
            <?php endforeach; ?>
            <span><strong>Date:</strong> <?php echo h($date); ?></span>
            <span><strong>Status:</strong> <?php echo h($status); ?> · <strong>Payment:</strong> <?php echo h($paymentLabel); ?></span>
        </div>
        <?php if ($actions): ?>
            <div class="trip-actions <?php echo count($actions) === 1 ? 'single' : ''; ?>">
                <?php foreach ($actions as $action): ?>
                    <a class="<?php echo h($action['class']); ?>" href="<?php echo h($action['url']); ?>"><?php echo h($action['label']); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
}

function render_payment_history_section(array $payments): void
{
    ?>
    <div class="section-title">
        <h2>💳 Payments</h2>
        <span class="badge"><?php echo h(count($payments)); ?></span>
    </div>

    <?php if (!$payments): ?>
        <div class="trip-card">
            <h3>No payments yet</h3>
            <div class="trip-meta"><span>Successful Cashfree demo payments will appear here.</span></div>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php foreach ($payments as $payment): ?>
        <article class="trip-card">
            <h3><?php echo h(ucwords($payment['booking_type'] ?? 'booking')); ?> payment · Rs <?php echo h(number_format((float) ($payment['amount'] ?? 0), 2)); ?></h3>
            <div class="trip-meta">
                <span><strong>Date:</strong> <?php echo h(display_booking_date($payment['paid_at'] ?? ($payment['created_at'] ?? ''))); ?></span>
                <span><strong>Status:</strong> <?php echo h(ucwords(str_replace('_', ' ', (string) ($payment['payment_status'] ?? 'pending')))); ?></span>
                <span><strong>Method:</strong> <?php echo h(($payment['payment_provider'] ?? 'demo') . ' · ' . ($payment['payment_method'] ?? 'Demo')); ?></span>
                <span><strong>Transaction:</strong> <?php echo h($payment['transaction_id'] ?? ($payment['cashfree_order_id'] ?? 'N/A')); ?></span>
            </div>
        </article>
    <?php endforeach; ?>
    <?php
}

function page_my_bookings(): void
{
    require_role('user', 'user-login');
    $userId = current_user_id() ?? 0;
    $rides = fetch_user_ride_bookings($userId);
    $hotels = fetch_user_hotel_bookings($userId);
    $trains = fetch_user_train_bookings($userId);
    $buses = fetch_user_bus_bookings($userId);
    $restaurants = fetch_user_restaurant_bookings($userId);
    $tickets = fetch_user_ticket_bookings($userId);
    $payments = fetch_user_payments($userId);
    $activeTab = strtolower(trim((string) ($_GET['type'] ?? 'all')));
    $activeTab = in_array($activeTab, ['all', 'rides', 'hotels', 'tickets', 'tours'], true) ? $activeTab : 'all';
    $bookingTabs = [
        'all' => ['All', count($rides) + count($hotels) + count($trains) + count($buses) + count($restaurants) + count($tickets)],
        'rides' => ['Rides', count($rides)],
        'hotels' => ['Hotels', count($hotels)],
        'tickets' => ['Tickets', count($trains) + count($buses)],
        'tours' => ['Tours', count($tickets)],
    ];

    app_header('My Bookings', false, 'with-bottom-nav bookings-mobile-screen');
    ?>
    <section class="bookings-screen">
        <div class="module-mobile-head">
            <a class="back-link" href="index.php?page=user-dashboard" aria-label="Back"></a>
            <h2>My Bookings</h2>
            <span></span>
        </div>

        <nav class="booking-filter-tabs" aria-label="Booking filters">
            <?php foreach ($bookingTabs as $tabKey => $tabInfo): ?>
                <?php $tabUrl = 'index.php?' . http_build_query(['page' => 'my-bookings', 'type' => $tabKey]); ?>
                <a class="<?php echo h($activeTab === $tabKey ? 'active' : ''); ?>" href="<?php echo h($tabUrl); ?>">
                    <?php echo h($tabInfo[0]); ?>
                    <span><?php echo h($tabInfo[1]); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="bookings-count-strip">
            <span><strong><?php echo h($bookingTabs[$activeTab][1]); ?></strong> <?php echo h(strtolower($bookingTabs[$activeTab][0])); ?></span>
            <a href="index.php?page=hotel-search">Book More</a>
        </div>

        <?php if ($activeTab === 'all'): ?>
            <?php render_my_booking_section('Rides', '', 'ride', $rides); ?>
            <?php render_my_booking_section('Hotels', '', 'hotel', $hotels); ?>
            <?php render_my_booking_section('Train Tickets', '', 'train', $trains); ?>
            <?php render_my_booking_section('Bus Tickets', '', 'bus', $buses); ?>
            <?php render_my_booking_section('Restaurants', '', 'restaurant', $restaurants); ?>
            <?php render_my_booking_section('Tours', '', 'ticket', $tickets); ?>
        <?php elseif ($activeTab === 'rides'): ?>
            <?php render_my_booking_section('Rides', '', 'ride', $rides); ?>
        <?php elseif ($activeTab === 'hotels'): ?>
            <?php render_my_booking_section('Hotels', '', 'hotel', $hotels); ?>
        <?php elseif ($activeTab === 'tickets'): ?>
            <?php render_my_booking_section('Train Tickets', '', 'train', $trains); ?>
            <?php render_my_booking_section('Bus Tickets', '', 'bus', $buses); ?>
        <?php elseif ($activeTab === 'tours'): ?>
            <?php render_my_booking_section('Tours', '', 'ticket', $tickets); ?>
        <?php endif; ?>
    </section>
    <?php
    app_footer('user', 'bookings');
    return;
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Booking history</span>
        <h2>All your trips in one place</h2>
        <p>Review rides, stays, seats, tables, tickets, payments, and pending actions.</p>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><strong><?php echo h(count($rides)); ?></strong><span>Rides</span></div>
        <div class="stat-card"><strong><?php echo h(count($hotels)); ?></strong><span>Hotels</span></div>
        <div class="stat-card"><strong><?php echo h(count($trains)); ?></strong><span>Trains</span></div>
        <div class="stat-card"><strong><?php echo h(count($buses)); ?></strong><span>Buses</span></div>
        <div class="stat-card"><strong><?php echo h(count($restaurants)); ?></strong><span>Food</span></div>
        <div class="stat-card"><strong><?php echo h(count($tickets)); ?></strong><span>Tickets</span></div>
    </div>

    <?php
    render_my_booking_section('Ride bookings', '🚕', 'ride', $rides);
    render_my_booking_section('Hotel bookings', '🏨', 'hotel', $hotels);
    render_my_booking_section('Train bookings', '🚆', 'train', $trains);
    render_my_booking_section('Bus bookings', '🚌', 'bus', $buses);
    render_my_booking_section('Restaurant bookings', '🍽', 'restaurant', $restaurants);
    render_my_booking_section('Tour/ticket bookings', '🎟', 'ticket', $tickets);
    render_payment_history_section($payments);
    ?>

    <div class="btn-row">
        <a class="btn btn-light" href="index.php?page=user-dashboard">Back to Dashboard</a>
    </div>
    <?php
    app_footer('user', 'bookings');
}

function page_customer_module(string $moduleKey): void
{
    require_role('user', 'user-login');
    $userId = current_user_id() ?? 0;
    $modules = [
        'book-ride' => ['Book Ride', '🚕', 'Choose pickup, drop, ride type, captain, map tracking, and payment.', 'Start Ride Booking'],
        'hotel-search' => ['Hotel Booking', '🏨', 'Search hotels by city, check-in date, guests, rooms, and price.', 'Search Hotels'],
        'train-search' => ['Train Booking', '🚆', 'Find trains, select class, passenger count, fare, and confirmation.', 'Search Trains'],
        'bus-search' => ['Bus Booking', '🚌', 'Book intercity buses by route, date, bus type, and seats.', 'Search Buses'],
        'restaurant-search' => ['Restaurant Booking', '🍽', 'Reserve restaurants by city, date, time, guests, and cuisine.', 'Find Restaurants'],
        'tour-ticket-search' => ['Tours/Tickets', '🎟', 'Browse demo ticket API tours, events, passes, and local experiences.', 'Browse Tickets'],
        'rewards-offers' => ['Rewards/Offers', '🎁', 'View reward points, active promo codes, and travel discounts.', 'View Offers'],
        'my-bookings' => ['My Bookings', '🧾', 'Review ride, hotel, train, bus, restaurant, ticket, and payment history.', 'View History'],
        'user-profile' => ['Profile', '👤', 'Manage customer account details and quickly logout from TripNovaa.', 'Profile Options'],
    ];
    $module = $modules[$moduleKey] ?? $modules['my-bookings'];
    $active = match ($moduleKey) {
        'my-bookings' => 'bookings',
        'rewards-offers' => 'rewards',
        'user-profile' => 'profile',
        default => 'home',
    };

    app_header($module[0], true, 'with-bottom-nav');
    ?>
    <div class="card module-page-card">
        <span class="module-page-icon"><?php echo h($module[1]); ?></span>
        <div>
            <h2 class="hero-title"><?php echo h($module[0]); ?></h2>
            <p class="lead"><?php echo h($module[2]); ?></p>
        </div>
        <div class="btn-row">
            <a class="btn btn-light" href="index.php?page=user-dashboard">Home</a>
            <?php if ($moduleKey === 'user-profile'): ?>
                <a class="btn btn-orange" href="index.php?page=logout">Logout</a>
            <?php else: ?>
                <a class="btn" href="index.php?page=<?php echo h($moduleKey); ?>"><?php echo h($module[3]); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($moduleKey === 'my-bookings'): ?>
        <div class="section-title"><h2>Booking summary</h2><span class="badge">Live data</span></div>
        <div class="stat-grid">
            <div class="stat-card"><strong><?php echo user_table_count('rides', $userId); ?></strong><span>Rides</span></div>
            <div class="stat-card"><strong><?php echo user_table_count('hotel_bookings', $userId); ?></strong><span>Hotels</span></div>
            <div class="stat-card"><strong><?php echo user_table_count('train_bookings', $userId); ?></strong><span>Trains</span></div>
            <div class="stat-card"><strong><?php echo user_table_count('bus_bookings', $userId); ?></strong><span>Buses</span></div>
            <div class="stat-card"><strong><?php echo user_table_count('restaurant_bookings', $userId); ?></strong><span>Restaurants</span></div>
            <div class="stat-card"><strong><?php echo user_table_count('ticket_bookings', $userId); ?></strong><span>Tickets</span></div>
        </div>
    <?php elseif ($moduleKey === 'rewards-offers'): ?>
        <div class="section-title"><h2>Rewards snapshot</h2><span class="badge">Demo</span></div>
        <div class="stat-grid">
            <div class="stat-card"><strong><?php echo user_table_count('rewards', $userId); ?></strong><span>Reward entries</span></div>
            <div class="stat-card"><strong><?php echo table_count('offers'); ?></strong><span>Active offers</span></div>
        </div>
    <?php elseif ($moduleKey === 'user-profile'): ?>
        <div class="section-title"><h2>Account</h2><span class="badge">User</span></div>
        <div class="profile-strip">
            <span class="avatar"><?php echo h(strtoupper(substr(current_user_name(), 0, 1))); ?></span>
            <span>
                <strong><?php echo h(current_user_name()); ?></strong>
                <span>Role: customer/user · Session protected dashboard</span>
            </span>
        </div>
    <?php else: ?>
        <div class="section-title"><h2>Coming next</h2><span class="badge">Module</span></div>
        <div class="placeholder-list">
            <div class="placeholder-row">
                <span><strong><?php echo h($module[0]); ?> flow</strong><span>The database and page route are ready; detailed booking forms will be connected next.</span></span>
                <span class="badge">Ready</span>
            </div>
        </div>
    <?php endif; ?>
    <?php
    app_footer('user', $active);
}

function render_captain_trip_card(array $ride, string $mode): void
{
    $dateTime = trim((string) (($ride['travel_date'] ?? '') . ' ' . substr((string) ($ride['travel_time'] ?? ''), 0, 5)));
    ?>
    <article class="trip-card">
        <h3><?php echo h(ride_type_label($ride['ride_type'] ?? 'car')); ?> ride · Rs <?php echo h($ride['fare'] ?? '0'); ?></h3>
        <div class="trip-meta">
            <span><strong>Customer:</strong> <?php echo h($ride['user_name'] ?? 'Customer'); ?> · <?php echo h($ride['user_phone'] ?? ''); ?></span>
            <span><strong>Pickup:</strong> <?php echo h($ride['pickup_location'] ?? ''); ?></span>
            <span><strong>Drop:</strong> <?php echo h($ride['drop_location'] ?? ''); ?></span>
            <span><strong>Date/time:</strong> <?php echo h($dateTime !== '' ? $dateTime : 'Not set'); ?></span>
            <span><strong>Status:</strong> <?php echo h(ride_status_label((string) ($ride['status'] ?? 'pending'))); ?></span>
        </div>

        <?php if ($mode === 'requests'): ?>
            <div class="trip-actions">
                <form method="post" action="index.php?page=captain-ride-requests">
                    <input type="hidden" name="action" value="captain_ride_action">
                    <input type="hidden" name="ride_action" value="accept">
                    <input type="hidden" name="ride_id" value="<?php echo h($ride['id']); ?>">
                    <button class="btn" type="submit">Accept</button>
                </form>
                <form method="post" action="index.php?page=captain-ride-requests">
                    <input type="hidden" name="action" value="captain_ride_action">
                    <input type="hidden" name="ride_action" value="reject">
                    <input type="hidden" name="ride_id" value="<?php echo h($ride['id']); ?>">
                    <button class="btn btn-light" type="submit">Reject</button>
                </form>
            </div>
        <?php elseif ($mode === 'current'): ?>
            <form class="trip-actions single" method="post" action="index.php?page=captain-current-trips">
                <input type="hidden" name="action" value="captain_ride_action">
                <input type="hidden" name="ride_action" value="complete">
                <input type="hidden" name="ride_id" value="<?php echo h($ride['id']); ?>">
                <button class="btn btn-orange" type="submit">Mark Completed</button>
            </form>
        <?php endif; ?>
    </article>
    <?php
}

function captain_trip_page_context(array $statuses = []): array
{
    return captain_trip_context(captain_trip_page_ride($statuses));
}

function captain_trip_page_ride(array $statuses = []): ?array
{
    $rideId = (int) ($_GET['ride_id'] ?? 0);
    $ride = $rideId > 0 ? get_captain_ride_by_id($rideId) : null;
    if ($ride && $statuses && !in_array((string) ($ride['status'] ?? ''), $statuses, true)) {
        return null;
    }

    if (!$ride) {
        $ride = fetch_latest_captain_ride($statuses);
    }

    return $ride ?: null;
}

function captain_mobile_head(string $title, string $backPage = 'captain-dashboard'): void
{
    ?>
    <div class="captain-mobile-head">
        <a class="back-link" href="index.php?page=<?php echo h($backPage); ?>" aria-label="Back"></a>
        <h2><?php echo h($title); ?></h2>
        <span></span>
    </div>
    <?php
}

function render_captain_request_card(array $trip, string $mode = 'request'): void
{
    $rideId = (int) ($trip['ride_id'] ?? 0);
    $detailsUrl = 'index.php?page=captain-trip-details' . ($rideId > 0 ? '&ride_id=' . $rideId : '');
    ?>
    <article class="captain-request-card">
        <span class="captain-trip-thumb"></span>
        <div class="captain-request-main">
            <strong><?php echo h($trip['title']); ?></strong>
            <small><?php echo h($trip['date']); ?> - <?php echo h($trip['distance']); ?></small>
            <span><?php echo h($trip['pickup']); ?></span>
        </div>
        <div class="captain-request-price">
            <strong>Rs <?php echo h(number_format((float) $trip['fare'])); ?></strong>
            <small>5% advance Rs <?php echo h(number_format((float) $trip['advance'])); ?></small>
        </div>
        <div class="captain-card-actions">
            <?php if ($mode === 'request' && $rideId > 0): ?>
                <form method="post" action="index.php?page=captain-ride-requests">
                    <input type="hidden" name="action" value="captain_ride_action">
                    <input type="hidden" name="ride_action" value="reject">
                    <input type="hidden" name="ride_id" value="<?php echo h($rideId); ?>">
                    <button class="btn btn-light" type="submit">Reject</button>
                </form>
            <?php else: ?>
                <a class="btn btn-light" href="index.php?page=captain-trip-history">History</a>
            <?php endif; ?>
            <a class="btn" href="<?php echo h($detailsUrl); ?>">View Details</a>
        </div>
    </article>
    <?php
}

function page_captain_trip_details(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['pending', 'captain_selected', 'accepted', 'ongoing']);
    $acceptUrl = 'index.php?page=captain-accept-trip' . ($trip['ride_id'] > 0 ? '&ride_id=' . $trip['ride_id'] : '');

    app_header('Trip Details', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Trip Details', 'captain-ride-requests'); ?>
        <article class="captain-detail-hero">
            <span class="captain-trip-thumb large"></span>
            <div>
                <strong><?php echo h($trip['title']); ?></strong>
                <small><?php echo h($trip['date']); ?> - <?php echo h($trip['time']); ?></small>
                <em><?php echo h($trip['route']); ?></em>
            </div>
        </article>

        <div class="captain-info-grid">
            <span><small>Pickup</small><strong><?php echo h($trip['pickup']); ?></strong></span>
            <span><small>Distance</small><strong><?php echo h($trip['distance']); ?></strong></span>
            <span><small>Travelers</small><strong><?php echo h($trip['travelers']); ?></strong></span>
            <span><small>Vehicle</small><strong><?php echo h($trip['vehicle']); ?></strong></span>
        </div>

        <div class="captain-breakdown-card">
            <h3>Trip Cost Breakdown</h3>
            <div><span>Captain service</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.61)); ?></strong></div>
            <div><span>Hotel stay / night</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.25)); ?></strong></div>
            <div><span>Local service and activities</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.09)); ?></strong></div>
            <div><span>TripNovaa fee</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.05)); ?></strong></div>
            <div class="total"><span>Total trip amount</span><strong>Rs <?php echo h(number_format($trip['fare'])); ?></strong></div>
        </div>

        <div class="captain-inclusion-row">
            <span>AC car</span><span>Fuel</span><span>Driver fee</span><span>Toll/Parking</span>
        </div>
        <a class="btn" href="<?php echo h($acceptUrl); ?>">Accept Trip</a>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_accept_trip(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['pending', 'captain_selected', 'accepted']);

    app_header('Accept Trip', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Accept Trip Request', 'captain-trip-details'); ?>
        <article class="captain-confetti-card">
            <span class="captain-group-avatar"></span>
            <strong><?php echo h($trip['title']); ?></strong>
            <small><?php echo h($trip['date']); ?> - <?php echo h($trip['travelers']); ?> travelers</small>
        </article>

        <div class="captain-pickup-card">
            <div><small>Pickup point</small><strong><?php echo h($trip['pickup']); ?></strong></div>
            <div><small>Pickup time</small><strong><?php echo h($trip['time']); ?></strong></div>
            <div><small>Total earnings</small><strong>Rs <?php echo h(number_format($trip['fare'])); ?></strong></div>
        </div>

        <div class="captain-note-card">Customer pays 5% advance to confirm this booking.</div>

        <?php if ($trip['ride_id'] > 0 && in_array($trip['raw_status'] ?? '', ['pending', 'captain_selected'], true)): ?>
            <form method="post" action="index.php?page=captain-accept-trip&ride_id=<?php echo h($trip['ride_id']); ?>">
                <input type="hidden" name="action" value="captain_ride_action">
                <input type="hidden" name="ride_action" value="accept">
                <input type="hidden" name="ride_id" value="<?php echo h($trip['ride_id']); ?>">
                <button class="btn" type="submit">Accept Trip</button>
            </form>
        <?php else: ?>
            <a class="btn" href="index.php?page=captain-advance-payment<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Continue</a>
        <?php endif; ?>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_advance_payment(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['accepted', 'ongoing', 'completed']);

    app_header('Advance Payment', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Advance Payment', 'captain-ride-requests'); ?>
        <div class="captain-breakdown-card advance-card">
            <h3>Secure your booking</h3>
            <div><span>Total trip amount</span><strong>Rs <?php echo h(number_format($trip['fare'])); ?></strong></div>
            <div><span>5% advance by customer</span><strong>Rs <?php echo h(number_format($trip['advance'])); ?></strong></div>
            <div><span>Your earnings after trip</span><strong>Rs <?php echo h(number_format($trip['remaining'])); ?></strong></div>
            <div><span>Platform fee</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.05)); ?></strong></div>
        </div>
        <div class="advance-timeline">
            <span class="done">Requested</span>
            <span class="done">Accepted</span>
            <span class="active">Advance</span>
            <span>Confirmed</span>
        </div>
        <div class="captain-note-card success">Waiting for customer to pay 5% advance. This booking will confirm after advance payment.</div>
        <a class="btn" href="index.php?page=captain-navigation<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Open Navigation</a>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_navigation(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['accepted', 'ongoing']);
    $ride = $trip['ride'] ?? [];

    app_header('Tracking', false, 'with-bottom-nav captain-mobile-screen captain-map-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('On My Way', 'captain-advance-payment'); ?>
        <div class="captain-map-card">
            <div
                id="captainMap"
                class="captain-leaflet-map"
                data-pickup-lat="<?php echo h(($ride['pickup_lat'] ?? null) ?: 34.0151); ?>"
                data-pickup-lng="<?php echo h(($ride['pickup_lng'] ?? null) ?: 71.5249); ?>"
                data-drop-lat="<?php echo h(($ride['drop_lat'] ?? null) ?: 33.6844); ?>"
                data-drop-lng="<?php echo h(($ride['drop_lng'] ?? null) ?: 73.0479); ?>"
            ></div>
            <b>12 min away</b>
        </div>
        <article class="captain-nav-card">
            <span class="captain-avatar-mini"><?php echo h(strtoupper(substr($trip['customer'], 0, 1))); ?></span>
            <div><strong><?php echo h($trip['title']); ?></strong><small><?php echo h($trip['pickup']); ?></small></div>
        </article>
        <div class="captain-nav-actions">
            <a href="tel:<?php echo h($trip['phone']); ?>">Call</a>
            <a href="index.php?page=captain-trip-chat<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Message</a>
            <a href="index.php?page=captain-trip-progress<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Navigate</a>
        </div>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_trip_progress(): void
{
    require_role('captain', 'captain-login');
    $ride = captain_trip_page_ride(['accepted', 'ongoing']);

    app_header('Trip In Progress', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Trip In Progress', 'captain-navigation'); ?>
        <?php if (!$ride): ?>
            <div class="card module-page-card">
                <span class="module-page-icon">TN</span>
                <h2 class="hero-title">No trip in progress</h2>
                <p class="lead">Accept a ride request first. After that, it will appear here and in Current Trips.</p>
                <div class="btn-row">
                    <a class="btn" href="index.php?page=captain-ride-requests">Open Requests</a>
                    <a class="btn btn-light" href="index.php?page=captain-current-trips">Current Trips</a>
                </div>
            </div>
        </section>
        <?php
        app_footer('captain', 'trips');
        return;
        ?>
        <?php endif; ?>
        <?php $trip = captain_trip_context($ride); ?>
        <div class="captain-scenic-card">
            <strong><?php echo h($trip['title']); ?></strong>
            <small>Day 1 of 10</small>
        </div>
        <div class="captain-progress-list">
            <span class="done">Booking verified</span>
            <span class="done">Pickup reached</span>
            <span class="active">Travel in progress</span>
            <span>Lunch and local call</span>
        </div>
        <div class="captain-passenger-row">
            <a href="index.php?page=captain-passenger-details<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Passenger Details</a>
            <a href="index.php?page=captain-trip-chat<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Trip Chat</a>
            <a href="index.php?page=captain-trip-earnings<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Earnings</a>
        </div>
        <?php if ($trip['ride_id'] > 0): ?>
            <form method="post" action="index.php?page=captain-trip-progress&ride_id=<?php echo h($trip['ride_id']); ?>">
                <input type="hidden" name="action" value="captain_ride_action">
                <input type="hidden" name="ride_action" value="complete">
                <input type="hidden" name="ride_id" value="<?php echo h($trip['ride_id']); ?>">
                <button class="btn" type="submit">End Day & Report</button>
            </form>
        <?php else: ?>
            <a class="btn" href="index.php?page=captain-trip-history">End Day & Report</a>
        <?php endif; ?>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_trip_earnings(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['accepted', 'ongoing', 'completed']);

    app_header('Trip Earnings', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Trip Earnings', 'captain-trip-progress'); ?>
        <div class="captain-breakdown-card earnings-breakdown">
            <h3>Earnings Breakdown</h3>
            <div><span>Trip value</span><strong>Rs <?php echo h(number_format($trip['fare'])); ?></strong></div>
            <div><span>Your earnings</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.61)); ?></strong></div>
            <div><span>Platform fee</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.05)); ?></strong></div>
            <div><span>Hotel commission</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.25)); ?></strong></div>
            <div><span>Local service & activities</span><strong>Rs <?php echo h(number_format($trip['fare'] * 0.09)); ?></strong></div>
            <div class="total"><span>5% advance to confirm</span><strong>Rs <?php echo h(number_format($trip['advance'])); ?></strong></div>
        </div>
        <div class="captain-note-card">You will receive Rs <?php echo h(number_format($trip['remaining'])); ?> after trip completion.</div>
    </section>
    <?php
    app_footer('captain', 'earnings');
}

function page_captain_passenger_details(): void
{
    require_role('captain', 'captain-login');
    $trip = captain_trip_page_context(['pending', 'captain_selected', 'accepted', 'ongoing']);
    $passengers = [
        [$trip['customer'], $trip['phone']],
        ['Suresh Kumar', '+91 98765 12001'],
        ['Maya Reddy', '+91 98765 12002'],
        ['Arjun K.', '+91 98765 12003'],
    ];

    app_header('Passenger Details', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Passenger Details', 'captain-trip-progress'); ?>
        <article class="captain-passenger-lead">
            <strong><?php echo h($trip['title']); ?></strong>
            <small>Lead Traveler</small>
            <span><?php echo h($trip['customer']); ?></span>
        </article>
        <div class="captain-contact-actions">
            <a href="tel:<?php echo h($trip['phone']); ?>">Call</a>
            <a href="index.php?page=captain-trip-chat<?php echo $trip['ride_id'] > 0 ? '&ride_id=' . h($trip['ride_id']) : ''; ?>">Message</a>
        </div>
        <div class="captain-list-card">
            <h3>Other Travelers</h3>
            <?php foreach ($passengers as $index => $passenger): ?>
                <a href="tel:<?php echo h($passenger[1]); ?>">
                    <span><?php echo h($index + 1); ?></span>
                    <strong><?php echo h($passenger[0]); ?></strong>
                    <small><?php echo h($passenger[1]); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="captain-note-card">Special requests: Vegetarian food, extra water, and first-aid kit.</div>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_trip_chat(): void
{
    require_role('captain', 'captain-login');
    $ride = fetch_captain_chat_ride((int) ($_GET['ride_id'] ?? 0));
    $trip = $ride ? captain_trip_context($ride) : null;
    $rideId = (int) ($trip['ride_id'] ?? 0);
    $canMessage = $ride && $rideId > 0 && (int) ($ride['user_id'] ?? 0) > 0;
    $messages = $canMessage
        ? fetch_ride_messages($rideId, (int) $ride['user_id'], (int) $ride['captain_id'])
        : [];

    app_header('Trip Chat', false, 'with-bottom-nav captain-mobile-screen captain-chat-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Trip Chat', 'captain-dashboard'); ?>
        <div class="captain-chat-title">
            <strong><?php echo h($trip['customer'] ?? 'No active chat'); ?></strong>
            <small><?php echo h($trip['title'] ?? 'Select a ride first'); ?> - <?php echo $canMessage ? 'Local chat active' : 'No active assigned ride'; ?></small>
        </div>
        <div class="captain-chat-thread" data-chat-thread>
            <?php render_ride_messages($messages, 'captain'); ?>
        </div>
        <form class="chat-compose captain-compose <?php echo $canMessage ? '' : 'disabled'; ?>" method="post" action="index.php?page=captain-trip-chat">
            <input type="hidden" name="action" value="send_ride_message">
            <input type="hidden" name="ride_id" value="<?php echo h($rideId); ?>">
            <input name="message" type="text" maxlength="1000" placeholder="<?php echo $canMessage ? 'Type a message...' : 'No active ride chat'; ?>" aria-label="Type a message" <?php echo $canMessage ? 'required' : 'disabled'; ?>>
            <button type="submit" aria-label="Send" <?php echo $canMessage ? '' : 'disabled'; ?>></button>
        </form>
    </section>
    <?php
    app_footer('captain', 'messages');
}

function page_captain_trip_history(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $accepted = fetch_captain_rides($captainId, 'accepted');
    $completed = fetch_captain_rides($captainId, 'completed');
    $pending = fetch_captain_rides($captainId, 'pending');
    $rows = array_merge($accepted, $pending, $completed);

    app_header('Trip History', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('My Trips', 'captain-dashboard'); ?>
        <div class="booking-filter-tabs captain-history-tabs">
            <a class="active" href="index.php?page=captain-trip-history">All</a>
            <a href="index.php?page=captain-ride-requests">Requested</a>
            <a href="index.php?page=captain-current-trips">Ongoing</a>
            <a href="index.php?page=captain-completed-trips">Completed</a>
        </div>
        <div class="mobile-result-list">
            <?php if (!$rows): ?>
                <article class="captain-empty-state">
                    <span class="captain-empty-icon"></span>
                    <strong>No trips yet</strong>
                    <small>Accepted and completed trips will appear here when you start taking rides.</small>
                    <a class="btn btn-light" href="index.php?page=captain-ride-requests">Open Requests</a>
                </article>
            <?php else: ?>
                <?php foreach ($rows as $ride): ?>
                    <?php $trip = captain_trip_context($ride); ?>
                    <?php render_captain_request_card($trip, 'history'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="btn" href="index.php?page=captain-ride-requests">View All Trips</a>
    </section>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_wallet(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $earnings = captain_total_earnings($captainId);
    $available = max(4560, $earnings * 0.25);

    app_header('Wallet', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Wallet', 'captain-dashboard'); ?>
        <div class="captain-wallet-hero">
            <small>Available Balance</small>
            <strong>Rs <?php echo h(number_format($available)); ?></strong>
        </div>
        <div class="captain-breakdown-card">
            <h3>Transactions</h3>
            <div><span>Trip earnings</span><strong>+ Rs 4,560</strong></div>
            <div><span>Advance received</span><strong>+ Rs 990</strong></div>
            <div><span>Platform fee</span><strong>- Rs 450</strong></div>
            <div><span>Payout to bank</span><strong>- Rs 1,000</strong></div>
        </div>
        <a class="btn" href="index.php?page=captain-earnings">Withdraw to Bank</a>
    </section>
    <?php
    app_footer('captain', 'earnings');
}

function page_captain_profile(): void
{
    require_role('captain', 'captain-login');
    $profile = current_captain_profile();

    app_header('Profile', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Profile', 'captain-dashboard'); ?>
        <div class="profile-identity captain-profile-identity">
            <span class="profile-photo"><?php echo h(strtoupper(substr((string) $profile['full_name'], 0, 1))); ?></span>
            <strong><?php echo h($profile['full_name']); ?></strong>
            <small><?php echo h($profile['city']); ?> - <?php echo h($profile['vehicle_number']); ?></small>
            <em>Verified Captain - Rating <?php echo h($profile['rating']); ?></em>
        </div>
        <nav class="profile-menu-list captain-profile-menu" aria-label="Captain profile">
            <a href="index.php?page=captain-profile"><span class="profile-menu-icon profile-info"></span><span><strong>Personal Information</strong><small><?php echo h($profile['email']); ?></small></span></a>
            <a href="index.php?page=captain-profile"><span class="profile-menu-icon profile-review"></span><span><strong>Vehicle Details</strong><small><?php echo h(ride_type_label((string) $profile['vehicle_type']) . ' - ' . $profile['vehicle_number']); ?></small></span></a>
            <a href="index.php?page=captain-profile"><span class="profile-menu-icon profile-address"></span><span><strong>Documents</strong><small><?php echo h($profile['license_number']); ?> - Verified</small></span></a>
            <a href="index.php?page=captain-wallet"><span class="profile-menu-icon profile-payment"></span><span><strong>Bank Details</strong><small>Wallet and payout settings</small></span></a>
            <a href="index.php?page=captain-earnings-analytics"><span class="profile-menu-icon profile-bell"></span><span><strong>Analytics & Reports</strong><small>Monthly performance</small></span></a>
            <a href="index.php?page=captain-profile"><span class="profile-menu-icon profile-settings"></span><span><strong>Notification Settings</strong><small>Trip and payment alerts</small></span></a>
            <a href="index.php?page=logout" class="logout-row"><span class="profile-menu-icon profile-logout"></span><span><strong>Logout</strong><small>Securely end captain session</small></span></a>
        </nav>
    </section>
    <?php
    app_footer('captain', 'profile');
}

function page_captain_earnings_analytics(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $earnings = captain_total_earnings($captainId);
    $total = max(28450, $earnings);

    app_header('Earnings Analytics', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Earnings Analytics', 'captain-earnings'); ?>
        <div class="captain-wallet-hero analytics-hero">
            <small>Total Earnings</small>
            <strong>Rs <?php echo h(number_format($total)); ?></strong>
            <span>Up 18% vs last month</span>
        </div>
        <div class="captain-info-grid">
            <span><small>Trips completed</small><strong>18</strong></span>
            <span><small>Avg earning/trip</small><strong>Rs 1,580</strong></span>
        </div>
        <div class="captain-chart-card">
            <span style="height: 32%;"></span>
            <span style="height: 46%;"></span>
            <span style="height: 38%;"></span>
            <span style="height: 64%;"></span>
            <span style="height: 54%;"></span>
            <span style="height: 82%;"></span>
            <span style="height: 70%;"></span>
        </div>
        <a class="btn" href="index.php?page=captain-trip-earnings">View Detailed Report</a>
    </section>
    <?php
    app_footer('captain', 'earnings');
}

function captain_reward_milestones(): array
{
    return [
        ['level' => '1M', 'tone' => 'purple', 'items' => ['Bottle', 'T-shirt', 'Keychain', 'Stickers', 'Face Mask']],
        ['level' => '10M', 'tone' => 'indigo', 'items' => ['Phone Holder', 'Riding Gloves', 'Raincoat', 'Cap', 'Sunglasses']],
        ['level' => '20M', 'tone' => 'blue', 'items' => ['Power Bank', 'Fast Charger', 'USB Cable', 'Earphones', 'Bike Charger']],
        ['level' => '30M', 'tone' => 'cyan', 'items' => ['Bluetooth Headset', 'Neckband', 'Flashlight', 'Bike Cleaning Kit', 'Helmet Cleaner']],
        ['level' => '40M', 'tone' => 'green', 'items' => ['Smart Bulb', 'Extension Board', 'Memory Card', 'USB Cover', 'Seat Cover']],
        ['level' => '50M', 'tone' => 'mint', 'items' => ['Smart Band', 'Wireless Earbuds', 'Bluetooth Speaker', 'Action Camera', 'Travel Pillow']],
        ['level' => '60M', 'tone' => 'yellow', 'items' => ['Grocery Voucher', 'Fuel Voucher', 'Mobile Recharge', 'Food Coupon', 'OTT Subscription']],
        ['level' => '70M', 'tone' => 'amber', 'items' => ['Health Checkup', 'Insurance Discount', 'Gym Discount', 'Hotel Voucher', 'Travel Coupon']],
        ['level' => '80M', 'tone' => 'orange', 'items' => ['Tablet', 'Smartphone', 'Laptop Bag', 'Power Backpack', 'Premium Helmet']],
        ['level' => '90M', 'tone' => 'red', 'items' => ['Mid-Range Smartphone', 'Tablet with SIM', 'Action Camera', 'Noise Cancelling Earbuds', 'Electric Scooter']],
        ['level' => '100M', 'tone' => 'rose', 'items' => ['Premium Smartphone', 'Laptop', 'Down Payment Support', 'Motorcycle Upgrade', 'International Trip']],
    ];
}

function page_captain_rewards(): void
{
    require_role('captain', 'captain-login');
    $milestones = captain_reward_milestones();
    $completedDownloads = 23.4;
    $progressPercent = min(100, ($completedDownloads / 100) * 100);

    app_header('Captain Rewards', false, 'with-bottom-nav captain-mobile-screen captain-rewards-screen');
    ?>
    <section class="captain-mobile captain-rewards-page">
        <div class="captain-rewards-top">
            <a class="back-link" href="index.php?page=captain-dashboard" aria-label="Back"></a>
            <strong>TripNova</strong>
            <a class="trip-home-alert" href="index.php?page=captain-rewards" aria-label="Rewards"><span><?php echo h(count($milestones)); ?></span></a>
        </div>

        <article class="captain-reward-hero">
            <span>
                <small>Captain Rewards</small>
                <strong>More downloads, bigger rewards.</strong>
            </span>
            <b></b>
        </article>

        <article class="reward-journey-card">
            <h3>Our Journey</h3>
            <div class="reward-progress-head">
                <span><strong><?php echo h(number_format($completedDownloads, 1)); ?>M</strong><small>Downloads Completed</small></span>
                <b><?php echo h(number_format($completedDownloads, 1)); ?>M / 100M</b>
            </div>
            <div class="reward-progress-bar"><span style="width: <?php echo h(number_format($progressPercent, 2)); ?>%;"></span></div>
            <a href="index.php?page=captain-rewards">View Milestones</a>
        </article>

        <div class="mobile-section-head"><h3>Milestone Rewards</h3></div>
        <div class="reward-milestone-list">
            <?php foreach ($milestones as $milestone): ?>
                <article class="reward-milestone-card">
                    <span class="reward-level reward-<?php echo h($milestone['tone']); ?>">
                        <strong><?php echo h($milestone['level']); ?></strong>
                        <small>Downloads</small>
                    </span>
                    <div class="reward-items">
                        <?php foreach ($milestone['items'] as $itemIndex => $item): ?>
                            <span class="reward-item reward-item-<?php echo h(($itemIndex % 5) + 1); ?>" title="<?php echo h($item); ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <small class="reward-count">5 items</small>
                    <a href="index.php?page=captain-rewards">View</a>
                </article>
            <?php endforeach; ?>
        </div>

        <article class="reward-how-card">
            <div>
                <h3>How to Unlock Rewards?</h3>
                <span>Keep delivering great service</span>
                <span>Help TripNovaa reach more people</span>
                <span>Unlock exciting rewards at each milestone</span>
            </div>
            <b></b>
        </article>
    </section>
    <?php
    app_footer('captain', 'rewards');
}

function page_captain_dashboard(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $pending = captain_ride_count('pending', $captainId);
    $accepted = captain_ride_count('accepted', $captainId);
    $completed = captain_ride_count('completed', $captainId);
    $earnings = captain_total_earnings($captainId);
    $feedbackStats = captain_feedback_average($captainId);
    $averageRating = $feedbackStats['count'] > 0 ? number_format((float) $feedbackStats['average'], 1) : 'New';
    $captainProfileTiles = [
        ['My Trips Posted', 'my-trips-posted', 'dash-ico-bag'],
        ['Driver Offers', 'driver-offers', 'dash-ico-car'],
        ['Offers', 'captain-offers', 'dash-ico-offer'],
        ['Booked Trips', 'captain-current-trips', 'dash-ico-calendar'],
        ['Saved Trips', 'saved-trips', 'dash-ico-heart'],
        ['Profile', 'captain-dashboard', 'dash-ico-profile'],
    ];

    $profile = current_captain_profile();
    $dashboardRide = fetch_latest_captain_ride(['pending', 'captain_selected', 'accepted', 'ongoing']);
    $trip = $dashboardRide ? captain_trip_context($dashboardRide) : null;
    $captainName = trim((string) ($profile['full_name'] ?? 'Captain')) ?: 'Captain';
    $expertCity = trim((string) ($profile['city'] ?? ''));
    $expertLabel = ($expertCity !== '' ? $expertCity : 'TripNovaa') . ' Tour Expert';

    app_header('Captain Dashboard', false, 'with-bottom-nav captain-mobile-screen captain-dashboard-mobile');
    ?>
    <section class="captain-mobile">
        <div class="captain-dashboard-top">
            <a class="captain-brand" href="index.php?page=captain-dashboard">
                <strong><span>Trip</span>Novaa</strong>
                <small>Work &bull; Earn &bull; Grow with TripNovaa</small>
            </a>
            <a class="trip-home-alert" href="index.php?page=captain-ride-requests" aria-label="Trip requests">
                <?php if ($pending > 0): ?>
                    <span><?php echo h($pending); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <article class="captain-home-card">
            <span class="profile-photo"><?php echo h(strtoupper(substr($captainName, 0, 1))); ?></span>
            <div>
                <strong>Good Morning, <?php echo h($captainName); ?></strong>
                <small><?php echo h($expertLabel); ?></small>
                <em>You are online</em>
            </div>
            <span class="captain-toggle active"></span>
        </article>

        <div class="captain-earning-card">
            <span>Today's Earnings</span>
            <strong>Rs <?php echo h(number_format(max(6240, $earnings), 0)); ?></strong>
            <small>After commission - <?php echo h($completed); ?> completed</small>
        </div>

        <nav class="captain-action-grid" aria-label="Captain quick actions">
            <a href="index.php?page=captain-ride-requests"><span class="dash-icon captain-quick-icon captain-quick-request"></span><strong>Trip Requests</strong></a>
            <a href="index.php?page=captain-trip-history"><span class="dash-icon captain-quick-icon captain-quick-trips"></span><strong>My Trips</strong></a>
            <a href="index.php?page=trip-messages<?php echo ($trip && $trip['ride_id'] > 0) ? '&ride_id=' . h($trip['ride_id']) : ''; ?>"><span class="dash-icon captain-quick-icon captain-quick-message"></span><strong>Messages</strong></a>
            <a href="index.php?page=captain-earnings"><span class="dash-icon captain-quick-icon captain-quick-earning"></span><strong>Earning</strong></a>
            <a href="index.php?page=captain-wallet"><span class="dash-icon captain-quick-icon captain-quick-wallet"></span><strong>Wallet</strong></a>
            <a href="index.php?page=captain-rewards"><span class="dash-icon captain-quick-icon captain-quick-reward"></span><strong>Rewards</strong></a>
            <a href="index.php?page=captain-profile"><span class="dash-icon captain-quick-icon captain-quick-profile"></span><strong>Profile</strong></a>
        </nav>

        <div class="mobile-section-head"><h3>Upcoming Pickups</h3></div>
        <?php if ($trip): ?>
            <?php render_captain_request_card($trip, 'history'); ?>
        <?php else: ?>
            <article class="captain-empty-state">
                <span class="captain-empty-icon"></span>
                <strong>No upcoming pickups</strong>
                <small>New accepted rides will appear here when customers assign trips to you.</small>
                <a class="btn btn-light" href="index.php?page=captain-ride-requests">Trip Requests</a>
            </article>
        <?php endif; ?>

        <div class="captain-info-grid">
            <span><small>Pending</small><strong><?php echo h($pending); ?></strong></span>
            <span><small>Accepted</small><strong><?php echo h($accepted); ?></strong></span>
            <span><small>Rating</small><strong><?php echo h($averageRating); ?></strong></span>
            <span><small>Earnings</small><strong>Rs <?php echo h(number_format($earnings, 0)); ?></strong></span>
        </div>
    </section>
    <?php
    app_footer('captain', 'home');
    return;
    ?>
    app_header('Captain Dashboard', true, 'with-bottom-nav');
    ?>
    <section class="dashboard-hero">
        <span class="dashboard-kicker">🚗 TripNovaa captain</span>
        <h2>Welcome, <?php echo h(current_user_name()); ?></h2>
        <p>Manage requests, active trips, completed rides, earnings, and offers from one mobile console.</p>
        <div class="mini-metrics">
            <div class="mini-metric"><strong><?php echo h($pending); ?></strong><span>Pending</span></div>
            <div class="mini-metric"><strong><?php echo h($accepted); ?></strong><span>Accepted</span></div>
            <div class="mini-metric"><strong>Rs <?php echo h(number_format($earnings, 0)); ?></strong><span>Earnings</span></div>
            <div class="mini-metric"><strong><?php echo h($averageRating); ?></strong><span>Rating</span></div>
        </div>
    </section>

    <div class="section-title"><h2>Captain profile</h2><a class="tiny-link" href="index.php?page=post-new-trip">Post Trip</a></div>
    <nav class="driver-home-grid" aria-label="Captain trip profile tools">
        <?php foreach ($captainProfileTiles as $tile): ?>
            <a href="index.php?page=<?php echo h($tile[1]); ?>">
                <span class="dash-icon <?php echo h($tile[2]); ?>"></span>
                <strong><?php echo h($tile[0]); ?></strong>
            </a>
        <?php endforeach; ?>
    </nav>
    <article class="driver-discount-banner">
        <span>
            <strong>Up to 20% OFF</strong>
            <small>on local sightseeing trips you post.</small>
        </span>
        <a href="index.php?page=driver-offers">View Offers</a>
    </article>

    <div class="section-title"><h2>Stats</h2><span class="badge">Live</span></div>
    <div class="stat-grid">
        <div class="stat-card"><strong><?php echo h($pending); ?></strong><span>Pending ride requests</span></div>
        <div class="stat-card"><strong><?php echo h($accepted); ?></strong><span>Accepted trips</span></div>
        <div class="stat-card"><strong><?php echo h($completed); ?></strong><span>Completed trips</span></div>
        <div class="stat-card"><strong>Rs <?php echo h(number_format($earnings, 0)); ?></strong><span>Total earnings</span></div>
        <div class="stat-card"><strong><?php echo h($averageRating); ?></strong><span>Average ride feedback (<?php echo h($feedbackStats['count']); ?>)</span></div>
    </div>

    <div class="section-title"><h2>Captain tools</h2><a class="tiny-link" href="index.php?page=captain-ride-requests">Requests</a></div>
    <section class="service-grid">
        <a class="service-card" href="index.php?page=captain-ride-requests"><span class="service-icon">📥</span><span><strong>Ride Requests</strong><span>Accept or reject pending customer rides.</span></span></a>
        <a class="service-card" href="index.php?page=captain-current-trips"><span class="service-icon">🧭</span><span><strong>Current Trips</strong><span>View accepted rides and mark completed.</span></span></a>
        <a class="service-card" href="index.php?page=captain-completed-trips"><span class="service-icon">✅</span><span><strong>Completed Trips</strong><span>Review completed ride history.</span></span></a>
        <a class="service-card" href="index.php?page=captain-earnings"><span class="service-icon">💵</span><span><strong>Earnings</strong><span>Total fare from completed rides.</span></span></a>
        <a class="service-card" href="index.php?page=captain-offers"><span class="service-icon">🎁</span><span><strong>Offers</strong><span>View active captain offers.</span></span></a>
        <a class="service-card logout-card" href="index.php?page=logout"><span class="service-icon">↗</span><span><strong>Logout</strong><span>Securely end your captain session.</span></span></a>
    </section>
    <?php
    app_footer('captain', 'home');
}

function page_captain_ride_requests(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $rides = fetch_captain_rides($captainId, 'pending');
    $cards = array_map('captain_trip_context', $rides);

    app_header('Trip Requests', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Trip Requests', 'captain-dashboard'); ?>
        <div class="booking-filter-tabs captain-history-tabs">
            <a class="active" href="index.php?page=captain-ride-requests">New (<?php echo h(count($cards)); ?>)</a>
            <a href="index.php?page=captain-trip-history">Upcoming</a>
            <a href="index.php?page=captain-current-trips">Accepted</a>
        </div>
        <div class="mobile-result-list">
            <?php if (!$cards): ?>
                <article class="captain-empty-state">
                    <span class="captain-empty-icon"></span>
                    <strong>No new trip requests</strong>
                    <small>When a user selects you as captain, their request will appear here.</small>
                    <a class="btn btn-light" href="index.php?page=captain-dashboard">Dashboard</a>
                </article>
            <?php else: ?>
                <?php foreach ($cards as $trip): ?>
                    <?php render_captain_request_card($trip, 'request'); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
    <?php
    app_footer('captain', 'trips');
    return;
    ?>
    app_header('Ride Requests', true, 'with-bottom-nav');
    ?>
    <h2 class="hero-title">Ride requests</h2>
    <p class="lead">Pending rides assigned to you. Accept to move into current trips or reject the request.</p>
    <?php if (!$rides): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">📭</span>
            <h2 class="hero-title">No pending requests</h2>
            <p class="lead">New customer ride requests assigned to you will appear here.</p>
            <a class="btn" href="index.php?page=captain-dashboard">Dashboard</a>
        </div>
    <?php else: ?>
        <?php foreach ($rides as $ride): ?>
            <?php render_captain_trip_card($ride, 'requests'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    app_footer('captain', 'requests');
}

function page_captain_current_trips(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $rides = array_merge(
        fetch_captain_rides($captainId, 'accepted'),
        fetch_captain_rides($captainId, 'ongoing')
    );

    app_header('Current Trips', true, 'with-bottom-nav');
    ?>
    <h2 class="hero-title">Current trips</h2>
    <p class="lead">Accepted and ongoing rides stay here until you mark them completed.</p>
    <?php if (!$rides): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">🧭</span>
            <h2 class="hero-title">No current trips</h2>
            <p class="lead">Accepted rides will appear here after you accept a request.</p>
            <a class="btn" href="index.php?page=captain-ride-requests">Open Requests</a>
        </div>
    <?php else: ?>
        <?php foreach ($rides as $ride): ?>
            <?php render_captain_trip_card($ride, 'current'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_completed_trips(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $rides = fetch_captain_rides($captainId, 'completed');

    app_header('Completed Trips', true, 'with-bottom-nav');
    ?>
    <h2 class="hero-title">Completed trips</h2>
    <p class="lead">A clean record of rides you finished successfully.</p>
    <?php if (!$rides): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">✅</span>
            <h2 class="hero-title">No completed trips yet</h2>
            <p class="lead">Complete an accepted ride to start building your history.</p>
            <a class="btn" href="index.php?page=captain-current-trips">Current Trips</a>
        </div>
    <?php else: ?>
        <?php foreach ($rides as $ride): ?>
            <?php render_captain_trip_card($ride, 'completed'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    app_footer('captain', 'trips');
}

function page_captain_earnings(): void
{
    require_role('captain', 'captain-login');
    $captainId = current_captain_id() ?? 0;
    $rides = fetch_captain_rides($captainId, 'completed');
    $earnings = captain_total_earnings($captainId);
    $tripCount = max(1, count($rides));

    app_header('Trip Earnings', false, 'with-bottom-nav captain-mobile-screen');
    ?>
    <section class="captain-mobile">
        <?php captain_mobile_head('Earnings', 'captain-dashboard'); ?>
        <div class="captain-wallet-hero earnings-hero">
            <small>Total Earnings</small>
            <strong>Rs <?php echo h(number_format(max(28450, $earnings), 0)); ?></strong>
            <span>This month</span>
        </div>
        <div class="captain-info-grid">
            <span><small>Trips</small><strong><?php echo h(max(18, count($rides))); ?></strong></span>
            <span><small>Average</small><strong>Rs <?php echo h(number_format(max(1580, $earnings / $tripCount), 0)); ?></strong></span>
        </div>
        <div class="captain-breakdown-card">
            <h3>Earnings Breakdown</h3>
            <div><span>Trip earnings</span><strong>Rs <?php echo h(number_format(max(19000, $earnings * 0.67), 0)); ?></strong></div>
            <div><span>Confirmed advances</span><strong>Rs 2,100</strong></div>
            <div><span>TripNovaa platform fee</span><strong>- Rs 1,000</strong></div>
            <div><span>Local services & activities</span><strong>Rs 4,600</strong></div>
            <div class="total"><span>Total</span><strong>Rs <?php echo h(number_format(max(28450, $earnings), 0)); ?></strong></div>
        </div>
        <div class="captain-action-row">
            <a class="btn btn-light" href="index.php?page=captain-wallet">Wallet</a>
            <a class="btn" href="index.php?page=captain-earnings-analytics">Analytics</a>
        </div>
    </section>
    <?php
    app_footer('captain', 'earnings');
    return;
    ?>
    app_header('Captain Earnings', true, 'with-bottom-nav');
    ?>
    <section class="dashboard-hero">
        <span class="dashboard-kicker">💵 Earnings</span>
        <h2>Rs <?php echo h(number_format($earnings, 0)); ?></h2>
        <p>Total fare from completed rides assigned to your captain account.</p>
        <div class="mini-metrics">
            <div class="mini-metric"><strong><?php echo h(count($rides)); ?></strong><span>Trips</span></div>
            <div class="mini-metric"><strong>Rs <?php echo h(number_format(count($rides) ? $earnings / count($rides) : 0, 0)); ?></strong><span>Average</span></div>
            <div class="mini-metric"><strong>PKR</strong><span>Currency</span></div>
        </div>
    </section>

    <div class="section-title"><h2>Completed earning rides</h2><span class="badge"><?php echo h(count($rides)); ?> trips</span></div>
    <?php if (!$rides): ?>
        <div class="card"><p class="lead">No completed rides yet, so earnings are Rs 0.</p></div>
    <?php else: ?>
        <?php foreach ($rides as $ride): ?>
            <?php render_captain_trip_card($ride, 'completed'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    app_footer('captain', 'earnings');
}

function page_captain_offers(): void
{
    require_role('captain', 'captain-login');
    $offers = [];
    $pdo = db();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM offers WHERE status = "active" ORDER BY valid_to ASC, id DESC');
            $stmt->execute();
            $offers = $stmt->fetchAll();
        } catch (Throwable $e) {
            set_flash('danger', 'Could not load offers: ' . $e->getMessage());
        }
    }

    app_header('Captain Offers', true, 'with-bottom-nav');
    ?>
    <h2 class="hero-title">Captain offers</h2>
    <p class="lead">Active TripNovaa offers and promos available in the demo system.</p>
    <?php if (!$offers): ?>
        <div class="card module-page-card">
            <span class="module-page-icon">🎁</span>
            <h2 class="hero-title">No active offers</h2>
            <p class="lead">Admin-created offers will appear here.</p>
        </div>
    <?php else: ?>
        <div class="placeholder-list">
            <?php foreach ($offers as $offer): ?>
                <div class="placeholder-row">
                    <span>
                        <strong><?php echo h($offer['title']); ?></strong>
                        <span><?php echo h($offer['code']); ?> · <?php echo h($offer['discount_type']); ?> <?php echo h($offer['discount_value']); ?> · valid until <?php echo h($offer['valid_to']); ?></span>
                    </span>
                    <span class="badge">Active</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    app_footer('captain', 'earnings');
}

function page_captain_offers_ui(): void
{
    require_role('captain', 'captain-login');

    $activeCategory = strtolower(trim((string) ($_GET['category'] ?? 'all')));
    $offerTabs = [
        'all' => 'All Offers',
        'discounts' => 'Discounts',
        'cashback' => 'Cashback',
        'seasonal' => 'Seasonal',
    ];

    if (!isset($offerTabs[$activeCategory])) {
        $activeCategory = 'all';
    }

    $promoCards = [
        ['category' => 'discounts', 'class' => 'promo-blue', 'title' => 'Flat 10% OFF', 'subtitle' => 'on Outstation Trips', 'code' => 'TRIP10', 'valid' => 'Valid till 31 May 2024', 'mark' => '%'],
        ['category' => 'discounts', 'class' => 'promo-green', 'title' => 'Rs 500 OFF', 'subtitle' => 'on Local Sightseeing', 'code' => 'LOCAL500', 'valid' => 'Valid till 25 May 2024', 'mark' => 'TN'],
        ['category' => 'cashback', 'class' => 'promo-orange', 'title' => 'Up to 15% Cashback', 'subtitle' => 'on Round Trip Bookings', 'code' => 'CASH15', 'valid' => 'Valid till 31 May 2024', 'mark' => 'Rs'],
        ['category' => 'seasonal', 'class' => 'promo-purple', 'title' => 'Refer & Earn', 'subtitle' => 'Invite friends and earn TripNovaa wallet credits', 'code' => 'INVITE', 'valid' => 'Invite Now', 'mark' => 'Gift'],
    ];
    $visiblePromos = $activeCategory === 'all'
        ? $promoCards
        : array_values(array_filter($promoCards, static fn(array $card): bool => $card['category'] === $activeCategory));

    $bankOffers = [
        ['label' => 'SBI Cards', 'value' => '10% OFF', 'category' => 'discounts'],
        ['label' => 'HDFC Bank', 'value' => '10% OFF', 'category' => 'discounts'],
        ['label' => 'ICICI Bank', 'value' => '10% OFF', 'category' => 'cashback'],
        ['label' => 'Paytm', 'value' => 'Rs 100 OFF', 'category' => 'cashback'],
    ];

    app_header('Offers', true, 'with-bottom-nav driver-offers-screen');
    ?>
    <div class="driver-screen-head">
        <h2>Offers</h2>
        <a href="index.php?page=captain-dashboard">Account</a>
    </div>
    <div class="driver-tabs">
        <?php foreach ($offerTabs as $categoryKey => $label): ?>
            <a class="<?php echo h($activeCategory === $categoryKey ? 'active' : ''); ?>" href="index.php?page=captain-offers&category=<?php echo h($categoryKey); ?>"><?php echo h($label); ?></a>
        <?php endforeach; ?>
    </div>

    <section class="promo-card-stack">
        <?php foreach ($visiblePromos as $card): ?>
            <a class="promo-card <?php echo h($card['class']); ?>" href="index.php?page=captain-offers&category=<?php echo h($card['category']); ?>">
                <span>
                    <strong><?php echo h($card['title']); ?></strong>
                    <small><?php echo h($card['subtitle']); ?></small>
                    <em>Use Code: <?php echo h($card['code']); ?></em>
                    <em><?php echo h($card['valid']); ?></em>
                </span>
                <b><?php echo h($card['mark']); ?></b>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="section-title">
        <h2>Bank & Wallet Offers</h2>
        <a class="tiny-link" href="index.php?page=captain-offers&category=all">View All</a>
    </div>
    <section class="bank-wallet-grid">
        <?php foreach ($bankOffers as $bankOffer): ?>
            <?php if ($activeCategory === 'all' || $activeCategory === $bankOffer['category']): ?>
                <a href="index.php?page=captain-offers&category=<?php echo h($bankOffer['category']); ?>">
                    <strong><?php echo h(substr($bankOffer['label'], 0, 1)); ?></strong>
                    <span><?php echo h($bankOffer['label']); ?></span>
                    <small><?php echo h($bankOffer['value']); ?></small>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
    <?php
    app_footer('captain', 'profile');
}

function admin_table_configs(): array
{
    return [
        'users' => [
            'title' => 'Users',
            'page' => 'admin-users',
            'table' => 'users',
            'sql' => 'SELECT id, full_name, email, phone, city, reward_points, otp_verified, status, created_at FROM users ORDER BY created_at DESC, id DESC',
            'columns' => ['id' => 'ID', 'full_name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'city' => 'City', 'reward_points' => 'Points', 'otp_verified' => 'OTP', 'status' => 'Status', 'created_at' => 'Joined'],
        ],
        'captains' => [
            'title' => 'Captains',
            'page' => 'admin-captains',
            'table' => 'captains',
            'action' => 'captain_status',
            'sql' => 'SELECT id, full_name, email, phone, city, vehicle_type, vehicle_number, license_number, id_card_type, id_card_number, availability_status, account_status, rating, created_at FROM captains ORDER BY created_at DESC, id DESC',
            'columns' => ['id' => 'ID', 'full_name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'city' => 'City', 'vehicle_type' => 'Vehicle', 'vehicle_number' => 'Number', 'license_number' => 'Driving Licence', 'id_card_type' => 'ID Type', 'id_card_number' => 'Aadhar/PAN', 'availability_status' => 'Available', 'account_status' => 'Status', 'rating' => 'Rating'],
        ],
        'rides' => [
            'title' => 'Rides',
            'page' => 'admin-rides',
            'table' => 'rides',
            'action' => 'ride_status',
            'sql' => 'SELECT r.id, u.full_name AS user_name, c.full_name AS captain_name, r.pickup_location, r.drop_location, r.ride_type, r.travel_date, r.travel_time, r.fare, r.payment_status, r.status, r.created_at FROM rides r LEFT JOIN users u ON u.id = r.user_id LEFT JOIN captains c ON c.id = r.captain_id ORDER BY r.created_at DESC, r.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'captain_name' => 'Captain', 'pickup_location' => 'Pickup', 'drop_location' => 'Drop', 'ride_type' => 'Type', 'travel_date' => 'Date', 'fare' => 'Fare', 'payment_status' => 'Payment', 'status' => 'Status'],
        ],
        'hotel-bookings' => [
            'title' => 'Hotel Bookings',
            'page' => 'admin-hotel-bookings',
            'table' => 'hotel_bookings',
            'sql' => 'SELECT h.id, u.full_name AS user_name, h.hotel_name, h.city, h.check_in_date, h.check_out_date, h.guests, h.room_type, h.amount, h.status, h.created_at FROM hotel_bookings h LEFT JOIN users u ON u.id = h.user_id ORDER BY h.created_at DESC, h.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'hotel_name' => 'Hotel', 'city' => 'City', 'check_in_date' => 'Check In', 'check_out_date' => 'Check Out', 'guests' => 'Guests', 'room_type' => 'Room', 'amount' => 'Amount', 'status' => 'Status'],
        ],
        'train-bookings' => [
            'title' => 'Train Bookings',
            'page' => 'admin-train-bookings',
            'table' => 'train_bookings',
            'sql' => 'SELECT t.id, u.full_name AS user_name, t.train_name, t.train_number, t.origin, t.destination, t.travel_date, t.seat_class, t.passengers, t.amount, t.status, t.created_at FROM train_bookings t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC, t.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'train_name' => 'Train', 'train_number' => 'No', 'origin' => 'From', 'destination' => 'To', 'travel_date' => 'Date', 'seat_class' => 'Seat', 'passengers' => 'People', 'amount' => 'Amount', 'status' => 'Status'],
        ],
        'bus-bookings' => [
            'title' => 'Bus Bookings',
            'page' => 'admin-bus-bookings',
            'table' => 'bus_bookings',
            'sql' => 'SELECT b.id, u.full_name AS user_name, b.bus_name, b.bus_number, b.origin, b.destination, b.travel_date, b.bus_type, b.seat_no, b.seats, b.amount, b.status, b.created_at FROM bus_bookings b LEFT JOIN users u ON u.id = b.user_id ORDER BY b.created_at DESC, b.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'bus_name' => 'Bus', 'bus_number' => 'No', 'origin' => 'From', 'destination' => 'To', 'travel_date' => 'Date', 'bus_type' => 'Type', 'seat_no' => 'Seat', 'seats' => 'People', 'amount' => 'Amount', 'status' => 'Status'],
        ],
        'restaurant-bookings' => [
            'title' => 'Restaurant Bookings',
            'page' => 'admin-restaurant-bookings',
            'table' => 'restaurant_bookings',
            'sql' => 'SELECT r.id, u.full_name AS user_name, r.restaurant_name, r.city, r.booking_date, r.booking_time, r.guests, r.amount, r.status, r.created_at FROM restaurant_bookings r LEFT JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC, r.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'restaurant_name' => 'Restaurant', 'city' => 'City', 'booking_date' => 'Date', 'booking_time' => 'Time', 'guests' => 'Guests', 'amount' => 'Amount', 'status' => 'Status'],
        ],
        'ticket-bookings' => [
            'title' => 'Ticket Bookings',
            'page' => 'admin-ticket-bookings',
            'table' => 'ticket_bookings',
            'sql' => 'SELECT t.id, u.full_name AS user_name, t.event_name, COALESCE(t.location, t.city) AS location, t.event_date, t.ticket_type, t.quantity, t.amount, t.api_reference, t.status, t.created_at FROM ticket_bookings t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.created_at DESC, t.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'event_name' => 'Event', 'location' => 'Location', 'event_date' => 'Date', 'ticket_type' => 'Type', 'quantity' => 'Qty', 'amount' => 'Amount', 'api_reference' => 'API Ref', 'status' => 'Status'],
        ],
        'payments' => [
            'title' => 'Payments',
            'page' => 'admin-payments',
            'table' => 'payments',
            'sql' => 'SELECT p.id, u.full_name AS user_name, p.booking_type, p.amount, p.currency, p.payment_provider, p.payment_method, p.payment_status, p.cashfree_order_id, p.transaction_id, p.paid_at, p.created_at FROM payments p LEFT JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC, p.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'booking_type' => 'Type', 'amount' => 'Amount', 'currency' => 'Currency', 'payment_provider' => 'Provider', 'payment_status' => 'Status', 'transaction_id' => 'Txn', 'paid_at' => 'Paid At'],
        ],
        'offers' => [
            'title' => 'Offers',
            'page' => 'admin-offers',
            'table' => 'offers',
            'action' => 'offer_status',
            'sql' => 'SELECT id, title, description, code, discount_type, discount_value, min_booking_amount, valid_from, valid_to, status, created_at FROM offers ORDER BY created_at DESC, id DESC',
            'columns' => ['id' => 'ID', 'title' => 'Title', 'code' => 'Code', 'discount_type' => 'Type', 'discount_value' => 'Value', 'min_booking_amount' => 'Minimum', 'valid_to' => 'Valid To', 'status' => 'Status'],
        ],
        'feedback' => [
            'title' => 'Feedback',
            'page' => 'admin-feedback',
            'table' => 'feedback',
            'sql' => 'SELECT f.id, u.full_name AS user_name, c.full_name AS captain_name, f.ride_id, f.feedback_type, f.rating, f.comments, f.status, f.created_at FROM feedback f LEFT JOIN users u ON u.id = f.user_id LEFT JOIN captains c ON c.id = f.captain_id ORDER BY f.created_at DESC, f.id DESC',
            'columns' => ['id' => 'ID', 'user_name' => 'User', 'captain_name' => 'Captain', 'ride_id' => 'Ride', 'feedback_type' => 'Type', 'rating' => 'Rating', 'comments' => 'Comments', 'status' => 'Status', 'created_at' => 'Created'],
        ],
    ];
}

function admin_prepare_table(string $key, PDO $pdo): void
{
    if ($key === 'captains') ensure_captain_table_ready($pdo);
    if ($key === 'rides') ensure_ride_table_ready($pdo);
    if ($key === 'hotel-bookings') ensure_hotel_booking_table_ready($pdo);
    if ($key === 'train-bookings') ensure_train_booking_table_ready($pdo);
    if ($key === 'bus-bookings') ensure_bus_booking_table_ready($pdo);
    if ($key === 'restaurant-bookings') ensure_restaurant_booking_table_ready($pdo);
    if ($key === 'ticket-bookings') ensure_ticket_booking_table_ready($pdo);
    if ($key === 'offers') ensure_default_offers($pdo);
}

function admin_fetch_rows(string $key): array
{
    $pdo = db();
    $configs = admin_table_configs();
    if (!$pdo || !isset($configs[$key])) return [];

    try {
        admin_prepare_table($key, $pdo);
        $stmt = $pdo->prepare($configs[$key]['sql']);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        set_flash('danger', 'Admin table load failed: ' . $e->getMessage());
        return [];
    }
}

function admin_pages(): array
{
    return [
        ['admin-users', 'Users', 'users', 'US', 'blue'],
        ['admin-captains', 'Captains', 'captains', 'CP', 'orange'],
        ['admin-rides', 'Rides', 'rides', 'RD', 'green'],
        ['admin-hotel-bookings', 'Hotels', 'hotel_bookings', 'HT', 'violet'],
        ['admin-train-bookings', 'Trains', 'train_bookings', 'TR', 'cyan'],
        ['admin-bus-bookings', 'Buses', 'bus_bookings', 'BS', 'blue'],
        ['admin-restaurant-bookings', 'Restaurants', 'restaurant_bookings', 'RS', 'orange'],
        ['admin-ticket-bookings', 'Tickets', 'ticket_bookings', 'TK', 'violet'],
        ['admin-payments', 'Payments', 'payments', 'PY', 'green'],
        ['admin-offers', 'Offers', 'offers', 'OF', 'cyan'],
        ['admin-feedback', 'Feedback', 'feedback', 'FB', 'slate'],
    ];
}

function render_admin_quick_links(string $activePage = ''): void
{
    ?>
    <div class="admin-link-grid" aria-label="Admin management pages">
        <?php foreach (admin_pages() as $item): ?>
            <a class="admin-link admin-link-<?php echo h($item[4]); ?> <?php echo $activePage === $item[0] ? 'active' : ''; ?>" href="index.php?page=<?php echo h($item[0]); ?>">
                <span class="admin-link-icon" aria-hidden="true"><?php echo h($item[3]); ?></span>
                <span class="admin-link-copy">
                    <strong><?php echo h($item[1]); ?></strong>
                    <small><?php echo h(str_replace('_', ' ', $item[2])); ?></small>
                </span>
                <span class="admin-link-arrow" aria-hidden="true">&rsaquo;</span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
}

function render_admin_action_cell(string $action, array $row): void
{
    if ($action === 'captain_status') {
        $statuses = ['pending', 'active', 'inactive', 'blocked'];
        ?>
        <form class="admin-inline-form" method="post" action="index.php?page=admin-captains">
            <input type="hidden" name="action" value="admin_update_captain_status">
            <input type="hidden" name="captain_id" value="<?php echo h($row['id'] ?? 0); ?>">
            <select name="account_status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo ($row['account_status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo h(ucwords($status)); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light" type="submit">Update</button>
        </form>
        <?php
        return;
    }

    if ($action === 'ride_status') {
        $statuses = ['pending', 'captain_selected', 'accepted', 'rejected', 'ongoing', 'completed', 'cancelled'];
        ?>
        <form class="admin-inline-form" method="post" action="index.php?page=admin-rides">
            <input type="hidden" name="action" value="admin_update_ride_status">
            <input type="hidden" name="ride_id" value="<?php echo h($row['id'] ?? 0); ?>">
            <select name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo ($row['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo h(ride_status_label($status)); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light" type="submit">Update</button>
        </form>
        <?php
        return;
    }

    if ($action === 'offer_status') {
        $statuses = ['active', 'inactive', 'expired'];
        ?>
        <form class="admin-inline-form" method="post" action="index.php?page=admin-offers">
            <input type="hidden" name="action" value="admin_update_offer_status">
            <input type="hidden" name="offer_id" value="<?php echo h($row['id'] ?? 0); ?>">
            <select name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo h($status); ?>" <?php echo ($row['status'] ?? '') === $status ? 'selected' : ''; ?>><?php echo h(ucwords($status)); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light" type="submit">Save</button>
        </form>
        <?php
    }
}

function render_admin_table(array $config, array $rows): void
{
    ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <?php foreach ($config['columns'] as $label): ?>
                        <th><?php echo h($label); ?></th>
                    <?php endforeach; ?>
                    <?php if (!empty($config['action'])): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="<?php echo h(count($config['columns']) + (!empty($config['action']) ? 1 : 0)); ?>">No records found.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($config['columns'] as $column => $label): ?>
                            <td><?php echo h($row[$column] ?? ''); ?></td>
                        <?php endforeach; ?>
                        <?php if (!empty($config['action'])): ?><td><?php render_admin_action_cell($config['action'], $row); ?></td><?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function render_admin_offer_form(): void
{
    ?>
    <div class="section-title"><h2>Add offer</h2><span class="badge">Admin</span></div>
    <form class="form card auth-card" method="post" action="index.php?page=admin-offers">
        <input type="hidden" name="action" value="admin_add_offer">
        <div class="field"><label for="offer_title">Title</label><input id="offer_title" name="title" type="text" placeholder="Weekend Ride Saver" required></div>
        <div class="field"><label for="offer_description">Description</label><textarea id="offer_description" name="description" placeholder="Short offer description"></textarea></div>
        <div class="row">
            <div class="field"><label for="offer_code">Code</label><input id="offer_code" name="code" type="text" placeholder="TRIP10" required></div>
            <div class="field"><label for="offer_discount_type">Type</label><select id="offer_discount_type" name="discount_type"><option value="percentage">Percentage</option><option value="flat">Flat</option></select></div>
        </div>
        <div class="row">
            <div class="field"><label for="offer_discount_value">Discount value</label><input id="offer_discount_value" name="discount_value" type="number" min="1" step="0.01" value="10" required></div>
            <div class="field"><label for="offer_min_amount">Minimum amount</label><input id="offer_min_amount" name="min_booking_amount" type="number" min="0" step="0.01" value="0"></div>
        </div>
        <div class="row">
            <div class="field"><label for="offer_valid_from">Valid from</label><input id="offer_valid_from" name="valid_from" type="date" value="<?php echo h(date('Y-m-d')); ?>" required></div>
            <div class="field"><label for="offer_valid_to">Valid to</label><input id="offer_valid_to" name="valid_to" type="date" value="<?php echo h(date('Y-m-d', strtotime('+30 days'))); ?>" required></div>
        </div>
        <div class="field"><label for="offer_status">Status</label><select id="offer_status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="expired">Expired</option></select></div>
        <button class="btn btn-orange" type="submit">Add Offer</button>
    </form>
    <?php
}

function page_admin_management(string $key): void
{
    require_role('admin', 'admin-login');
    $configs = admin_table_configs();
    if (!isset($configs[$key])) redirect_to('admin-dashboard');

    $config = $configs[$key];
    $rows = admin_fetch_rows($key);

    app_header($config['title'], true, 'with-bottom-nav');
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Admin</span>
        <h2><?php echo h($config['title']); ?></h2>
        <p>View and manage TripNovaa <?php echo h(strtolower($config['title'])); ?> records from MySQL.</p>
    </div>
    <?php render_admin_quick_links($config['page']); ?>
    <?php if ($key === 'offers') render_admin_offer_form(); ?>
    <div class="section-title"><h2><?php echo h($config['title']); ?> table</h2><span class="badge"><?php echo h(count($rows)); ?> records</span></div>
    <?php render_admin_table($config, $rows); ?>
    <div class="btn-row"><a class="btn btn-light" href="index.php?page=admin-dashboard">Back to Dashboard</a></div>
    <?php
    app_footer('admin', 'home');
}

function page_admin_dashboard(): void
{
    require_role('admin', 'admin-login');
    app_header('Admin Dashboard', true, 'with-bottom-nav');
    $cards = [
        ['Total Users', 'users', 'admin-users'],
        ['Total Captains', 'captains', 'admin-captains'],
        ['Total Rides', 'rides', 'admin-rides'],
        ['Total Hotel Bookings', 'hotel_bookings', 'admin-hotel-bookings'],
        ['Total Train Bookings', 'train_bookings', 'admin-train-bookings'],
        ['Total Bus Bookings', 'bus_bookings', 'admin-bus-bookings'],
        ['Total Restaurant Bookings', 'restaurant_bookings', 'admin-restaurant-bookings'],
        ['Total Ticket Bookings', 'ticket_bookings', 'admin-ticket-bookings'],
        ['Total Payments', 'payments', 'admin-payments'],
        ['Total Offers', 'offers', 'admin-offers'],
        ['Total Rewards', 'rewards', 'admin-dashboard'],
    ];
    ?>
    <div class="auth-hero">
        <span class="auth-badge">Admin control</span>
        <h2>Platform overview</h2>
        <p>Monitor TripNovaa users, captains, bookings, payments, rewards, offers, and feedback.</p>
    </div>

    <div class="stat-grid">
        <?php foreach ($cards as $card): ?>
            <a class="stat-card" href="index.php?page=<?php echo h($card[2]); ?>">
                <strong><?php echo h(table_count($card[1])); ?></strong>
                <span><?php echo h($card[0]); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="section-title"><h2>Management pages</h2><span class="badge">Live tables</span></div>
    <?php render_admin_quick_links('admin-dashboard'); ?>
    <?php
    app_footer('admin', 'home');
}

match ($page) {
    'splash' => page_splash(),
    'get-started' => page_get_started(),
    'role-selection' => page_role_selection_welcome(),
    'user-register' => page_user_register(),
    'user-login' => page_user_login(),
    'captain-register' => page_captain_register(),
    'captain-login' => page_captain_login(),
    'admin-login' => page_admin_login(),
    'otp' => page_otp(),
    'user-dashboard' => page_user_dashboard_home(),
    'book-ride' => page_book_ride(),
    'available-captains' => page_available_captains(),
    'ride-confirm' => page_ride_confirm(),
    'ride-tracking' => page_ride_tracking(),
    'ride-success' => page_ride_success(),
    'feedback' => page_feedback(),
    'feedback-success' => page_feedback_success(),
    'payment' => page_payment(),
    'payment-success' => page_payment_success(),
    'payment-failed' => page_payment_failed(),
    'hotel-search' => page_hotel_search(),
    'hotel-list' => page_hotel_list(),
    'hotel-book' => page_hotel_book(),
    'hotel-success' => page_hotel_success(),
    'train-search' => page_train_search(),
    'train-list' => page_train_list(),
    'train-book' => page_train_book(),
    'train-success' => page_train_success(),
    'bus-search' => page_bus_search(),
    'bus-list' => page_bus_list(),
    'bus-book' => page_bus_book(),
    'bus-success' => page_bus_success(),
    'restaurant-search' => page_restaurant_search(),
    'restaurant-list' => page_restaurant_list(),
    'restaurant-book' => page_restaurant_book(),
    'restaurant-success' => page_restaurant_success(),
    'plan-trip' => page_plan_trip(),
    'plan-trip-transport' => page_plan_trip_transport(),
    'plan-trip-options' => page_plan_trip_options(),
    'plan-trip-detail' => page_plan_trip_detail(),
    'plan-trip-captain' => page_plan_trip_captain(),
    'plan-trip-arrival' => page_plan_trip_arrival(),
    'plan-trip-accepted' => page_plan_trip_accepted(),
    'plan-trip-deposit' => page_plan_trip_deposit(),
    'plan-trip-guide' => page_plan_trip_guide(),
    'plan-trip-complete' => page_plan_trip_complete(),
    'plan-trip-reminder' => page_plan_trip_reminder(),
    'group-tours' => page_group_tours(),
    'group-tour-details' => page_group_tour_details(),
    'group-tour-captain' => page_group_tour_captain(),
    'group-tour-seats' => page_group_tour_seats(),
    'group-tour-advance' => page_group_tour_advance(),
    'group-tour-confirmed' => page_group_tour_confirmed(),
    'group-tour-booking' => page_group_tour_booking(),
    'group-tour-itinerary' => page_group_tour_itinerary(),
    'group-tour-during' => page_group_tour_during(),
    'group-tour-remaining' => page_group_tour_remaining(),
    'group-tour-completed' => page_group_tour_completed(),
    'group-tour-more' => page_group_tour_more(),
    'tour-ticket-search' => page_tour_ticket_search(),
    'tour-ticket-results' => page_tour_ticket_results(),
    'tour-ticket-book' => page_tour_ticket_book(),
    'ticket-success' => page_ticket_success(),
    'rewards-offers' => page_rewards_offers(),
    'apply-offer' => page_apply_offer(),
    'post-new-trip' => page_post_new_trip(),
    'my-trips-posted' => page_my_trips_posted(),
    'driver-offers' => page_driver_offers(),
    'saved-trips' => page_saved_trips(),
    'trip-messages' => page_trip_messages(),
    'driver-chat' => page_driver_chat(),
    'my-bookings' => page_my_bookings(),
    'user-profile' => page_user_profile(),
    'captain-dashboard' => page_captain_dashboard(),
    'captain-ride-requests' => page_captain_ride_requests(),
    'captain-trip-details' => page_captain_trip_details(),
    'captain-accept-trip' => page_captain_accept_trip(),
    'captain-advance-payment' => page_captain_advance_payment(),
    'captain-navigation' => page_captain_navigation(),
    'captain-trip-progress' => page_captain_trip_progress(),
    'captain-trip-earnings' => page_captain_trip_earnings(),
    'captain-passenger-details' => page_captain_passenger_details(),
    'captain-trip-chat' => page_captain_trip_chat(),
    'captain-trip-history' => page_captain_trip_history(),
    'captain-wallet' => page_captain_wallet(),
    'captain-rewards' => page_captain_rewards(),
    'captain-profile' => page_captain_profile(),
    'captain-earnings-analytics' => page_captain_earnings_analytics(),
    'captain-current-trips' => page_captain_current_trips(),
    'captain-completed-trips' => page_captain_completed_trips(),
    'captain-earnings' => page_captain_earnings(),
    'captain-offers' => page_captain_offers_ui(),
    'admin-dashboard' => page_admin_dashboard(),
    'admin-users' => page_admin_management('users'),
    'admin-captains' => page_admin_management('captains'),
    'admin-rides' => page_admin_management('rides'),
    'admin-hotel-bookings' => page_admin_management('hotel-bookings'),
    'admin-train-bookings' => page_admin_management('train-bookings'),
    'admin-bus-bookings' => page_admin_management('bus-bookings'),
    'admin-restaurant-bookings' => page_admin_management('restaurant-bookings'),
    'admin-ticket-bookings' => page_admin_management('ticket-bookings'),
    'admin-payments' => page_admin_management('payments'),
    'admin-offers' => page_admin_management('offers'),
    'admin-feedback' => page_admin_management('feedback'),
    default => page_splash(),
};

DROP DATABASE IF EXISTS tripnovaa_db;
CREATE DATABASE tripnovaa_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tripnovaa_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  city VARCHAR(100) DEFAULT NULL,
  reward_points INT UNSIGNED NOT NULL DEFAULT 0,
  otp_verified TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE captains (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL DEFAULT 'Lahore',
  vehicle_type ENUM('bike', 'car', 'auto', 'mini', 'sedan', 'suv', 'other') NOT NULL DEFAULT 'car',
  vehicle_number VARCHAR(40) NOT NULL,
  license_number VARCHAR(80) NOT NULL,
  id_card_type ENUM('aadhar', 'pan') DEFAULT NULL,
  id_card_number VARCHAR(80) DEFAULT NULL,
  current_lat DECIMAL(10,7) DEFAULT 31.5204000,
  current_lng DECIMAL(10,7) DEFAULT 74.3587000,
  availability_status ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'available',
  account_status ENUM('pending', 'active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
  rating DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE otp_verifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  captain_id INT UNSIGNED DEFAULT NULL,
  role_type ENUM('user', 'captain') NOT NULL,
  phone VARCHAR(30) NOT NULL,
  otp_code VARCHAR(10) NOT NULL,
  purpose ENUM('register', 'login', 'forgot_password') NOT NULL DEFAULT 'login',
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_otp_captain FOREIGN KEY (captain_id) REFERENCES captains(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE hotels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hotel_name VARCHAR(160) NOT NULL,
  city VARCHAR(100) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  price_per_night DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  rating DECIMAL(3,2) NOT NULL DEFAULT 4.50,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE trains (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  train_name VARCHAR(160) NOT NULL,
  train_number VARCHAR(40) NOT NULL UNIQUE,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  departure_time TIME NOT NULL,
  arrival_time TIME NOT NULL,
  class_options VARCHAR(160) NOT NULL,
  base_fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE buses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bus_name VARCHAR(160) NOT NULL,
  bus_number VARCHAR(40) NOT NULL UNIQUE,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  departure_time TIME NOT NULL,
  arrival_time TIME NOT NULL,
  bus_type VARCHAR(80) NOT NULL,
  total_seats INT UNSIGNED NOT NULL DEFAULT 40,
  base_fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE restaurants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_name VARCHAR(160) NOT NULL,
  city VARCHAR(100) NOT NULL,
  address VARCHAR(255) DEFAULT NULL,
  cuisine VARCHAR(120) DEFAULT NULL,
  average_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  rating DECIMAL(3,2) NOT NULL DEFAULT 4.50,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ticket_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_name VARCHAR(180) NOT NULL,
  city VARCHAR(100) NOT NULL,
  venue VARCHAR(180) DEFAULT NULL,
  event_date DATETIME NOT NULL,
  category VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  available_tickets INT UNSIGNED NOT NULL DEFAULT 100,
  api_source VARCHAR(100) NOT NULL DEFAULT 'TripNovaa Demo API',
  status ENUM('active', 'inactive', 'sold_out') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rides (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  captain_id INT UNSIGNED DEFAULT NULL,
  pickup_location VARCHAR(255) NOT NULL,
  drop_location VARCHAR(255) NOT NULL,
  pickup_lat DECIMAL(10,7) DEFAULT NULL,
  pickup_lng DECIMAL(10,7) DEFAULT NULL,
  drop_lat DECIMAL(10,7) DEFAULT NULL,
  drop_lng DECIMAL(10,7) DEFAULT NULL,
  ride_type ENUM('bike', 'car', 'premium_car', 'mini_bus', 'auto', 'mini', 'sedan', 'suv') NOT NULL DEFAULT 'car',
  travel_date DATE DEFAULT NULL,
  travel_time TIME DEFAULT NULL,
  distance_km DECIMAL(8,2) DEFAULT 0.00,
  fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_status ENUM('unpaid', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid',
  status ENUM('pending', 'captain_selected', 'accepted', 'rejected', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accepted_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rides_user (user_id),
  INDEX idx_rides_captain (captain_id),
  CONSTRAINT fk_rides_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rides_captain FOREIGN KEY (captain_id) REFERENCES captains(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ride_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ride_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  captain_id INT UNSIGNED NOT NULL,
  sender_role ENUM('user', 'captain') NOT NULL,
  message_body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ride_messages_ride (ride_id, created_at),
  INDEX idx_ride_messages_user (user_id),
  INDEX idx_ride_messages_captain (captain_id),
  CONSTRAINT fk_ride_messages_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  CONSTRAINT fk_ride_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ride_messages_captain FOREIGN KEY (captain_id) REFERENCES captains(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE hotel_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  hotel_id INT UNSIGNED DEFAULT NULL,
  hotel_name VARCHAR(160) NOT NULL,
  city VARCHAR(100) NOT NULL,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  guests INT UNSIGNED NOT NULL DEFAULT 1,
  rooms INT UNSIGNED NOT NULL DEFAULT 1,
  room_type VARCHAR(100) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_hotel_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_hotel_booking_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE train_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  train_id INT UNSIGNED DEFAULT NULL,
  train_name VARCHAR(160) NOT NULL,
  train_number VARCHAR(40) NOT NULL,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  travel_date DATE NOT NULL,
  seat_class VARCHAR(60) NOT NULL,
  passengers INT UNSIGNED NOT NULL DEFAULT 1,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_train_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_train_booking_train FOREIGN KEY (train_id) REFERENCES trains(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE bus_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  bus_id INT UNSIGNED DEFAULT NULL,
  bus_name VARCHAR(160) NOT NULL,
  bus_number VARCHAR(40) NOT NULL,
  origin VARCHAR(100) NOT NULL,
  destination VARCHAR(100) NOT NULL,
  travel_date DATE NOT NULL,
  bus_type VARCHAR(80) NOT NULL,
  seat_no VARCHAR(40) DEFAULT NULL,
  seats INT UNSIGNED NOT NULL DEFAULT 1,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bus_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bus_booking_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE restaurant_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  restaurant_id INT UNSIGNED DEFAULT NULL,
  restaurant_name VARCHAR(160) NOT NULL,
  city VARCHAR(100) NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  guests INT UNSIGNED NOT NULL DEFAULT 1,
  special_request TEXT DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_restaurant_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_restaurant_booking_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  ticket_event_id INT UNSIGNED DEFAULT NULL,
  event_name VARCHAR(180) NOT NULL,
  location VARCHAR(100) DEFAULT NULL,
  city VARCHAR(100) NOT NULL,
  event_date DATETIME NOT NULL,
  ticket_type VARCHAR(100) DEFAULT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tickets INT UNSIGNED NOT NULL DEFAULT 1,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  api_reference VARCHAR(120) DEFAULT NULL,
  status ENUM('pending', 'payment_pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ticket_booking_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_booking_event FOREIGN KEY (ticket_event_id) REFERENCES ticket_events(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  booking_type ENUM('ride', 'hotel', 'train', 'bus', 'restaurant', 'ticket', 'other') NOT NULL,
  ride_id INT UNSIGNED DEFAULT NULL,
  hotel_booking_id INT UNSIGNED DEFAULT NULL,
  train_booking_id INT UNSIGNED DEFAULT NULL,
  bus_booking_id INT UNSIGNED DEFAULT NULL,
  restaurant_booking_id INT UNSIGNED DEFAULT NULL,
  ticket_booking_id INT UNSIGNED DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(10) NOT NULL DEFAULT 'PKR',
  payment_provider ENUM('cashfree', 'demo', 'cash') NOT NULL DEFAULT 'demo',
  payment_method VARCHAR(80) DEFAULT 'Demo Test Mode',
  cashfree_order_id VARCHAR(120) DEFAULT NULL,
  transaction_id VARCHAR(120) DEFAULT NULL,
  payment_status ENUM('pending', 'success', 'failed', 'refunded', 'demo_success') NOT NULL DEFAULT 'pending',
  paid_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_hotel FOREIGN KEY (hotel_booking_id) REFERENCES hotel_bookings(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_train FOREIGN KEY (train_booking_id) REFERENCES train_bookings(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_bus FOREIGN KEY (bus_booking_id) REFERENCES bus_bookings(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_restaurant FOREIGN KEY (restaurant_booking_id) REFERENCES restaurant_bookings(id) ON DELETE SET NULL,
  CONSTRAINT fk_payment_ticket FOREIGN KEY (ticket_booking_id) REFERENCES ticket_bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE offers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(140) NOT NULL,
  description TEXT,
  code VARCHAR(40) NOT NULL UNIQUE,
  discount_type ENUM('flat', 'percentage') NOT NULL DEFAULT 'flat',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_booking_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL,
  status ENUM('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rewards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  points INT NOT NULL,
  source_type ENUM('signup', 'ride', 'hotel', 'train', 'bus', 'restaurant', 'ticket', 'offer', 'admin_adjustment') NOT NULL,
  source_id INT UNSIGNED DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  captain_id INT UNSIGNED DEFAULT NULL,
  ride_id INT UNSIGNED DEFAULT NULL,
  feedback_type ENUM('ride', 'hotel', 'train', 'bus', 'restaurant', 'ticket', 'app') NOT NULL DEFAULT 'app',
  rating TINYINT UNSIGNED NOT NULL,
  comments TEXT,
  status ENUM('visible', 'hidden') NOT NULL DEFAULT 'visible',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_feedback_captain FOREIGN KEY (captain_id) REFERENCES captains(id) ON DELETE SET NULL,
  CONSTRAINT fk_feedback_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO admins (full_name, email, password_hash, role, status) VALUES
('TripNovaa Admin', 'admin@tripnovaa.com', '$2y$10$1zr36Kvs.5SWC.EwqgO9JeVJ7hcCCeAEhT32F0Vgu0GKnsdPyxjR6', 'super_admin', 'active');

INSERT INTO users (full_name, email, phone, password_hash, city, reward_points, otp_verified, status) VALUES
('Sara Khan', 'sara@tripnovaa.com', '03001112222', '$2y$10$iTZelGMIEgBphLAmi6XbZu/KAEpZYxrFmVE8em74n6jzEzC2HJUJa', 'Lahore', 150, 1, 'active'),
('Ali Raza', 'ali@tripnovaa.com', '03003334444', '$2y$10$iTZelGMIEgBphLAmi6XbZu/KAEpZYxrFmVE8em74n6jzEzC2HJUJa', 'Karachi', 80, 1, 'active');

INSERT INTO captains
(full_name, email, phone, password_hash, city, vehicle_type, vehicle_number, license_number, id_card_type, id_card_number, current_lat, current_lng, availability_status, account_status, rating)
VALUES
('Ahmed Captain', 'ahmed.captain@tripnovaa.com', '03110001111', '$2y$10$WdL2JlIOm0e6yMZ/.JWvLeS76Cpac/Xl6Y/a6IB8cBPWxkG9jZNFe', 'Lahore', 'mini', 'LEA-2456', 'LIC-LHR-1001', 'aadhar', '1234-5678-9001', 31.5204000, 74.3587000, 'available', 'active', 4.90),
('Bilal Rider', 'bilal.rider@tripnovaa.com', '03112223333', '$2y$10$WdL2JlIOm0e6yMZ/.JWvLeS76Cpac/Xl6Y/a6IB8cBPWxkG9jZNFe', 'Lahore', 'bike', 'LEB-7788', 'LIC-LHR-1002', 'pan', 'ABCDE1234F', 31.5497000, 74.3436000, 'available', 'active', 4.70),
('Zain Captain', 'zain.captain@tripnovaa.com', '03114445555', '$2y$10$WdL2JlIOm0e6yMZ/.JWvLeS76Cpac/Xl6Y/a6IB8cBPWxkG9jZNFe', 'Lahore', 'sedan', 'LEC-9090', 'LIC-LHR-1003', 'aadhar', '1234-5678-9003', 31.4697000, 74.2728000, 'offline', 'active', 4.60);

INSERT INTO hotels (hotel_name, city, address, price_per_night, rating) VALUES
('Pearl Skyline Hotel', 'Lahore', 'Gulberg Main Boulevard', 12500.00, 4.80),
('Sea View Grand', 'Karachi', 'Clifton Beach Road', 15000.00, 4.70),
('Mountain Nest Resort', 'Murree', 'Mall Road Murree', 18000.00, 4.60);

INSERT INTO trains (train_name, train_number, origin, destination, departure_time, arrival_time, class_options, base_fare) VALUES
('Green Line Express', 'GL-101', 'Karachi', 'Islamabad', '22:00:00', '14:30:00', 'Economy, AC Business, Sleeper', 6500.00),
('Tezgam Express', 'TG-202', 'Lahore', 'Karachi', '18:15:00', '12:00:00', 'Economy, AC Standard', 5200.00),
('Khyber Mail', 'KM-303', 'Peshawar', 'Karachi', '20:30:00', '16:45:00', 'Economy, AC Business', 7000.00),
('TripNovaa Express', 'TN-707', 'Peshawar', 'Lahore', '10:00:00', '15:15:00', 'Executive, AC Business', 7200.00),
('Business Train', 'BT-909', 'Lahore', 'Islamabad', '16:20:00', '21:45:00', 'Business Class, Executive', 8500.00);

INSERT INTO buses (bus_name, bus_number, origin, destination, departure_time, arrival_time, bus_type, total_seats, base_fare) VALUES
('Daewoo Express', 'DW-501', 'Lahore', 'Islamabad', '09:00:00', '13:30:00', 'Luxury', 42, 2800.00),
('Faisal Movers', 'FM-702', 'Lahore', 'Multan', '11:30:00', '16:00:00', 'Executive', 40, 2500.00),
('Skyways', 'SW-414', 'Lahore', 'Islamabad', '14:15:00', '18:45:00', 'Business Class', 38, 2300.00),
('TripNovaa Bus', 'TN-BUS-88', 'Islamabad', 'Peshawar', '20:45:00', '01:20:00', 'Premium Sleeper', 30, 3600.00);

INSERT INTO restaurants (restaurant_name, city, address, cuisine, average_price, rating) VALUES
('TripNovaa Cafe', 'Lahore', 'Gulberg Main Boulevard', 'Continental, Coffee, Desserts', 2200.00, 4.80),
('Mountain Grill', 'Murree', 'Mall Road View Point', 'BBQ, Pakistani, Steaks', 3200.00, 4.70),
('City Food Lounge', 'Islamabad', 'Blue Area Food Street', 'Fast Food, Chinese, Local', 1800.00, 4.50),
('Royal Restaurant', 'Karachi', 'Clifton Block 4', 'Fine Dining, Seafood, Pakistani', 4200.00, 4.90);

INSERT INTO ticket_events (event_name, city, venue, event_date, category, price, available_tickets, api_source) VALUES
('Lahore Heritage Tour', 'Lahore', 'Lahore Fort', DATE_ADD(NOW(), INTERVAL 10 DAY), 'Tour', 1800.00, 120, 'TripNovaa Demo API'),
('Northern Areas Adventure Pass', 'Hunza', 'Hunza Valley', DATE_ADD(NOW(), INTERVAL 20 DAY), 'Adventure', 9500.00, 40, 'TripNovaa Demo API'),
('Karachi Food Festival', 'Karachi', 'Expo Center', DATE_ADD(NOW(), INTERVAL 15 DAY), 'Event', 2500.00, 300, 'TripNovaa Demo API');

INSERT INTO offers (title, description, code, discount_type, discount_value, min_booking_amount, valid_from, valid_to, status) VALUES
('Trip Ride Saver', '10% off on ride bookings.', 'TRIP10', 'percentage', 10.00, 300.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active'),
('Hotel Comfort Deal', '20% off on hotel bookings.', 'HOTEL20', 'percentage', 20.00, 2000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active'),
('Bus Seat Discount', 'Rs. 50 off on bus bookings.', 'BUS50', 'flat', 50.00, 300.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active'),
('Ticket Explorer Deal', '15% off on tour and ticket bookings.', 'TICKET15', 'percentage', 15.00, 500.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active');

INSERT INTO otp_verifications (user_id, captain_id, role_type, phone, otp_code, purpose, is_verified, expires_at) VALUES
(1, NULL, 'user', '03001112222', '123456', 'login', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE)),
(NULL, 1, 'captain', '03110001111', '123456', 'login', 1, DATE_ADD(NOW(), INTERVAL 10 MINUTE));

INSERT INTO rides
(user_id, captain_id, pickup_location, drop_location, pickup_lat, pickup_lng, drop_lat, drop_lng, ride_type, distance_km, fare, payment_status, status, accepted_at)
VALUES
(1, 1, 'Gulberg, Lahore', 'Allama Iqbal International Airport', 31.5204000, 74.3587000, 31.5216000, 74.4036000, 'mini', 14.50, 950.00, 'paid', 'accepted', NOW()),
(2, 2, 'Clifton, Karachi', 'Dolmen Mall Tariq Road', 24.8138000, 67.0299000, 24.8736000, 67.0589000, 'bike', 9.20, 420.00, 'unpaid', 'pending', NULL);

INSERT INTO ride_messages (ride_id, user_id, captain_id, sender_role, message_body, created_at) VALUES
(1, 1, 1, 'captain', 'Hi, I will be your captain for this trip.', DATE_SUB(NOW(), INTERVAL 6 MINUTE)),
(1, 1, 1, 'user', 'Hello Captain, can you share the pickup location details?', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(1, 1, 1, 'captain', 'Sure, pickup point is near Gulberg, Lahore.', DATE_SUB(NOW(), INTERVAL 4 MINUTE));

INSERT INTO hotel_bookings
(user_id, hotel_id, hotel_name, city, check_in_date, check_out_date, guests, rooms, room_type, amount, status)
VALUES
(1, 1, 'Pearl Skyline Hotel', 'Lahore', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 1, 'Deluxe Suite', 25000.00, 'confirmed');

INSERT INTO train_bookings
(user_id, train_id, train_name, train_number, origin, destination, travel_date, seat_class, passengers, amount, status)
VALUES
(1, 1, 'Green Line Express', 'GL-101', 'Karachi', 'Islamabad', DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'AC Business', 1, 6500.00, 'confirmed');

INSERT INTO bus_bookings
(user_id, bus_id, bus_name, bus_number, origin, destination, travel_date, bus_type, seat_no, seats, amount, status)
VALUES
(2, 1, 'Daewoo Express', 'DW-501', 'Lahore', 'Islamabad', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'Luxury', 'S04', 2, 5600.00, 'confirmed');

INSERT INTO restaurant_bookings
(user_id, restaurant_id, restaurant_name, city, booking_date, booking_time, guests, special_request, amount, status)
VALUES
(1, 1, 'TripNovaa Cafe', 'Lahore', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '20:30:00', 4, 'Window table if available', 2200.00, 'confirmed');

INSERT INTO ticket_bookings
(user_id, ticket_event_id, event_name, location, city, event_date, ticket_type, quantity, price, tickets, amount, api_reference, status)
VALUES
(2, 3, 'Karachi Food Festival', 'Karachi', 'Karachi', DATE_ADD(NOW(), INTERVAL 15 DAY), 'Event Entry', 2, 2500.00, 2, 5000.00, 'DEMO-TICKET-API-1001', 'confirmed');

INSERT INTO payments
(user_id, booking_type, ride_id, hotel_booking_id, train_booking_id, bus_booking_id, restaurant_booking_id, ticket_booking_id, amount, currency, payment_provider, payment_method, cashfree_order_id, transaction_id, payment_status, paid_at)
VALUES
(1, 'ride', 1, NULL, NULL, NULL, NULL, NULL, 950.00, 'PKR', 'cashfree', 'Cashfree Test Mode', 'CF-RIDE-ORDER-1001', 'TXN-RIDE-1001', 'demo_success', NOW()),
(1, 'hotel', NULL, 1, NULL, NULL, NULL, NULL, 25000.00, 'PKR', 'cashfree', 'Cashfree Test Mode', 'CF-HOTEL-ORDER-1001', 'TXN-HOTEL-1001', 'demo_success', NOW()),
(2, 'ticket', NULL, NULL, NULL, NULL, NULL, 1, 5000.00, 'PKR', 'demo', 'Demo Payment', 'CF-TICKET-ORDER-1001', 'TXN-TICKET-1001', 'demo_success', NOW());

INSERT INTO rewards (user_id, points, source_type, source_id, description) VALUES
(1, 100, 'signup', 1, 'Signup bonus points'),
(1, 50, 'ride', 1, 'Reward points for completed ride payment'),
(2, 80, 'ticket', 1, 'Reward points for ticket booking');

INSERT INTO feedback (user_id, captain_id, ride_id, feedback_type, rating, comments, status) VALUES
(1, 1, 1, 'ride', 5, 'Smooth ride and polite captain.', 'visible'),
(2, NULL, NULL, 'app', 4, 'Good travel booking experience.', 'visible');
